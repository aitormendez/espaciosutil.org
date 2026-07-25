import assert from 'node:assert/strict';
import test from 'node:test';

import {
  AUDIO_DESKTOP_MEDIA_QUERY,
  resolveFeaturedAudioLayout,
} from './featuredAudioLayout.js';

test('usa el layout móvil por debajo de md', () => {
  assert.equal(AUDIO_DESKTOP_MEDIA_QUERY, '(min-width: 768px)');
  assert.equal(resolveFeaturedAudioLayout(false), 'mobile');
});

test('conserva el layout de escritorio desde md', () => {
  assert.equal(resolveFeaturedAudioLayout(true), 'desktop');
});
