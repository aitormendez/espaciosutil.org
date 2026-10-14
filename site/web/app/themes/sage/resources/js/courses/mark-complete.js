const selector = '[data-lesson-completion]';
const states = new Map();
const pending = new Map();
const manualOverrides = new Set();
let initialized = false;
let saveQueue = Promise.resolve();

const buttonsFor = (postId) =>
  [...document.querySelectorAll(selector)].filter(
    (button) => Number(button.dataset.postId) === Number(postId)
  );

function renderButton(button, completed) {
  button.dataset.completed = String(completed);
  button.classList.toggle('completed', completed);
  button.classList.toggle('text-sol', completed);
  const inactiveColor = button.classList.contains('course-index-completion') ? 'text-morado1' : 'text-gris2';
  button.classList.toggle(inactiveColor, !completed);
  button.setAttribute('aria-pressed', String(completed));
  const action = completed ? 'Marcar como no vista' : 'Marcar como vista';
  const title = button.dataset.lessonTitle;
  button.setAttribute('aria-label', title ? `${action}: ${title}` : action);
  button.title = action;
  button.querySelector('.icon-show')?.classList.toggle('hidden', !completed);
  button.querySelector('.icon-hide')?.classList.toggle('hidden', completed);
  const text = button.querySelector('.btn-text');
  if (text) text.textContent = completed ? 'Vista' : 'Marcar como vista';
}

export function syncCompletionButtons(root = document) {
  root.querySelectorAll(selector).forEach((button) => {
    const id = Number(button.dataset.postId);
    if (!states.has(id)) states.set(id, button.dataset.completed === 'true');
    renderButton(button, states.get(id));
    button.disabled = pending.has(id);
    button.setAttribute('aria-busy', String(pending.has(id)));
  });
}

function announce(message, error = false) {
  let status = document.getElementById('lesson-completion-status');
  if (!status) {
    status = document.createElement('div');
    status.id = 'lesson-completion-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    document.body.append(status);
  }
  status.className = error
    ? 'fixed bottom-4 left-4 right-4 z-50 rounded bg-morado5 p-4 text-white shadow-lg'
    : 'sr-only';
  status.textContent = message;
}

export function resetLessonAutoCompletion(postId) {
  manualOverrides.delete(Number(postId));
}

export function canAutoCompleteLesson(postId, initiallyCompleted = false) {
  const id = Number(postId);
  return id > 0 && !manualOverrides.has(id) &&
    !(states.get(id) ?? initiallyCompleted) && !pending.has(id);
}

export async function setLessonCompleted(postId, completed, { automatic = false } = {}) {
  const id = Number(postId);
  if (!Number.isInteger(id) || id <= 0 || !window.wpApiSettings?.nonce) return false;
  if (pending.has(id)) return pending.get(id);
  if (automatic && !canAutoCompleteLesson(id)) return false;

  const focusedButton = buttonsFor(id).find((button) => button === document.activeElement);
  const request = saveQueue.then(async () => {
    try {
      const root = window.wpApiSettings.root || '/wp-json/';
      const response = await fetch(`${root.replace(/\/?$/, '/')}cde/v1/complete/`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.wpApiSettings.nonce,
        },
        body: JSON.stringify({ post_id: id, action: completed ? 'complete' : 'uncomplete' }),
      });
      if (!response.ok) throw new Error('No se pudo guardar el estado.');
      const data = await response.json();
      if (data.success !== true || !data.completed_lessons || typeof data.completed_lessons !== 'object') {
        throw new Error('Respuesta de progreso no válida.');
      }
      // WordPress puede devolver un objeto con índices discontinuos al desmarcar.
      const saved = Object.values(data.completed_lessons).map(Number).includes(id);
      if (saved !== completed) throw new Error('El servidor no confirmó el cambio.');
      states.set(id, saved);
      if (!automatic) manualOverrides.add(id);
      buttonsFor(id).forEach((button) => renderButton(button, saved));
      announce(saved ? 'Lección marcada como vista.' : 'Lección marcada como no vista.');
      return true;
    } catch (error) {
      announce('No se pudo guardar el estado de la lección. Inténtalo de nuevo.', true);
      console.error('Error al guardar la lección vista:', error);
      return false;
    } finally {
      pending.delete(id);
      buttonsFor(id).forEach((button) => {
        button.disabled = false;
        button.setAttribute('aria-busy', 'false');
      });
      if (focusedButton?.isConnected && document.activeElement === document.body) {
        focusedButton.focus({ preventScroll: true });
      }
    }
  });
  saveQueue = request.catch(() => {});
  pending.set(id, request);
  buttonsFor(id).forEach((button) => {
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
  });
  return request;
}

export default function initMarkComplete() {
  syncCompletionButtons();
  if (initialized) return;
  initialized = true;
  // Delegación: también cubre filas cargadas por AJAX y navegación con Barba.
  document.addEventListener('click', (event) => {
    const button = event.target.closest?.(selector);
    if (!button || button.disabled) return;
    event.preventDefault();
    void setLessonCompleted(button.dataset.postId, button.dataset.completed !== 'true');
  });
}
