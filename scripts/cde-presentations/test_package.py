import importlib.util
import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from PIL import Image
from reportlab.pdfgen import canvas

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('converter', Path(__file__).with_name('convert.py'))
converter = importlib.util.module_from_spec(spec)
spec.loader.exec_module(converter)

class PackageTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)
        self.package = self.root / 'package'
        (self.package / 'slides').mkdir(parents=True)
        img = self.package / 'slides/0001-640.webp'
        Image.new('RGB', (640, 360), 'purple').save(img, 'WEBP')
        self.data = {'schema_version': 1, 'clave_leccion': 'cde:01:00:0.1', 'source_sha256': 'a'*64, 'renderer_version': 'fixture', 'conversion_profile':'test', 'page_count':1, 'slides':[{'page':1,'width':640,'height':360,'variants':[{'path':'slides/0001-640.webp','width':640,'height':360,'mime_type':'image/webp','bytes':img.stat().st_size,'sha256':converter.digest(img)}]}]}
        self.write()
    def tearDown(self): self.temp.cleanup()
    def write(self):
        self.data.pop('revision', None)
        self.data['revision'] = __import__('hashlib').sha256(converter.canonical(self.data)).hexdigest()
        (self.package / 'manifest.json').write_bytes(converter.canonical(self.data))
    def php(self):
        script = 'require $argv[1]; try { $p=EspacioSutil\\Presentations\\Package::read($argv[2], true); echo $p["revision"]; } catch(Throwable $e) {fwrite(STDERR,$e->getMessage()); exit(1);}'
        return subprocess.run(['php','-r',script,str(ROOT/'site/web/app/mu-plugins/cde-presentations/package.php'),str(self.package)],capture_output=True,text=True)
    def test_python_php_canonical_and_valid_package(self): self.assertEqual(self.php().stdout,self.data['revision'])
    def test_tampered_image(self):
        (self.package/'slides/0001-640.webp').write_bytes(b'changed');self.assertNotEqual(self.php().returncode,0)
    def test_pdf_disguised_as_webp(self):
        p=self.package/'slides/0001-640.webp';p.write_bytes(b'%PDF-1.7\n')
        self.data['slides'][0]['variants'][0].update(bytes=p.stat().st_size,sha256=converter.digest(p));self.write();self.assertNotEqual(self.php().returncode,0)
    def test_extra_source_pdf(self):
        (self.package/'source.pdf').write_bytes(b'%PDF-1.7');self.assertNotEqual(self.php().returncode,0)
    def test_missing_file(self):
        (self.package/'slides/0001-640.webp').unlink();self.assertNotEqual(self.php().returncode,0)
    def test_traversal(self):
        self.data['slides'][0]['variants'][0]['path']='../private.webp';self.write();self.assertNotEqual(self.php().returncode,0)
    def test_symlink(self):
        p=self.package/'slides/0001-640.webp';real=self.root/'real.webp';p.rename(real);p.symlink_to(real);self.assertNotEqual(self.php().returncode,0)
    def test_duplicate_width(self):
        self.data['slides'][0]['variants']*=2;self.write();self.assertNotEqual(self.php().returncode,0)
    def test_bad_page_order(self):
        self.data['slides'][0]['page']=2;self.write();self.assertNotEqual(self.php().returncode,0)
    def test_text_field_not_allowed(self):
        self.data['slides'][0]['text']='extra';self.write();self.assertNotEqual(self.php().returncode,0)
    def test_bad_geometry(self):
        self.data['slides'][0]['height']=640;self.write();self.assertNotEqual(self.php().returncode,0)
    def test_manifest_tampered(self):
        self.data['page_count']=2;(self.package/'manifest.json').write_bytes(converter.canonical(self.data));self.assertNotEqual(self.php().returncode,0)
    def test_converter_raster_deterministic_no_upscale(self):
        png=self.root/'image.png';Image.new('RGB',(640,360),'navy').save(png)
        pdf=self.root/'source.pdf';c=canvas.Canvas(str(pdf),pagesize=(640,360));c.drawImage(str(png),0,0,640,360);c.save()
        # ReportLab adds a default unused font: remove it to represent a flattened PDF.
        from pypdf import PdfReader,PdfWriter
        from pypdf.generic import NameObject,DictionaryObject
        reader=PdfReader(pdf);reader.pages[0]['/Resources'][NameObject('/Font')]=DictionaryObject();writer=PdfWriter();writer.add_page(reader.pages[0]);writer.write(pdf)
        a=converter.convert(pdf,'cde:01:00:0.1',self.root/'a',self.root/'ea')
        b=converter.convert(pdf,'cde:01:00:0.1',self.root/'b',self.root/'eb')
        self.assertEqual(a['revision'],b['revision']);self.assertEqual([v['width'] for v in a['slides'][0]['variants']],[640])
    def test_converter_rejects_invalid_pdf(self):
        pdf=self.root/'bad.pdf';pdf.write_bytes(b'bad')
        with self.assertRaises(Exception):converter.convert(pdf,'cde:01:00:0.1',self.root/'out',self.root/'ev')

if __name__=='__main__':unittest.main()
