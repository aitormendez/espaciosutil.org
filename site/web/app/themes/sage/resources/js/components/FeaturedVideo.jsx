import React, { useEffect, useRef, useCallback } from 'react';
import {
  MediaPlayer,
  MediaProvider,
  Poster,
  Captions,
  Track,
} from '@vidstack/react';
import '@vidstack/react/player/styles/base.css';
import '@vidstack/react/player/styles/default/theme.css';
import '@vidstack/react/player/styles/default/layouts/video.css';
import {
  DefaultVideoLayout,
  defaultLayoutIcons,
} from '@vidstack/react/player/layouts/default';

const FeaturedVideo = ({ videoId, videoLibraryId, pullZone, videoName }) => {
  const playerRef = useRef(null);
  const hlsUrl = `https://${pullZone}.b-cdn.net/${videoId}/playlist.m3u8`;
  const thumbnailUrl = `https://${pullZone}.b-cdn.net/${videoId}/thumbnail.jpg`;
  const lastSavedTime = useRef(0);
  const saveInterval = 5; // Save every 5 seconds

  const saveVideoProgress = useCallback(
    async (currentTime) => {
      try {
        const response = await fetch(
          '/wp-json/espacio-sutil/v1/video-progress',
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': window.wpApiSettings?.nonce,
            },
            credentials: 'include',
            body: JSON.stringify({
              video_id: videoId,
              progress: currentTime,
            }),
          }
        );
        if (!response.ok) {
          console.error('Failed to save video progress', await response.json());
        }
      } catch (error) {
        console.error('Error saving video progress:', error);
      }
    },
    [videoId]
  );

  useEffect(() => {
    const player = playerRef.current;
    if (!player) return;

    const fetchVideoProgress = async () => {
      try {
        const response = await fetch(
          `/wp-json/espacio-sutil/v1/video-progress?video_id=${videoId}`,
          {
            headers: {
              'X-WP-Nonce': window.wpApiSettings?.nonce,
            },
            credentials: 'include',
          }
        );
        if (response.ok) {
          const data = await response.json();
          if (data.progress > 0) {
            player.currentTime = data.progress;
            lastSavedTime.current = data.progress;
          }
        } else {
          console.error(
            'Failed to fetch video progress',
            await response.json()
          );
        }
      } catch (error) {
        console.error('Error fetching video progress:', error);
      }
    };

    fetchVideoProgress();

    const handleTimeUpdate = () => {
      const currentTime = player.currentTime;
      if (Math.abs(currentTime - lastSavedTime.current) >= saveInterval) {
        saveVideoProgress(currentTime);
        lastSavedTime.current = currentTime;
      }
    };

    const handlePause = () => {
      saveVideoProgress(player.currentTime);
    };

    const handleEnded = () => {
      saveVideoProgress(0); // Reset progress when video ends
    };

    player.addEventListener('timeupdate', handleTimeUpdate);
    player.addEventListener('pause', handlePause);
    player.addEventListener('ended', handleEnded);

    return () => {
      player.removeEventListener('timeupdate', handleTimeUpdate);
      player.removeEventListener('pause', handlePause);
      player.removeEventListener('ended', handleEnded);
      // Save final progress when component unmounts or player is destroyed
      saveVideoProgress(player.currentTime);
    };
  }, [videoId, saveVideoProgress, saveInterval]);

  return (
    <MediaPlayer
      className="w-full h-full rounded-md overflow-hidden"
      title={videoName || 'Video Destacado'}
      src={hlsUrl}
      aspectRatio="16/9"
      crossOrigin
      playsInline
      ref={playerRef}
    >
      <MediaProvider>
        {[
          { lang: 'es', label: 'Español' },
          { lang: 'en', label: 'English' },
          { lang: 'fr', label: 'Français' },
          { lang: 'de', label: 'Deutsch' },
          { lang: 'it', label: 'Italiano' },
          { lang: 'ru', label: 'Русский' },
          { lang: 'zh', label: '中文（简体）' },
        ].map(({ lang, label }, i) => (
          <Track
            key={lang}
            kind="subtitles"
            src={`https://${pullZone}.b-cdn.net/${videoId}/captions/${lang}.vtt`}
            label={label}
            lang={lang}
            default={i === 0}
          />
        ))}
      </MediaProvider>
      <Captions className="vds-captions" />
      <Poster
        className="absolute inset-0 block h-full w-full bg-black rounded-md opacity-0 transition-opacity data-[visible]:opacity-100 [&>img]:h-full [&>img]:w-full [&>img]:object-cover"
        src={thumbnailUrl}
        alt="Video Thumbnail"
      />
      <DefaultVideoLayout icons={defaultLayoutIcons} />
    </MediaPlayer>
  );
};

export default FeaturedVideo;
