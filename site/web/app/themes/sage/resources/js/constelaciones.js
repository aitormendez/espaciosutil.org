import { tsParticles } from 'tsparticles-engine';
import { loadSlim } from 'tsparticles-slim';

loadSlim(tsParticles);

export function constelaciones() {
  tsParticles.load('tsparticles', {
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
