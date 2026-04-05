import.meta.glob(['../images/**', '../fonts/**']);

import { constelaciones, destruirConstelaciones } from './constelaciones.js';
import {
  navegacion,
  navegacionMovil,
  particlesBgColor,
  setBgColorAtLoadPage,
  syncActiveMenuState,
} from './nav.js';
import { coloresHover } from './colores.js';
import { toc } from './toc.js';
import { initCookieConsent } from './cookieConsent.js';
import { initHomeEnhancements } from './home.js';

if (import.meta.env.DEV && typeof window !== 'undefined') {
  const devtoolsHook = window.__REACT_DEVTOOLS_GLOBAL_HOOK__;

  if (devtoolsHook?.on && !devtoolsHook.__es_sutil_patched) {
    const originalOn = devtoolsHook.on;

    devtoolsHook.on = function patchedDevtoolsOn(event, payload) {
      if (event === 'renderer' && payload?.version === '') {
        payload.version = '0.0.0';
      }

      return originalOn.call(this, event, payload);
    };

    devtoolsHook.__es_sutil_patched = true;
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const xlMin = window.matchMedia('(min-width: 1280px)');
  let navMode = null;
  let destroyNavigation = () => {};

  initCookieConsent();
  syncActiveMenuState();

  if (!document.getElementById('wpadminbar')) {
    const { barbaInit } = await import('./barba.js');
    barbaInit();
  }

  particlesBgColor();
  setBgColorAtLoadPage();
  coloresHover();
  toc();

  const initNavigationByViewport = () => {
    const nextNavMode = xlMin.matches ? 'desktop' : 'mobile';

    if (nextNavMode === navMode) {
      return;
    }

    destroyNavigation();
    navMode = nextNavMode;

    if (nextNavMode === 'desktop') {
      destroyNavigation = navegacion();
      constelaciones();
      return;
    }

    destruirConstelaciones();
    destroyNavigation = navegacionMovil();
  };

  initNavigationByViewport();

  const handleViewportNavigationChange = () => initNavigationByViewport();

  if (typeof xlMin.addEventListener === 'function') {
    xlMin.addEventListener('change', handleViewportNavigationChange);
  } else if (typeof xlMin.addListener === 'function') {
    xlMin.addListener(handleViewportNavigationChange);
  }

  if (document.body.classList.contains('page-template-series')) {
    const { infiniteScrollSeries } = await import('./infinite-scroll.js');
    infiniteScrollSeries();
  }

  initHomeEnhancements();

  if (document.body.classList.contains('page-template-template-curso')) {
    const initCourseIndexModule = await import('./courses/course-index.js');
    initCourseIndexModule.default();
  }

  if (document.querySelector('[data-media-props]')) {
    const initFeaturedVideoPlayerModule = await import(
      './initFeaturedVideoPlayer.jsx'
    );
    initFeaturedVideoPlayerModule.default();
  }

  if (document.body.classList.contains('single-cde')) {
    const markCompleteModule = await import('./courses/mark-complete.js');
    markCompleteModule.default();

    const lessonQuizModule = await import('./lessons/quiz.js');
    lessonQuizModule.default();
  }
});
