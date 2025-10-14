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
} from '@vidstack/react/player/layouts/default';

const FeaturedVideo = ({ videoId, videoLibraryId, pullZone, videoName }) => {
  const playerRef = useRef(null);
  const hlsUrl = `https://${pullZone}.b-cdn.net/${videoId}/playlist.m3u8`;
  const thumbnailUrl = `https://${pullZone}.b-cdn.net/${videoId}/thumbnail.jpg`;
  const lastSavedTime = useRef(0);
  const saveInterval = 5; // Save every 5 seconds
  const [videoSrc, setVideoSrc] = useState(hlsUrl);
  const [posterUrl, setPosterUrl] = useState(thumbnailUrl);
  const [subtitleTracks, setSubtitleTracks] = useState([]);

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

  return (
    <MediaPlayer
      className="w-full h-full rounded-md overflow-hidden"
      title={videoName || 'Video Destacado'}
      src={videoSrc}
      aspectRatio="16/9"
      crossOrigin
      playsInline
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
      <DefaultVideoLayout icons={defaultLayoutIcons} />
    </MediaPlayer>
  );
};

export default FeaturedVideo;
