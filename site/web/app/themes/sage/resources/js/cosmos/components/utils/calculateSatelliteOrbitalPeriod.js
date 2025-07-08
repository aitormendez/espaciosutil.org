const G = 6.6743e-11; // Valor de la constante de gravitación universal en m^3/kg/s^2

export function calculateSatelliteOrbitalPeriod(primary, orbitAlt) {
  // Calcula la distancia al centro del objeto primario
  const radius = primary.current.scale.x / 2;
  const distanceToPrimary = radius + orbitAlt;

  // Calcula el período orbital de acuerdo con la tercera ley de Kepler.
  // Ahora, el período es inversamente proporcional a la altura orbital.
  // Puedes ajustar el factor de escala (0.000001) para controlar la velocidad.
  const orbitalPeriod = (2 * Math.PI * Math.sqrt(Math.pow(distanceToPrimary, 3) / G) * 0.000003) / orbitAlt;

  return orbitalPeriod;
}
