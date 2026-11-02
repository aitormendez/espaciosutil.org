import test from 'node:test';
import assert from 'node:assert/strict';
import { resolveSectionColor } from './sectionColor.js';

test('family and fallback CSS tokens resolve before GSAP interpolation', () => {
  for (const token of ['--color-serie-esencial', '--color-serie-principal', '--color-serie-espiritual', '--color-serie-complementaria', '--color-cde']) {
    assert.equal(resolveSectionColor(`var(${token})`, (name) => {
      assert.equal(name, token);
      return ' rgb(1, 2, 3) ';
    }), 'rgb(1, 2, 3)');
  }
});

test('existing menu colors are unchanged', () => {
  const unexpected = () => assert.fail('literal color must not read a token');
  for (const color of ['#123456', 'rgb(1, 2, 3)', 'transparent']) {
    assert.equal(resolveSectionColor(color, unexpected), color);
  }
  assert.equal(resolveSectionColor(null, unexpected), '');
  assert.equal(resolveSectionColor('var(--missing)', () => ''), '');
});
