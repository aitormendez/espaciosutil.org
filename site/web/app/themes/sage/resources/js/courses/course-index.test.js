import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { MOBILE_COURSE_INDEX_QUERY, shouldScrollToCourseTopics } from './courseIndexScroll.js';

// Execute the production controller with deterministic DOM/transport/animation
// doubles. These are interaction unit tests, not browser or GSAP visual tests.
const source = readFileSync(new URL('./course-index.js', import.meta.url), 'utf8')
  .replace(/^import[\s\S]*?;\n/gm, '')
  .replace(/^export (?:default )?function /gm, 'function ');
const camel = (name) => name.replace(/-([a-z])/g, (_, c) => c.toUpperCase());

function harness({ reduced = false, mobile = false, legacy = false } = {}) {
  let doc;
  class Element {
    constructor(tag = 'div', attrs = {}) {
      this.tagName = tag.toUpperCase(); this.parentNode = null; this.children = [];
      this.dataset = {}; this.attrs = new Map(); this.style = {}; this.listeners = new Map();
      this.classes = new Set(); this.disabled = false; this.text = ''; this.html = '';
      this.classList = {
        add: (...names) => names.forEach((n) => this.classes.add(n)),
        remove: (...names) => names.forEach((n) => this.classes.delete(n)),
        contains: (n) => this.classes.has(n),
        toggle: (n, force = !this.classes.has(n)) => { force ? this.classes.add(n) : this.classes.delete(n); return force; },
      };
      Object.entries(attrs).forEach(([key, value]) => this.setAttribute(key, value));
    }
    get id() { return this.getAttribute('id') ?? ''; }
    set id(value) { this.setAttribute('id', value); }
    get isConnected() { return this === doc || Boolean(this.parentNode?.isConnected); }
    get textContent() { return this.text + this.children.map((c) => c.textContent).join(''); }
    set textContent(value) { this.text = value; this.children.forEach((c) => { c.parentNode = null; }); this.children = []; }
    get innerHTML() { return this.html; }
    set innerHTML(value) { this.html = value; this.children.forEach((c) => { c.parentNode = null; }); this.children = []; this.onHTML?.(value); }
    setAttribute(key, value) {
      value = String(value); this.attrs.set(key, value);
      if (key.startsWith('data-')) this.dataset[camel(key.slice(5))] = value;
      if (key === 'class') this.classes = new Set(value.split(/\s+/));
    }
    getAttribute(key) { return this.attrs.get(key) ?? null; }
    hasAttribute(key) { return this.attrs.has(key); }
    removeAttribute(key) { this.attrs.delete(key); }
    append(...nodes) { nodes.forEach((n) => { n.parentNode = this; this.children.push(n); }); return this; }
    contains(node) { return node === this || this.children.some((c) => c.contains(node)); }
    matches(selector) {
      return selector.split(',').some((part) => {
        part = part.trim();
        const attr = part.match(/^(\w+)?\[([^=\]]+)(?:="([^"]*)")?\]$/);
        if (attr) return (!attr[1] || this.tagName === attr[1].toUpperCase()) &&
          this.hasAttribute(attr[2]) && (attr[3] === undefined || this.getAttribute(attr[2]) === attr[3]);
        if (part.startsWith('.')) return this.classList.contains(part.slice(1));
        if (part.startsWith('#')) return this.id === part.slice(1);
        return this.tagName === part.toUpperCase();
      });
    }
    closest(selector) { return this.matches(selector) ? this : this.parentNode?.closest(selector) ?? null; }
    querySelectorAll(selector) { return this.children.flatMap((c) => [...(c.matches(selector) ? [c] : []), ...c.querySelectorAll(selector)]); }
    querySelector(selector) { return this.querySelectorAll(selector)[0] ?? null; }
    getElementById(id) { return this.querySelectorAll('[id]').find((e) => e.id === id) ?? null; }
    addEventListener(type, handler) { const list = this.listeners.get(type) ?? []; list.push(handler); this.listeners.set(type, list); }
    removeEventListener(type, handler) { this.listeners.set(type, (this.listeners.get(type) ?? []).filter((h) => h !== handler)); }
    dispatch(type, props = {}) {
      const event = { target: this, preventDefault() { this.prevented = true; }, ...props };
      for (let node = this; node; node = node.parentNode) (node.listeners.get(type) ?? []).forEach((handler) => handler(event));
      return event;
    }
    click() { if (!this.disabled) this.dispatch('click'); }
    scrollIntoView(options) { scrolls.push({ element: this, options }); }
  }
  const node = (tag, attrs, text) => { const n = new Element(tag, attrs); if (text) n.textContent = text; return n; };
  doc = new Element('document'); const page = node('main'); doc.append(page);
  const pending = [], scrolls = [], tweens = [], hooks = [], syncs = [], errors = [];
  let completionInits = 0;
  const animate = (panel, options) => { tweens.push(options); options.onComplete?.(); };
  const context = vm.createContext({
    document: doc,
    window: { matchMedia: (query) => ({ matches: query.includes('reduced-motion') ? reduced : mobile }) },
    AbortController,
    fetch: (url, options) => new Promise((resolve, reject) => pending.push({ url, options, resolve, reject })),
    console: { error: (...args) => errors.push(args) },
    initMarkComplete: () => { completionInits++; },
    syncCompletionButtons: (root) => syncs.push(root),
    gsap: { killTweensOf() {}, fromTo: (panel, from, to) => animate(panel, to), to: animate },
    barba: { hooks: { beforeLeave: (callback) => hooks.push(callback) } },
    MOBILE_COURSE_INDEX_QUERY, shouldScrollToCourseTopics,
  });
  vm.runInContext(source, context, { filename: 'course-index.js' });
  function family(key) {
    const root = node('section', legacy ? {} : { 'data-course-family': key });
    const heading = node('h2', { id: legacy ? 'course-topics-heading' : `heading-${key}`, 'data-course-topics-heading': '' }, 'Temas');
    const panel = node('div', { id: legacy ? 'indice-ajax-container' : `topics-${key}`, ...(legacy ? {} : { 'data-course-topics': '' }) });
    root.append(heading, panel); page.append(root);
    return { root, heading, panel, series: [] };
  }
  function series(family, id, title = 'Serie de prueba') {
    const item = node('div', { class: 'serie-accordion-item' });
    const toggle = node('button', { class: 'serie-accordion-toggle', 'aria-controls': `blocks-${id}` });
    toggle.append(node('span', { 'data-series-title': '' }, title), node('span', { class: 'serie-accordion-icon' }, '+'));
    const panel = node('div', { id: `blocks-${id}` });
    const buttons = [1, 2].map((n) => node('button', { class: 'serie-cde-button', 'data-post-id': `${id}${n}`, role: 'tab', 'aria-selected': 'false' }));
    panel.append(...buttons); item.append(toggle, panel); family.root.append(item);
    const result = { item, toggle, panel, buttons }; family.series.push(result); return result;
  }
  function nested(container, tag = 'button') {
    const item = node('li', { class: 'course-index-item' });
    const toggle = node(tag, { 'data-toggle-children': 'true' }); toggle.append(node('span', { class: 'course-index-chevron' }, '-'));
    const panel = node('div', { 'data-children-wrapper': '' }); item.append(toggle, panel); container.append(item);
    return { toggle, panel };
  }
  const resolve = (index, html = `result-${index}`) => pending[index].resolve({ ok: true, json: async () => ({ html }) });
  return { context, node, doc, page, family, series, nested, pending, resolve, scrolls, tweens, hooks, syncs, errors,
    init: () => context.initCourseIndex(), completionInits: () => completionInits };
}
const flush = () => new Promise((resolve) => setImmediate(resolve));

