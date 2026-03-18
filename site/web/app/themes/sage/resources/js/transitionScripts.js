import {coloresHover} from './colores.js';
import {gsap} from 'gsap';
import {toc} from './toc.js';
import {initHomeEnhancements} from './home.js';

export function transitionScriptsEnter() {
  const xlMin = window.matchMedia('(min-width: 1280px)');
  const nav = document.getElementById('nav');

  coloresHover();

  if (!xlMin.matches) {
    gsap.set(nav, {
      x: '-100vw',
    });
  }
}

export async function transitionScriptsAfter() {
  toc();

  if (document.body.classList.contains('page-template-series')) {
    const {infiniteScrollSeries} = await import('./infinite-scroll.js');
    infiniteScrollSeries();
  }

  if (document.body.classList.contains('page-template-template-curso')) {
    const initCourseIndexModule = await import('./courses/course-index.js');
    initCourseIndexModule.default();
  }

  if (document.body.classList.contains('home')) {
    initHomeEnhancements();
  }
}
