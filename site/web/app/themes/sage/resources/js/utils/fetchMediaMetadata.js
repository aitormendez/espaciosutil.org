const BASE_ENDPOINT = '/wp-json/espacio-sutil/v1/video-resolutions';

/**
 * Recupera metadatos de Bunny Stream para un asset de audio o video.
 *
 * @param {Object} options
 * @param {string} options.mediaId
 * @param {string} [options.libraryId]
 * @param {AbortSignal} [options.signal]
 * @returns {Promise<Object>}
 */
export async function fetchMediaMetadata({ mediaId, libraryId, signal } = {}) {
  if (!mediaId) {
    throw new Error('fetchMediaMetadata requiere mediaId');
  }

  const params = new URLSearchParams({ video_id: mediaId });

  if (libraryId) {
    params.set('library_id', libraryId);
  }

  const response = await fetch(`${BASE_ENDPOINT}?${params.toString()}`, {
    signal,
  });

  if (!response.ok) {
    let details = null;

    try {
      details = await response.json();
    } catch (error) {
      // Ignoramos: el cuerpo no siempre es JSON válido.
    }

    const error = new Error('No se pudo obtener la metadata del medio.');
    error.status = response.status;
    error.details = details;
    throw error;
  }

  const payload = await response.json();

  return {
    hlsUrl:
      typeof payload?.hlsUrl === 'string' && payload.hlsUrl.trim()
        ? payload.hlsUrl.trim()
        : null,
    thumbnailUrl:
      typeof payload?.thumbnailUrl === 'string' && payload.thumbnailUrl.trim()
        ? payload.thumbnailUrl.trim()
        : null,
    captions: Array.isArray(payload?.captions) ? payload.captions : [],
    mediaKind: payload?.mediaKind === 'audio' ? 'audio' : 'video',
    length:
      typeof payload?.length === 'number' && Number.isFinite(payload.length)
        ? payload.length
        : null,
    title:
      typeof payload?.title === 'string' && payload.title.trim()
        ? payload.title.trim()
        : null,
  };
}
