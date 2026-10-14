import assert from 'node:assert/strict';
import test from 'node:test';

function element(id, completed = false) {
  const classes = new Set();
  const children = new Map();
  return {
    dataset: { postId: String(id), completed: String(completed), lessonTitle: 'Lección de prueba' },
    attributes: {}, disabled: false,
    classList: { toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); }, contains: (name) => classes.has(name) },
    setAttribute(name, value) { this.attributes[name] = value; },
    querySelector(name) { if (!children.has(name)) children.set(name, element()); return children.get(name); },
    closest() { return this; },
  };
}

async function fixture() {
  const buttons = [element(7), element(7)];
  const listeners = [];
  const body = [];
  globalThis.document = {
    querySelectorAll: () => buttons,
    addEventListener: (type, fn) => listeners.push(fn),
    createElement: () => element(),
    getElementById: (id) => body.find((el) => el.id === id),
    body: { append: (el) => body.push(el) },
  };
  globalThis.window = { wpApiSettings: { root: '/wp-json/', nonce: 'test-nonce' } };
  const module = await import(`./mark-complete.js?test=${Math.random()}`);
  return { buttons, listeners, body, module };
}
const reply = (completed) => ({ ok: true, json: async () => ({ success: true, completed_lessons: completed }) });
const tick = () => new Promise((resolve) => setImmediate(resolve));

test('el botón alterna, sincroniza todas las apariciones y respeta el desmarcado manual', async () => {
  const { buttons, module } = await fixture();
  const actions = [];
  globalThis.fetch = async (url, options) => {
    assert.equal(url, '/wp-json/cde/v1/complete/');
    assert.equal(options.headers['X-WP-Nonce'], 'test-nonce');
    const body = JSON.parse(options.body);
    actions.push(body.action);
    return reply(body.action === 'complete' ? [7] : { 1: 22 });
  };
  module.default();
  assert.equal(await module.setLessonCompleted(7, true), true);
  for (const button of buttons) {
    assert.equal(button.attributes['aria-pressed'], 'true');
    assert.equal(button.querySelector('.btn-text').textContent, 'Vista');
  }
  assert.equal(await module.setLessonCompleted(7, false), true);
  assert.equal(buttons[0].dataset.completed, 'false');
  assert.equal(module.canAutoCompleteLesson(7), false);
  assert.equal(await module.setLessonCompleted(7, true, { automatic: true }), false);
  assert.deepEqual(actions, ['complete', 'uncomplete']);
});

test('doble inicialización y filas AJAX tienen un solo manejador', async () => {
  const { buttons, listeners, module } = await fixture();
  let calls = 0;
  globalThis.fetch = async () => { calls++; return reply([9]); };
  module.default();
  module.default();
  assert.equal(listeners.length, 1);
  const ajaxButton = element(9);
  buttons.push(ajaxButton);
  listeners[0]({ target: ajaxButton, preventDefault() {} });
  await tick();
  assert.equal(calls, 1);
  assert.equal(ajaxButton.attributes['aria-pressed'], 'true');
  const restoredButton = element(9);
  buttons.push(restoredButton);
  module.syncCompletionButtons();
  assert.equal(restoredButton.dataset.completed, 'true');
});

test('bloquea dobles clics y serializa escrituras de distintas lecciones', async () => {
  const { buttons, module } = await fixture();
  const resolvers = [];
  globalThis.fetch = () => new Promise((resolve) => resolvers.push(resolve));
  const first = module.setLessonCompleted(7, true);
  const duplicate = module.setLessonCompleted(7, true);
  const next = module.setLessonCompleted(8, true);
  assert.equal(buttons[0].disabled, true);
  await tick();
  assert.equal(resolvers.length, 1);
  resolvers[0](reply([7]));
  await first;
  await duplicate;
  await tick();
  assert.equal(resolvers.length, 2);
  resolvers[1](reply([7, 8]));
  assert.equal(await next, true);
  assert.equal(buttons[0].disabled, false);
});

