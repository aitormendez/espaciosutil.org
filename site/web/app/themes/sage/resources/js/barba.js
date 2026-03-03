import barba from '@barba/core';
import {gsap} from 'gsap';
import {transitionScriptsAfter, transitionScriptsEnter} from './transitionScripts.js';
import {ScrollToPlugin} from 'gsap/ScrollToPlugin.js';
import {setBgColorAtLoadPage, syncActiveMenuState} from './nav.js';
gsap.registerPlugin(ScrollToPlugin);

function normalizePath(pathname) {
  if (!pathname || pathname === '/') {
    return '/';
  }

  return pathname.endsWith('/') ? pathname : `${pathname}/`;
}

function navContextFromPath(pathname) {
  const path = normalizePath(pathname);
  const cdePrefixes = [
    '/curso-de-desarrollo-espiritual/',
    '/indice-de-lecciones/',
    '/suscripciones/',
    '/el-curso-en-profundidad/',
    '/bases-de-colaboracion/',
    '/login/',
    '/cuenta-de-membresia/',
    '/pago-de-membresia/',
    '/confirmacion-de-membresia/',
  ];

  return cdePrefixes.some((prefix) => path.startsWith(prefix)) ? 'cde' : 'es';
}

function isSensitivePath(pathname) {
  const path = normalizePath(pathname);
  const sensitivePrefixes = [
    '/login/',
    '/cuenta-de-membresia/',
    '/pago-de-membresia/',
    '/confirmacion-de-membresia/',
    '/wp/wp-login.php/',
    '/wp/wp-admin/',
  ];

  return sensitivePrefixes.some((prefix) => path.startsWith(prefix));
}

function shouldPreventByHref(href) {
  if (!href) {
    return false;
  }

  const url = new URL(href, window.location.origin);

  if (url.origin !== window.location.origin) {
    return false;
  }

  if (isSensitivePath(url.pathname)) {
    return true;
  }

  const currentContext = document.body?.dataset?.navContext || 'es';
  const targetContext = navContextFromPath(url.pathname);

  return currentContext !== targetContext;
}

export function barbaInit() {
  let body = document.querySelector('body');
  barba.init({
    prevent: ({el, href}) => {
      if (el?.closest('[data-barba-prevent]')) {
        return true;
      }

      return shouldPreventByHref(href);
    },
    transitions: [
      {
        name: 'opacity-transition',
        leave(data) {
          return gsap.to(data.current.container, {
            opacity: 0,
            duration: 1,
            onComplete: () => {
              data.current.container.classList.add('hidden');
              data.current.container.classList.remove('relative');
            },
          });
        },
        enter(data) {
          return gsap.from(data.next.container, {
            opacity: 0,
            duration: 1,
          });
        },
      },
    ],
  });

  barba.hooks.afterLeave(() => {
    gsap.to(window, {duration: 0.5, scrollTo: 0});
  });

  barba.hooks.afterLeave((data) => {
    let parser = new DOMParser();
    let htmlDoc = parser.parseFromString(data.next.html, 'text/html');
    let nextBody = htmlDoc.querySelector('body');
    if (!nextBody) {
      return;
    }
    let bodyClasses = nextBody.getAttribute('class') || '';
    body.setAttribute('class', bodyClasses);
    body.dataset.navContext = nextBody.dataset.navContext || 'es';
    body.dataset.section = nextBody.dataset.section || '';
    body.dataset.sectionColor = nextBody.dataset.sectionColor || '#000000';
    setBgColorAtLoadPage();
  });

  barba.hooks.enter(() => {
    transitionScriptsEnter();
  });

  barba.hooks.after(() => {
    syncActiveMenuState();
    transitionScriptsAfter();
  });
}
