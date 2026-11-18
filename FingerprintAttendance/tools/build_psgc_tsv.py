"""Convert official PSGC JSON dumps into compact TSV files used at runtime.

Source: jobuntux/psgc data/2025-2Q, based on the Philippine Statistics Authority
PSGC 2nd Quarter 2025 release (18 regions, 42,011 barangays).

Usage:
  python tools/build_psgc_tsv.py
"""
from __future__ import annotations

import gzip
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "FingerprintAttendance" / "Data" / "psgc"
OUT = SRC


def clean(name: str) -> str:
    return " ".join((name or "").replace("\t", " ").split())


def write_tsv(path: Path, rows):
    path.write_text("\n".join(rows) + "\n", encoding="utf-8")
    print(f"Wrote {path.name} ({len(rows)} rows)")


def main() -> None:
    regions = json.loads((SRC / "regions.json").read_text(encoding="utf-8"))
    provinces = json.loads((SRC / "provinces.json").read_text(encoding="utf-8"))
    muncities = json.loads((SRC / "muncities.json").read_text(encoding="utf-8"))
    barangays = json.loads((SRC / "barangays.json").read_text(encoding="utf-8"))

    write_tsv(
        OUT / "regions.tsv",
        [f"{r['regCode']}\t{clean(r['regionName'])}" for r in regions],
    )
    write_tsv(
        OUT / "provinces.tsv",
        [
            f"{p['provCode']}\t{p['regCode']}\t{clean(p['provName'])}"
            for p in provinces
        ],
    )
    write_tsv(
        OUT / "muncities.tsv",
        [
            f"{m['munCityCode']}\t{m['provCode']}\t{clean(m['munCityName'])}"
            for m in muncities
        ],
    )

    brgy_rows = [
        f"{b['psgcCode']}\t{b['munCityCode']}\t{clean(b['brgyName'])}"
        for b in barangays
    ]
    gz_path = OUT / "barangays.tsv.gz"
    payload = ("\n".join(brgy_rows) + "\n").encode("utf-8")
    with gzip.open(gz_path, "wb", compresslevel=9) as handle:
        handle.write(payload)
    print(f"Wrote {gz_path.name} ({len(brgy_rows)} rows, {gz_path.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
