import React, { useEffect, useRef, useState, useCallback } from 'react';
import { MediaPlayer, MediaProvider, Track } from '@vidstack/react';
import '@vidstack/react/player/styles/base.css';
import '@vidstack/react/player/styles/default/theme.css';
import '@vidstack/react/player/styles/default/layouts/audio.css';
import {
  DefaultAudioLayout,
  defaultLayoutIcons,
} from '@vidstack/react/player/layouts/default';
import { fetchMediaMetadata } from '../utils/fetchMediaMetadata.js';
import { buildChaptersVtt } from '../utils/mediaChapters.js';

const DEFAULT_AUDIO_TITLE = 'Audio destacado';

const FeaturedAudio = ({
  audioId,
  audioLibraryId,
  pullZone,
  audioName,
  lessonTitle,
  chapters = [],
  defaultHlsUrl,
  defaultPosterUrl,
}) => {
  const playerRef = useRef(null);
  const fallbackHlsUrl =
    typeof defaultHlsUrl === 'string' && defaultHlsUrl.trim()
      ? defaultHlsUrl.trim()
      : pullZone
        ? `https://${pullZone}.b-cdn.net/${audioId}/playlist.m3u8`
        : '';
  const fallbackPosterUrl =
    typeof defaultPosterUrl === 'string' && defaultPosterUrl.trim()
      ? defaultPosterUrl.trim()
      : pullZone
        ? `https://${pullZone}.b-cdn.net/${audioId}/thumbnail.jpg`
        : '';
  const [audioSrc, setAudioSrc] = useState(fallbackHlsUrl);
  const [posterUrl, setPosterUrl] = useState(fallbackPosterUrl);
  const [chapterTrackUrl, setChapterTrackUrl] = useState(null);
  const lastSavedTime = useRef(0);
  const saveInterval = 5;

  const resolvedTitle =
    (typeof audioName === 'string' && audioName.trim()
      ? audioName.trim()
      : typeof lessonTitle === 'string' && lessonTitle.trim()
        ? `${lessonTitle.trim()} · Audio`
        : DEFAULT_AUDIO_TITLE);

  useEffect(() => {
    setAudioSrc(fallbackHlsUrl);
    setPosterUrl(fallbackPosterUrl);
  }, [fallbackHlsUrl, fallbackPosterUrl]);

  useEffect(() => {
    if (!audioId || !audioLibraryId) {
      setAudioSrc(fallbackHlsUrl);
      setPosterUrl(fallbackPosterUrl);
      return undefined;
    }

    const controller = new AbortController();

    const fetchMetadata = async () => {
      try {
        const mediaMetadata = await fetchMediaMetadata({
          mediaId: audioId,
          libraryId: audioLibraryId,
          signal: controller.signal,
        });

        setAudioSrc(mediaMetadata?.hlsUrl ?? fallbackHlsUrl);
        setPosterUrl(mediaMetadata?.thumbnailUrl ?? fallbackPosterUrl);
      } catch (error) {
        if (error.name !== 'AbortError') {
          console.error('Error fetching Bunny metadata (audio):', error);
        }
        setAudioSrc(fallbackHlsUrl);
        setPosterUrl(fallbackPosterUrl);
      }
    };

    fetchMetadata();

    return () => {
      controller.abort();
    };
  }, [audioId, audioLibraryId, fallbackHlsUrl, fallbackPosterUrl]);

  const saveAudioProgress = useCallback(
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
              video_id: audioId,
              progress: currentTime,
            }),
          }
        );
        if (!response.ok) {
          console.error(
            'Failed to save audio progress',
            await response.json()
          );
        }
      } catch (error) {
        console.error('Error saving audio progress:', error);
      }
    },
    [audioId]
  );

  useEffect(() => {
    const player = playerRef.current;
    if (!player) return;

    const fetchAudioProgress = async () => {
      try {
        const response = await fetch(
          `/wp-json/espacio-sutil/v1/video-progress?video_id=${audioId}`,
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
            'Failed to fetch audio progress',
            await response.json()
          );
        }
      } catch (error) {
        console.error('Error fetching audio progress:', error);
      }
    };

    fetchAudioProgress();

    const handleTimeUpdate = () => {
      const currentTime = player.currentTime;
      if (Math.abs(currentTime - lastSavedTime.current) >= saveInterval) {
        saveAudioProgress(currentTime);
        lastSavedTime.current = currentTime;
      }
    };

    const handlePause = () => {
      saveAudioProgress(player.currentTime);
    };

    const handleEnded = () => {
      saveAudioProgress(0);
    };

    player.addEventListener('timeupdate', handleTimeUpdate);
    player.addEventListener('pause', handlePause);
    player.addEventListener('ended', handleEnded);

    return () => {
      player.removeEventListener('timeupdate', handleTimeUpdate);
      player.removeEventListener('pause', handlePause);
      player.removeEventListener('ended', handleEnded);
      saveAudioProgress(player.currentTime);
    };
  }, [audioId, saveAudioProgress, saveInterval]);

  useEffect(() => {
    const handleSeekClick = (event) => {
      const target = event.target.closest('[data-video-seek]');
      if (!target) {
        return;
      }

      const seekTo = Number(target.dataset.videoSeek);
      if (!Number.isFinite(seekTo)) {
        return;
      }

      const playerElement = playerRef.current;
      if (!playerElement) {
        return;
      }

      playerElement.currentTime = Math.max(0, seekTo);
      if (typeof playerElement.play === 'function') {
        const playPromise = playerElement.play();
        if (playPromise && typeof playPromise.catch === 'function') {
          playPromise.catch(() => {});
        }
      }
    };

    document.addEventListener('click', handleSeekClick);

    return () => {
      document.removeEventListener('click', handleSeekClick);
    };
  }, []);

  useEffect(() => {
    if (!Array.isArray(chapters) || chapters.length === 0) {
      setChapterTrackUrl((prev) => {
        if (prev) {
          URL.revokeObjectURL(prev);
        }
        return null;
      });
      return undefined;
    }

    const vttContent = buildChaptersVtt(chapters);

    if (!vttContent) {
      setChapterTrackUrl((prev) => {
        if (prev) {
          URL.revokeObjectURL(prev);
        }
        return null;
      });
      return undefined;
    }

    const blob = new Blob([vttContent], { type: 'text/vtt' });
    const nextUrl = URL.createObjectURL(blob);

    setChapterTrackUrl((prev) => {
      if (prev) {
        URL.revokeObjectURL(prev);
      }
      return nextUrl;
    });

    return () => {
      URL.revokeObjectURL(nextUrl);
    };
  }, [chapters]);

  const resolvedSrc =
    audioSrc && typeof audioSrc === 'string'
      ? { src: audioSrc, type: 'application/x-mpegURL' }
      : null;

  return (
    <div className="featured-audio-player w-full max-w-3xl rounded-lg bg-morado4/60 p-6 shadow-lg shadow-morado4/40">
      {posterUrl ? (
        <div className="mb-4 flex justify-center">
          <img
            src={posterUrl}
            alt=""
            role="presentation"
            className="h-40 w-40 rounded-full object-cover shadow-inner shadow-black/40"
          />
        </div>
      ) : null}
      <MediaPlayer
        className="w-full"
        title={resolvedTitle}
        src={resolvedSrc ?? undefined}
        ref={playerRef}
        crossOrigin
        playsInline
        load="eager"
        viewType="audio"
        streamType="on-demand"
      >
        <MediaProvider>
          {chapterTrackUrl ? (
            <Track kind="chapters" src={chapterTrackUrl} label="Subíndice" default />
          ) : null}
        </MediaProvider>
        <DefaultAudioLayout icons={defaultLayoutIcons} />
      </MediaPlayer>
    </div>
  );
};

export default FeaturedAudio;
