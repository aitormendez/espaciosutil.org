// https://chat.openai.com/c/dbf7c030-265c-4ff1-bcde-e5e53cdb0013

Math.radians = function (degrees) {
  return (degrees * Math.PI) / 180;
};

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

  orbit(primary, orbitalPeriod) {
    // Calcula el ángulo actual de la órbita teniendo en cuenta la velocidad.
    this.orbitAngle += this.orbitAngleMod * this.speed * (1 / orbitalPeriod);

    // Calcula el ángulo actual de la órbita teniendo en cuenta la velocidad.
    this.orbitAngle += this.orbitAngleMod * this.speed * (1 / orbitalPeriod);

    // Aplica la inclinación orbital a las coordenadas x y z.
    const xOrbital = this.orbitAlt * Math.cos(Math.radians(this.orbitAngle + this.initialPos));
    const zOrbital = this.orbitAlt * Math.sin(Math.radians(this.orbitAngle + this.initialPos));
    const xInclined = xOrbital * Math.cos(Math.radians(this.inclination));
    const zInclined = xOrbital * Math.sin(Math.radians(this.inclination));

    this.y = primary.current.position.y + zInclined;
    this.x = primary.current.position.x + xInclined;
    this.z = primary.current.position.z + zOrbital;

    return {
      x: this.x,
      y: this.y,
      z: this.z,
      angle: this.orbitAngle,
    };
  }
}
