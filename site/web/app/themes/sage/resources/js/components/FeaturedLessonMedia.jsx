import React, { useMemo, useState } from 'react';
import FeaturedVideo from './FeaturedVideo.jsx';
import FeaturedAudio from './FeaturedAudio.jsx';

const normalizeMediaEntry = (entry) => {
  if (!entry || typeof entry !== 'object') {
    return null;
  }

  const id =
    typeof entry.id === 'string' && entry.id.trim() ? entry.id.trim() : null;

  if (!id) {
    return null;
  }

  const libraryId =
    typeof entry.library_id === 'string' && entry.library_id.trim()
      ? entry.library_id.trim()
      : typeof entry.libraryId === 'string' && entry.libraryId.trim()
      ? entry.libraryId.trim()
      : '457097';

  const name =
    typeof entry.name === 'string' && entry.name.trim()
      ? entry.name.trim()
      : null;

  const chapters = Array.isArray(entry.chapters) ? entry.chapters : [];

  return {
    id,
    libraryId,
    name,
    chapters,
    defaultHlsUrl:
      typeof entry.default_hls_url === 'string' && entry.default_hls_url.trim()
        ? entry.default_hls_url.trim()
        : null,
    defaultPosterUrl:
      typeof entry.default_thumbnail_url === 'string' &&
      entry.default_thumbnail_url.trim()
        ? entry.default_thumbnail_url.trim()
        : null,
  };
};

const FeaturedLessonMedia = ({ video, audio, pullZone, lessonTitle }) => {
  const normalizedVideo = useMemo(() => normalizeMediaEntry(video), [video]);
  const normalizedAudio = useMemo(() => normalizeMediaEntry(audio), [audio]);

  const hasVideo = Boolean(normalizedVideo);
  const hasAudio = Boolean(normalizedAudio);

  const [activeMedia, setActiveMedia] = useState(() => {
    if (hasVideo) return 'video';
    if (hasAudio) return 'audio';
    return null;
  });

  if (!hasVideo && !hasAudio) {
    return null;
  }

  const showToggle = hasVideo && hasAudio;

  const handleSwitch = (mediaType) => () => {
    setActiveMedia(mediaType);
  };

  const renderVideo = activeMedia === 'video' && hasVideo;
  const renderAudio = activeMedia === 'audio' && hasAudio;

  return (
    <div className="featured-lesson-media w-full">
      {showToggle ? (
        <div className="mb-5 flex justify-center gap-3">
          <button
            type="button"
            className={`rounded-sm font-sans px-4 py-2 text-sm transition-colors ${
              renderVideo
                ? 'bg-morado1 text-morado5 shadow-lg shadow-morado1/40'
                : 'bg-morado4/60 text-morado1 hover:bg-morado4/80'
            }`}
            onClick={handleSwitch('video')}
            aria-pressed={renderVideo}
          >
            Ver video
          </button>
          <button
            type="button"
            className={`rounded-sm font-sans px-4 py-2 text-sm transition-colors ${
              renderAudio
                ? 'bg-morado1 text-morado5 shadow-lg shadow-morado1/40'
                : 'bg-morado4/60 text-morado1 hover:bg-morado4/80'
            }`}
            onClick={handleSwitch('audio')}
            aria-pressed={renderAudio}
          >
            Escuchar audio
          </button>
        </div>
      ) : null}

      <div className="relative flex w-full justify-center font-sans">
        {renderVideo ? (
          <FeaturedVideo
            key={`video-${normalizedVideo.id}`}
            videoId={normalizedVideo.id}
            videoLibraryId={normalizedVideo.libraryId}
            pullZone={pullZone}
            videoName={normalizedVideo.name}
            lessonTitle={lessonTitle}
            chapters={normalizedVideo.chapters}
            defaultHlsUrl={normalizedVideo.defaultHlsUrl}
            defaultPosterUrl={normalizedVideo.defaultPosterUrl}
          />
        ) : null}

        {renderAudio ? (
          <FeaturedAudio
            key={`audio-${normalizedAudio.id}`}
            audioId={normalizedAudio.id}
            audioLibraryId={normalizedAudio.libraryId}
            pullZone={pullZone}
            audioName={normalizedAudio.name}
            lessonTitle={lessonTitle}
            chapters={normalizedAudio.chapters}
            defaultHlsUrl={normalizedAudio.defaultHlsUrl}
            defaultPosterUrl={normalizedAudio.defaultPosterUrl}
          />
        ) : null}
      </div>
    </div>
  );
};

export default FeaturedLessonMedia;
