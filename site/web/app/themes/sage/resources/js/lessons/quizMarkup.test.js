import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const quizTemplate = readFileSync(
  new URL(
    '../../views/partials/content-single-cde.blade.php',
    import.meta.url
  ),
  'utf8'
);
const quizStyles = readFileSync(
  new URL('../../css/layouts/quiz.css', import.meta.url),
  'utf8'
);

const getRuleBody = (selector) => {
  const escapedSelector = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return quizStyles.match(new RegExp(`${escapedSelector}\\s*{([^}]*)}`))?.[1] || '';
};

test('separa la llamada a validar de móvil sin cambiar el texto de escritorio', () => {
  assert.match(
    quizTemplate,
    /quiz-validate-mobile[^>]*>\s*Validar y continuar\s*</
  );
  assert.match(
    quizTemplate,
    /quiz-validate-desktop[^>]*>\s*Validar y pasar a la siguiente\s*</
  );
});

test('garantiza un área táctil mínima en cada respuesta móvil', () => {
  const optionRule = getRuleBody(
    "#lesson-quiz[data-quiz-state='questions'] .quiz-option"
  );

  assert.match(optionRule, /min-height:\s*44px/);
});

test('asigna a las respuestas el espacio móvil restante', () => {
  const optionsRule = getRuleBody(
    "#lesson-quiz[data-quiz-state='questions'] .quiz-options"
  );

  assert.match(optionsRule, /flex:\s*1 1 auto/);
});
