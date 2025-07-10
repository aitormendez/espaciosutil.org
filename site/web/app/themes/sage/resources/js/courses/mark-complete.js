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
        const container = btn.querySelector('div');
        const iconShow = container.querySelector('.icon-show');
        const iconHide = container.querySelector('.icon-hide');
        const text = container.querySelector('.btn-text');

        if (isCurrentlyCompleted) {
          text.textContent = 'Marcar como vista';
          iconShow.classList.add('hidden');
          iconHide.classList.remove('hidden');
          btn.classList.remove('completed', 'bg-sol');
          btn.classList.add('bg-morado2');
        } else {
          text.textContent = 'Vista';
          iconShow.classList.remove('hidden');
          iconHide.classList.add('hidden');
          btn.classList.add('completed', 'bg-sol');
          btn.classList.remove('bg-morado2');
        }
      }
    });
  }
}
