#!/usr/bin/env python3
"""PDF final -> paquete privado reproducible. No extrae texto ni ejecuta OCR."""
import argparse
import hashlib
import json
import re
import subprocess
import tempfile
from pathlib import Path

from PIL import Image, ImageDraw
from pypdf import PdfReader

WIDTHS = (640, 1280, 1920, 2560, 3840)


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def canonical(data):
    return json.dumps(data, ensure_ascii=False, sort_keys=True, separators=(',', ':')).encode()


def convert(pdf, key, output, evidence):
    if not re.fullmatch(r'cde:[0-9]+:[0-9]+:[0-9]+(?:\.[0-9]+)*', key):
        raise ValueError('Clave de lección inválida')
    if pdf.is_symlink() or not pdf.is_file() or pdf.stat().st_size > 100 * 1024**2:
        raise ValueError('PDF no regular o demasiado grande')
    if output.resolve() == evidence.resolve() or output.resolve() in evidence.resolve().parents:
        raise ValueError('La evidencia debe quedar fuera del paquete')
    source_hash = digest(pdf)
    reader = PdfReader(pdf, strict=True)
    if reader.is_encrypted or not 1 <= len(reader.pages) <= 100:
        raise ValueError('PDF cifrado o fuera del límite de páginas')
    if output.exists():
        raise ValueError('La salida debe ser una carpeta nueva')
    output.mkdir(parents=True)
    (output / 'slides').mkdir()
    evidence.mkdir(parents=True, exist_ok=True)
    renderer = subprocess.run(['pdftoppm', '-v'], capture_output=True, text=True, check=True).stderr.splitlines()[0]
    manifest = {'schema_version': 1, 'clave_leccion': key, 'source_sha256': source_hash,
                'renderer_version': renderer, 'conversion_profile': 'webp-q88-method6-v1',
                'page_count': len(reader.pages), 'slides': []}
    thumbs = []
    with tempfile.TemporaryDirectory(prefix='cde-presentation-') as tmp:
        for number, page in enumerate(reader.pages, 1):
            w, h = float(page.mediabox.width), float(page.mediabox.height)
            if min(w, h) <= 0 or max(w, h) / min(w, h) > 10:
                raise ValueError('Geometría fuera de límites')
            fonts = subprocess.run(['pdffonts', '-f', str(number), '-l', str(number), str(pdf)], capture_output=True, text=True, check=True, timeout=30).stdout.splitlines()[2:]
            images = subprocess.run(['pdfimages', '-f', str(number), '-l', str(number), '-list', str(pdf)], capture_output=True, text=True, check=True, timeout=30).stdout.splitlines()[2:]
            native = [int(parts[3]) for line in images if len(parts := line.split()) > 4 and parts[2] == 'image']
            # Fuente sin fuentes tipográficas y con imagen: límite conservador, sin inventar detalle.
            limit = min(3840, max(native)) if not fonts and native else 3840
            widths = [width for width in WIDTHS if width <= limit] or [limit]
            render_w, render_h = (h, w) if int(page.get('/Rotate', 0)) % 180 else (w, h)
            if max(widths) ** 2 * render_h / render_w > 40_000_000:
                raise ValueError('La página excede el límite de píxeles antes de renderizar')
            prefix = Path(tmp) / 'page'
            subprocess.run(['pdftoppm', '-f', str(number), '-l', str(number), '-singlefile', '-scale-to-x', str(max(widths)), '-scale-to-y', '-1', '-png', str(pdf), str(prefix)], check=True, capture_output=True, timeout=120)
            with Image.open(prefix.with_suffix('.png')) as src:
                src.load()
                master = src.convert('RGB')
            if master.width * master.height > 40_000_000:
                raise ValueError('Página demasiado grande')
            slide = {'page': number, 'width': master.width, 'height': master.height, 'variants': []}
            for width in widths:
                im = master if width == master.width else master.resize((width, round(master.height * width / master.width)), Image.Resampling.LANCZOS)
                relative = f'slides/{number:04d}-{width}.webp'
                file = output / relative
                im.save(file, 'WEBP', quality=88, method=6)
                slide['variants'].append({'path': relative, 'width': im.width, 'height': im.height, 'mime_type': 'image/webp', 'bytes': file.stat().st_size, 'sha256': digest(file)})
            thumb = master.copy()
            thumb.thumbnail((384, 240))
            thumbs.append(thumb)
            manifest['slides'].append(slide)
            print(f'Página {number}/{len(reader.pages)}: {len(widths)} variantes', flush=True)
    if digest(pdf) != source_hash:
        raise ValueError('El PDF cambió durante la conversión')
    manifest['revision'] = hashlib.sha256(canonical(manifest)).hexdigest()
    (output / 'manifest.json').write_bytes(canonical(manifest))
    if sum(p.stat().st_size for p in output.rglob('*') if p.is_file()) > 1024**3:
        raise ValueError('Paquete mayor de 1 GiB')
    sheet = Image.new('RGB', (3 * 400, ((len(thumbs) + 2) // 3) * 270), '#eeeeee')
    draw = ImageDraw.Draw(sheet)
    for i, thumb in enumerate(thumbs):
        x, y = (i % 3) * 400 + 8, (i // 3) * 270 + 24
        sheet.paste(thumb, (x, y))
        draw.text((x, y - 18), f'{i + 1:02d}', fill='black')
    sheet.save(evidence / 'contact-sheet.jpg', quality=92)
    (evidence / 'conversion.json').write_text(json.dumps({'source': str(pdf), 'source_sha256': source_hash, 'revision': manifest['revision'], 'pages': len(thumbs), 'package_bytes': sum(p.stat().st_size for p in output.rglob('*') if p.is_file())}, indent=2) + '\n')
    return manifest


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--pdf', type=Path, required=True)
    parser.add_argument('--lesson-key', required=True)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--evidence', type=Path, required=True)
    args = parser.parse_args()
    convert(args.pdf, args.lesson_key, args.output, args.evidence)
