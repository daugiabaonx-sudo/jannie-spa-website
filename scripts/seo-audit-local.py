#!/usr/bin/env python3
"""
JN Step 10 — Local SEO / AEO audit adapter for the `sunext-semrush-ahrefs-os` skill.

Why this exists:
  The skill's crawler (`run_suite3_technical_site_audit_spider`) refuses loopback hosts through its
  SSRF guard (`validate_safe_url`). That guard is correct and is NOT bypassed here. Instead this
  dev-only script fetches the local Docker site itself (restricted to the `--base` host) and feeds
  each response into the skill's pure analysis functions:
    - Suite 3: `audit_single_page`               (technical + on-page checks)
    - Suite 6: `compute_bilingual_readability`   (VI/EN readability)
    - Suite 8: `audit_robots_txt_for_ai_crawlers`, `compute_autogeo_geu_score` (AEO / GEO)
  On top of that it runs JN-specific consistency checks (robots meta vs sitemap vs robots.txt,
  self-referencing canonicals, duplicate tags, JSON-LD graph integrity, NAP data provenance).

Usage (host, Docker stack running):
  python3 scripts/seo-audit-local.py --base http://localhost:8080 --out "docs/jn/seo-aeo/<dir>/1. report"
  python3 scripts/seo-audit-local.py --robots-file docs/jn/robots.production.txt ...   # audit prod robots body

Exit code: 0 when no CRITICAL/HIGH issue remains, 1 otherwise.
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from collections import defaultdict
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

REPO_ROOT = Path(__file__).resolve().parent.parent
DEFAULT_SKILL_DIR = REPO_ROOT / ".agent" / "skills" / "sunext-semrush-ahrefs-os"
SKILL_DIR = Path(os.environ.get("SEO_OS_SKILL_DIR", str(DEFAULT_SKILL_DIR)))
if not (SKILL_DIR / "scripts" / "engines").is_dir():
    SKILL_DIR = Path.home() / ".gemini" / "config" / "skills" / "sunext-semrush-ahrefs-os"
sys.path.insert(0, str(SKILL_DIR))

# pylint: disable=wrong-import-position
from scripts.core.security_and_http import parse_html_document  # noqa: E402
from scripts.engines.suite3_technical_site_audit_spider import audit_single_page  # noqa: E402
from scripts.engines.suite6_content_schema_readability_engine import compute_bilingual_readability  # noqa: E402
from scripts.engines.suite8_brand_radar_llms_and_botlog_engine import (  # noqa: E402
    audit_robots_txt_for_ai_crawlers,
    compute_autogeo_geu_score,
)

USER_AGENT = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"

# Routes and the indexability policy the site is expected to follow.
CONTENT_PAGES = [
    "/", "/coaching/", "/my-story/", "/course/", "/client-success-story/", "/booking/",
    "/my-favorite-product/", "/chinh-sach-bao-mat/", "/dieu-khoan-dich-vu/",
]
UTILITY_PAGES = ["/cart/", "/tai-khoan/"]
NOINDEX_PROBES = ["/?s=coaching"]
SEVERITY_ORDER = {"CRITICAL": 0, "HIGH": 1, "MEDIUM": 2, "LOW": 3, "INFO": 4}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    """Record redirects instead of following them."""

    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: D401
        return None


def fetch(url: str, allowed_netloc: str, timeout: int = 15) -> Dict[str, Any]:
    """GET a URL on the allowed host only; never follows redirects."""
    parsed = urllib.parse.urlparse(url)
    if parsed.scheme not in ("http", "https") or parsed.netloc != allowed_netloc:
        raise ValueError(f"Refusing to fetch outside --base host: {url}")
    opener = urllib.request.build_opener(NoRedirect)
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT, "Accept-Language": "vi"})
    start = time.perf_counter()
    try:
        with opener.open(req, timeout=timeout) as resp:
            body = resp.read().decode("utf-8", errors="replace")
            status = resp.status
            headers = {k.lower(): v for k, v in resp.headers.items()}
    except urllib.error.HTTPError as err:
        body = err.read().decode("utf-8", errors="replace") if err.fp else ""
        status = err.code
        headers = {k.lower(): v for k, v in (err.headers or {}).items()}
    elapsed = round((time.perf_counter() - start) * 1000, 1)
    return {"status_code": status, "headers": headers, "body": body, "response_time_ms": elapsed, "redirect_chain": []}


def count_tags(body: str, pattern: str) -> int:
    return len(re.findall(pattern, body, flags=re.I))


def iter_graph_nodes(blocks: List[Any]) -> List[Dict[str, Any]]:
    nodes: List[Dict[str, Any]] = []
    for block in blocks:
        if isinstance(block, dict) and "@graph" in block:
            nodes.extend(n for n in block["@graph"] if isinstance(n, dict))
        elif isinstance(block, dict):
            nodes.append(block)
    return nodes


def node_types(node: Dict[str, Any]) -> List[str]:
    t = node.get("@type", [])
    return t if isinstance(t, list) else [t]


def collect_id_refs(value: Any, refs: List[str]) -> None:
    if isinstance(value, dict):
        if set(value.keys()) == {"@id"}:
            refs.append(value["@id"])
        for v in value.values():
            collect_id_refs(v, refs)
    elif isinstance(value, list):
        for v in value:
            collect_id_refs(v, refs)


def visible_text(body: str) -> str:
    stripped = re.sub(r"<(script|style|noscript)\b.*?</\1>", " ", body, flags=re.I | re.S)
    return re.sub(r"<[^>]+>", " ", stripped)


def jn_checks(path: str, url: str, resp: Dict[str, Any], dom: Any, policy: str) -> List[Dict[str, Any]]:
    """Project-specific checks layered on top of the skill's Suite 3 rules."""
    issues: List[Dict[str, Any]] = []
    body = resp["body"]
    headers = resp["headers"]

    def add(rule: str, sev: str, title: str, evidence: str, fix: str) -> None:
        issues.append({"rule_id": rule, "category": "JN_Consistency", "severity": sev, "title": title,
                       "url": url, "evidence": evidence, "fix_snippet": fix, "epistemic_label": "[FACT]"})

    robots_meta = (dom.meta_robots or "").lower() + " " + headers.get("x-robots-tag", "").lower()
    is_noindex = "noindex" in robots_meta
    if policy == "index" and is_noindex:
        add("JN-IDX-01", "CRITICAL", "Content page is noindex", robots_meta.strip(), "Remove noindex for content pages.")
    if policy == "noindex" and not is_noindex:
        add("JN-IDX-02", "HIGH", "Utility/search page is indexable", f"robots='{robots_meta.strip()}'",
            "Emit `noindex, follow` for cart/checkout/account/search routes.")

    n_canon = count_tags(body, r"<link[^>]+rel=[\"']canonical[\"']")
    n_desc = count_tags(body, r"<meta[^>]+name=[\"']description[\"']")
    if n_canon > 1:
        add("JN-CAN-02", "HIGH", f"Duplicate canonical tags ({n_canon})", "multiple <link rel=canonical>", "Print one canonical only.")
    if n_desc > 1:
        add("JN-META-05", "MEDIUM", f"Duplicate meta description tags ({n_desc})", "multiple <meta name=description>", "Print one description only.")
    if policy == "index" and dom.canonical and dom.canonical.rstrip("/") != url.split("?")[0].rstrip("/"):
        add("JN-CAN-03", "HIGH", "Canonical is not self-referencing", f"canonical={dom.canonical}", "Point canonical to the page URL.")

    title = dom.title or ""
    if re.search(r"jannie nguy[eễ]n.*jannie nguy[eễ]n", title, flags=re.I):
        add("JN-META-06", "MEDIUM", "Brand repeated in <title>", title, "Disable automatic title branding; brand is already in the title.")

    nodes = iter_graph_nodes(dom.json_ld_blocks)
    if any(isinstance(b, dict) and b.get("_parse_error") for b in dom.json_ld_blocks):
        add("JN-SCH-01", "CRITICAL", "Invalid JSON-LD", "parse error", "Fix JSON encoding.")
    ids = {n.get("@id") for n in nodes if n.get("@id")}
    refs: List[str] = []
    for n in nodes:
        for k, v in n.items():
            if k != "@id":
                collect_id_refs(v, refs)
    dangling = sorted({r for r in refs if r not in ids})
    if dangling:
        add("JN-SCH-02", "MEDIUM", "Dangling JSON-LD @id references", ", ".join(dangling[:5]), "Emit the referenced nodes in @graph.")
    types = {t for n in nodes for t in node_types(n)}
    if policy != "skip" and nodes:
        if "WebSite" not in types:
            add("JN-SCH-03", "MEDIUM", "JSON-LD graph lacks WebSite node", f"types={sorted(types)}", "Add WebSite node with publisher + inLanguage.")
        webpages = [n for n in nodes if {"WebPage", "ProfilePage", "CollectionPage", "AboutPage", "ContactPage", "FAQPage"} & set(node_types(n))]
        if webpages and not any("isPartOf" in w for w in webpages):
            add("JN-SCH-04", "LOW", "WebPage lacks isPartOf", "WebPage node without isPartOf", "Link WebPage -> WebSite via isPartOf.")
        if path != "/" and "BreadcrumbList" not in types and policy == "index":
            add("JN-SCH-05", "LOW", "No BreadcrumbList on inner page", f"types={sorted(types)}", "Add BreadcrumbList (Home -> Page).")
    text = visible_text(body)
    digits_text = re.sub(r"\D", "", text)
    for n in nodes:
        tel = n.get("telephone")
        if tel and re.sub(r"\D", "", str(tel))[-9:] not in digits_text:
            add("JN-SCH-06", "CRITICAL", "Schema telephone not shown anywhere on the page (unverified NAP data)",
                f"telephone={tel}", "Remove telephone until the owner confirms the real number.")
        if n.get("priceRange") and "price" not in json.dumps(nodes):
            add("JN-SCH-07", "LOW", "priceRange declared while no price is published", f"priceRange={n.get('priceRange')}", "Omit priceRange until prices are public.")
    return issues


