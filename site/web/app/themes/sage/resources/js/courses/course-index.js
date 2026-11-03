import initMarkComplete, { syncCompletionButtons } from './mark-complete.js';
import { gsap } from 'gsap';
import barba from '@barba/core';
import {
  MOBILE_COURSE_INDEX_QUERY,
  shouldScrollToCourseTopics,
} from './courseIndexScroll.js';

const instances = new WeakMap();
let lifecycleRegistered = false;
let regionId = 0;
const reducedMotion = () =>
  window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

/** Cancel pending reads and release only this module's handlers when Barba leaves. */
export function destroyCourseIndex(root = document) {
  const panels = [...root.querySelectorAll('[data-course-topics], #indice-ajax-container')];
  if (root.matches?.('[data-course-topics], #indice-ajax-container')) panels.push(root);
  panels.forEach((panel) => instances.get(panel)?.());
}

function initializeFamily(root, container, topicsHeading) {
  if (instances.has(container)) return;
  const seriesToggles = [...root.querySelectorAll('.serie-accordion-toggle')];
  const blockButtons = [...root.querySelectorAll('.serie-cde-button')];
  if (!seriesToggles.length && !blockButtons.length) return;

  const panelMap = new Map();
  let activeBlockButton = null;
  let requestNumber = 0;
  let controller = null;
  let disposed = false;
  const isCurrent = (number) =>
    !disposed && container.isConnected && number === requestNumber;

  const setExpanded = (toggle, panel, expanded, isSeries = false, animate = true) => {
    gsap.killTweensOf(panel);
    toggle.setAttribute('aria-expanded', String(expanded));
    panel.setAttribute('aria-hidden', String(!expanded));
    panel.inert = !expanded;
    if (isSeries) toggle.classList.toggle('is-open', expanded);
    else panel.dataset.expanded = String(expanded);
    const icon = toggle.querySelector(isSeries ? '.serie-accordion-icon' : '.course-index-chevron');
    if (icon) icon.textContent = expanded ? '-' : '+';

    const finish = () => {
      if (isSeries) panel.classList.toggle('hidden', !expanded);
      panel.style.height = expanded ? 'auto' : isSeries ? '' : '0px';
      panel.style.opacity = expanded || isSeries ? '' : '0';
    };
    if (expanded) {
      panel.classList.remove('hidden', 'is-collapsed');
    }
    if (!animate || reducedMotion()) {
      finish();
    } else if (expanded) {
      gsap.fromTo(panel, { height: 0, opacity: 0 }, {
        height: 'auto', opacity: 1, duration: 0.3, ease: 'power2.out', onComplete: finish,
      });
    } else {
      gsap.to(panel, {
        height: 0, opacity: 0, duration: 0.25, ease: 'power2.inOut', onComplete: finish,
      });
    }
  };

  const childrenPanel = (toggle) => {
    const item = toggle.closest('.course-index-item');
    const panel = item?.querySelector('[data-children-wrapper]');
    return panel?.closest('.course-index-item') === item ? panel : null;
  };
  const initializeChildren = () => {
    container.querySelectorAll('[data-toggle-children="true"]').forEach((toggle) => {
      const panel = childrenPanel(toggle);
      if (!panel) return;
      panel.style.overflow = 'hidden';
      setExpanded(toggle, panel, panel.dataset.expanded !== 'false', false, false);
    });
  };
  const clearButton = (button) => {
    if (!button) return;
    button.classList.remove('is-active');
    button.removeAttribute('aria-current');
    button.removeAttribute('aria-busy');
    button.disabled = false;
  };
  const scrollToTopicsOnMobile = () => {
    const mobile = window.matchMedia?.(MOBILE_COURSE_INDEX_QUERY).matches ?? false;
    if (shouldScrollToCourseTopics(mobile, Boolean(topicsHeading))) {
      topicsHeading.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start' });
    }
  };

  const loadBlock = async (button) => {
    const postId = button.dataset.postId;
    if (!/^[1-9]\d*$/.test(postId ?? '') || activeBlockButton === button) return;
    const number = ++requestNumber;
    controller?.abort();
    controller = new AbortController();
    const { signal } = controller;
    clearButton(activeBlockButton);
    activeBlockButton = button;
    button.classList.add('is-active');
    button.setAttribute('aria-current', 'true');
    button.setAttribute('aria-busy', 'true');
    button.disabled = true;
    container.setAttribute('aria-busy', 'true');
    // Stop animations on descendants before their DOM is replaced.
    container.querySelectorAll('[data-children-wrapper]').forEach((panel) => gsap.killTweensOf(panel));
    container.innerHTML = '<p>Cargando...</p>';

    const toggle = button.closest('.serie-accordion-item')?.querySelector('.serie-accordion-toggle');
    const title = button.dataset.serieName ?? toggle?.dataset.serieName ??
      toggle?.querySelector('[data-series-title]')?.textContent ?? toggle?.querySelector('span')?.textContent ?? '';
    try {
      const response = await fetch(
        `/espaciosutil/v1/indice-revelador/${postId}?serie_name=${encodeURIComponent(title.trim())}`,
        { signal }
      );
      if (!response.ok) throw new Error('No se pudo cargar el índice.');
      const data = await response.json();
      if (!isCurrent(number) || signal.aborted) return;
      if (typeof data?.html !== 'string') throw new Error('Respuesta de índice no válida.');
      container.innerHTML = data.html;
      syncCompletionButtons(container);
      initializeChildren();
      scrollToTopicsOnMobile();
    } catch (error) {
      if (!isCurrent(number) || signal.aborted) return;
      container.innerHTML = '<p>Ha ocurrido un error al cargar el índice. Selecciona de nuevo el bloque para reintentar.</p>';
      clearButton(button);
      activeBlockButton = null; // The same block must remain retryable after failure.
      console.error('Error fetching course index:', error);
    } finally {
      // A late request must not clear the busy state of a newer selection.
      if (isCurrent(number)) {
        container.setAttribute('aria-busy', 'false');
        button.removeAttribute('aria-busy');
        button.disabled = false;
      }
    }
  };

  seriesToggles.forEach((toggle) => {
    const id = toggle.getAttribute('aria-controls');
    // Resolve only inside this family, never a global panel with a matching ID.
    const panel = id && [...root.querySelectorAll('[id]')].find((node) => node.id === id);
    if (!panel) return;
    panelMap.set(toggle, panel);
    panel.style.overflow = 'hidden';
    setExpanded(toggle, panel, false, true, false);
  });

  if (!container.id) container.id = `course-topics-${++regionId}`;
  container.setAttribute('role', 'region');
  container.setAttribute('aria-live', 'polite');
  container.setAttribute('aria-busy', 'false');
  if (topicsHeading) {
    if (!topicsHeading.id) topicsHeading.id = `${container.id}-heading`;
    container.setAttribute('aria-labelledby', topicsHeading.id);
  } else if (!container.hasAttribute('aria-label')) {
    container.setAttribute('aria-label', 'Temas del curso');
  }
  blockButtons.forEach((button) => {
    // These are ordinary action buttons, not an incomplete tab widget.
    if (button.getAttribute('role') === 'tab') button.removeAttribute('role');
    button.removeAttribute('aria-selected');
    button.setAttribute('aria-controls', container.id);
  });

  const onClick = (event) => {
    const toggle = event.target.closest?.('.serie-accordion-toggle');
    if (toggle && panelMap.has(toggle)) {
      const expanded = toggle.getAttribute('aria-expanded') !== 'true';
      if (expanded) {
        panelMap.forEach((panel, other) => {
          if (other !== toggle && other.getAttribute('aria-expanded') === 'true') {
            setExpanded(other, panel, false, true);
          }
        });
      }
      setExpanded(toggle, panelMap.get(toggle), expanded, true);
      return;
    }
    const block = event.target.closest?.('.serie-cde-button');
    if (block && blockButtons.includes(block)) {
      void loadBlock(block);
      return;
    }
    const childToggle = event.target.closest?.('[data-toggle-children="true"]');
    if (childToggle && container.contains(childToggle)) {
      const panel = childrenPanel(childToggle);
      if (panel) setExpanded(childToggle, panel, panel.dataset.expanded === 'false');
    }
  };
  // Native buttons already synthesize click for Enter/Space; do not toggle twice.
  const onKeydown = (event) => {
    const toggle = event.target.closest?.('[data-toggle-children="true"]');
    if (!toggle || !container.contains(toggle) || toggle.tagName === 'BUTTON' ||
        (toggle.tagName === 'A' && toggle.hasAttribute('href'))) return;
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      onClick({ target: toggle });
    }
  };
  root.addEventListener('click', onClick);
  root.addEventListener('keydown', onKeydown);
  container.dataset.courseIndexInitialized = 'true';
  initializeChildren();
  instances.set(container, () => {
    disposed = true;
    requestNumber++;
    controller?.abort();
    clearButton(activeBlockButton);
    panelMap.forEach((panel) => gsap.killTweensOf(panel));
    container.querySelectorAll('[data-children-wrapper]').forEach((panel) => gsap.killTweensOf(panel));
    root.removeEventListener('click', onClick);
    root.removeEventListener('keydown', onKeydown);
    container.setAttribute('aria-busy', 'false');
    delete container.dataset.courseIndexInitialized;
    instances.delete(container);
  });
}

export default function initCourseIndex() {
  initMarkComplete();
  if (!lifecycleRegistered) {
    barba.hooks.beforeLeave(({ current }) => {
      if (current?.container) destroyCourseIndex(current.container);
    });
    lifecycleRegistered = true;
  }
  const families = document.querySelectorAll('section[data-course-family]');
  if (families.length) {
    families.forEach((family) => {
      const container = family.querySelector('[data-course-topics]');
      if (container) initializeFamily(family, container, family.querySelector('[data-course-topics-heading]'));
    });
  } else {
    // Transitional compatibility for a cached/older template, not the new layout.
    const container = document.getElementById('indice-ajax-container');
    if (container) initializeFamily(document, container, document.getElementById('course-topics-heading'));
  }
}
