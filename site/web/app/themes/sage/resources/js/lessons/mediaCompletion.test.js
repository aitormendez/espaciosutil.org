import assert from 'node:assert/strict';
import test from 'node:test';
import { lessonCompletionTime, observeLessonCompletion } from './mediaCompletion.js';

const tick = () => new Promise((resolve) => setImmediate(resolve));
function setup({ duration = 600, complete = async () => true, canComplete = () => true } = {}) {
  const player = new EventTarget();
  Object.assign(player, { duration, currentTime: 0, paused: false, seeking: false });
  let calls = 0;
  let time = 0;
  const dispose = observeLessonCompletion(player, {
    canComplete,
    complete: () => { calls++; return complete(); },
    now: () => time,
  });
  const emit = (name, position = player.currentTime) => {
    player.currentTime = position;
    player.dispatchEvent(new Event(name));
  };
  return { player, emit, dispose, calls: () => calls, advance: (ms) => { time += ms; } };
}

test('completa al 95 % del contenido, antes del outro de 36 segundos', () => {
  assert.equal(lessonCompletionTime(600), 535.8);
  assert.ok(Math.abs(lessonCompletionTime(3600) - 3385.8) < 0.000001);
  assert.ok(Math.abs(lessonCompletionTime(60) - 22.8) < 0.000001);
  assert.equal(lessonCompletionTime(30), 28.5);
  for (const invalid of [0, -1, NaN, Infinity, undefined]) {
    assert.equal(lessonCompletionTime(invalid), null);
  }
});

test('vídeo/audio completan una sola vez tras avanzar por el umbral', async () => {
  const s = setup();
  s.emit('playing', 530);
  s.emit('time-update', 535);
  assert.equal(s.calls(), 0);
  s.emit('time-update', 536);
  await tick();
  s.emit('time-update', 540);
  s.emit('time-update', 550);
  assert.equal(s.calls(), 1);
  s.dispose();
});

test('restaurar posición, pausar o arrastrar la barra no completa', async () => {
  const s = setup();
  s.emit('time-update', 550);
  s.emit('playing', 550);
  s.emit('seeking');
  s.player.seeking = true;
  s.emit('time-update', 580);
  s.player.seeking = false;
  s.player.paused = true;
  s.emit('seeked');
  s.emit('time-update', 581);
  assert.equal(s.calls(), 0);
  s.player.paused = false;
  s.emit('playing');
  s.emit('time-update', 582);
  await tick();
  assert.equal(s.calls(), 1);
  s.dispose();
});

test('respeta estado visto o decisión manual y retira eventos al desmontar', async () => {
  const s = setup({ canComplete: () => false });
  s.emit('playing', 530);
  s.emit('time-update', 540);
  await tick();
  assert.equal(s.calls(), 0);
  s.dispose();
  s.emit('playing', 550);
  s.emit('time-update', 560);
  assert.equal(s.calls(), 0);
});

test('evita peticiones duplicadas y espera 30 segundos antes de reintentar un fallo', async () => {
  let resolve;
  const s = setup({ complete: () => new Promise((done) => { resolve = done; }) });
  s.emit('playing', 530);
  s.emit('time-update', 536);
  s.emit('time-update', 537);
  assert.equal(s.calls(), 1);
  resolve(false);
  await tick();
  s.emit('time-update', 540);
  assert.equal(s.calls(), 1);
  s.advance(30000);
  s.emit('time-update', 550);
  assert.equal(s.calls(), 2);
  resolve(true);
  await tick();
  s.dispose();
});
