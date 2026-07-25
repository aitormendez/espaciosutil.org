export const AUDIO_DESKTOP_MEDIA_QUERY = '(min-width: 768px)';

export const resolveFeaturedAudioLayout = (isDesktop) =>
  isDesktop ? 'desktop' : 'mobile';
