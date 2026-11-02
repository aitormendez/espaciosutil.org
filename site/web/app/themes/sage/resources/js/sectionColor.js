/** Resolve token references before GSAP parses/interpolates a background color. */
export function resolveSectionColor(color, readToken) {
  const value = typeof color === 'string' ? color.trim() : '';
  const token = /^var\((--[a-zA-Z0-9-]+)\)$/.exec(value);

  return token ? readToken(token[1]).trim() : value;
}
