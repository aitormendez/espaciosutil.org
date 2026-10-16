// Ejecutar con playwright-cli run-code en una lección o ficha accesible con práctica.
// No escribe contenido ni asociaciones. Usa viewport de prueba y pantalla completa real.
async page => {
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 if(await page.evaluate(()=>!!document.fullscreenElement)) await page.evaluate(()=>document.exitFullscreen());
 const reject=page.getByRole('button',{name:'Rechazar',exact:true});if(await reject.isVisible())await reject.click();
 const viewer=page.locator('[data-practice-viewer]').first();
 const button=name=>viewer.getByRole('button',{name,exact:true});
 const index=viewer.locator('.practice-index');
 const measurements=[];let screensChecked=0;
 const measure=()=>viewer.evaluate(root=>{
   const box=s=>{const e=root.querySelector(s),r=e.getBoundingClientRect();return{x:r.x,y:r.y,right:r.right,bottom:r.bottom,width:r.width,height:r.height,scroll:e.scrollWidth,client:e.clientWidth}};
   const controls=[...root.querySelectorAll('.practice-toolbar button,.practice-controls button')].filter(b=>b.getClientRects().length).map(b=>({name:b.textContent.trim(),height:b.getBoundingClientRect().height,right:b.getBoundingClientRect().right,left:b.getBoundingClientRect().left}));
   return{viewport:innerWidth,main:box('.practice-viewer-main'),screen:box('.practice-screen'),toolbar:box('.practice-toolbar'),footer:box('.practice-controls'),controls,headingSize:root.querySelector('.practice-screen h2')?getComputedStyle(root.querySelector('.practice-screen h2')).fontSize:null};
 });
 const valid=(m,label,full=false)=>{
   check(m.main.scroll<=m.main.client+1,label+': sin desbordamiento horizontal');
   check(m.screen.width>m.main.width-50,label+': columna de texto completa');
   check(m.toolbar.bottom<=m.screen.y+1 && m.screen.bottom<=m.footer.y+1,label+': no se superponen controles y contenido');
   for(const b of m.controls)check(b.height>=43 && b.left>=-1 && b.right<=m.viewport+1,label+': controles táctiles dentro de pantalla '+b.name);
   if(full)check(m.footer.bottom<=m.height+1,label+': navegación visible');
 };
 for(const [width,height] of [[320,568],[360,640],[390,844],[430,932],[768,1024],[844,390]]){
   await page.setViewportSize({width,height});await button('Guía paso a paso').click();
   await button('Mostrar índice').click();await viewer.locator('[data-practice-jump]').first().click();
   check(!await index.isVisible(),'Cerrar índice al elegir destino');
   let inline=await measure();valid(inline,`${width} incrustado`);
   await button('Pantalla completa').click();await page.waitForFunction(()=>!!document.fullscreenElement);
   let full={...await measure(),height};valid(full,`${width} fullscreen`,true);
   await button('Mostrar índice').click();check(await index.isVisible(),'Índice visible en fullscreen');
   check((await measure()).screen.width===full.screen.width,'Abrir índice no estrecha el texto');
   await viewer.locator('[data-practice-jump]').first().click();
   check(!await index.isVisible(),'Seleccionar bloque cierra índice en fullscreen');
   const count=await viewer.evaluate(r=>JSON.parse(r.dataset.guide).screens.length);
   if(width===320 || width===844){
     screensChecked++;for(let i=1;i<count;i++){
       screensChecked++;await button('Siguiente').click();const m={...await measure(),height};valid(m,`${width} pantalla ${i+1}`,true);
       check(await viewer.locator('.practice-screen').evaluate(el=>el.scrollTop===0),'Cada pantalla empieza arriba');
     }
     check(await button('Siguiente').isDisabled(),'Fin de guía');
   }
   await button('Texto completo').click();await button('Mostrar índice').click();
   await viewer.locator('[data-practice-jump]').last().click();
   check(!await index.isVisible(),'Índice de texto se cierra');
   check(await page.evaluate(()=>document.activeElement.hasAttribute('data-practice-heading')),'Índice lleva foco al encabezado de lectura');
   check(await viewer.locator('[data-practice-reading]').evaluate(el=>el.scrollWidth<=el.clientWidth+1),'Lectura sin desbordamiento');
   await button('Guía paso a paso').click();await button('Salir de pantalla completa').click();
   await page.waitForFunction(()=>!document.fullscreenElement);
   measurements.push({width,height,inline,full});
 }
 await page.setViewportSize({width:390,height:844});
 await button('Mostrar índice').click();await viewer.locator('[data-practice-jump]').first().click();
 await viewer.scrollIntoViewIfNeeded();await page.screenshot({path:'output/playwright/practicas-mobile/embedded-390.png'});
 await button('Pantalla completa').click();await page.waitForFunction(()=>!!document.fullscreenElement);
 await page.screenshot({path:'output/playwright/practicas-mobile/fullscreen-390.png'});
 await button('Siguiente').click();await button('Siguiente').click();
 const response=page.waitForResponse(r=>r.url().includes('cde_practica_audio=1')&&r.status()===200);
 await button('Activar audio').click();await response;check(await button('Desactivar audio').getAttribute('aria-pressed')==='true','Audio activado');
 await page.screenshot({path:'output/playwright/practicas-mobile/paragraph-390.png'});
 await button('Desactivar audio').click();
 await button('Mostrar índice').click();await page.screenshot({path:'output/playwright/practicas-mobile/index-390.png'});
 await button('Cerrar índice').click();await button('Texto completo').click();
 await page.screenshot({path:'output/playwright/practicas-mobile/reading-390.png'});
 await button('Salir de pantalla completa').click();
 return{url:page.url(),measurements,screensChecked,audio:true,indexGuideAndReading:true};
}
