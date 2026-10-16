// Ejecutar con playwright-cli run-code en una página local que cargue los estilos del tema.
// Usa únicamente un documento de prueba del navegador; no escribe en WordPress.
async (page) => {
  await page.reload();
  const css = await page.evaluate(() => {
    const result = [];
    const visit = rules => { for (const rule of rules) {
      if (rule.selectorText?.includes('.lesson-study-content')) result.push(rule.cssText);
      else if (rule.cssRules) visit(rule.cssRules);
    } };
    for (const sheet of document.styleSheets) { try { visit(sheet.cssRules); } catch {} }
    return result.join('\n');
  });
  if (!css.includes('100cqw')) throw new Error('No se cargaron los estilos de separadores');
  const cases = [[1,1],[1,0],[0,1],[0,0]];
  let html = '<style>body{margin:0}.lesson-study-content{width:100%;--practice-border:#353535}.lesson-presentation,#lesson-quiz{max-width:896px;margin:40px auto}.practice-lesson-section{border-block:1px solid #353535}</style><style>' + css + '</style>';
  for (const [practice,presentation] of cases) {
    html += `<div class="lesson-study-content" data-case="${practice}${presentation}"><div class="lesson-summary">Texto</div>`;
    if (practice) html += '<section class="practice-lesson-section">Práctica</section>';
    if (presentation) html += '<section class="lesson-presentation">Presentación</section>';
    html += '<section id="lesson-quiz">Cuestionario</section></div>';
  }
  html += '<div class="lesson-study-content" data-case="empty"><div class="lesson-summary">Texto</div><section class="practice-lesson-section"></section><section class="lesson-presentation" hidden>Oculta</section><section id="lesson-quiz">Cuestionario</section></div>';
  await page.setContent(html);
  const results = await page.evaluate(() => Array.from(document.querySelectorAll('[data-case]')).map(parent => ({
    scenario:parent.dataset.case,
    sections:Array.from(parent.children).map(el => {
      const s=getComputedStyle(el),p=getComputedStyle(el,'::before');
      return {name:el.className||el.id,empty:!el.textContent,hidden:el.hidden,separator:p.content==='""',top:s.borderTopWidth,bottom:s.borderBottomWidth,width:p.width};
    })
  })));
  for (const result of results) {
    const visible = result.sections.filter(s => !s.empty && !s.hidden);
    if (visible.filter(s => s.separator).length !== visible.length-1) throw new Error('Número de separadores: '+result.scenario);
    if (result.sections.some(s => (s.empty || s.hidden) && s.separator)) throw new Error('Separador en bloque vacío u oculto');
    if (result.sections.some(s => s.name==='practice-lesson-section' && parseFloat(s.bottom)>0)) throw new Error('Borde inferior duplicado');
  }
  return results;
}
