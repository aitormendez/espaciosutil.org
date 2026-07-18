const MOBILE_SUBINDEX_QUERY = '(max-width: 767px)';

export function isSubindexAction(target) {
  return Boolean(target?.closest?.('[data-video-seek], a[href^="#"]'));
}

export function getSubindexToggleState(isOpen, closedLabel, openLabel) {
  return isOpen
    ? {
        expanded: 'true',
        label: openLabel,
        icon: '−',
        panelClasses: ['grid-rows-[1fr]', 'opacity-100', 'mt-4'],
      }
    : {
        expanded: 'false',
        label: closedLabel,
        icon: '+',
        panelClasses: ['grid-rows-[0fr]', 'opacity-0', 'mt-0'],
      };
}

export default function initLessonSubindex() {
  document.querySelectorAll('[data-lesson-subindex]').forEach((root) => {
    if (root.dataset.lessonSubindexInitialized === 'true') {
      return;
    }

    const toggle = root.querySelector('[data-lesson-subindex-toggle]');
    const panel = root.querySelector('[data-lesson-subindex-panel]');
    const label = root.querySelector('[data-lesson-subindex-label]');
    const icon = root.querySelector('[data-lesson-subindex-icon]');

    if (!toggle || !panel || !label || !icon) {
      return;
    }

    const isMobile = () => window.matchMedia(MOBILE_SUBINDEX_QUERY).matches;

    const setOpen = (isOpen) => {
      const state = getSubindexToggleState(
        isOpen,
        label.dataset.closedLabel,
        label.dataset.openLabel
      );

      panel.classList.remove(
        'grid-rows-[0fr]',
        'grid-rows-[1fr]',
        'opacity-0',
        'opacity-100',
        'mt-0',
        'mt-4'
      );
      panel.classList.add(...state.panelClasses);
      panel.setAttribute('aria-hidden', String(!isOpen));
      toggle.setAttribute('aria-expanded', state.expanded);
      label.textContent = state.label;
      icon.textContent = state.icon;
    };

    const closeOnMobile = () => {
      if (isMobile()) {
        setOpen(false);
      }
    };

    toggle.addEventListener('click', () => {
      if (!isMobile()) {
        return;
      }

      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    panel.addEventListener('click', (event) => {
      if (isSubindexAction(event.target)) {
        closeOnMobile();
      }
    });

    root.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeOnMobile();
        toggle.focus();
      }
    });

    if (isMobile()) {
      setOpen(false);
    }

    root.dataset.lessonSubindexInitialized = 'true';
  });
}
