import { gsap } from 'gsap';

const HOVER_CONTAINER_SELECTOR = '.colores-hover';
const SELF_HOVER_SELECTOR = '.colores-hover-self';
const ALWAYS_ON_SELECTOR = '.colores-bg';
const TARGET_SELECTOR = '[data-colores-target]';
const TARGET_CLASS = 'colores-target';
const LAYER_CLASS = 'colores-layer';
const COLOR_CYCLE_DURATION = 1;
const COLOR_FADE_IN_DURATION = 1;
const COLOR_FADE_OUT_DURATION = 0.5;

const colorStates = new WeakMap();
let delegationBound = false;

function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function randomInt(min, max) {
  return gsap.utils.random(min, max, 1);
}

function buildAnimatedGradient() {
  return [
    `radial-gradient(${randomInt(100, 200)}% ${randomInt(50, 200)}% at ${randomInt(0, 100)}% ${randomInt(0, 100)}%, rgba(0, 133, 255, 0.9) 0%, rgba(255, 255, 255, 0.3) 100%)`,
    `radial-gradient(${randomInt(100, 200)}% ${randomInt(50, 200)}% at ${randomInt(0, 100)}% ${randomInt(0, 100)}%, rgba(255, 245, 0, 0.9) 0%, rgba(255, 255, 255, 0.3) 100%)`,
    `radial-gradient(${randomInt(100, 200)}% ${randomInt(50, 200)}% at ${randomInt(0, 100)}% ${randomInt(0, 100)}%, rgba(0, 133, 255, 0.9) 0%, rgba(255, 255, 255, 0.3) 100%)`,
  ].join(', ');
}

function ensureLayer(element) {
  const existing = element.querySelector(`:scope > .${LAYER_CLASS}`);
  if (existing) {
    return existing;
  }

  const layer = document.createElement('span');
  layer.className = LAYER_CLASS;
  layer.setAttribute('aria-hidden', 'true');

  if (element.firstChild) {
    element.insertBefore(layer, element.firstChild);
  } else {
    element.appendChild(layer);
  }

  return layer;
}

function ensureState(element) {
  const existingState = colorStates.get(element);
  if (existingState) {
    return existingState;
  }

  element.classList.add(TARGET_CLASS);
  const layer = ensureLayer(element);
  const initialGradient = buildAnimatedGradient();

  gsap.set(layer, {
    opacity: 0,
    backgroundImage: initialGradient,
  });

  const state = {
    alwaysOn: false,
    reducedMotion: prefersReducedMotion(),
    activeCount: 0,
    running: false,
    layer,
    currentGradient: initialGradient,
    loopBg: null,
    visibility: null,
  };

  if (!state.reducedMotion) {
    state.loopBg = gsap.to(layer, {
      duration: COLOR_CYCLE_DURATION,
      backgroundImage: () => {
        const nextGradient = buildAnimatedGradient();
        state.currentGradient = nextGradient;
        return nextGradient;
      },
      ease: 'none',
      repeat: -1,
      repeatRefresh: true,
      paused: true,
    });
  }

  const fadeDuration = state.reducedMotion ? 0 : COLOR_FADE_IN_DURATION;
  state.visibility = gsap.to(layer, {
    duration: fadeDuration,
    opacity: 1,
    ease: 'power1.out',
    paused: true,
    onReverseComplete: () => {
      const currentState = colorStates.get(element);
      if (
        !currentState ||
        currentState.reducedMotion ||
        currentState.alwaysOn ||
        currentState.activeCount > 0 ||
        !currentState.loopBg
      ) {
        return;
      }

      currentState.loopBg.pause();
      currentState.running = false;
    },
  });

  colorStates.set(element, state);
  return state;
}

function activateColor(element) {
  const state = ensureState(element);
  if (state.alwaysOn) {
    return;
  }

  state.activeCount += 1;
  if (state.activeCount > 1) {
    return;
  }

  if (state.reducedMotion) {
    gsap.set(state.layer, {
      backgroundImage: state.currentGradient,
      opacity: 1,
    });
    return;
  }

  if (state.loopBg && !state.running) {
    state.loopBg.play();
    state.running = true;
  }

  state.visibility.timeScale(1);
  state.visibility.play();
}

function deactivateColor(element) {
  const state = ensureState(element);
  if (state.alwaysOn) {
    return;
  }

  if (state.activeCount > 0) {
    state.activeCount -= 1;
  }

  if (state.activeCount > 0) {
    return;
  }

  if (state.reducedMotion) {
    gsap.set(state.layer, { opacity: 0 });
    return;
  }

  const reverseScale =
    COLOR_FADE_OUT_DURATION > 0
      ? COLOR_FADE_IN_DURATION / COLOR_FADE_OUT_DURATION
      : 1;

  state.visibility.timeScale(reverseScale);
  state.visibility.reverse();
}

function setAlwaysOn(element) {
  const state = ensureState(element);
  if (state.alwaysOn) {
    return;
  }

  state.alwaysOn = true;
  state.activeCount = 1;

  if (state.reducedMotion) {
    gsap.set(state.layer, {
      backgroundImage: state.currentGradient,
      opacity: 1,
    });
    return;
  }

  if (state.loopBg && !state.running) {
    state.loopBg.play();
    state.running = true;
  }

  state.visibility.timeScale(1);
  state.visibility.play();
}

function resolveLegacyTarget(container) {
  return (
    container.querySelector(TARGET_SELECTOR) ?? container.querySelector('a')
  );
}

function toElement(node) {
  return node instanceof Element ? node : null;
}

function getHoverTarget(node) {
  const element = toElement(node);
  if (!element) {
    return null;
  }

  const selfTarget = element.closest(SELF_HOVER_SELECTOR);
  if (selfTarget) {
    return selfTarget;
  }

  const container = element.closest(HOVER_CONTAINER_SELECTOR);
  if (!container) {
    return null;
  }

  const target = resolveLegacyTarget(container);
  if (!target || !target.contains(element)) {
    return null;
  }

  return target;
}

function handlePointerOver(event) {
  const target = getHoverTarget(event.target);
  if (!target || target.contains(toElement(event.relatedTarget))) {
    return;
  }

  activateColor(target);
}

function handlePointerOut(event) {
  const target = getHoverTarget(event.target);
  if (!target || target.contains(toElement(event.relatedTarget))) {
    return;
  }

  deactivateColor(target);
}

function handleFocusIn(event) {
  const target = getHoverTarget(event.target);
  if (target) {
    activateColor(target);
  }
}

function handleFocusOut(event) {
  const target = getHoverTarget(event.target);
  if (target) {
    deactivateColor(target);
  }
}

function bindDelegatedEventsOnce() {
  if (delegationBound) {
    return;
  }

  delegationBound = true;
  document.addEventListener('pointerover', handlePointerOver);
  document.addEventListener('pointerout', handlePointerOut);
  document.addEventListener('focusin', handleFocusIn);
  document.addEventListener('focusout', handleFocusOut);
}

function initHoverTargets() {
  document.querySelectorAll(SELF_HOVER_SELECTOR).forEach((element) => {
    ensureState(element);
  });

  document.querySelectorAll(HOVER_CONTAINER_SELECTOR).forEach((container) => {
    const target = resolveLegacyTarget(container);
    if (!target) {
      return;
    }

    ensureState(target);
  });
}

function initAlwaysOnTargets() {
  document.querySelectorAll(ALWAYS_ON_SELECTOR).forEach((element) => {
    setAlwaysOn(element);
  });
}

export function coloresHover() {
  bindDelegatedEventsOnce();
  initHoverTargets();
  initAlwaysOnTargets();
}
