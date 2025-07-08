// se utiliza para obtener la jerarquía de datos de los posts con términos en la taxonomía planeta
// y construir con ellos un subsistema planetario

export async function getSubsistemaPlanetario() {
  const apiUrl = '/wp-json/wp/v2/planeta?parent=0';

  try {
    const response = await fetch(apiUrl);

    if (response.status === 200) {
      const terminosPadre = await response.json();
      const subsistemaPlanetario = [];

      await Promise.all(
        terminosPadre.map(async (terminoPadre) => {
          // Hacer una solicitud para obtener los términos hijos únicos del padre
          const apiUrlHijos = `/wp-json/wp/v2/planeta?parent=${terminoPadre.id}`;
          const responseHijos = await fetch(apiUrlHijos);

          if (responseHijos.status === 200) {
            const terminosHijos = await responseHijos.json();
            // Inicializa un array para almacenar todos los posts asociados a los hijos
            const allHijosPosts = [];

            // Para cada término hijo, buscar los posts asociados
            await Promise.all(
              terminosHijos.map(async (terminoHijo) => {
                const apiUrlPages = `/wp-json/wp/v2/pages?planeta=${terminoHijo.id}`;
                const apiUrlSeries = `/wp-json/wp/v2/serie?planeta=${terminoHijo.id}`;
                const apiUrlNoticias = `/wp-json/wp/v2/noticia?planeta=${terminoHijo.id}`;

                const [responsePages, responseSeries, responseNoticias] = await Promise.all([
                  fetch(apiUrlPages),
                  fetch(apiUrlSeries),
                  fetch(apiUrlNoticias),
                ]);

                if (responsePages.status === 200) {
                  const pages = await responsePages.json();
                  terminoHijo.posts = {pages, series: [], noticias: []};
                  allHijosPosts.push(...pages);
                } else {
                  console.error('Error al obtener las páginas del término hijo:', terminoHijo.name);
                }

                if (responseSeries.status === 200) {
                  const series = await responseSeries.json();
                  terminoHijo.posts.series = series;
                  allHijosPosts.push(...series);
                } else {
                  console.error('Error al obtener las series del término hijo:', terminoHijo.name);
                }

                if (responseNoticias.status === 200) {
                  const noticias = await responseNoticias.json();
                  terminoHijo.posts.noticias = noticias;
                  allHijosPosts.push(...noticias);
                } else {
                  console.error('Error al obtener las noticias del término hijo:', terminoHijo.name);
                }
              }),
            );

            subsistemaPlanetario.push({
              padre: terminoPadre,
              hijos: allHijosPosts, // Cambiamos a los posts
            });
          } else {
            console.error('Error al obtener los términos hijos del término padre:', terminoPadre.name);
          }
        }),
      );
      return subsistemaPlanetario;
    } else {
      throw new Error('Error al obtener los términos de la taxonomía.');
    }
  } catch (error) {
    console.error(error);
    throw error;
  }
}
