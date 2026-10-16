"""Pruebas HTTP sobre el controlador real en el harness local; no certifican WordPress remoto."""
import json,unittest,urllib.request,urllib.error
BASE='http://127.0.0.1:8877/admin-post.php?action=cde_presentation&lesson=2389&kind='
class HTTPTests(unittest.TestCase):
 def request(self,url,mode='',method='GET',extra='',headers=None):
  req=urllib.request.Request(url,headers={'Cookie':f'qa_mode={mode}; {extra}',**(headers or {})},method=method)
  try:r=urllib.request.urlopen(req)
  except urllib.error.HTTPError as e:r=e
  return r.status,r.headers,r.read()
 def image_url(self):
  status,headers,body=self.request(BASE+'manifest','member');self.assertEqual(status,200)
  return json.loads(body)['presentation']['slides'][0]['sources'][0]['url']
 def test_anonymous_denied(self):self.assertEqual(self.request(BASE+'manifest')[0],401)
 def test_wrong_member_denied(self):self.assertEqual(self.request(BASE+'manifest','denied')[0],403)
 def test_draft_denied(self):self.assertEqual(self.request(BASE+'manifest','member',extra='qa_state=draft')[0],404)
 def test_missing_pmpro_closed(self):self.assertEqual(self.request(BASE+'manifest','member',extra='qa_dependency=missing')[0],503)
 def test_public_lesson_matches_lesson_policy(self):self.assertEqual(self.request(BASE+'manifest',extra='qa_public=1')[0],200)
 def test_image_authorized_private(self):
  status,headers,body=self.request(self.image_url(),'member');self.assertEqual(status,200);self.assertEqual(headers['Content-Type'],'image/webp');self.assertIn('no-store',headers['Cache-Control']);self.assertTrue(body.startswith(b'RIFF'))
 def test_copied_url_denied(self):self.assertEqual(self.request(self.image_url())[0],401)
 def test_head_authorized_no_body(self):
  status,headers,body=self.request(self.image_url(),'member','HEAD');self.assertEqual(status,200);self.assertEqual(body,b'');self.assertGreater(int(headers['Content-Length']),0)
 def test_conditional_without_session_denied(self):self.assertEqual(self.request(self.image_url(),headers={'If-None-Match':'*','If-Modified-Since':'Thu, 08 Oct 2026 08:00:00 GMT'})[0],401)
 def test_stale_revision(self):
  import re
  url=re.sub(r'revision=[a-f0-9]+','revision='+'0'*64,self.image_url());self.assertEqual(self.request(url,'member')[0],409)
 def test_editor_preview(self):self.assertEqual(self.request(BASE+'manifest&preview=1','editor',extra='qa_state=draft')[0],200)
 def test_member_cannot_preview(self):self.assertEqual(self.request(BASE+'manifest&preview=1','member')[0],403)
 def test_post_not_allowed(self):self.assertEqual(self.request(BASE+'manifest','member','POST')[0],405)
 def test_storage_not_public(self):self.assertEqual(self.request('http://127.0.0.1:8877/storage/manifest.json','member')[0],404)
if __name__=='__main__':unittest.main()
