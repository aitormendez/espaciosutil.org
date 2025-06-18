import barba from '@barba/core';
import {gsap} from 'gsap';
import {transitionScriptsAfter, transitionScriptsEnter} from './transitionScripts.js';
import {ScrollToPlugin} from 'gsap/ScrollToPlugin.js';
gsap.registerPlugin(ScrollToPlugin);

export function barbaInit() {
  let body = document.querySelector('body');
  barba.init({
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
    let bodyClasses = htmlDoc.querySelector('body').getAttribute('class');
    body.setAttribute('class', bodyClasses);
  });

  barba.hooks.enter(() => {
    transitionScriptsEnter();
  });

  barba.hooks.after(() => {
    transitionScriptsAfter();
  });
}