test('cada familia conserva acordeón, panel, selección y etiqueta independientes; init idempotente', async () => {
  const h = harness(); const a = h.family('esencial'), b = h.family('principal');
  const a1 = h.series(a, 10), a2 = h.series(a, 20), b1 = h.series(b, 30);
  h.init(); h.init();
  assert.equal(h.hooks.length, 1);
  a1.toggle.click(); b1.toggle.click(); a2.toggle.click();
  assert.equal(a1.toggle.getAttribute('aria-expanded'), 'false');
  assert.equal(a2.toggle.getAttribute('aria-expanded'), 'true');
  assert.equal(b1.toggle.getAttribute('aria-expanded'), 'true');
  a2.toggle.click(); assert.equal(a2.toggle.getAttribute('aria-expanded'), 'false');
  a1.buttons[0].click(); b1.buttons[0].click();
  assert.equal(h.pending.length, 2);
  assert.equal(h.pending[0].options.signal.aborted, false, 'la familia B no cancela a la A');
  h.resolve(1, 'familia B'); h.resolve(0, 'familia A'); await flush();
  assert.equal(a.panel.innerHTML, 'familia A'); assert.equal(b.panel.innerHTML, 'familia B');
  assert.equal(a1.buttons[0].getAttribute('aria-current'), 'true');
  assert.equal(b1.buttons[0].getAttribute('aria-current'), 'true');
  assert.equal(a.panel.getAttribute('aria-labelledby'), a.heading.id);
  assert.equal(b.panel.getAttribute('aria-labelledby'), b.heading.id);
  assert.equal(a.panel.getAttribute('role'), 'region');
  assert.equal(a.panel.getAttribute('aria-busy'), 'false');
  assert.equal(a1.buttons[0].getAttribute('aria-controls'), a.panel.id);
  assert.equal(a1.buttons[0].getAttribute('role'), null);
  assert.equal(a1.buttons[0].getAttribute('aria-selected'), null);
  assert.equal(h.syncs.length, 2);
  a1.buttons[0].click(); assert.equal(h.pending.length, 2, 'un bloque ya cargado no repite GET');
});

