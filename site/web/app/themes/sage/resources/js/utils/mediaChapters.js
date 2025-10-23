/**
 * Convierte segundos a timestamp WebVTT.
 *
 * @param {number} totalSeconds
 * @returns {string}
 */
export const toVttTimestamp = (totalSeconds) => {
  const safeTotal = Math.max(
    0,
    Number.isFinite(totalSeconds) ? totalSeconds : 0
  );
  const hours = Math.floor(safeTotal / 3600);
  const minutes = Math.floor((safeTotal % 3600) / 60);
  const seconds = Math.floor(safeTotal % 60);

  return `${hours.toString().padStart(2, '0')}:${minutes
    .toString()
    .padStart(2, '0')}:${seconds.toString().padStart(2, '0')}.000`;
};

/**
 * Genera el contenido WebVTT para una pista de capítulos.
 *
 * @param {Array} chapters
 * @returns {string|null}
 */
export const buildChaptersVtt = (chapters) => {
  if (!Array.isArray(chapters) || chapters.length === 0) {
    return null;
  }

  const sortedChapters = [...chapters].sort(
    (a, b) => (a?.time ?? 0) - (b?.time ?? 0)
  );

  const lines = ['WEBVTT', ''];

  sortedChapters.forEach((chapter, index) => {
    const startSeconds = Math.max(0, Number(chapter?.time ?? 0));
    const nextChapter = sortedChapters[index + 1];
    const nextStart = nextChapter
      ? Math.max(startSeconds, Number(nextChapter.time ?? startSeconds + 1))
      : startSeconds + 1;
    const endSeconds =
      nextStart <= startSeconds ? startSeconds + 1 : nextStart;
    const title =
      typeof chapter?.title === 'string' && chapter.title.trim()
        ? chapter.title.trim()
        : `Capítulo ${index + 1}`;

    lines.push(
      `${toVttTimestamp(startSeconds)} --> ${toVttTimestamp(endSeconds)}`
    );
    lines.push(title);
    lines.push('');
  });

  return lines.join('\n');
};
