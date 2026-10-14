// Ejecutar con playwright-cli run-code en la lección local, con sesión de miembro y viewport: null.
async (page) => {
 const check=(value,label)=>{if(!value)throw new Error(label);};
 await page.reload();
 check(page.viewportSize()===null,'La ventana debe usar viewport nativo');
 const lessonId=await page.locator('.practice-lesson-section').getAttribute('id');
 const contentId=lessonId.replace('lesson-practices-', 'lesson-content-');
 const nav=page.getByRole('navigation',{name:'En esta lección',exact:true});
 const viewer=page.locator('[data-practice-viewer]');
 await viewer.waitFor({state:'visible'});
 const cdp=await page.context().newCDPSession(page);
 const {windowId,bounds}=await cdp.send('Browser.getWindowForTarget');
 const measurements=[];
 try {
  for (const width of [1440,1280,1024]) {
   await cdp.send('Browser.setWindowBounds',{windowId,bounds:{width,height:1000,windowState:'normal'}});
   await page.waitForFunction(w=>window.outerWidth===w,width);
   await page.getByRole('button',{name:'Guía paso a paso',exact:true}).click();
   await nav.getByRole('link',{name:'Práctica',exact:true}).click();
   const measure=await viewer.evaluate(el=>{const b=el.getBoundingClientRect(),n=document.querySelector('.lesson-practice-nav').getBoundingClientRect();return {innerWidth,outerWidth,width:b.width,x:b.x,navRight:n.right,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth+1,viewerOverflow:el.scrollWidth>el.clientWidth+1,background:getComputedStyle(document.querySelector('.lesson-with-practices')).backgroundColor};});
   check(!measure.overflow&&!measure.viewerOverflow,'Sin desbordamiento en '+width);
   check(measure.x>=measure.navRight+31,'Columnas del wireframe en '+width);
   check(measure.background==='rgb(16, 16, 16)','Fondo oscuro');
   await page.getByRole('button',{name:'Texto completo',exact:true}).click();
   await page.getByRole('button',{name:'6. Cómo se hace, paso a paso',exact:true}).click();
   const reading=await page.evaluate(()=>({target:document.activeElement.textContent,mode:document.querySelector('[data-practice-viewer]').dataset.mode,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth+1}));
   check(reading.mode==='reading'&&reading.target==='6. Cómo se hace, paso a paso'&&!reading.overflow,'Índice de lectura en '+width);
   measurements.push({...measure,reading});
  }
  check(measurements[0].width>measurements[1].width&&measurements[1].width>measurements[2].width,'El visor responde a la ventana');
  await page.getByRole('button',{name:'Guía paso a paso',exact:true}).click();
  await page.getByRole('button',{name:'Siguiente',exact:true}).click();
  check((await viewer.locator('[data-practice-screen]').textContent()).includes('1.1. En este corpus'),'Ruta completa visible');
  await page.getByRole('button',{name:'Pantalla completa',exact:true}).click();
  await page.waitForFunction(()=>!!document.fullscreenElement);
  await page.getByRole('button',{name:'Mostrar índice',exact:true}).click();
  check(await viewer.locator('.practice-index').isVisible(),'Índice en pantalla completa');
  await page.keyboard.press('Escape');
  await page.waitForFunction(()=>!document.fullscreenElement);
  await nav.getByRole('link',{name:'Contenido de la lección',exact:true}).click();
  check(new URL(page.url()).hash==='#'+contentId,'Ancla al texto original');
  await nav.getByRole('link',{name:'Práctica',exact:true}).click();
 } finally {
  await cdp.send('Browser.setWindowBounds',{windowId,bounds:{width:bounds.width,height:bounds.height,windowState:'normal'}});
  await cdp.detach();
 }
 await viewer.scrollIntoViewIfNeeded();
 await page.screenshot({path:'output/playwright/practicas-leccion-original.png'});
 await page.bringToFront();
 return {nativeViewport:true,measurements,lesson:'cde:08:00:0.4.3.2',url:page.url()};
}
