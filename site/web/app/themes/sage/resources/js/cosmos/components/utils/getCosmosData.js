async function fetchJson(url) {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`Error al obtener ${url}.`);
  }

  return response.json();
}

async function getPagesByPlanetSlug(termSlug) {
  const terms = await fetchJson(`/wp-json/wp/v2/planeta?slug=${termSlug}`);

  if (!terms.length) {
    return [];
  }

  return fetchJson(`/wp-json/wp/v2/pages?planeta=${terms[0].id}`);
}

let cosmosDataPromise;

export function getCosmosData() {
  if (!cosmosDataPromise) {
    cosmosDataPromise = Promise.all([
      getPagesByPlanetSlug('satelite-de-terapia'),
      getPagesByPlanetSlug('satelite-de-formacion'),
      fetchJson('/wp-json/wp/v2/revelador/'),
      fetchJson('/wp-json/wp/v2/noticia/'),
    ]).then(([terapias, formaciones, reveladores, noticias]) => ({
      terapias,
      formaciones,
      reveladores,
      noticias,
    }));
  }

  return cosmosDataPromise;
}

export function resetCosmosDataCache() {
  cosmosDataPromise = undefined;
}
