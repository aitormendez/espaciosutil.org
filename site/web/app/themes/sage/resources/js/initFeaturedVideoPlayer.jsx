import React from 'react';
import { createRoot } from 'react-dom/client';
import FeaturedLessonMedia from './components/FeaturedLessonMedia.jsx';

const initFeaturedVideoPlayer = () => {
  const container = document.getElementById('featured-lesson-media');

  if (!container) {
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
};

export default initFeaturedVideoPlayer;
