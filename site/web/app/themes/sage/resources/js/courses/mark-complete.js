export default function initMarkComplete() {
  const btn = document.getElementById('mark-complete');
  if (btn) {
    btn.addEventListener('click', async () => {
      const postId = btn.dataset.postId;

      // Lee el estado actual ANTES de hacer fetch
      const isCurrentlyCompleted = btn.classList.contains('completed');

      const res = await fetch('/wp-json/cde/v1/complete/', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wpApiSettings.nonce,
        },
        body: JSON.stringify({
          post_id: postId,
          action: isCurrentlyCompleted ? 'uncomplete' : 'complete',
        }),
      });

      if (res.ok) {
        // Ahora actualizas el estado según la acción previa
        if (isCurrentlyCompleted) {
          btn.textContent = 'Marcar como vista';
          btn.classList.remove('completed');
          btn.classList.remove('bg-sol');
          btn.classList.remove('text-gris5');
          btn.classList.add('uncompleted');
          btn.classList.add('bg-morado3');
          btn.classList.add('text-gris1');
        } else {
          btn.textContent = 'Vista';
          btn.classList.remove('uncompleted');
          btn.classList.remove('bg-morado3');
          btn.classList.remove('text-gris5');
          btn.classList.add('completed');
          btn.classList.add('bg-sol');
          btn.classList.add('text-gris5');
        }
      }
    });
  }
}