test('un error mantiene el estado anterior y permite reintentar', async (t) => {
  const { buttons, body, module } = await fixture();
  t.mock.method(console, 'error', () => {});
  for (const response of [
    { ok: false },
    { ok: true, json: async () => { throw new Error('JSON inválido'); } },
    reply([]),
  ]) {
    globalThis.fetch = async () => response;
    assert.equal(await module.setLessonCompleted(7, true), false);
    assert.equal(buttons[0].dataset.completed, 'false');
    assert.equal(buttons[0].disabled, false);
    assert.match(body[0].textContent, /No se pudo guardar/);
  }
  globalThis.fetch = async () => reply([7]);
  assert.equal(await module.setLessonCompleted(7, true), true);
});

test('sin nonce o id válido no envía progreso', async () => {
  const { module } = await fixture();
  globalThis.fetch = async () => { throw new Error('No debe llamar'); };
  assert.equal(await module.setLessonCompleted(0, true), false);
  delete window.wpApiSettings.nonce;
  assert.equal(await module.setLessonCompleted(7, true), false);
});

test('recupera el foco tras guardar sin quitárselo a otro control', async () => {
  const { buttons, module } = await fixture();
  const button = buttons[0];
  button.isConnected = true;
  button.focus = () => { document.activeElement = button; };
  let disabled = false;
  Object.defineProperty(button, 'disabled', {
    get: () => disabled,
    set(value) {
      disabled = value;
      if (value && document.activeElement === button) document.activeElement = document.body;
    },
  });
  document.activeElement = button;
  globalThis.fetch = async () => reply([7]);
  await module.setLessonCompleted(7, true);
  assert.equal(document.activeElement, button);

  let resolve;
  globalThis.fetch = () => new Promise((done) => { resolve = done; });
  const request = module.setLessonCompleted(7, false);
  await tick();
  const otherControl = {};
  document.activeElement = otherControl;
  resolve(reply([]));
  await request;
  assert.equal(document.activeElement, otherControl);
});

test('vuelve a marcar tras desmarcar y reproducir otra vez, sin recargar la lección', async () => {
  const { module } = await fixture();
  const { observeLessonCompletion } = await import('../lessons/mediaCompletion.js');
  const actions = [];
  globalThis.fetch = async (url, options) => {
    const { action } = JSON.parse(options.body);
    actions.push(action);
    return reply(action === 'complete' ? [7] : []);
  };
  module.default();
  const player = new EventTarget();
  Object.assign(player, { duration: 600, currentTime: 0, paused: false, seeking: false });
  const dispose = observeLessonCompletion(player, {
    canComplete: () => module.canAutoCompleteLesson(7),
    complete: () => module.setLessonCompleted(7, true, { automatic: true }),
    onReplay: () => module.resetLessonAutoCompletion(7),
  });
  const emit = (type, time = player.currentTime) => {
    player.currentTime = time;
    player.dispatchEvent(new Event(type));
  };
  emit('play');
  emit('playing', 530);
  emit('time-update', 536);
  await tick();
  assert.deepEqual(actions, ['complete']);
  await module.setLessonCompleted(7, false);
  emit('time-update', 540);
  emit('playing'); // Recuperación de buffering: no representa otra reproducción.
  emit('time-update', 541);
  await tick();
  assert.deepEqual(actions, ['complete', 'uncomplete']);

  // Retroceder mientras se reproduce permite volver a cruzar el umbral.
  emit('seeking');
  emit('seeked', 10);
  emit('time-update', 530);
  emit('time-update', 536);
  await tick();
  assert.deepEqual(actions, ['complete', 'uncomplete', 'complete']);

  // También se habilita tras pausar y volver a reproducir, aunque siga en el tramo final.
  player.paused = true;
  emit('pause');
  await module.setLessonCompleted(7, false);
  emit('seeked', 550);
  emit('time-update', 551);
  assert.equal(actions.length, 4);
  player.paused = false;
  emit('play');
  emit('playing');
  emit('time-update', 552);
  await tick();
  assert.deepEqual(actions, ['complete', 'uncomplete', 'complete', 'uncomplete', 'complete']);
  dispose();
});
