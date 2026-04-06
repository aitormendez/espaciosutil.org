import test from 'node:test';
import assert from 'node:assert/strict';

import { textureMap } from './textureMap.js';

test('las texturas del cosmos se resuelven como assets del build y usan webp', () => {
  assert.match(textureMap.venus, /\.webp$/);
  assert.match(textureMap.haumea, /\.webp$/);
  assert.match(textureMap.mars, /\.webp$/);
  assert.match(textureMap.neptune, /\.webp$/);
  assert.match(textureMap.jupiter, /\.webp$/);
});
