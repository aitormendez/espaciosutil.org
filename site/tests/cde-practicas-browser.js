// Ejecutar mediante playwright-cli run-code en la ficha local, con miembro QA autenticado.
// Antes de cargar la ficha, instrumentar window.Audio para registrar instancias en window.__practiceAudio.
async (page) => {
  const checks = [];
  const check = (value, label) => { if (!value) throw new Error(label); checks.push(label); };
  const button = name => page.getByRole('button', {name, exact:true});
  const state = () => page.evaluate(() => ({counter:document.querySelector('[data-practice-counter]').textContent, indexHidden:document.querySelector('.practice-index').hidden, fullscreen:!!document.fullscreenElement, audio:window.__practiceAudio.map(a=>({paused:a.paused,time:a.currentTime,src:a.currentSrc,ended:a.ended})), status:document.querySelector('[data-practice-status]').textContent}));
  await button('Activar audio').click();
  await page.waitForFunction(() => window.__practiceAudio[0].currentTime > .3);
  let s=await state(); check(!s.audio[0].paused && !s.status,'voz real reproducida');
  await button('Siguiente').click();
  await page.waitForFunction(() => window.__practiceAudio[0].currentTime > .3 && window.__practiceAudio[0].currentSrc.includes('screen=1&'));
  s=await state();check(s.counter.startsWith('2 ') && !s.audio[0].paused,'siguiente cambia narración');
  const source=s.audio[0].src; const time=s.audio[0].time;
  await button('Pantalla completa').click(); await page.waitForFunction(()=>!!document.fullscreenElement && document.querySelector('.practice-index').hidden);
  s=await state(); check(s.fullscreen && s.indexHidden,'pantalla completa oculta índice');
  check(s.audio[0].src===source && s.audio[0].time>=time,'pantalla completa no reinicia audio');
  await button('Mostrar índice').click();check(!(await state()).indexHidden,'mostrar índice');
  await button('Ocultar índice').click();check((await state()).indexHidden,'ocultar índice');
  await page.keyboard.press('Escape');
  await page.waitForFunction(()=>!document.fullscreenElement && !document.querySelector('.practice-index').hidden);
  s=await state(); check(!s.indexHidden && s.counter.startsWith('2 '),'Escape restaura índice y posición');
  await button('Texto completo').click();
  check((await state()).audio[0].paused,'lectura completa detiene audio');
  await button('Guía paso a paso').click();
  await page.waitForFunction(()=>window.__practiceAudio[0].currentTime>.3);
  check((await state()).counter.startsWith('2 '),'regreso a guía conserva posición');
  await button('Desactivar audio').click();check((await state()).audio[0].paused,'desactivar detiene audio');
  await button('6. Cómo se hace, paso a paso').click();
  while (await button('Siguiente').isEnabled()) await button('Siguiente').click();
  check((await state()).counter.startsWith('49 '),'49 pantallas y límite final');
  await button('Anterior').click();check((await state()).counter.startsWith('48 '),'retroceso funciona');
  await button('Activar audio').click();
  await page.waitForFunction(()=>window.__practiceAudio[0].currentTime>.3);
  await page.waitForFunction(()=>window.__practiceAudio[0].ended,{},{timeout:30000});
  check((await state()).counter.startsWith('48 '),'fin del audio no avanza pantalla');
  await button('Desactivar audio').click();
  const url = await page.evaluate(()=>JSON.parse(document.querySelector('[data-practice-viewer]').dataset.guide).screens[0].audio);
  const access = await page.evaluate(async url=>{
    const r=await fetch(url);const bytes=await r.arrayBuffer();
    const stale=new URL(url);stale.searchParams.set('revision','0'.repeat(64));const r2=await fetch(stale);
    const invalid=new URL(url);invalid.searchParams.set('screen','999');const r3=await fetch(invalid);
    return {member:r.status,bytes:bytes.byteLength,type:r.headers.get('content-type'),cache:r.headers.get('cache-control'),stale:r2.status,invalid:r3.status};
  },url);
  check(access.member===200 && access.bytes>10000 && access.type==='audio/mpeg' && access.cache.includes('no-store'),'audio privado disponible para miembro');
  check(access.stale===404 && access.invalid===404,'revision antigua e índice inválido denegados');
  const guest=await page.context().browser().newContext();
  await guest.addCookies([{name:'cde_practicas_preview',value:'1',domain:'espaciosutil.test',path:'/'}]);
  const guestPage=await guest.newPage();
  const locked=await guestPage.goto('http://espaciosutil.test/practicas/sentir-el-suelo-prueba-local/');
  check(locked.status()===403 && await guestPage.locator('[data-practice-viewer]').count()===0,'guía no se entrega al anónimo');
  const denied=await guestPage.evaluate(async url=>(await fetch(url)).status,url); check(denied===403,'audio anónimo denegado');
  await guest.close();
  await button('1. De dónde viene esta práctica').click();
  await page.screenshot({path:'output/playwright/practicas-individual.png'});
  return {checks,access};
}
