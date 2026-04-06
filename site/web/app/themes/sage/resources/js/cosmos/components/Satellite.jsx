import React from 'react';
import { memo, useEffect, useMemo, useRef } from 'react';
import PropTypes from 'prop-types';
import { useTexture } from '@react-three/drei';
import { useFrame } from '@react-three/fiber';
import { Orbiter } from './utils/orbiter.js';
import { calculateSatelliteOrbitalPeriod } from './utils/calculateSatelliteOrbitalPeriod.js';
import barba from '@barba/core';
import { solapaContentAbrir, solapaContentCerrar } from './utils/solapa.js';
import { getSatelliteMotionConfig } from './utils/cosmosConfig.js';
import { getTexturePath } from './utils/textureMap.js';

export const Satellite = memo(function Satellite(props) {
  // Obtén la ruta de la textura según el tipo especificado en props.textureType.
  const texture = useTexture(getTexturePath(props.textureType));
  const satelliteRef = useRef();
  const runningRef = useRef(true);
  const motionConfig = useRef(
    getSatelliteMotionConfig(
      props.itemJson,
      props.itemIndex ?? 0,
      props.sessionSeed,
      props.orbitName
    )
  );
  const satelliteOrbiterRef = useRef(new Orbiter());
  const orbitalPeriod = useMemo(
    () =>
      calculateSatelliteOrbitalPeriod(
        props.centerPlanetRef,
        motionConfig.current.orbitAlt
      ),
    [props.centerPlanetRef]
  );

  if (!satelliteOrbiterRef.current.orbitAlt) {
    satelliteOrbiterRef.current.setOrbitParameters(
      motionConfig.current.orbitAlt,
      motionConfig.current.initialPos,
      motionConfig.current.inclination,
      motionConfig.current.speed
    );
  }

  useEffect(() => {
    // Actualiza las órbitas y posiciones del satélite
    const satellite = satelliteOrbiterRef.current.orbit(
      props.centerPlanetRef,
      orbitalPeriod,
      0
    );

    // Actualiza la posición del satélite en la escena
    if (satelliteRef.current) {
      satelliteRef.current.position.x = satellite.x;
      satelliteRef.current.position.y = satellite.y;
      satelliteRef.current.position.z = satellite.z;
    }
  }, [orbitalPeriod, props.centerPlanetRef]);

  useFrame((state, delta) => {
    if (!satelliteRef.current) {
      return;
    }

    satelliteRef.current.rotation.x += motionConfig.current.rotationX * delta;
    satelliteRef.current.rotation.y += motionConfig.current.rotationY * delta;

    if (!runningRef.current) {
      return;
    }

    // Calcula la nueva posición del satélite en cada frame
    const satellite = satelliteOrbiterRef.current.orbit(
      props.centerPlanetRef,
      orbitalPeriod,
      delta
    );

    // Actualiza la posición del satélite en la escena
    satelliteRef.current.position.x = satellite.x;
    satelliteRef.current.position.y = satellite.y;
    satelliteRef.current.position.z = satellite.z;
  });

  return (
    <mesh
      {...props}
      ref={satelliteRef}
      castShadow={false}
      receiveShadow={false}
      onPointerEnter={(event) => {
        props.stopRunning();
        runningRef.current = false;
        document.body.style.cursor = 'pointer';
        event.stopPropagation();
        solapaContentAbrir(props.epigrafe, props.titulo);
      }}
      onPointerLeave={(event) => {
        props.startRunning();
        runningRef.current = true;
        document.body.style.cursor = 'default';
        event.stopPropagation();
        solapaContentCerrar();
      }}
      onClick={(event) => {
        barba.go(props.itemJson.link);
        document.body.style.cursor = 'default';
      }}
    >
      <sphereGeometry args={[1, 16, 8]} />
      <meshStandardMaterial map={texture} />
    </mesh>
  );
});

Satellite.propTypes = {
  stopRunning: PropTypes.func,
  startRunning: PropTypes.func,
  textureType: PropTypes.oneOf([
    'sand',
    'haumea',
    'mars',
    'neptune',
    'venus',
    'mercury',
    'jupiter',
    'saturn',
  ]),
  centerPlanetRef: PropTypes.object,
  orbitalPeriod: PropTypes.number,
  itemJson: PropTypes.object,
  itemIndex: PropTypes.number,
  orbitName: PropTypes.string,
  sessionSeed: PropTypes.string,
  epigrafe: PropTypes.string,
  titulo: PropTypes.string,
};
