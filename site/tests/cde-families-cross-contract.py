#!/usr/bin/env python3
"""Diagnóstico previo al despliegue: --runtime-root RUTA a una candidata de Contenidos.

Inspección estática, sin importar ni ejecutar el publicador, WordPress o NocoDB.
El MU-plugin acepta códigos configurados en ACF, no los cuatro slugs históricos.
No certifica planes, publicación ni readback. Sale 1 si hay incompatibilidad
conocida y 2 si el contrato no se puede verificar; ninguno autoriza el apply.
"""
from __future__ import annotations

import argparse
import ast
from pathlib import Path
import sys

LEGACY = frozenset(("espirituales-esenciales", "otras-principales", "otras-espirituales", "informacion-complementaria"))


def check(root: Path) -> int:
    path = root / "scripts" / "cde_families.py"
    try:
        module = ast.parse(path.read_text(encoding="utf-8"), filename=str(path))
    except (OSError, SyntaxError, UnicodeError) as exc:
        print(f"NO VERIFICADO: no se puede inspeccionar {path}: {exc}", file=sys.stderr)
        return 2
    for node in module.body:
        if not isinstance(node, (ast.Assign, ast.AnnAssign)):
            continue
        targets = node.targets if isinstance(node, ast.Assign) else [node.target]
        if not any(isinstance(t, ast.Name) and t.id == "FAMILIES" for t in targets):
            continue
        try:
            values = ast.literal_eval(node.value)
        except (TypeError, ValueError):
            continue
        if isinstance(values, (tuple, list, set, dict)) and all(isinstance(v, str) for v in values):
            if set(values) == LEGACY:
                print(f"INCOMPATIBLE: {path} declara el contrato histórico de cuatro slugs; "
                      "la asignación ACF requiere un código del catálogo editable. No aplicar.", file=sys.stderr)
                return 1
    print(f"NO VERIFICADO: {path} no declara el catálogo histórico reconocido. "
          "Su ausencia no demuestra que consuma los códigos dinámicos, los selle ni los revalide.", file=sys.stderr)
    return 2


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--runtime-root", type=Path, required=True)
    sys.exit(check(parser.parse_args().runtime_root.resolve()))
