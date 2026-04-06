export const textureMap = {
  haumea: new URL('../textures/haumea-512.webp', import.meta.url).href,
  jupiter: new URL('../textures/jupiter-512.webp', import.meta.url).href,
  mars: new URL('../textures/mars-512.webp', import.meta.url).href,
  mercury: new URL('../textures/mercury-512.webp', import.meta.url).href,
  neptune: new URL('../textures/neptune-512.webp', import.meta.url).href,
  saturn: new URL('../textures/saturn-512.webp', import.meta.url).href,
  venus: new URL('../textures/venus-512.webp', import.meta.url).href,
};

export function getTexturePath(textureType) {
  return textureMap[textureType] ?? textureMap.mars;
}
