export function getCosmosQualityProfile({
  devicePixelRatio = 1,
  hardwareConcurrency = 4,
  prefersReducedMotion = false,
  maxTouchPoints = 0,
} = {}) {
  const reducedMotion = Boolean(prefersReducedMotion);
  const lowCpu = hardwareConcurrency > 0 && hardwareConcurrency <= 4;
  const touchFirst = maxTouchPoints > 0;
  const constrained = reducedMotion || lowCpu || touchFirst;

  return {
    dpr: constrained ? [1, 1.1] : [1, 1.5],
    enableEffects: !constrained,
    multisampling: constrained ? 0 : 4,
  };
}

export function getRuntimeCosmosQualityProfile() {
  if (typeof window === 'undefined') {
    return getCosmosQualityProfile();
  }

  return getCosmosQualityProfile({
    devicePixelRatio: window.devicePixelRatio ?? 1,
    hardwareConcurrency: navigator.hardwareConcurrency ?? 4,
    prefersReducedMotion:
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    maxTouchPoints: navigator.maxTouchPoints ?? 0,
  });
}
