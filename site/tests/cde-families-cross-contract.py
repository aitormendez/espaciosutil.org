#!/usr/bin/env python3
"""Read-only conformance between local WordPress and an explicit CDE candidate.

Run from site, e.g. python3 tests/cde-families-cross-contract.py --runtime-root PATH.
No NocoDB access, no WP content writes, no runtime activation or bytecode files.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import runpy
import subprocess
import sys

sys.dont_write_bytecode = True

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--runtime-root", type=Path, required=True)
args = parser.parse_args()
root = args.runtime_root.resolve(strict=True)
module_path = root / "scripts" / "cde_families.py"
if not module_path.is_file():
    parser.error("The selected runtime has no scripts/cde_families.py")
site = Path(__file__).resolve().parents[1]
php = """if (!defined('WP_ENV') || WP_ENV !== 'development') { WP_CLI::error('Development only'); }
echo wp_json_encode(\\EspacioSutil\\Cde\\Families::CATALOG, JSON_UNESCAPED_UNICODE);"""
process = subprocess.run(["wp", "eval", php], cwd=site, text=True, capture_output=True, timeout=60)
if process.returncode:
    raise SystemExit("Could not read the local WordPress family catalog; no writes attempted.")
try:
    wordpress = json.loads(process.stdout)
except json.JSONDecodeError:
    raise SystemExit("Local WordPress catalog response was not JSON.") from None
candidate = runpy.run_path(str(module_path))
expected = {
    "espirituales-esenciales": "Enseñanzas espirituales esenciales",
    "otras-principales": "Otras enseñanzas del grupo principal",
    "otras-espirituales": "Otras enseñanzas espirituales",
    "informacion-complementaria": "Información complementaria",
}
assert wordpress == expected, "WordPress labels or stable slugs differ from the approved contract"
assert tuple(wordpress) == candidate["FAMILIES"], "Python and WordPress family catalogs differ"
normalize = candidate["normalize_selector"]
error_type = candidate["FamilyError"]
checks = 2
for slug in expected:
    for value in (slug, [slug], {"value": slug}):
        assert normalize(value) == slug
        checks += 1
for value in (None, "", [], list(expected)[:2], "01", "audio_sintético", "video_grabado", "unknown", {"value": "unknown"}):
    try:
        normalize(value)
    except error_type:
        checks += 1
    else:
        raise AssertionError(f"Candidate accepted an invalid or inferred family: {value!r}")
records = [
    {"id": 1, "fields": {"codigo_serie": "01", "serie": "Urantia", "clave_leccion": "test:a", "familia_serie": "espirituales-esenciales"}},
    {"id": 2, "fields": {"codigo_serie": "01", "serie": "Urantia", "clave_leccion": "test:b", "familia_serie": "otras-principales"}},
    {"id": 3, "fields": {"codigo_serie": "00", "serie": "Guía de estudio", "clave_leccion": "test:c"}},
]
before = json.dumps(records, sort_keys=True)
audit = candidate["audit_inventory"](records)
assert audit["writes_performed"] == 0 and len(audit["rows"]) == 3
assert all("series_family_conflict" in row["diagnostics"] for row in audit["rows"][:2])
assert "field_absent_from_row" in audit["rows"][2]["diagnostics"]
assert before == json.dumps(records, sort_keys=True), "Audit mutated human input"
checks += 4
print(f"OK {checks} cross-contract checks; WordPress catalog and candidate agree. Read-only, no remote access.")
print(f"Candidate inspected: {root}")
