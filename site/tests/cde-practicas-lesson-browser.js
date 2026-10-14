// Ejecutar con playwright-cli run-code, sesión local de miembro y viewport: null.
// La lección 2967 es una ubicación temporal autorizada para revisar el diseño.
async (page) => {
 await page.goto('http://espaciosutil.test/lecciones-del-cde/el-circulo-carmesi-introduccion/que-es-el-circulo-carmesi/?cde_practicas_preview=1#lesson-practices-2967');
 await page.reload();
 const viewer=page.locator('[data-practice-viewer]');
 await viewer.waitFor({state:'visible'});
 const check=(v,label)=>{if(!v)throw new Error(label);};
 check(page.viewportSize()===null,'Ventana libre');
 check(await page.locator('[data-media-props]').count()===1,'Medios originales');
 check(await page.locator('[data-lesson-subindex]').count()===1,'Subíndice original');
 check(await page.locator('#lesson-quiz').count()===1,'Cuestionario original');
 check(await page.locator('.lesson-practice-layout,.lesson-with-practices').count()===0,'Diseño habitual sin envoltura añadida');
 const media=JSON.parse(await page.locator('[data-media-props]').getAttribute('data-media-props'));
 check(!!media.video&&!!media.audio,'Vídeo y audio');
 await viewer.getByRole('button',{name:'Siguiente',exact:true}).click();
 check((await viewer.locator('[data-practice-screen]').textContent()).includes('1.1. En este corpus'),'Guía operativa');
 await viewer.getByRole('button',{name:'Texto completo',exact:true}).click();
 await viewer.getByRole('button',{name:'6. Cómo se hace, paso a paso',exact:true}).click();
 check(await page.evaluate(()=>document.activeElement.textContent)==='6. Cómo se hace, paso a paso','Índice del texto completo');
 await viewer.getByRole('button',{name:'Guía paso a paso',exact:true}).click();
 const cdp=await page.context().newCDPSession(page);
 const {windowId,bounds}=await cdp.send('Browser.getWindowForTarget');
 const widths=[];
 try {
  for(const width of [1440,1280,1024]) {
   await cdp.send('Browser.setWindowBounds',{windowId,bounds:{width,height:1000,windowState:'normal'}});
   await page.waitForFunction(w=>outerWidth===w,width);
   const m=await viewer.evaluate(el=>({window:innerWidth,viewer:el.getBoundingClientRect().width,overflow:document.documentElement.scrollWidth>document.documentElement.clientWidth+1}));
   check(!m.overflow,'Sin desbordamiento a '+width);widths.push(m);
  }
 } finally {await cdp.send('Browser.setWindowBounds',{windowId,bounds:{width:bounds.width,height:bounds.height,windowState:'normal'}});await cdp.detach();}
 check(widths[0].viewer>widths[1].viewer&&widths[1].viewer>widths[2].viewer,'El visor responde a la ventana');
 await viewer.getByRole('button',{name:'Pantalla completa',exact:true}).click();
 await page.waitForFunction(()=>!!document.fullscreenElement);
 await viewer.getByRole('button',{name:'Mostrar índice',exact:true}).click();
 check(await viewer.locator('.practice-index').isVisible(),'Índice en pantalla completa');
 await page.keyboard.press('Escape');
 await page.waitForFunction(()=>!document.fullscreenElement);
 await page.locator('#lesson-practices-2967').scrollIntoViewIfNeeded();
 await page.screenshot({path:'output/playwright/practicas-leccion-completa.png'});
 await page.bringToFront();
 return {url:page.url(),title:await page.getByRole('heading',{level:1}).textContent(),nativeViewport:true,video:true,audio:true,quiz:true,subindex:true,guide:true,readingIndex:true,fullscreen:true,widths};
}
