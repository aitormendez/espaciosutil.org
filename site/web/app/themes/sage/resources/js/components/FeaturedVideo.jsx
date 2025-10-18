import React, { useEffect, useRef, useCallback, useState } from 'react';
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
  DefaultTooltip,
} from '@vidstack/react/player/layouts/default';

const WideIcon = (props) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" {...props}>
    <rect x="3" y="6" width="18" height="12" rx="2" strokeWidth="1.5" />
    <rect x="7" y="10" width="10" height="4" stroke="none" fill="currentColor" />
  </svg>
);

const CenteredIcon = (props) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" {...props}>
    <rect x="6" y="5" width="12" height="14" rx="2" strokeWidth="1.5" />
    <rect x="9" y="9" width="6" height="6" stroke="none" fill="currentColor" />
  </svg>
);

const LayoutToggleButton = ({ isFullWidth, onToggle }) => {
  const label = isFullWidth
    ? 'Cambiar a vista centrada'
    : 'Cambiar a ancho completo';
  const Icon = isFullWidth ? CenteredIcon : WideIcon;

  return (
    <DefaultTooltip content={label} placement="top end">
      <button
        type="button"
        className="vds-button vds-layout-toggle-button"
        onClick={onToggle}
        aria-label={label}
        aria-pressed={!isFullWidth}
      >
        <Icon className="vds-icon" />
      </button>
    </DefaultTooltip>
  );
};

const FeaturedVideo = ({ videoId, videoLibraryId, pullZone, videoName }) => {
  const playerRef = useRef(null);
  const hlsUrl = `https://${pullZone}.b-cdn.net/${videoId}/playlist.m3u8`;
  const thumbnailUrl = `https://${pullZone}.b-cdn.net/${videoId}/thumbnail.jpg`;
  const lastSavedTime = useRef(0);
  const saveInterval = 5; // Save every 5 seconds
  const [videoSrc, setVideoSrc] = useState(hlsUrl);
  const [posterUrl, setPosterUrl] = useState(thumbnailUrl);
  const [subtitleTracks, setSubtitleTracks] = useState([]);
  const [isFullWidth, setIsFullWidth] = useState(true);
  const [isDesktop, setIsDesktop] = useState(() => {
    if (typeof window === 'undefined') return true;
    return window.matchMedia('(min-width: 768px)').matches;
  });

  useEffect(() => {
    if (!videoId || !videoLibraryId) {
      setSubtitleTracks([]);
      setVideoSrc(hlsUrl);
      setPosterUrl(thumbnailUrl);
      return undefined;
    }

    const controller = new AbortController();

    const fetchVideoMetadata = async () => {
      try {
        const response = await fetch(
          `/wp-json/espacio-sutil/v1/video-resolutions?library_id=${videoLibraryId}&video_id=${videoId}`,
          { signal: controller.signal }
        );

        if (!response.ok) {
          console.error('Failed to fetch Bunny metadata', await response.json());
          return;
        }

        const data = await response.json();

        if (data?.hlsUrl) {
          setVideoSrc(data.hlsUrl);
        } else {
          setVideoSrc(hlsUrl);
        }

        if (data?.thumbnailUrl) {
          setPosterUrl(data.thumbnailUrl);
        } else {
          setPosterUrl(thumbnailUrl);
        }

        if (Array.isArray(data?.captions)) {
          const sanitizedTracks = data.captions
            .filter(
              (track) =>
                typeof track?.lang === 'string' &&
                typeof track?.label === 'string' &&
                typeof track?.src === 'string'
            )
            .map((track) => ({
              lang: track.lang,
              label: track.label,
              src: track.src,
              default: Boolean(track.default),
            }));

          if (
            sanitizedTracks.length > 0 &&
            !sanitizedTracks.some((track) => track.default)
          ) {
            sanitizedTracks[0].default = true;
          }

          setSubtitleTracks(sanitizedTracks);
        } else {
          setSubtitleTracks([]);
        }
      } catch (error) {
        if (error.name !== 'AbortError') {
          console.error('Error fetching Bunny metadata:', error);
        }
        setVideoSrc(hlsUrl);
        setPosterUrl(thumbnailUrl);
        setSubtitleTracks([]);
      }
    };

    fetchVideoMetadata();

    return () => {
      controller.abort();
    };
  }, [hlsUrl, thumbnailUrl, videoId, videoLibraryId]);

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

  useEffect(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return undefined;

    const mediaQuery = window.matchMedia('(min-width: 768px)');

    const handleMatchChange = (event) => {
      const matches = event.matches;
      setIsDesktop(matches);
      if (!matches) {
        setIsFullWidth(true);
      }
    };

    handleMatchChange(mediaQuery);

    if (typeof mediaQuery.addEventListener === 'function') {
      mediaQuery.addEventListener('change', handleMatchChange);
      return () => {
        mediaQuery.removeEventListener('change', handleMatchChange);
      };
    }

    if (typeof mediaQuery.addListener === 'function') {
      mediaQuery.addListener(handleMatchChange);
      return () => {
        mediaQuery.removeListener(handleMatchChange);
      };
    }

    return undefined;
  }, []);

  const effectiveFullWidth = isDesktop ? isFullWidth : true;

  const playerClassName = `featured-video-player block w-full h-full rounded-md overflow-hidden transition-[max-width] duration-300 ease-in-out${
    effectiveFullWidth ? '' : ' mx-auto max-w-4xl'
  }`;

  const handleLayoutToggle = useCallback(() => {
    setIsFullWidth((prev) => !prev);
  }, []);

  return (
    <MediaPlayer
      className={playerClassName}
      title={videoName || 'Video Destacado'}
      src={videoSrc}
      aspectRatio="16/9"
      crossOrigin
      playsInline
      data-layout-mode={effectiveFullWidth ? 'wide' : 'narrow'}
      ref={playerRef}
    >
      <MediaProvider>
        {subtitleTracks.map((track, index) => (
          <Track
            key={`${track.lang}-${index}`}
            kind="subtitles"
            src={track.src}
            label={track.label}
            lang={track.lang}
            default={Boolean(track.default)}
          />
        ))}
      </MediaProvider>
      <Captions className="vds-captions" />
      <Poster
        className="absolute inset-0 block h-full w-full bg-black rounded-md opacity-0 transition-opacity data-[visible]:opacity-100 [&>img]:h-full [&>img]:w-full [&>img]:object-cover"
        src={posterUrl}
        alt="Video Thumbnail"
      />
      <DefaultVideoLayout
        icons={defaultLayoutIcons}
        slots={{
          afterFullscreenButton: isDesktop ? (
            <LayoutToggleButton
              isFullWidth={effectiveFullWidth}
              onToggle={handleLayoutToggle}
            />
          ) : undefined,
        }}
      />
    </MediaPlayer>
  );
};

export default FeaturedVideo;
