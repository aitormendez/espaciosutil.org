import assert from 'node:assert/strict';
import test from 'node:test';

import {
  MOBILE_COURSE_INDEX_QUERY,
  shouldScrollToCourseTopics,
} from './courseIndexScroll.js';

test('autoriza el scroll cuando hay encabezado en móvil', () => {
  assert.equal(MOBILE_COURSE_INDEX_QUERY, '(max-width: 767px)');
  assert.equal(shouldScrollToCourseTopics(true, true), true);
});

test('evita el scroll sin encabezado o fuera de móvil', () => {
  assert.equal(shouldScrollToCourseTopics(true, false), false);
  assert.equal(shouldScrollToCourseTopics(false, true), false);
});
