import test from 'node:test';
import assert from 'node:assert/strict';

import { getCosmosQualityProfile } from './qualityProfile.js';

test('usa un perfil alto en equipos de escritorio capaces', () => {
  const profile = getCosmosQualityProfile({
    devicePixelRatio: 2,
    hardwareConcurrency: 8,
    prefersReducedMotion: false,
    maxTouchPoints: 0,
  });

  assert.equal(profile.enableEffects, true);
  assert.deepEqual(profile.dpr, [1, 1.5]);
  assert.equal(profile.multisampling, 4);
});

test('reduce calidad y desactiva efectos en dispositivos modestos o con reduced motion', () => {
  const profile = getCosmosQualityProfile({
    devicePixelRatio: 3,
    hardwareConcurrency: 2,
    prefersReducedMotion: true,
    maxTouchPoints: 5,
  });

  assert.equal(profile.enableEffects, false);
  assert.deepEqual(profile.dpr, [1, 1.1]);
  assert.equal(profile.multisampling, 0);
});
