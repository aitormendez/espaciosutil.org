import { gsap } from 'gsap';

const container = document.getElementById('indice-ajax-container');
const seriesToggles = document.querySelectorAll('.serie-accordion-toggle');
const blockButtons = document.querySelectorAll('.serie-cde-button');

if (!container || (!seriesToggles.length && !blockButtons.length)) {
  // No course index present on this page.
} else {
  const panelMap = new Map();
  let activeBlockButton = null;

  seriesToggles.forEach((toggle) => {
    const panelId = toggle.getAttribute('aria-controls');
    if (!panelId) {
      return;
    }

    const panel = document.getElementById(panelId);
    if (!panel) {
      return;
    }

    panel.style.overflow = 'hidden';
    panel.classList.add('hidden');

    panelMap.set(toggle, panel);

    toggle.addEventListener('click', () => {
      const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
      if (isExpanded) {
        collapsePanel(toggle);
        return;
      }

      seriesToggles.forEach((otherToggle) => {
        if (
          otherToggle !== toggle &&
          otherToggle.getAttribute('aria-expanded') === 'true'
        ) {
          collapsePanel(otherToggle);
        }
      });

      expandPanel(toggle);
    });
  });

  function expandPanel(toggle) {
    const panel = panelMap.get(toggle);
    if (!panel) {
      return;
    }

    gsap.killTweensOf(panel);
    toggle.setAttribute('aria-expanded', 'true');
    toggle.classList.add('is-open');
    updateToggleIcon(toggle, '-');

    panel.classList.remove('hidden');
    gsap.fromTo(
      panel,
      { height: 0, opacity: 0 },
      {
        height: 'auto',
        opacity: 1,
        duration: 0.3,
        ease: 'power2.out',
        onComplete: () => {
          panel.style.height = 'auto';
        },
      }
    );
  }

  function collapsePanel(toggle) {
    const panel = panelMap.get(toggle);
    if (!panel) {
      return;
    }

    gsap.killTweensOf(panel);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.classList.remove('is-open');
    updateToggleIcon(toggle, '+');

    gsap.to(panel, {
      height: 0,
      opacity: 0,
      duration: 0.25,
      ease: 'power2.inOut',
      onComplete: () => {
        panel.classList.add('hidden');
        panel.style.height = '';
        panel.style.opacity = '';
      },
    });
  }

  function updateToggleIcon(toggle, symbol) {
    const icon = toggle.querySelector('.serie-accordion-icon');
    if (icon) {
      icon.textContent = symbol;
    }
  }

  blockButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      if (!button.dataset.postId) {
        return;
      }

      if (activeBlockButton === button) {
        return;
      }

      if (activeBlockButton) {
        activeBlockButton.classList.remove('is-active');
        activeBlockButton.removeAttribute('aria-current');
        activeBlockButton.removeAttribute('aria-busy');
        activeBlockButton.disabled = false;
      }

      activeBlockButton = button;
      activeBlockButton.classList.add('is-active');
      activeBlockButton.setAttribute('aria-current', 'true');
      activeBlockButton.setAttribute('aria-busy', 'true');
      activeBlockButton.disabled = true;

      const postId = button.dataset.postId;
      const seriesTitle =
        button
          .closest('.serie-accordion-item')
          ?.querySelector('.serie-accordion-toggle span')?.textContent ?? '';
      const serieName = encodeURIComponent(seriesTitle.trim());

      container.innerHTML = '<p>Cargando...</p>';

      try {
        const response = await fetch(
          `/espaciosutil/v1/indice-revelador/${postId}?serie_name=${serieName}`
        );
        if (!response.ok) {
          throw new Error('Network response was not ok.');
        }
        const data = await response.json();
        container.innerHTML = data.html;
      } catch (error) {
        container.innerHTML =
          '<p>Ha ocurrido un error al cargar el índice.</p>';
        console.error('Error fetching course index:', error);
        button.classList.remove('is-active');
        button.removeAttribute('aria-current');
      } finally {
        button.removeAttribute('aria-busy');
        button.disabled = false;
      }
    });
  });

  blockButtons.forEach((button) => {
    if (button.classList.contains('is-active')) {
      return;
    }

    button.setAttribute('role', 'tab');
    button.setAttribute('aria-selected', 'false');
  });

  container.setAttribute('role', 'region');
  container.setAttribute('aria-live', 'polite');
}
