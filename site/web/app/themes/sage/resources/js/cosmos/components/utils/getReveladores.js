export async function getReveladores() {
  // URL de la API de WordPress
  const apiUrl = '/wp-json/wp/v2/revelador/';

  try {
    const response = await fetch(apiUrl);

    if (response.status === 200) {
      const data = await response.json();
      const terminosArray = data.map((termino) => termino);
      return terminosArray;
    } else {
      throw new Error('Error al obtener los términos de la taxonomía.');
    }
  } catch (error) {
    console.error(error);
    throw error; // Propaga el error para que el llamante pueda manejarlo
  }
}
