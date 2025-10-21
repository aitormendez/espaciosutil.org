import React from 'react';
import { createRoot } from 'react-dom/client';
import FeaturedVideo from './components/FeaturedVideo.jsx';

const initFeaturedVideoPlayer = () => {
  const featuredVideoContainer = document.getElementById('featured-video-player');
  if (featuredVideoContainer) {
    const videoId = featuredVideoContainer.dataset.videoId;
    const videoLibraryId = featuredVideoContainer.dataset.videoLibraryId;
    const pullZone = featuredVideoContainer.dataset.pullZone;
    const videoName = featuredVideoContainer.dataset.videoName;
    let chapters = [];

    if (featuredVideoContainer.dataset.videoChapters) {
      try {
        chapters = JSON.parse(featuredVideoContainer.dataset.videoChapters);
      } catch (error) {
        console.error('No se pudo parsear el subíndice de capítulos del video.', error);
      }
    }

    if (videoId && videoLibraryId && pullZone) {
      const root = createRoot(featuredVideoContainer);
      root.render(
        <FeaturedVideo
          videoId={videoId}
          videoLibraryId={videoLibraryId}
          pullZone={pullZone}
          videoName={videoName}
          chapters={chapters}
        />
      );
    }
  }
};

export default initFeaturedVideoPlayer;