def parse_sitemap(xml: str) -> List[str]:
    return [u.strip() for u in re.findall(r"<loc>([^<]+)</loc>", xml)]


def robots_disallows(robots: str) -> List[str]:
    rules: List[str] = []
    agents: List[str] = []
    for raw in robots.splitlines():
        line = raw.split("#", 1)[0].strip()
        if ":" not in line:
            continue
        k, v = [x.strip() for x in line.split(":", 1)]
        if k.lower() == "user-agent":
            agents = [v]
        elif k.lower() == "disallow" and "*" in agents and v:
            rules.append(v)
    return rules


def is_disallowed(path: str, rules: List[str]) -> bool:
    for rule in rules:
        pattern = "^" + re.escape(rule).replace(r"\*", ".*").replace(r"\$", "$")
        if re.match(pattern, path):
            return True
    return False


def audit(base: str, robots_override: Optional[str]) -> Dict[str, Any]:
    netloc = urllib.parse.urlparse(base).netloc
    domain = netloc.split(":")[0]
    pages: List[Dict[str, Any]] = []
    all_issues: List[Dict[str, Any]] = []
    titles: Dict[str, List[str]] = defaultdict(list)
    descs: Dict[str, List[str]] = defaultdict(list)

    robots_live = fetch(base + "/robots.txt", netloc)
    robots_body = robots_override if robots_override is not None else robots_live["body"]
    sitemap = fetch(base + "/sitemap.xml", netloc)
    llms = fetch(base + "/llms.txt", netloc)
    sitemap_urls = parse_sitemap(sitemap["body"]) if sitemap["status_code"] == 200 else []
    disallow = robots_disallows(robots_body)
    ai_bots = audit_robots_txt_for_ai_crawlers(robots_body)
    blocked_ai = sum(1 for v in ai_bots.values() if v.startswith("BLOCKED"))

    targets: List[Tuple[str, str]] = [(p, "index") for p in CONTENT_PAGES]
    targets += [(p, "noindex") for p in UTILITY_PAGES + NOINDEX_PROBES]

    for path, policy in targets:
        url = base + path
        resp = fetch(url, netloc)
        result = audit_single_page(url, resp, domain)
        dom = parse_html_document(resp["body"], base_url=url)
        issues = [i for i in result["issues"] if not (policy == "noindex" and i["rule_id"] == "AUD-IDX-01")]
        issues += jn_checks(path, url, resp, dom, policy)
        readability = compute_bilingual_readability(dom.full_body_text, "vi")
        geu = compute_autogeo_geu_score(dom.full_body_text, dom.headings.get("h2", []), len(dom.json_ld_blocks),
                                         llms["status_code"] == 200, blocked_ai)
        types = sorted({t for n in iter_graph_nodes(dom.json_ld_blocks) for t in node_types(n)})
        if policy == "index":
            titles[dom.title].append(path)
            descs[dom.meta_description].append(path)
            in_sitemap = url in sitemap_urls
            if not in_sitemap:
                issues.append({"rule_id": "JN-SMAP-01", "category": "JN_Consistency", "severity": "HIGH",
                               "title": "Indexable page missing from sitemap.xml", "url": url,
                               "evidence": "not in <loc>", "fix_snippet": "Add page to sitemap.", "epistemic_label": "[FACT]"})
            if robots_override is not None and is_disallowed(path, disallow):
                issues.append({"rule_id": "JN-ROB-01", "category": "JN_Consistency", "severity": "CRITICAL",
                               "title": "Indexable page disallowed by robots.txt", "url": url,
                               "evidence": path, "fix_snippet": "Remove the Disallow rule.", "epistemic_label": "[FACT]"})
        elif url in sitemap_urls:
            issues.append({"rule_id": "JN-SMAP-02", "category": "JN_Consistency", "severity": "HIGH",
                           "title": "noindex page listed in sitemap.xml", "url": url, "evidence": "in <loc>",
                           "fix_snippet": "Remove from sitemap.", "epistemic_label": "[FACT]"})
        all_issues.extend(issues)
        pages.append({
            "path": path, "policy": policy, "status": resp["status_code"], "ttfb_ms": resp["response_time_ms"],
            "title": dom.title, "title_len": len(dom.title), "description_len": len(dom.meta_description),
            "meta_robots": dom.meta_robots, "canonical": dom.canonical, "lang": dom.lang,
            "h1": dom.headings.get("h1", []), "h2": dom.headings.get("h2", []), "word_count": result["word_count"],
            "images_total": result["images_total"], "images_missing_alt": result["images_missing_alt"],
            "json_ld_types": types, "readability": readability, "geo": geu, "issue_count": len(issues),
        })

    for group, label, rule in ((titles, "title", "JN-DUP-01"), (descs, "meta description", "JN-DUP-02")):
        for value, paths in group.items():
            if value and len(paths) > 1:
                all_issues.append({"rule_id": rule, "category": "JN_Consistency", "severity": "MEDIUM",
                                   "title": f"Duplicate {label} across pages", "url": ", ".join(paths),
                                   "evidence": value[:90], "fix_snippet": f"Give each page a unique {label}.",
                                   "epistemic_label": "[FACT]"})
    if llms["status_code"] != 200:
        all_issues.append({"rule_id": "JN-AEO-01", "category": "12_AI_Search_GEO_and_LLMs_Readiness", "severity": "MEDIUM",
                           "title": "/llms.txt not served", "url": base + "/llms.txt",
                           "evidence": f"HTTP {llms['status_code']}", "fix_snippet": "Serve /llms.txt (Markdown).",
                           "epistemic_label": "[FACT]"})
    has_faq = any("FAQPage" in p["json_ld_types"] for p in pages)
    if not has_faq:
        all_issues.append({"rule_id": "JN-AEO-02", "category": "12_AI_Search_GEO_and_LLMs_Readiness", "severity": "LOW",
                           "title": "No FAQPage / question-answer blocks on any page", "url": base,
                           "evidence": "0 FAQPage nodes", "fix_snippet": "Add owner-approved FAQ block to /coaching/.",
                           "epistemic_label": "[FACT]"})

    all_issues.sort(key=lambda i: (SEVERITY_ORDER.get(i["severity"], 9), i["rule_id"]))
    return {
        "base": base, "generated_at": time.strftime("%Y-%m-%dT%H:%M:%S%z"), "skill_dir": str(SKILL_DIR),
        "robots_source": "file" if robots_override is not None else "live", "robots_body": robots_body,
        "robots_disallow": disallow, "ai_crawler_permissions": ai_bots,
        "sitemap_status": sitemap["status_code"], "sitemap_urls": sitemap_urls, "llms_status": llms["status_code"],
        "pages": pages, "issues": all_issues,
        "summary": {s: sum(1 for i in all_issues if i["severity"] == s) for s in SEVERITY_ORDER},
    }


