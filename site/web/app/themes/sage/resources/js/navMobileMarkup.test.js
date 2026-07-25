import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const headerTemplate = readFileSync(
  new URL('../views/sections/header.blade.php', import.meta.url),
  'utf8'
);
const navigationScript = readFileSync(new URL('./nav.js', import.meta.url), 'utf8');

test('no duplica las pestañas de membresía en el menú móvil global', () => {
  assert.doesNotMatch(
    headerTemplate,
    /@if \(\$navContextData\['is_pmpro_page'\]\)[\s\S]*?<x-navigation name="membresia_navigation" class="flex xl:hidden"\s*\/>[\s\S]*?@endif/
  );
});

test('inicializa los desplegables móviles solo dentro del menú global', () => {
  assert.match(
    navigationScript,
    /const mobileMenus = gsap\.utils\.toArray\('#nav \.my-menu-item'\);/
  );
});