test('aborta selección anterior e ignora incluso respuestas tardías que no respetan abort', async () => {
  const h = harness(); const a = h.family('esencial'); const s = h.series(a, 10);
  h.init(); s.buttons[0].click(); s.buttons[1].click();
  assert.equal(h.pending[0].options.signal.aborted, true);
  assert.equal(s.buttons[0].disabled, false);
  h.resolve(0, 'viejo'); await flush();
  assert.equal(a.panel.innerHTML, '<p>Cargando...</p>');
  assert.equal(a.panel.getAttribute('aria-busy'), 'true');
  assert.equal(s.buttons[1].disabled, true);
  h.resolve(1, 'nuevo'); await flush();
  assert.equal(a.panel.innerHTML, 'nuevo'); assert.equal(h.syncs.length, 1);
});

test('A→B→A: una respuesta antigua del mismo botón no libera la nueva petición', async () => {
  const h = harness(); const a = h.family('esencial'); const s = h.series(a, 10);
  h.init(); s.buttons[0].click(); s.buttons[1].click(); s.buttons[0].click();
  h.resolve(0, 'A antiguo'); await flush();
  assert.equal(a.panel.getAttribute('aria-busy'), 'true');
  assert.equal(s.buttons[0].disabled, true);
  assert.equal(a.panel.innerHTML, '<p>Cargando...</p>');
  h.resolve(2, 'A actual'); await flush();
  h.resolve(1, 'B antiguo'); await flush();
  assert.equal(a.panel.innerHTML, 'A actual');
  assert.equal(h.syncs.length, 1);
});

test('JSON tardío y errores viejos tampoco sobreescriben selección posterior', async () => {
  const h = harness(); const a = h.family('esencial'); const s = h.series(a, 10);
  h.init(); s.buttons[0].click();
  let json;
  h.pending[0].resolve({ ok: true, json: () => new Promise((resolve) => { json = resolve; }) }); await flush();
  s.buttons[1].click(); h.resolve(1, 'nuevo'); await flush();
  json({ html: 'viejo' }); await flush();
  assert.equal(a.panel.innerHTML, 'nuevo');
  s.buttons[0].click(); s.buttons[1].click();
  h.pending[2].reject(new Error('Error viejo')); await flush();
  assert.equal(a.panel.getAttribute('aria-busy'), 'true'); assert.equal(h.errors.length, 0);
  h.resolve(3, 'último'); await flush(); assert.equal(a.panel.innerHTML, 'último');
});

for (const failure of ['http', 'json', 'shape', 'network']) {
  test(`error ${failure}: libera estado y permite reintentar el mismo bloque`, async () => {
    const h = harness(); const a = h.family('esencial'); const s = h.series(a, 10);
    h.init(); s.buttons[0].click();
    if (failure === 'network') h.pending[0].reject(new Error('offline'));
    else h.pending[0].resolve({ ok: failure !== 'http', json: async () => {
      if (failure === 'json') throw new Error('JSON inválido');
      return { html: null };
    } });
    await flush();
    assert.match(a.panel.innerHTML, /reintentar/);
    assert.equal(s.buttons[0].getAttribute('aria-current'), null);
    assert.equal(s.buttons[0].disabled, false);
    assert.equal(a.panel.getAttribute('aria-busy'), 'false');
    s.buttons[0].click(); assert.equal(h.pending.length, 2);
    h.resolve(1, 'recuperado'); await flush(); assert.equal(a.panel.innerHTML, 'recuperado');
  });
}

