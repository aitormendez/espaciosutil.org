function runAfterFirstPaint(callback) {
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(callback, {timeout: 1000});
        return;
      }

      window.setTimeout(callback, 150);
    });
  });
}

export function initHomeEnhancements() {
  if (!document.body.classList.contains('home')) {
    return;
  }

  runAfterFirstPaint(async () => {
    const latestVideos = document.querySelector('#ultimos-videos-subidos');

    if (latestVideos && latestVideos.dataset.initialized !== '1') {
      latestVideos.dataset.initialized = '1';

      const {ultimosVideosSubidos} = await import('./youTubeApi.js');
      ultimosVideosSubidos();
    }
  });

  if (!window.matchMedia('(min-width: 1280px)').matches) {
    return;
  }

  runAfterFirstPaint(async () => {
    const {cosmos} = await import('./cosmos/cosmos.jsx');
    cosmos();
  });
}
