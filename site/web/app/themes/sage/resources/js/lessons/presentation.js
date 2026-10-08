import Swiper from 'swiper';
import { A11y, Zoom } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/zoom';

const instances = new Map();

export function destroyPresentations(scope = document) {
  for (const [root, destroy] of instances) {
    if (scope.contains(root)) { destroy(); instances.delete(root); }
  }
}

export default function initPresentations(scope = document) {
  scope.querySelectorAll('[data-lesson-presentation]').forEach((root) => {
    if (instances.has(root)) return;
    const abort = new AbortController();
    const options = { signal: abort.signal };
    const config = JSON.parse(root.dataset.lessonPresentation);
    const shell = root.querySelector('[data-presentation-shell]');
    const home = root.querySelector('[data-presentation-home]');
    const dialog = root.querySelector('dialog');
    const target = root.querySelector('.swiper');
    const wrapper = root.querySelector('.swiper-wrapper');
    const status = root.querySelector('[data-presentation-status]');
    const counter = root.querySelector('[data-presentation-counter]');
    const previous = root.querySelector('[data-presentation-prev]');
    const next = root.querySelector('[data-presentation-next]');
    const expand = root.querySelector('[data-presentation-expand]');
    const close = root.querySelector('[data-presentation-close]');
    const retry = root.querySelector('[data-presentation-retry]');
    const zoom = root.querySelector('[data-presentation-zoom]');
    let swiper, slides = [], reloaded = false, lastFocus, loading = false, zoomFactor = 1;

    function updateZoomControl(scale) {
      const enlarged = scale > 1;
      const label = enlarged ? 'Restablecer zoom' : 'Acercar';
      zoom.querySelector('[data-presentation-zoom-in]').hidden = enlarged;
      zoom.querySelector('[data-presentation-zoom-out]').hidden = !enlarged;
      zoom.setAttribute('aria-label', label);
      zoom.title = label;
    }
    function controls() {
      if (!swiper) return;
      counter.textContent = `${swiper.activeIndex + 1} / ${slides.length}`;
      previous.disabled = swiper.isBeginning;
      next.disabled = swiper.isEnd;
      zoom.disabled = !dialog.open;
      updateZoomControl(swiper.zoom.scale);
      wrapper.querySelectorAll('.swiper-slide').forEach((el, i) => el.setAttribute('aria-hidden', String(i !== swiper.activeIndex)));
      loadNeighbours();
    }
    function loadNeighbours() {
      wrapper.querySelectorAll('img').forEach((img, i) => {
        if (Math.abs(i - swiper.activeIndex) > 1) {
          img.removeAttribute('srcset'); img.removeAttribute('src'); delete img.dataset.mode;
          return;
        }
        const factor = dialog.open && i === swiper.activeIndex ? zoomFactor : 1;
        const mode = dialog.open ? `expanded-${factor}` : 'inline';
        if (img.dataset.mode === mode) return;
        img.dataset.mode = mode;
        const variants = slides[i].sources.filter((v) => v.width <= (dialog.open ? 3840 : 2560));
        const available = variants.length ? variants : [slides[i].sources[0]];
        img.sizes = dialog.open ? `${Math.ceil(innerWidth * factor)}px` : '(min-width: 1024px) 896px, calc(100vw - 32px)';
        img.srcset = available.map((v) => `${v.url} ${v.width}w`).join(', ');
        img.src = available[0].url;
      });
    }
    function showError(message) {
      status.textContent = message;
      retry.hidden = false;
    }
    async function load(index = 0) {
      if (loading || abort.signal.aborted) return;
      loading = true;
      retry.hidden = true;
      status.textContent = 'Cargando presentación…';
      try {
        const response = await fetch(config.manifestUrl, { credentials: 'same-origin', cache: 'no-store', signal: abort.signal });
        if (!response.ok) throw new Error([401, 403].includes(response.status) ? 'Tu sesión no permite acceder. Inicia sesión y vuelve a intentarlo.' : 'No se ha podido cargar la presentación.');
        const model = await response.json();
        if (!model.available) throw new Error('La presentación no está disponible.');
        slides = model.presentation.slides;
        swiper?.destroy(true, true);
        wrapper.replaceChildren();
        slides.forEach((slide) => {
          const el = document.createElement('div'); el.className = 'swiper-slide';
          const box = document.createElement('div'); box.className = 'swiper-zoom-container';
          const img = document.createElement('img');
          img.alt = slide.alt; img.decoding = 'async';
          img.width = slide.sources[0].width; img.height = slide.sources[0].height;
          img.addEventListener('error', () => {
            if (!img.hasAttribute('src')) return;
            if (!reloaded) { reloaded = true; load(swiper?.activeIndex || 0); }
            else showError('No se ha podido cargar una diapositiva. Comprueba tu sesión y vuelve a intentarlo.');
          }, options);
          box.append(img); el.append(box); wrapper.append(el);
        });
        swiper = new Swiper(target, {
          modules: [A11y, Zoom], speed: matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220,
          initialSlide: Math.min(index, slides.length - 1), zoom: { maxRatio: 3, toggle: false },
          a11y: { enabled: true, slideLabelMessage: 'Diapositiva {{index}} de {{slidesLength}}' },
          on: { slideChange() { if (swiper) { swiper.zoom.out(); controls(); } }, zoomChange(_, scale) { zoomFactor = scale; if (swiper) { updateZoomControl(scale); loadNeighbours(); } } },
        });
        if (abort.signal.aborted) { swiper.destroy(); return; }
        status.textContent = ''; expand.disabled = false; controls();
      } catch (error) {
        if (error.name !== 'AbortError') showError(error.message);
      } finally { loading = false; }
    }
    previous.addEventListener('click', () => swiper?.slidePrev(), options);
    next.addEventListener('click', () => swiper?.slideNext(), options);
    retry.addEventListener('click', () => { reloaded = false; load(swiper?.activeIndex || 0); }, options);
    expand.addEventListener('click', () => {
      lastFocus = document.activeElement;
      dialog.showModal(); dialog.querySelector('[data-presentation-dialog-content]').append(shell);
      expand.hidden = true; close.hidden = false; swiper.update(); controls(); close.focus();
    }, options);
    close.addEventListener('click', () => dialog.close(), options);
    dialog.addEventListener('close', () => {
      swiper?.zoom.out(); home.append(shell); expand.hidden = false; close.hidden = true;
      swiper?.update(); controls(); if (lastFocus?.isConnected) lastFocus.focus();
    }, options);
    zoom.addEventListener('click', () => { if (swiper.zoom.scale > 1) swiper.zoom.out(); else swiper.zoom.in(); controls(); }, options);
    document.addEventListener('keydown', (event) => {
      if (!swiper || event.defaultPrevented || event.isComposing || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      const focused = event.target;
      if (focused.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"]), [role="textbox"]')) return;
      // Al recorrer la página, solo responde el visor visible; respeta otros controles.
      if (!root.contains(focused) && focused !== document.body && focused !== document.documentElement) return;
      const bounds = target.getBoundingClientRect();
      const center = bounds.top + bounds.height / 2;
      if (!dialog.open && (!bounds.width || !bounds.height || center < 0 || center > innerHeight || bounds.right <= 0 || bounds.left >= innerWidth)) return;
      event.preventDefault();
      const button = event.key === 'ArrowLeft' ? previous : next;
      if (!button.disabled) button.click();
    }, options);
    target.addEventListener('dblclick', () => { if (dialog.open) { swiper.zoom.toggle(); controls(); } }, options);
    instances.set(root, () => { abort.abort(); if (dialog.open) dialog.close(); swiper?.destroy(true, true); });
    load();
  });
}
