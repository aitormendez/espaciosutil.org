export async function getTerapias() {
  return new Promise((resolve, reject) => {
    // Paso 1: Obtener el ID del término "satelite-de-formacion" en la taxonomía "planeta"
    const termSlug = 'satelite-de-terapia';
    const termUrl = `/wp-json/wp/v2/planeta?slug=${termSlug}`;

    fetch(termUrl)
      .then((response) => response.json())
      .then((termData) => {
        if (termData.length > 0) {
          const termId = termData[0].id;

          // Paso 2: Obtener todas las páginas que contienen el término "satelite-de-formacion"
          const pagesUrl = `/wp-json/wp/v2/pages?planeta=${termId}`;

          fetch(pagesUrl)
            .then((response) => response.json())
            .then((pagesData) => {
              const pagesArray = pagesData.map((page) => {
                return page;
              });
              resolve(pagesArray);
            })
            .catch((error) => {
              reject(`Error al obtener las páginas: ${error}`);
            });
        } else {
          reject(`No se encontró el término '${termSlug}' en la taxonomía 'planeta'.`);
        }
      })
      .catch((error) => {
        reject(`Error al obtener el término '${termSlug}': ${error}`);
      });
  });
}
