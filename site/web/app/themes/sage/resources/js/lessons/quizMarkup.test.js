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

test('permite que las preguntas móviles crezcan en el scroll de página', () => {
  const questionStateRule = getRuleBody(
    "#lesson-quiz[data-quiz-state='questions']"
  );
  const targetRule = getRuleBody(
    "#lesson-quiz[data-quiz-state='questions'] [data-quiz-target]"
  );
  const optionsRule = getRuleBody(
    "#lesson-quiz[data-quiz-state='questions'] .quiz-options"
  );

  assert.doesNotMatch(questionStateRule, /height:\s*min\(42rem,\s*100svh\)/);
  assert.match(targetRule, /overflow:\s*visible/);
  assert.doesNotMatch(targetRule, /flex:\s*1 1 auto/);
  assert.doesNotMatch(optionsRule, /overflow-y:\s*auto/);
  assert.doesNotMatch(optionsRule, /overscroll-behavior:\s*contain/);
});

test('mantiene la botonera móvil pegada al borde inferior', () => {
  const footerRule = getRuleBody(
    "#lesson-quiz[data-quiz-state='questions'] .quiz-footer"
  );

  assert.match(footerRule, /position:\s*sticky/);
  assert.match(footerRule, /bottom:\s*0/);
  assert.match(footerRule, /z-index:\s*1/);
});
