#!/usr/bin/env python3
"""
Verify that every visible text node (and input/textarea placeholder) in
stitch_jannie_nguyen_website_wireframe/code.html appears verbatim in
docs/jn/wireframe-spec.md.
"""
import sys
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
HTML_PATH = ROOT / "stitch_jannie_nguyen_website_wireframe" / "code.html"
SPEC_PATH = ROOT / "docs" / "jn" / "wireframe-spec.md"


class VisibleTextExtractor(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.skip_depth = 0
        self.current_tag = None
        self.current_class = ""
        self.text_nodes = []
        self.placeholders = []

    def handle_starttag(self, tag: str, attrs: list) -> None:
        attr_dict = dict(attrs)
        self.current_tag = tag
        self.current_class = attr_dict.get("class", "")
        if tag in ("script", "style"):
            self.skip_depth += 1
        if "placeholder" in attr_dict and attr_dict["placeholder"].strip():
            self.placeholders.append({
                "line": self.getpos()[0],
                "tag": tag,
                "id": attr_dict.get("id", ""),
                "text": " ".join(attr_dict["placeholder"].split()),
            })

    def handle_endtag(self, tag: str) -> None:
        if tag in ("script", "style") and self.skip_depth > 0:
            self.skip_depth -= 1
        self.current_tag = None
        self.current_class = ""

    def handle_data(self, data: str) -> None:
        if self.skip_depth > 0:
            return
        normalized = " ".join(data.split())
        if not normalized:
            return
        is_icon = "material-symbols-outlined" in self.current_class and normalized.isidentifier()
        self.text_nodes.append({
            "line": self.getpos()[0],
            "tag": self.current_tag,
            "kind": "icon" if is_icon else "text",
            "text": normalized,
        })


def main() -> int:
    html_text = HTML_PATH.read_text(encoding="utf-8")
    spec_text = SPEC_PATH.read_text(encoding="utf-8")

    parser = VisibleTextExtractor()
    parser.feed(html_text)

    total_nodes = len(parser.text_nodes)
    text_only_nodes = [n for n in parser.text_nodes if n["kind"] == "text"]
    icon_only_nodes = [n for n in parser.text_nodes if n["kind"] == "icon"]
    unique_texts = sorted(set(n["text"] for n in parser.text_nodes))

    missing = []
    matched_nodes = 0
    for node in parser.text_nodes:
        if node["text"] in spec_text:
            matched_nodes += 1
        else:
            missing.append(node)

    missing_placeholders = []
    matched_placeholders = 0
    for ph in parser.placeholders:
        if ph["text"] in spec_text:
            matched_placeholders += 1
        else:
            missing_placeholders.append(ph)

    print("=== WIREFRAME VERBATIM TEXT VERIFICATION ===")
    print(f"Source HTML       : {HTML_PATH.relative_to(ROOT)}")
    print(f"Target Spec       : {SPEC_PATH.relative_to(ROOT)}")
    print(f"Total DOM nodes   : {total_nodes} ({len(text_only_nodes)} prose/label nodes + {len(icon_only_nodes)} icon nodes)")
    print(f"Unique strings    : {len(unique_texts)}")
    print(f"Matched DOM nodes : {matched_nodes} / {total_nodes} ({matched_nodes * 100.0 / total_nodes:.2f}%)")
    print(f"Placeholders      : {matched_placeholders} / {len(parser.placeholders)} matched")

    if missing or missing_placeholders:
        print("\nMISSING ITEMS:")
        for m in missing:
            print(f"  [L{m['line']}] ({m['kind']}:{m['tag']}) {m['text']}")
        for mp in missing_placeholders:
            print(f"  [L{mp['line']}] (placeholder:{mp['id']}) {mp['text']}")
        return 1

    print("STATUS            : PASS (100% verbatim match)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
