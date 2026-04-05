import React from 'react';
import { createRoot } from 'react-dom/client';
import FeaturedLessonMedia from './components/FeaturedLessonMedia.jsx';

const initFeaturedVideoPlayer = () => {
  const containers = document.querySelectorAll('[data-media-props]');

  containers.forEach((container) => {
    if (container.dataset.mediaMounted === 'true') {
      return;
    }

    const rawProps = container.dataset.mediaProps;
    if (!rawProps) {
      return;
    }

    let parsedProps = null;

    try {
      parsedProps = JSON.parse(rawProps);
    } catch (error) {
      console.error('No se pudo parsear la configuración del reproductor.', error);
      return;
    }

    if (!parsedProps) {
      return;
    }

    const root = createRoot(container);
    root.render(<FeaturedLessonMedia {...parsedProps} />);
    container.dataset.mediaMounted = 'true';
  });
};

export default initFeaturedVideoPlayer;
