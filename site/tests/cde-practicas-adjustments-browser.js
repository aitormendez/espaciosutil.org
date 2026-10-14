// Recorrido de ajustes: ejecutar en la ficha local autenticada con playwright-cli run-code.
async (page) => {
  const check = (value, label) => { if (!value) throw new Error(label); };
  const button = name => page.getByRole('button', {name, exact:true});
  await button('Guía paso a paso').click();
  await button('1. De dónde viene esta práctica').click();
  let data=await page.evaluate(()=>JSON.parse(document.querySelector('[data-practice-viewer]').dataset.guide));
  check(data.screens.length===49,'49 pantallas');
  check(data.screens[0].path.length===1 && data.screens[0].speech==='Uno. De dónde viene esta práctica.','portada y punto hablado');
  check(data.screens[1].path.length===2 && data.screens[1].speech==='Uno punto uno. En este corpus, canalizar es permitir que la energía pase.','hijo conserva ruta sin narrar padre');
  check(data.screens.filter(s=>s.type==='heading').every(s=>s.speech.endsWith('.')),'todos los encabezados tienen punto hablado');
  await button('Siguiente').click();
  check(await page.getByRole('heading',{name:'1.1. En este corpus, canalizar es permitir que la energía pase',exact:true}).count()===1,'encabezado visual sin punto final');
  await button('Texto completo').click();
  await button('6. Cómo se hace, paso a paso').click();
  let reading=await page.evaluate(()=>({mode:document.querySelector('[data-practice-viewer]').dataset.mode,hidden:document.querySelector('.practice-index').hidden,focus:document.activeElement.textContent}));
  check(reading.mode==='reading' && !reading.hidden && reading.focus==='6. Cómo se hace, paso a paso','índice navega al encabezado de lectura');
  await button('Pantalla completa').click();
  await page.waitForFunction(()=>!!document.fullscreenElement && document.querySelector('.practice-index').hidden);
  await button('Mostrar índice').click();
  await button('6. Cómo se hace, paso a paso').click();
  const full=await page.evaluate(()=>({focus:document.activeElement.textContent,scroll:document.querySelector('[data-practice-reading]').scrollTop,top:document.activeElement.getBoundingClientRect().top,toolbarBottom:document.querySelector('[data-practice-toolbar]').getBoundingClientRect().bottom,overflow:document.fullscreenElement.scrollHeight>document.fullscreenElement.clientHeight+2}));
  check(full.focus==='6. Cómo se hace, paso a paso' && full.scroll>0 && full.top>=full.toolbarBottom-1 && !full.overflow,'lectura con scroll interno e índice visible en pantalla completa');
  await page.screenshot({path:'output/playwright/practicas-texto-completo-fullscreen-v2.png'});
  await page.keyboard.press('Escape');
  await page.waitForFunction(()=>!document.fullscreenElement && !document.querySelector('.practice-index').hidden);
  await button('Guía paso a paso').click();
  check((await page.locator('[data-practice-counter]').textContent()).startsWith('2 '),'lectura no cambia posición en guía');
  await button('1. De dónde viene esta práctica').click();
  await page.screenshot({path:'output/playwright/practicas-portada-v2.png'});
  return {screens:49,headingNarrations:21,full,reading,indexBothModes:true};
}
