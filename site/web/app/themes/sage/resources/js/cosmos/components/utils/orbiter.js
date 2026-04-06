function radians(degrees) {
  return (degrees * Math.PI) / 180;
}

function getPrimaryPosition(primary) {
  return primary?.current?.position ?? { x: 0, y: 0, z: 0 };
}

function isValidOrbitalPeriod(orbitalPeriod) {
  return Number.isFinite(orbitalPeriod) && orbitalPeriod > 0;
}

export class Orbiter {
  constructor() {
    this.orbitAngle = 0; // Ángulo formado por el radio de la órbita y el plano x.
    this.orbitAngleMod = 0.4; // Incremento/decremento del ángulo de la órbita.
    this.inclination = 0; // Inclinación orbital en grados.
    this.x = 0;
    this.y = 0;
    this.z = 0;
    this.initialPos = 0;
    this.speed = 1; // Factor de velocidad de la órbita.
  }

  setOrbitParameters(orbitAlt, initialPos, inclination, speed) {
    this.orbitAlt = orbitAlt;
    this.initialPos = initialPos;
    this.inclination = inclination;
    this.speed = speed;
  }

  orbit(primary, orbitalPeriod, delta = 1) {
    if (isValidOrbitalPeriod(orbitalPeriod)) {
      this.orbitAngle +=
        this.orbitAngleMod * this.speed * (delta / orbitalPeriod);
    }

    const primaryPosition = getPrimaryPosition(primary);

    // Aplica la inclinación orbital a las coordenadas x y z.
    const xOrbital = this.orbitAlt * Math.cos(radians(this.orbitAngle + this.initialPos));
    const zOrbital = this.orbitAlt * Math.sin(radians(this.orbitAngle + this.initialPos));
    const xInclined = xOrbital * Math.cos(radians(this.inclination));
    const zInclined = xOrbital * Math.sin(radians(this.inclination));

    this.y = primaryPosition.y + zInclined;
    this.x = primaryPosition.x + xInclined;
    this.z = primaryPosition.z + zOrbital;

    return {
      x: this.x,
      y: this.y,
      z: this.z,
      angle: this.orbitAngle,
    };
  }
}