def to_markdown(report: Dict[str, Any]) -> str:
    s = report["summary"]
    lines = [
        "# Kết quả kiểm toán SEO/AEO tự động (local)", "",
        f"- Base: `{report['base']}` · Thời điểm: `{report['generated_at']}` · robots: `{report['robots_source']}`",
        f"- Engine: skill `sunext-semrush-ahrefs-os` (`audit_single_page`, `compute_bilingual_readability`, "
        f"`audit_robots_txt_for_ai_crawlers`, `compute_autogeo_geu_score`) + JN consistency checks",
        f"- Tổng hợp: CRITICAL **{s['CRITICAL']}** · HIGH **{s['HIGH']}** · MEDIUM **{s['MEDIUM']}** · LOW **{s['LOW']}**",
        f"- sitemap.xml: HTTP {report['sitemap_status']} ({len(report['sitemap_urls'])} URL) · llms.txt: HTTP {report['llms_status']}",
        "", "## Trang", "",
        "| Path | Policy | HTTP | Title (len) | Desc len | robots | H1 | Words | JSON-LD types | GEU | Issues |",
        "|---|---|---|---|---|---|---|---|---|---|---|",
    ]
    for p in report["pages"]:
        lines.append(
            f"| `{p['path']}` | {p['policy']} | {p['status']} | {p['title_len']} | {p['description_len']} | "
            f"{p['meta_robots'] or '—'} | {len(p['h1'])} | {p['word_count']} | {', '.join(p['json_ld_types']) or '—'} | "
            f"{p['geo']['geu_citation_score_100']} | {p['issue_count']} |")
    lines += ["", "## Vấn đề (sắp theo mức độ)", "", "| Mức | Rule | Vấn đề | URL | Bằng chứng |", "|---|---|---|---|---|"]
    for i in report["issues"]:
        ev = str(i["evidence"]).replace("|", "\\|").replace("\n", " ")[:120]
        lines.append(f"| {i['severity']} | `{i['rule_id']}` | {i['title']} | {i['url']} | {ev} |")
    lines += ["", "## Quyền truy cập bot AI (robots.txt)", "", "| Bot | Trạng thái |", "|---|---|"]
    for bot, state in report["ai_crawler_permissions"].items():
        lines.append(f"| {bot} | {state} |")
    return "\n".join(lines) + "\n"


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--base", default="http://localhost:8080")
    ap.add_argument("--out", required=True, help="Output directory for audit-local.json / audit-local.md")
    ap.add_argument("--robots-file", help="Audit this robots.txt body instead of the live (local) one")
    ap.add_argument("--name", default="audit-local", help="Output file stem")
    args = ap.parse_args()

    robots_override = Path(args.robots_file).read_text(encoding="utf-8") if args.robots_file else None
    report = audit(args.base.rstrip("/"), robots_override)
    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    (out / f"{args.name}.json").write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
    (out / f"{args.name}.md").write_text(to_markdown(report), encoding="utf-8")
    s = report["summary"]
    print(f"CRITICAL={s['CRITICAL']} HIGH={s['HIGH']} MEDIUM={s['MEDIUM']} LOW={s['LOW']} -> {out}/{args.name}.md")
    return 0 if s["CRITICAL"] == 0 and s["HIGH"] == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
