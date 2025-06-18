export async function getNoticias() {
  // URL de la API de WordPress
  const apiUrl = '/wp-json/wp/v2/noticia/';

  try {
    const response = await fetch(apiUrl);

    if (response.status === 200) {
      const data = await response.json();
      const terminosArray = data.map((termino) => termino);
      return terminosArray;
    } else {
      throw new Error('Error al obtener los posts del tipo noticia.');
    }
  } catch (error) {
    console.error(error);
    throw error; // Propaga el error para que el llamante pueda manejarlo
  }
}
