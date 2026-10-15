// Ejecutar con playwright-cli run-code sobre una lección accesible con práctica, con viewport: null.
// No crea ni modifica asociaciones. La sesión debe tener acceso a la lección abierta.
async (page) => {
  const check = (value, label) => { if (!value) throw new Error(label); };
  await page.reload();
  const viewer = page.locator('[data-practice-viewer]').first();
  const index = viewer.locator('.practice-index');
  await viewer.locator('[data-practice-screen]:not([hidden])').waitFor();
  check(page.viewportSize() === null, 'Ventana nativa redimensionable');
  check(await index.count() === 1, 'Un único índice para ambos modos');
  const cdp = await page.context().newCDPSession(page);
  const {windowId, bounds} = await cdp.send('Browser.getWindowForTarget');
  const measurements = [];
  const measure = () => viewer.evaluate(root => {
    const box = el => { const r = el.getBoundingClientRect(); return {x:r.x,width:r.width,right:r.right}; };
    return {section:box(root.closest('.practice-lesson-section')),main:box(root.querySelector('.practice-viewer-main')),lesson:box(document.querySelector('.contenido')),index:box(root.querySelector('.practice-index')),visible:!root.querySelector('.practice-index').hidden,viewport:document.documentElement.clientWidth,overflow:document.documentElement.scrollWidth > document.documentElement.clientWidth + 1};
  });
  try {
    for (const width of [1920, 1440, 1024]) {
      await cdp.send('Browser.setWindowBounds', {windowId,bounds:{width,height:1000,windowState:'normal'}});
      await page.waitForFunction(w => outerWidth === w, width);
      await page.waitForFunction(() => {
        const root = document.querySelector('[data-practice-viewer]');
        const main = root.querySelector('.practice-viewer-main').getBoundingClientRect();
        const style = getComputedStyle(root);
        const fits = main.left - root.getBoundingClientRect().left >= parseFloat(style.getPropertyValue('--practice-index-width')) + parseFloat(style.getPropertyValue('--practice-index-gap'));
        return root.dataset.indexFits === String(fits);
      });
      const guide = await measure();
      check(Math.abs(guide.section.width - guide.viewport) < 2 && guide.section.x === 0, 'Sección a ancho completo');
      check(Math.abs(guide.main.width - guide.lesson.width) < 1 && Math.abs(guide.main.x - guide.lesson.x) < 1, 'Alineación con la columna de la lección');
      check(!guide.overflow, 'Sin desbordamiento');
      if (guide.visible) check(guide.index.x === 0 && guide.index.right < guide.main.x, 'Índice en el margen izquierdo sin superposición');
      await viewer.getByRole('button', {name:'Texto completo',exact:true}).click();
      const reading = await measure();
      check(reading.main.width === guide.main.width && reading.visible === guide.visible, 'La lectura usa toda la columna y el mismo estado de índice');
      if (!reading.visible) await viewer.getByRole('button', {name:'Mostrar índice',exact:true}).click();
      await viewer.getByRole('button', {name:'6. Cómo se hace, paso a paso',exact:true}).click();
      check(await page.evaluate(() => document.activeElement.textContent) === '6. Cómo se hace, paso a paso', 'Destino del índice en lectura');
      await viewer.getByRole('button', {name:'Guía paso a paso',exact:true}).click();
      if (!await index.isVisible()) await viewer.getByRole('button', {name:'Mostrar índice',exact:true}).click();
      await viewer.getByRole('button', {name:'1. De dónde viene esta práctica',exact:true}).click();
      check((await viewer.locator('[data-practice-screen]').textContent()).includes('1. De dónde viene esta práctica'), 'Destino del índice en guía');
      measurements.push({width,guide,reading});
    }
    check(measurements[0].guide.visible && !measurements.at(-1).guide.visible, 'Apertura y cierre automáticos al redimensionar');
    await viewer.getByRole('button', {name:'Mostrar índice',exact:true}).click();
    await viewer.getByRole('button', {name:'Cerrar índice',exact:true}).press('Escape');
    check(!await index.isVisible(), 'Escape cierra el panel');
    await viewer.getByRole('button', {name:'Pantalla completa',exact:true}).click();
    await page.waitForFunction(() => !!document.fullscreenElement);
    check(!await index.isVisible(), 'Pantalla completa comienza con el índice cerrado');
    await viewer.getByRole('button', {name:'Mostrar índice',exact:true}).click();
    check(await index.isVisible(), 'Índice en pantalla completa');
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => !document.fullscreenElement);
  } finally {
    await cdp.send('Browser.setWindowBounds', {windowId,bounds:{width:bounds.width,height:bounds.height,windowState:'normal'}});
    await cdp.detach();
  }
  return {url:page.url(),measurements};
}
