import test from 'node:test';
import assert from 'node:assert/strict';

import { Orbiter } from './orbiter.js';

function createPrimary(position = { x: 0, y: 0, z: 0 }) {
  return {
    current: {
      position,
    },
  };
}

test('orbit actualiza el angulo una sola vez y lo escala con delta', () => {
  const orbiter = new Orbiter();
  const primary = createPrimary();

  orbiter.setOrbitParameters(3, 0, 0, 1);

  const result = orbiter.orbit(primary, 10, 0.5);

  assert.ok(Math.abs(result.angle - 0.02) < 1e-9);
});

test('orbit no genera valores no finitos cuando el periodo orbital no es valido', () => {
  const orbiter = new Orbiter();
  const primary = createPrimary();

  orbiter.setOrbitParameters(3, 45, 30, 1);

  const result = orbiter.orbit(primary, null, 0.5);

  assert.equal(result.angle, 0);
  assert.ok(Number.isFinite(result.x));
  assert.ok(Number.isFinite(result.y));
  assert.ok(Number.isFinite(result.z));
});
