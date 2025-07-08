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

    if (videoId && videoLibraryId && pullZone) {
      const root = createRoot(featuredVideoContainer);
      root.render(<FeaturedVideo videoId={videoId} videoLibraryId={videoLibraryId} pullZone={pullZone} videoName={videoName} />);
    }
  }
};

export default initFeaturedVideoPlayer;
