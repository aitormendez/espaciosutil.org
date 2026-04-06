import test from 'node:test';
import assert from 'node:assert/strict';

import {
  buildPlanetConfigs,
  getSatelliteMotionConfig,
} from './cosmosConfig.js';

test('buildPlanetConfigs genera el mismo sistema para una misma semilla', () => {
  const first = buildPlanetConfigs('semilla-cosmos');
  const second = buildPlanetConfigs('semilla-cosmos');

  assert.deepEqual(first, second);
});

test('buildPlanetConfigs cambia el paisaje orbital cuando cambia la semilla', () => {
  const first = buildPlanetConfigs('semilla-a');
  const second = buildPlanetConfigs('semilla-b');

  assert.notDeepEqual(
    first.map(({ name, initialPos, inclination, speed }) => ({
      name,
      initialPos,
      inclination,
      speed,
    })),
    second.map(({ name, initialPos, inclination, speed }) => ({
      name,
      initialPos,
      inclination,
      speed,
    }))
  );
});

test('las velocidades planetarias generadas son evocadoras y claramente visibles', () => {
  const planets = buildPlanetConfigs('semilla-rapida');

  planets.forEach((planet) => {
    assert.ok(planet.speed >= 90, `${planet.name} demasiado lento`);
    assert.ok(planet.speed <= 240, `${planet.name} demasiado rapido`);
  });
});

test('getSatelliteMotionConfig usa la semilla de sesion para variar satelites sin perder estabilidad', () => {
  const stable = getSatelliteMotionConfig(
    { id: 77 },
    2,
    'sesion-uno',
    'planetNoticias'
  );
  const repeated = getSatelliteMotionConfig(
    { id: 77 },
    2,
    'sesion-uno',
    'planetNoticias'
  );
  const changed = getSatelliteMotionConfig(
    { id: 77 },
    2,
    'sesion-dos',
    'planetNoticias'
  );

  assert.deepEqual(stable, repeated);
  assert.notDeepEqual(stable, changed);
  assert.ok(stable.speed >= 140);
  assert.ok(stable.speed <= 280);
});