test('desmontaje Barba cancela, retira listeners y no muta ni desplaza desde respuestas viejas', async () => {
  const h = harness({ mobile: true }); const a = h.family('esencial'); const s = h.series(a, 10);
  h.init(); s.buttons[0].click(); h.hooks[0]({ current: { container: h.page } });
  assert.equal(h.pending[0].options.signal.aborted, true);
  const html = a.panel.innerHTML;
  h.resolve(0, 'respuesta fuera de página'); await flush();
  assert.equal(a.panel.innerHTML, html); assert.equal(h.scrolls.length, 0);
  s.buttons[1].click(); assert.equal(h.pending.length, 1);
  h.init(); s.buttons[1].click(); assert.equal(h.pending.length, 2);
  assert.equal(h.hooks.length, 1);
});

test('una región desconectada no se actualiza aunque no haya hook de salida', async () => {
  const h = harness(); const a = h.family('esencial'); const s = h.series(a, 10);
  h.init(); s.buttons[0].click(); a.root.parentNode = null;
  h.resolve(0, 'tardío'); await flush();
  assert.equal(h.syncs.length, 0); assert.equal(a.panel.innerHTML, '<p>Cargando...</p>');
});

test('anidados conservan estado e inert; el teclado nativo no ejecuta un doble toggle', async () => {
  const h = harness(); const a = h.family('esencial'); const s = h.series(a, 10);
  const nested = h.nested(a.panel); h.init();
  assert.equal(nested.toggle.getAttribute('aria-expanded'), 'true');
  nested.toggle.dispatch('keydown', { key: 'Enter' });
  assert.equal(nested.toggle.getAttribute('aria-expanded'), 'true');
  nested.toggle.click(); assert.equal(nested.toggle.getAttribute('aria-expanded'), 'false');
  assert.equal(nested.panel.inert, true);
  nested.toggle.click(); assert.equal(nested.panel.inert, false);
  let ajaxNested;
  a.panel.onHTML = (html) => { if (html === 'árbol AJAX') ajaxNested = h.nested(a.panel); };
  s.buttons[0].click(); h.resolve(0, 'árbol AJAX'); await flush();
  ajaxNested.toggle.click(); assert.equal(ajaxNested.toggle.getAttribute('aria-expanded'), 'false');
});

test('compatibilidad de anidados no nativos con Enter/Espacio', () => {
  const h = harness(); const a = h.family('esencial'); h.series(a, 10);
  const nested = h.nested(a.panel, 'div'); h.init();
  assert.equal(nested.toggle.dispatch('keydown', { key: ' ' }).prevented, true);
  assert.equal(nested.toggle.getAttribute('aria-expanded'), 'false');
  nested.toggle.dispatch('keydown', { key: 'Enter' });
  assert.equal(nested.toggle.getAttribute('aria-expanded'), 'true');
});

for (const reduced of [false, true]) {
  test(`scroll al heading de su familia en móvil y reduced-motion=${reduced}`, async () => {
    const h = harness({ mobile: true, reduced }); const a = h.family('esencial'); const s = h.series(a, 10, 'Ra & la Ley del Uno');
    h.init(); s.toggle.click();
    assert.equal(h.tweens.length, reduced ? 0 : 1);
    s.buttons[0].click(); assert.match(h.pending[0].url, /serie_name=Ra%20%26%20la%20Ley%20del%20Uno$/);
    h.resolve(0); await flush();
    assert.equal(h.scrolls[0].element, a.heading);
    assert.equal(h.scrolls[0].options.behavior, reduced ? 'auto' : 'smooth');
  });
}

test('no desplaza escritorio; fallback legacy conserva endpoint y región', async () => {
  const h = harness({ legacy: true }); const a = h.family('legacy'); const s = h.series(a, 10);
  h.init(); s.buttons[0].click();
  assert.match(h.pending[0].url, /^\/espaciosutil\/v1\/indice-revelador\/101\?/);
  assert.equal(h.pending[0].options.method, undefined, 'GET por defecto, sin nueva escritura');
  h.resolve(0); await flush(); assert.equal(h.scrolls.length, 0);
  assert.equal(a.panel.getAttribute('aria-labelledby'), 'course-topics-heading');
});

test('no resuelve aria-controls fuera de su familia y no consulta IDs de bloque inválidos', () => {
  const h = harness(); const a = h.family('esencial'), b = h.family('principal');
  const a1 = h.series(a, 10), b1 = h.series(b, 20);
  a1.toggle.setAttribute('aria-controls', b1.panel.id); a1.buttons[0].dataset.postId = '../malformed';
  h.init(); a1.toggle.click();
  assert.equal(b1.toggle.getAttribute('aria-expanded'), 'false');
  a1.buttons[0].click(); assert.equal(h.pending.length, 0);
});
