import.meta.glob(['../images/**', '../fonts/**']);

import { constelaciones } from './constelaciones.js';
import { particlesBgColor, setBgColorAtLoadPage } from './nav.js';
import { coloresHover } from './colores.js';
import { toc } from './toc.js';
import { cosmos } from './cosmos/cosmos.jsx';

document.addEventListener('DOMContentLoaded', async () => {
  const xlMin = window.matchMedia('(min-width: 1280px)');

  if (!document.getElementById('wpadminbar')) {
    const { barbaInit } = await import('./barba.js');
    barbaInit();
  }

  particlesBgColor();
  setBgColorAtLoadPage();
  coloresHover();
  toc();

  if (xlMin.matches) {
    const { navegacion } = await import('./nav.js');
    navegacion();
    constelaciones();
  } else {
    const { navegacionMovil } = await import('./nav.js');
    navegacionMovil();
  }

  if (document.body.classList.contains('page-template-series')) {
    const { infiniteScrollSeries } = await import('./infinite-scroll.js');
    infiniteScrollSeries();
  }

  if (document.body.classList.contains('post-type-archive-event')) {
    const { eventos } = await import('./eventos.js');
    eventos();
  }

  if (document.body.classList.contains('home') && xlMin.matches) {
    cosmos();
  }

  if (document.body.classList.contains('home')) {
    const { ultimosVideosSubidos } = await import('./youTubeApi.js');
    ultimosVideosSubidos();
  }

  if (document.body.classList.contains('page-template-template-curso')) {
    await import('./courses/course-index.js');
  }

  if (document.body.classList.contains('single-cde')) {
    const initFeaturedVideoPlayerModule = await import(
      './initFeaturedVideoPlayer.jsx'
    );
    initFeaturedVideoPlayerModule.default();

    const markCompleteModule = await import('./courses/mark-complete.js');
    markCompleteModule.default();
  }
});
