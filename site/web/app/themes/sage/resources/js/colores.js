import {gsap} from 'gsap';

// colores animados en hover para el bloque button
// Vale para cualquier estructura con un wrappeer '.colores-hover' y un 'a' dentro

export function coloresHover() {
  document.querySelectorAll('.colores-hover').forEach((item) => {
    const link = item.querySelector('a');

    link.startBg = gsap.to(link, {
      duration: '1',
      backgroundImage:
        'radial-gradient(random(100, 200)% random(50, 200)% at random(0, 100)% random(0, 100)%, rgba(0, 133, 255, 0.9) 0%, rgba(255, 255, 255, 0.3) 100%), radial-gradient(random(100, 200)% random(50, 200)% at random(0, 100)% random(0, 100)%, rgba(255, 245, 0, 0.9) 0%, rgba(255, 255, 255, 0.3) 100%), radial-gradient(random(100, 200)% random(50, 200)% at random(0, 100)% random(0, 100)%, rgba(0, 133, 255, 0.9) 0%, rgba(255, 255, 255, 0.3) 100%)',
      repeat: -1,
      repeatRefresh: true,
      paused: true,
    });

    link.stopBg = gsap.to(link, {
      duration: '1',
      backgroundImage: 'radial-gradient(transparent 100%, transparent 100%)',
      paused: true,
    });

    link.addEventListener('mouseenter', () => {
      link.startBg.restart();
      link.stopBg.pause();
    });

    link.addEventListener('mouseleave', () => {
      link.startBg.pause();
      link.stopBg.restart();
    });
  });
}
