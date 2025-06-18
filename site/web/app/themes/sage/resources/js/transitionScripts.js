import {coloresHover} from './colores.js';
import {gsap} from 'gsap';
import {toc} from './toc.js';
import {cosmos} from './cosmos/cosmos.jsx';

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
  // cosmos();

  if (document.body.classList.contains('page-template-series')) {
    const {infiniteScrollSeries} = await import('./infinite-scroll.js');
    infiniteScrollSeries();
  }

  if (document.body.classList.contains('home')) {
    const {ultimosVideosSubidos} = await import('./youTubeApi.js');
    // const {cosmos} = await import('./cosmos/cosmos.jsx');
    ultimosVideosSubidos();
    cosmos();
  }

  if (document.body.classList.contains('post-type-archive-event')) {
    const {eventos} = await import('./eventos.js');
    eventos();
  }
}
