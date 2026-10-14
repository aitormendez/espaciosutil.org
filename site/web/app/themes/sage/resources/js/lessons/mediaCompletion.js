export const LESSON_OUTRO_SECONDS = 36;
export const LESSON_COMPLETION_RATIO = 0.95;

export function lessonCompletionTime(duration) {
  if (!Number.isFinite(duration) || duration <= 0) return null;
  // Un clip menor que el outro no puede contener un outro completo.
  const contentDuration = duration > LESSON_OUTRO_SECONDS
    ? duration - LESSON_OUTRO_SECONDS
    : duration;
  return contentDuration * LESSON_COMPLETION_RATIO;
}

export function observeLessonCompletion(player, { canComplete, complete, onReplay = () => {}, now = Date.now }) {
  let saving = false;
  let saved = false;
  let retryAfter = 0;
  let playing = false;
  let previousTime = null;
  let replayRequested = false;

  const rearm = () => {
    saved = false;
    retryAfter = 0;
    onReplay();
  };
  // play representa un inicio/reanudación; playing también ocurre tras buffering.
  const requestReplay = () => { replayRequested = true; };

  const resetPlayback = () => { playing = false; previousTime = null; };
  const startPlayback = () => {
    playing = true;
    previousTime = player.currentTime;
    if (replayRequested) {
      replayRequested = false;
      rearm();
    }
  };
  const check = async () => {
    const currentTime = player.currentTime;
    const previous = previousTime;
    previousTime = currentTime;
    const threshold = lessonCompletionTime(player.duration);
    // No completar al cargar una posición guardada, pausar o arrastrar la barra.
    if (!playing || player.paused || player.seeking || previous === null ||
        currentTime <= previous || threshold === null || currentTime < threshold ||
        saved || saving || now() < retryAfter || !canComplete()) return;
    saving = true;
    try {
      saved = await complete();
    } finally {
      saving = false;
      retryAfter = now() + 30000;
    }
  };

  player.addEventListener('play', requestReplay);
  player.addEventListener('playing', startPlayback);
  player.addEventListener('pause', resetPlayback);
  player.addEventListener('seeking', resetPlayback);
  // Tras seeked se exige avance real de reproducción antes de completar.
  const afterSeek = () => {
    if (player.paused) return;
    const threshold = lessonCompletionTime(player.duration);
    if (threshold !== null && player.currentTime < threshold) rearm();
    startPlayback();
  };
  player.addEventListener('seeked', afterSeek);
  // Vidstack emite time-update; timeupdate pertenece al elemento multimedia nativo.
  player.addEventListener('time-update', check);
  return () => {
    player.removeEventListener('play', requestReplay);
    player.removeEventListener('playing', startPlayback);
    player.removeEventListener('pause', resetPlayback);
    player.removeEventListener('seeking', resetPlayback);
    player.removeEventListener('seeked', afterSeek);
    player.removeEventListener('time-update', check);
  };
}
