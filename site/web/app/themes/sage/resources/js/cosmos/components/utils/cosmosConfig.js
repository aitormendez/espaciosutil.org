const BASE_PLANET_DEFINITIONS = [
  {
    key: 'terapias',
    name: 'planetTerapias',
    scale: 0.3,
    textureType: 'venus',
    linkTo: 'orientacion-terapeutica',
    linkToLabel: 'Acompañamiento terapéutico',
    orbitAlt: 3.5,
    epigrafe: 'Terapia',
    getTitle: (item) => item.title.rendered,
  },
  {
    key: 'divulgaciones',
    name: 'planetDivulgaciones',
    scale: 0.3,
    textureType: 'haumea',
    linkTo: 'divulgacion',
    linkToLabel: 'Divulgación',
    orbitAlt: 1.5,
  },
  {
    key: 'reveladores',
    name: 'planetReveladores',
    scale: 0.5,
    textureType: 'mars',
    linkTo: 'reveladores',
    linkToLabel: 'Reveladores',
    orbitAlt: 3,
    epigrafe: 'Revelador',
    getTitle: (item) => item.name,
  },
  {
    key: 'noticias',
    name: 'planetNoticias',
    scale: 0.5,
    textureType: 'neptune',
    linkTo: 'noticias',
    linkToLabel: 'Noticias',
    orbitAlt: 2,
    epigrafe: 'Noticia',
    getTitle: (item) => item.title.rendered,
  },
  {
    key: 'formaciones',
    name: 'planetFormaciones',
    scale: 0.7,
    textureType: 'jupiter',
    linkTo: 'formacion',
    linkToLabel: 'Formación',
    orbitAlt: 4,
    epigrafe: 'Formación',
    getTitle: (item) => item.title.rendered,
  },
];

const PLANET_MOTION_PROFILES = {
  planetTerapias: { speedMin: 90, speedMax: 135 },
  planetDivulgaciones: { speedMin: 155, speedMax: 230 },
  planetReveladores: { speedMin: 105, speedMax: 155 },
  planetNoticias: { speedMin: 130, speedMax: 195 },
  planetFormaciones: { speedMin: 95, speedMax: 140 },
};

const PLANET_ROTATIONS = {
  planetTerapias: { rotationX: 0.14, rotationY: 0.11, rotationZ: 0 },
  planetDivulgaciones: { rotationX: 0.09, rotationY: 0.15, rotationZ: 0 },
  planetReveladores: { rotationX: 0.12, rotationY: 0.08, rotationZ: 0 },
  planetNoticias: { rotationX: 0.07, rotationY: 0.13, rotationZ: 0 },
  planetFormaciones: { rotationX: 0, rotationY: -0.2, rotationZ: 90 },
};

function xmur3(value) {
  let hash = 1779033703 ^ value.length;

  for (let index = 0; index < value.length; index += 1) {
    hash = Math.imul(hash ^ value.charCodeAt(index), 3432918353);
    hash = (hash << 13) | (hash >>> 19);
  }

  return () => {
    hash = Math.imul(hash ^ (hash >>> 16), 2246822507);
    hash = Math.imul(hash ^ (hash >>> 13), 3266489909);

    return (hash ^= hash >>> 16) >>> 0;
  };
}

function mulberry32(seed) {
  let value = seed;

  return () => {
    value += 0x6d2b79f5;

    let next = Math.imul(value ^ (value >>> 15), value | 1);
    next ^= next + Math.imul(next ^ (next >>> 7), next | 61);

    return ((next ^ (next >>> 14)) >>> 0) / 4294967296;
  };
}

function createRandomGenerator(seed) {
  const value = String(seed);
  const seedFactory = xmur3(value);

  return mulberry32(seedFactory());
}

function randomBetween(rng, min, max) {
  return min + rng() * (max - min);
}

function randomIntegerBetween(rng, min, max) {
  return Math.round(randomBetween(rng, min, max));
}

export function createSessionSeed() {
  if (
    typeof window !== 'undefined' &&
    window.crypto &&
    typeof window.crypto.randomUUID === 'function'
  ) {
    return window.crypto.randomUUID();
  }

  return `cosmos-${Date.now()}-${Math.random()}`;
}

export function buildPlanetConfigs(seed) {
  const rng = createRandomGenerator(seed);

  return BASE_PLANET_DEFINITIONS.map((planet) => {
    const profile = PLANET_MOTION_PROFILES[planet.name];

    return {
      ...planet,
      initialPos: randomIntegerBetween(rng, 0, 360),
      inclination: randomIntegerBetween(rng, 0, 180),
      speed: randomIntegerBetween(rng, profile.speedMin, profile.speedMax),
    };
  });
}

export function getPlanetRotationConfig(name) {
  return PLANET_ROTATIONS[name] ?? PLANET_ROTATIONS.planetNoticias;
}

export function getSatelliteMotionConfig(
  item,
  index,
  sessionSeed,
  orbitName = 'satellite'
) {
  const rng = createRandomGenerator(
    `${sessionSeed}:${orbitName}:${item?.id ?? index}:${index}`
  );

  return {
    orbitAlt: randomBetween(rng, 0.72, 1.45),
    initialPos: randomIntegerBetween(rng, 0, 360),
    inclination: randomIntegerBetween(rng, 0, 180),
    speed: randomIntegerBetween(rng, 145, 280),
    rotationX: randomBetween(rng, 0.09, 0.22),
    rotationY: randomBetween(rng, 0.08, 0.21),
  };
}
