import assert from 'node:assert/strict';
import test from 'node:test';

import { getSubindexToggleState, isSubindexAction } from './subindex.js';

test('describe el estado visual abierto del panel', () => {
  assert.deepEqual(getSubindexToggleState(true, 'Índice', 'Ocultar índice'), {
    expanded: 'true',
    label: 'Ocultar índice',
    icon: '−',
    panelClasses: ['grid-rows-[1fr]', 'opacity-100', 'mt-4'],
  });
});

test('identifica los enlaces y marcas de tiempo como acciones que cierran el índice', () => {
  const actionTarget = {
    closest(selector) {
      return selector === '[data-video-seek], a[href^="#"]' ? {} : null;
    },
  };

  assert.equal(isSubindexAction(actionTarget), true);
});

test('no cierra el índice al pulsar contenido que no es una acción', () => {
  const passiveTarget = {
    closest() {
      return null;
    },
  };

  assert.equal(isSubindexAction(passiveTarget), false);
});
