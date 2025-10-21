import tocbot from 'tocbot';
import {gsap} from 'gsap';
import {ScrollTrigger} from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);

export function toc() {
  const tocContainer = document.getElementById('toc');
  const footer = document.getElementById('footer');

  if (tocContainer && footer) {
    gsap.to(tocContainer, {
      scrollTrigger: {
        trigger: footer,
        toggleActions: 'play reverse play reverse',
        markers: false,
      },
      opacity: 0,
    });
  }

  if (tocContainer) {
    if (tocContainer.dataset.toc) {
      tocbot.init({
        // Where to render the table of contents.
        tocSelector: '.js-toc',
        // Where to grab the headings to build the table of contents.
        contentSelector: '#toc-content',
        // Which headings to grab inside of the contentSelector element.
        headingSelector: 'h1, h2, h3, h4, h5',
        // For headings inside relative or absolute positioned containers within content.
        hasInnerContainers: true,
      });
    }
  }
}
