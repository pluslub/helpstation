#!/usr/bin/env python3
"""Recalculate the 8.1/8.2 item counts in docs/要件定義書.md and update
the status line and section headers to match. Exits with status 1 (no
file change) or 0 (file updated / already up to date) for CI use.
"""
import re
import sys
from pathlib import Path

DOC_PATH = Path("docs/要件定義書.md")


def count_items(section_text: str) -> int:
    # Top-level items look like "12. 内容..." at the start of a line.
    # Sub-bullets (e.g. "    - ...") are indented and not matched.
    return len(re.findall(r"(?m)^\d+\.\s", section_text))


def main() -> int:
    text = DOC_PATH.read_text(encoding="utf-8")

    m1 = re.search(r"### 8\.1.*?\n(.*?)### 8\.2", text, re.S)
    m2 = re.search(r"### 8\.2.*?\n(.*?)\n## 9\.", text, re.S)
    if not m1 or not m2:
        print("Could not locate section 8.1/8.2 boundaries", file=sys.stderr)
        return 1

    solved = count_items(m1.group(1))
    held = count_items(m2.group(1))
    total = solved + held

    new_text = text

    new_text, n_status = re.subn(
        r"8章\d+項目中\d+件解決・\d+件保留",
        f"8章{total}項目中{solved}件解決・{held}件保留",
        new_text,
    )
    new_text, n_h1 = re.subn(
        r"### 8\.1 解決済み（\d+件）",
        f"### 8.1 解決済み（{solved}件）",
        new_text,
    )
    new_text, n_h2 = re.subn(
        r"### 8\.2 保留・未解決（\d+件）",
        f"### 8.2 保留・未解決（{held}件）",
        new_text,
    )

    if n_status == 0 or n_h1 == 0 or n_h2 == 0:
        print(
            "Warning: one or more expected patterns were not found "
            f"(status={n_status}, header8.1={n_h1}, header8.2={n_h2})",
            file=sys.stderr,
        )

    if new_text != text:
        DOC_PATH.write_text(new_text, encoding="utf-8")
        print(f"Updated: {solved} solved / {held} held / {total} total")
    else:
        print(f"Already up to date: {solved} solved / {held} held / {total} total")

    return 0


if __name__ == "__main__":
    sys.exit(main())
