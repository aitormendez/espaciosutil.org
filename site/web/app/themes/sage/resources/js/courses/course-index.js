const buttons = document.querySelectorAll('.revelador-button');
const container = document.getElementById('indice-ajax-container');

console.log('coures-index.js loaded');

buttons.forEach((button) => {
  button.addEventListener('click', async () => {
    const postId = button.dataset.postId;
    const reveladorName = encodeURIComponent(button.textContent.trim());
    container.innerHTML = '<p>Cargando...</p>';

    try {
      const response = await fetch(
        `/espaciosutil/v1/indice-revelador/${postId}?revelador_name=${reveladorName}`
      );
      if (!response.ok) {
        throw new Error('Network response was not ok.');
      }
      const data = await response.json();
      container.innerHTML = data.html;
    } catch (error) {
      container.innerHTML = '<p>Ha ocurrido un error al cargar el índice.</p>';
      console.error('Error fetching course index:', error);
    }
  });
});
