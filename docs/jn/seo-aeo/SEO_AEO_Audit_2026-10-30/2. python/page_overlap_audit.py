#!/usr/bin/env python3
"""
JN Step 14 — Page-level cannibalization / duplicate-content check.

Fetches the indexable pages of the local site (host restricted to --base) and compares them with the
skill's own char 3-gram cosine (`cosine_counter` / `char_ngrams`, Suite 2 — threshold 0.84 = skill's
cannibalization rule). Two views per page pair:
  - "intent": char 3-gram cosine of title + H1 + H2 (what the page claims to be about) — flagged at 0.84
  - "shared": share of the smaller page's 5-word shingles that also appear on the other page
              (literally duplicated copy). Char 3-gram cosine is NOT used for body text: any two
              Vietnamese texts share most character trigrams, so it scores every pair at 0.88–0.99.
Also lists H2 headings repeated across pages (shared sections) and leftover placeholder markers.

Usage (Docker stack running):
  python3 "docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-30/2. python/page_overlap_audit.py" \
      --base http://localhost:8080 --out "docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-30/1. report"
"""

from __future__ import annotations

import argparse
import html
import itertools
import json
import os
import re
import sys
import urllib.parse
import urllib.request
from collections import defaultdict
from pathlib import Path
from typing import Dict, List

SKILL_DIR = Path(os.environ.get("SEO_OS_SKILL_DIR", str(Path.home() / ".claude" / "skills" / "sunext-SEO-AEO")))
sys.path.insert(0, str(SKILL_DIR / "scripts"))

from seo_intent_cluster_and_scoring import char_ngrams, cosine_counter  # noqa: E402

PAGES = [
    "/", "/coaching/", "/my-story/", "/course/", "/client-success-story/", "/booking/",
    "/my-favorite-product/", "/chinh-sach-bao-mat/", "/dieu-khoan-dich-vu/",
]
CANNIBALIZATION_THRESHOLD = 0.84
SHINGLE_WORDS = 5
SHARED_COPY_THRESHOLD = 0.5
PLACEHOLDER_RE = re.compile(r"\[(?:CẦN NỘI DUNG|PLACEHOLDER)[^\]]*\]")


def fetch(base: str, path: str) -> str:
    url = urllib.parse.urljoin(base, path) + "?jn_overlap=1"
    if urllib.parse.urlparse(url).netloc != urllib.parse.urlparse(base).netloc:
        raise ValueError(f"Refusing off-host URL: {url}")
    req = urllib.request.Request(url, headers={"User-Agent": "JN-overlap-audit/1.0"})
    with urllib.request.urlopen(req, timeout=20) as resp:  # noqa: S310 — host checked above
        return resp.read().decode("utf-8", "replace")


def shingles(text: str) -> set:
    words = re.findall(r"\w+", text.lower())
    return {" ".join(words[i:i + SHINGLE_WORDS]) for i in range(len(words) - SHINGLE_WORDS + 1)}


def shared_ratio(a: set, b: set) -> float:
    smaller = min(len(a), len(b))
    return len(a & b) / smaller if smaller else 0.0


def strip_tags(fragment: str) -> str:
    return re.sub(r"\s+", " ", html.unescape(re.sub(r"<[^>]+>", " ", fragment))).strip()


