const G = 6.6743e-11; // Valor de la constante de gravitación universal en m^3/kg/s^2

export function calculateOrbitalPeriod(primary, orbitAlt) {
  // Calcula la distancia al centro del objeto primario
  const radius = primary.current.scale.x / 2;
  const distanceToPrimary = radius + orbitAlt;

  // Calcula el período orbital de acuerdo con la tercera ley de Kepler.
  const orbitalPeriod = 2 * Math.PI * Math.sqrt(Math.pow(distanceToPrimary, 3) / G) * 0.000003;

  return orbitalPeriod;
}
