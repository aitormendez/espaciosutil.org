import test from 'node:test';
import assert from 'node:assert/strict';

import { getCosmosData, resetCosmosDataCache } from './getCosmosData.js';

const responses = new Map([
  ['/wp-json/wp/v2/planeta?slug=satelite-de-terapia', [{ id: 41 }]],
  ['/wp-json/wp/v2/planeta?slug=satelite-de-formacion', [{ id: 40 }]],
  ['/wp-json/wp/v2/revelador/', [{ id: 1, name: 'Uno' }]],
  ['/wp-json/wp/v2/noticia/', [{ id: 2, title: { rendered: 'Dos' } }]],
  ['/wp-json/wp/v2/pages?planeta=41', [{ id: 3, title: { rendered: 'Tres' } }]],
  ['/wp-json/wp/v2/pages?planeta=40', [{ id: 4, title: { rendered: 'Cuatro' } }]],
]);

test.beforeEach(() => {
  resetCosmosDataCache();
});

test.afterEach(() => {
  delete global.fetch;
});

test('getCosmosData agrega todas las colecciones con una sola tanda de peticiones', async () => {
  const calls = [];

  global.fetch = async (url) => {
    calls.push(url);

    return {
      ok: true,
      async json() {
        return responses.get(url) ?? [];
      },
    };
  };

  const result = await getCosmosData();

  assert.deepEqual(result, {
    terapias: [{ id: 3, title: { rendered: 'Tres' } }],
    formaciones: [{ id: 4, title: { rendered: 'Cuatro' } }],
    reveladores: [{ id: 1, name: 'Uno' }],
    noticias: [{ id: 2, title: { rendered: 'Dos' } }],
  });
  assert.equal(calls.length, 6);
});

test('getCosmosData reutiliza la misma promesa para evitar cargas duplicadas', async () => {
  let requestCount = 0;

  global.fetch = async (url) => {
    requestCount += 1;

    return {
      ok: true,
      async json() {
        return responses.get(url) ?? [];
      },
    };
  };

  const [first, second] = await Promise.all([getCosmosData(), getCosmosData()]);

  assert.deepEqual(first, second);
  assert.equal(requestCount, 6);
});