def parse_page(doc: str) -> Dict[str, object]:
    head, _, body = doc.partition("</head>")
    body = re.sub(r"<(script|style|svg)[^>]*>.*?</\1>", " ", body, flags=re.S | re.I)
    main = re.search(r"<main[^>]*>(.*?)</main>", body, re.S | re.I)
    main_html = main.group(1) if main else body
    title_match = re.search(r"<title>(.*?)</title>", head, re.S)
    title = strip_tags(title_match.group(1)) if title_match else ""
    h1 = [strip_tags(h) for h in re.findall(r"<h1[^>]*>(.*?)</h1>", main_html, re.S)]
    h2 = [strip_tags(h) for h in re.findall(r"<h2[^>]*>(.*?)</h2>", main_html, re.S)]
    text = strip_tags(main_html)
    return {
        "title": title,
        "h1": h1,
        "h2": h2,
        "words": len(text.split()),
        "text": text,
        "placeholders": sorted(set(m[:90] for m in PLACEHOLDER_RE.findall(text))),
    }


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default="http://localhost:8080")
    ap.add_argument("--out", required=True)
    args = ap.parse_args()

    pages = {p: parse_page(fetch(args.base, p)) for p in PAGES}
    intent_vec = {p: char_ngrams(" ".join([d["title"], *d["h1"], *d["h2"]]).lower()) for p, d in pages.items()}
    body_shingles = {p: shingles(str(d["text"])) for p, d in pages.items()}

    pairs = []
    for a, b in itertools.combinations(PAGES, 2):
        pairs.append({
            "a": a,
            "b": b,
            "intent_cosine": round(cosine_counter(intent_vec[a], intent_vec[b]), 3),
            "shared_copy": round(shared_ratio(body_shingles[a], body_shingles[b]), 3),
        })
    pairs.sort(key=lambda r: (r["intent_cosine"], r["shared_copy"]), reverse=True)

    shared_h2: Dict[str, List[str]] = defaultdict(list)
    for p, d in pages.items():
        for h in set(d["h2"]):
            shared_h2[h].append(p)
    shared = {h: ps for h, ps in shared_h2.items() if len(ps) > 1}

    report = {
        "base": args.base,
        "threshold": CANNIBALIZATION_THRESHOLD,
        "pages": {p: {k: v for k, v in d.items() if k != "text"} for p, d in pages.items()},
        "pairs": pairs,
        "shared_h2": shared,
    }
    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    (out / "page-overlap.json").write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    lines = [
        "# Trùng lặp ý định & nội dung giữa các trang (local)",
        "",
        f"- Base: `{args.base}` · Độ đo: cosine char 3-gram của skill (`cosine_counter`), ngưỡng cannibalization `{CANNIBALIZATION_THRESHOLD}`",
        f"- `intent` = cosine char 3-gram của title + H1 + H2 (cờ ≥ {CANNIBALIZATION_THRESHOLD})",
        f"- `shared` = tỷ lệ cụm {SHINGLE_WORDS} từ của trang ngắn hơn xuất hiện nguyên văn ở trang kia (cờ ≥ {SHARED_COPY_THRESHOLD})",
        "",
        "## Trang",
        "",
        "| Path | Words | H1 | Placeholder còn hiển thị |",
        "|---|---|---|---|",
    ]
    for p, d in pages.items():
        lines.append(f"| `{p}` | {d['words']} | {' / '.join(d['h1'])} | {len(d['placeholders'])} |")
    lines += ["", "## Cặp trang (sắp theo intent)", "", "| A | B | intent | shared | Cờ |", "|---|---|---|---|---|"]
    for r in pairs[:15]:
        flags = []
        if r["intent_cosine"] >= CANNIBALIZATION_THRESHOLD:
            flags.append("⚠️ trùng ý định")
        if r["shared_copy"] >= SHARED_COPY_THRESHOLD:
            flags.append("⚠️ lặp nội dung")
        lines.append(f"| `{r['a']}` | `{r['b']}` | {r['intent_cosine']} | {r['shared_copy']} | {' · '.join(flags)} |")
    lines += ["", "## H2 lặp lại trên nhiều trang", "", "| H2 | Trang |", "|---|---|"]
    for h, ps in sorted(shared.items(), key=lambda kv: -len(kv[1])):
        lines.append(f"| {h} | {', '.join(f'`{p}`' for p in ps)} |")
    (out / "page-overlap.md").write_text("\n".join(lines) + "\n", encoding="utf-8")
    intent_flags = sum(1 for r in pairs if r["intent_cosine"] >= CANNIBALIZATION_THRESHOLD)
    copy_flags = sum(1 for r in pairs if r["shared_copy"] >= SHARED_COPY_THRESHOLD)
    print(f"intent >= {CANNIBALIZATION_THRESHOLD}: {intent_flags} · shared copy >= {SHARED_COPY_THRESHOLD}: {copy_flags}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
