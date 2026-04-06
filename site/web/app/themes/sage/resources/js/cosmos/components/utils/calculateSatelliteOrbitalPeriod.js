const G = 6.6743e-11; // Valor de la constante de gravitación universal en m^3/kg/s^2

function resolvePrimaryRadius(primary) {
  if (typeof primary === 'number') {
    return primary;
  }

  const scaleX = primary?.current?.scale?.x;

  if (typeof scaleX === 'number' && Number.isFinite(scaleX)) {
    return scaleX / 2;
  }

  return 0.5;
}

export function calculateSatelliteOrbitalPeriod(primary, orbitAlt) {
  // Calcula la distancia al centro del objeto primario
  const radius = resolvePrimaryRadius(primary);
  const distanceToPrimary = radius + orbitAlt;

  // Calcula el período orbital de acuerdo con la tercera ley de Kepler.
  // Ahora, el período es inversamente proporcional a la altura orbital.
  // Puedes ajustar el factor de escala (0.000001) para controlar la velocidad.
  const orbitalPeriod = (2 * Math.PI * Math.sqrt(Math.pow(distanceToPrimary, 3) / G) * 0.000003) / orbitAlt;

  return orbitalPeriod;
}
