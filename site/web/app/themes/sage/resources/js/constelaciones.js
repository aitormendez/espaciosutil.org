import { tsParticles } from 'tsparticles-engine';
import { loadSlim } from 'tsparticles-slim';

loadSlim(tsParticles);

const CONTAINER_ID = 'tsparticles';

function getConstelacionesContainer() {
  return tsParticles
    .dom()
    .find((container) => container.id === CONTAINER_ID && !container.destroyed);
}

export function constelaciones() {
  if (getConstelacionesContainer()) {
    return;
  }

  tsParticles.load(CONTAINER_ID, {
    particles: {
      color: { value: '#ffffff' },
      links: { enable: true },
      move: { enable: true, speed: { min: 0.05, max: 0.3 } },
      number: { density: { enable: true, area: 800 }, value: 100 },
      shape: { type: 'circle' },
      size: { value: { min: 1, max: 4 } },
      zIndex: { value: { min: 0, max: 100 } },
    },
  });
}

export function destruirConstelaciones() {
  const container = getConstelacionesContainer();

  if (!container) {
    return;
  }

  container.destroy();
}
