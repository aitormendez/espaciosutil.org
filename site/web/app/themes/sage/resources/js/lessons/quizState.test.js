import assert from 'node:assert/strict';
import test from 'node:test';

import { getQuizNavigationState } from './quizState.js';

test('desactiva anterior y mantiene validar en la primera pregunta', () => {
  assert.deepEqual(getQuizNavigationState(0, 12), {
    isFirst: true,
    isLast: false,
    previousDisabled: true,
    showValidate: true,
    showSubmit: false,
  });
});

test('reserva finalizar para la última pregunta', () => {
  assert.deepEqual(getQuizNavigationState(11, 12), {
    isFirst: false,
    isLast: true,
    previousDisabled: false,
    showValidate: false,
    showSubmit: true,
  });
});

test('mantiene anterior y validar en una pregunta intermedia', () => {
  assert.deepEqual(getQuizNavigationState(5, 12), {
    isFirst: false,
    isLast: false,
    previousDisabled: false,
    showValidate: true,
    showSubmit: false,
  });
});

test('finaliza directamente un cuestionario de una sola pregunta', () => {
  assert.deepEqual(getQuizNavigationState(0, 1), {
    isFirst: true,
    isLast: true,
    previousDisabled: true,
    showValidate: false,
    showSubmit: true,
  });
});
