let audioOwner = null;
const mounted = new Map();

export function initPracticeViewers(scope = document) {
  for (const [root, destroy] of mounted) {
    if (!root.isConnected) { destroy(); mounted.delete(root); }
  }
  scope.querySelectorAll('[data-practice-viewer]').forEach((root) => {
    if (mounted.has(root)) return;
    let guide;
    try { guide = JSON.parse(root.dataset.guide); } catch { return; }
    if (!guide.screens?.length) return; // Full reading remains available without JS or an adapted structure.
    const q = (selector) => root.querySelector(selector);
    const screen = q('[data-practice-screen]');
    const reading = q('[data-practice-reading]');
    const index = q('.practice-index');
    const status = q('[data-practice-status]');
    const audioButton = q('[data-action="audio"]');
    const fullButton = q('[data-action="fullscreen"]');
    const controller = new AbortController();
    const options = { signal: controller.signal };
    const audio = new Audio();
    audio.preload = 'none';
    let position = 0;
    let mode = 'guide';
    let enabled = false;
    let fullscreen = false;
    let indexOpen = false;
    const embedded = root.classList.contains('practice-viewer--embedded');
    let indexFits = null;
    let embeddedIndexOpen = null;
    const indexToggle = q('.practice-index-toggle');
    let revision = 0;
    const stop = () => { revision++; audio.pause(); audio.removeAttribute('src'); audio.load(); };
    const syncAudio = () => {
      audioButton.setAttribute('aria-pressed', String(enabled));
      q('[data-audio-label]').textContent = enabled ? 'Desactivar audio' : 'Activar audio';
      q('[data-audio-off]').hidden = enabled;
      q('[data-audio-on]').hidden = !enabled;
    };
    const disable = () => { enabled = false; stop(); syncAudio(); if (audioOwner === disable) audioOwner = null; };
    const narrate = async () => {
      stop();
      if (!enabled || mode !== 'guide') return;
      const token = revision;
      const url = guide.screens[position].audio;
      if (!url) { disable(); status.textContent = 'La narración de esta pantalla todavía no está disponible.'; return; }
      audio.src = url;
      try { await audio.play(); }
      catch (error) {
        if (token !== revision) return;
        disable();
        status.textContent = 'No se ha podido reproducir el audio. Pulsa Activar audio para intentarlo de nuevo.';
      }
    };
    const syncIndex = () => {
      if (embedded && !fullscreen) {
        const style = getComputedStyle(root);
        const needed = parseFloat(style.getPropertyValue('--practice-index-width')) + parseFloat(style.getPropertyValue('--practice-index-gap'));
        const available = q('.practice-viewer-main').getBoundingClientRect().left - root.getBoundingClientRect().left;
        const fits = available >= needed;
        // Resizing across the fit boundary restores the automatic state; changing mode does not.
        if (fits !== indexFits) { indexFits = fits; embeddedIndexOpen = null; }
        root.dataset.indexFits = String(fits);
        index.hidden = !(embeddedIndexOpen ?? fits);
      } else { index.hidden = fullscreen ? !indexOpen : false; }
      root.classList.toggle('practice-index-open', fullscreen && indexOpen);
      const toggle = indexToggle;
      toggle.setAttribute('aria-expanded', String(!index.hidden));
      toggle.querySelector('span').textContent = index.hidden ? 'Mostrar índice' : 'Ocultar índice';
      if (index.hidden && index.contains(document.activeElement)) toggle.focus({ preventScroll: true });
    };
    const render = () => {
      const current = guide.screens[position];
      screen.replaceChildren();
      if (current.type === 'heading') {
        current.path.forEach((title, i) => {
          const el = document.createElement(i === current.path.length - 1 ? 'h2' : 'p');
          el.textContent = title;
          screen.append(el);
        });
      } else {
        screen.innerHTML = current.html; // Server-sanitized inline editorial markup only.
      }
      screen.dataset.kind = current.type;
      q('[data-practice-counter]').textContent = `${position + 1} de ${guide.screens.length} pantallas`;
      q('[data-practice-progress]').value = position + 1;
      q('[data-action="previous"]').disabled = position === 0;
      q('[data-action="next"]').disabled = position === guide.screens.length - 1;
      root.querySelectorAll('[data-practice-jump]').forEach((button) => {
        const section = guide.sections.find((s) => s.screen === Number(button.dataset.practiceJump));
        if (section?.title === current.section) button.setAttribute('aria-current', 'step');
        else button.removeAttribute('aria-current');
      });
      status.textContent = '';
    };
    const go = (next) => {
      const target = Math.max(0, Math.min(guide.screens.length - 1, next));
      if (target === position) return;
      position = target; render(); narrate(); screen.focus({ preventScroll: true });
    };
    const setMode = (next) => {
      if (next === mode) return;
      mode = next; root.dataset.mode = mode;
      screen.hidden = mode !== 'guide'; reading.hidden = mode !== 'reading';
      q('[data-practice-controls]').hidden = mode !== 'guide';
      audioButton.hidden = mode !== 'guide';
      for (const name of ['guide', 'reading']) q(`[data-action="${name}"]`).setAttribute('aria-pressed', String(mode === name));
      if (mode === 'guide') render();
      else root.querySelectorAll('[data-practice-jump]').forEach((item) => item.removeAttribute('aria-current'));
      syncIndex(); narrate();
    };
    root.addEventListener('click', async (event) => {
      const button = event.target.closest('button');
      if (!button || !root.contains(button)) return;
      if (button.hasAttribute('data-practice-jump')) {
        if (embedded && !fullscreen && !indexFits) { embeddedIndexOpen = false; syncIndex(); }
        if (mode === 'reading') {
          const target = reading.querySelector(`[data-practice-heading="${button.dataset.practiceSection}"]`);
          if (target) {
            target.scrollIntoView({ block: 'start', behavior: 'instant' });
            target.focus({ preventScroll: true });
            root.querySelectorAll('[data-practice-jump]').forEach((item) => {
              if (item === button) item.setAttribute('aria-current', 'location');
              else item.removeAttribute('aria-current');
            });
          }
        } else { go(Number(button.dataset.practiceJump)); }
        return;
      }
      switch (button.dataset.action) {
        case 'next': go(position + 1); break;
        case 'previous': go(position - 1); break;
        case 'guide': case 'reading': setMode(button.dataset.action); break;
        case 'audio':
          if (enabled) { disable(); status.textContent = ''; }
          else {
            audioOwner?.(); audioOwner = disable;
            document.querySelectorAll('audio, video').forEach((media) => media.pause());
            enabled = true; syncAudio(); narrate();
          }
          break;
        case 'index':
          if (embedded && !fullscreen) embeddedIndexOpen = index.hidden;
          else indexOpen = !indexOpen;
          syncIndex();
          if (index.hidden) indexToggle.focus({ preventScroll: true });
          break;
        case 'fullscreen':
          try {
            if (document.fullscreenElement === root) await document.exitFullscreen();
            else await root.requestFullscreen();
          } catch { status.textContent = 'El navegador no ha permitido abrir la pantalla completa.'; }
          break;
      }
    }, options);
    root.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && document.fullscreenElement === root) {
        document.exitFullscreen().catch(() => {});
        return;
      }
      if (event.key === 'Escape' && embedded && !fullscreen && !index.hidden) {
        embeddedIndexOpen = false; syncIndex(); indexToggle.focus({ preventScroll: true });
        return;
      }
      if (mode !== 'guide' || event.altKey || event.ctrlKey || event.metaKey || /INPUT|TEXTAREA|SELECT/.test(event.target.tagName)) return;
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault(); go(position + (event.key === 'ArrowRight' ? 1 : -1));
      }
    }, options);
    document.addEventListener('fullscreenchange', () => {
      const now = document.fullscreenElement === root;
      if (now === fullscreen) return;
      fullscreen = now; indexOpen = false;
      root.classList.toggle('practice-is-fullscreen', fullscreen);
      q('[data-fullscreen-enter]').hidden = fullscreen;
      q('[data-fullscreen-exit]').hidden = !fullscreen;
      q('[data-fullscreen-label]').textContent = fullscreen ? 'Salir de pantalla completa' : 'Pantalla completa';
      syncIndex();
      (fullscreen ? (mode === 'guide' ? screen : reading) : fullButton).focus({ preventScroll: true });
    }, options);
    // Leaving the page or starting another lesson medium must not leave an invisible narration playing.
    window.addEventListener('pagehide', disable, options);
    document.addEventListener('play', (event) => { if (/AUDIO|VIDEO/.test(event.target.tagName)) disable(); }, { ...options, capture: true });
    audio.addEventListener('error', () => {
      if (enabled && audio.getAttribute('src')) { disable(); status.textContent = 'El audio no está disponible. Puedes continuar leyendo.'; }
    }, options);
    audioButton.disabled = !guide.screens.every((s) => s.audio);
    if (audioButton.disabled) audioButton.title = 'La narración de esta práctica está pendiente de preparación.';
    fullButton.hidden = !document.fullscreenEnabled || typeof root.requestFullscreen !== 'function';
    root.dataset.mode = 'guide';
    root.dataset.practiceReady = 'true';
    q('[data-practice-toolbar]').hidden = false;
    q('[data-practice-controls]').hidden = false;
    screen.hidden = false; reading.hidden = true;
    render(); syncIndex();
    const resizeObserver = embedded ? new ResizeObserver(syncIndex) : null;
    resizeObserver?.observe(root);
    resizeObserver?.observe(q('.practice-viewer-main'));
    mounted.set(root, () => { disable(); controller.abort(); resizeObserver?.disconnect(); });
  });
}
