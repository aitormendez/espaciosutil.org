import React from 'react';
import { useState, useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import { Html, useTexture } from '@react-three/drei';
import { useFrame } from '@react-three/fiber';
import { Orbiter } from './utils/orbiter.js';
import { calculateSatelliteOrbitalPeriod } from './utils/calculateSatelliteOrbitalPeriod.js';
import barba from '@barba/core';
import { solapaContentAbrir, solapaContentCerrar } from './utils/solapa.js';
import { planets } from './utils/arrayTexturas.js';

// Define un objeto que mapea los tipos de textura a las rutas de los archivos de textura.
const textureMap = {};

for (const planet of planets) {
  textureMap[
    planet
  ] = `./app/themes/sage/resources/js/cosmos/components/textures/${planet}-512.jpg`;
}

export const Satellite = function Satellite(props) {
  let running = true;

  // Obtén la ruta de la textura según el tipo especificado en props.textureType.
  const texturePath = textureMap[props.textureType] || textureMap.sand;
  const texture = useTexture(texturePath);

  // Crea una referencia para el satélite
  const satelliteRef = useRef();

  let rotationX = Math.random();
  let rotationY = Math.random();

  useFrame((state, delta) => {
    satelliteRef.current.rotation.x += rotationX * delta;
    satelliteRef.current.rotation.y += rotationY * delta;
  });

  const orbitParameters = {
    orbitAlt: Math.random() + 0.7, // Altitud orbital aleatoria
    initialPos: Math.random() * 360, // Posición inicial aleatoria en grados
    inclination: Math.random() * 180, // Inclinación orbital aleatoria en grados
    speed: Math.random() + 0.5, // Variación de la velocidad
  };

  // Crea un Orbiter para el satélite
  const satelliteOrbiter = new Orbiter();
  satelliteOrbiter.setOrbitParameters(
    orbitParameters.orbitAlt,
    orbitParameters.initialPos,
    orbitParameters.inclination,
    orbitParameters.speed
  );

  const orbitalPeriod = calculateSatelliteOrbitalPeriod(
    props.centerPlanetRef,
    orbitParameters.orbitAlt
  );

  useEffect(() => {
    // Actualiza las órbitas y posiciones del satélite
    const satellite = satelliteOrbiter.orbit(
      props.centerPlanetRef,
      orbitalPeriod
    );

    // Actualiza la posición del satélite en la escena
    if (satelliteRef.current) {
      satelliteRef.current.position.x = satellite.x;
      satelliteRef.current.position.y = satellite.y;
      satelliteRef.current.position.z = satellite.z;
    }
  }, [props.centerPlanetRef, props.orbitalPeriod]);

  useFrame((state, delta) => {
    if (running === true) {
      // Calcula la nueva posición del satélite en cada frame
      const satellite = satelliteOrbiter.orbit(
        props.centerPlanetRef,
        orbitalPeriod
      );

      // Actualiza la posición del satélite en la escena
      if (satelliteRef.current) {
        satelliteRef.current.position.x = satellite.x;
        satelliteRef.current.position.y = satellite.y;
        satelliteRef.current.position.z = satellite.z;
      }
    }
  });

  return (
    <mesh
      {...props}
      ref={satelliteRef}
      castShadow
      receiveShadow
      onPointerEnter={(event) => {
        props.stopRunning();
        running = false;
        document.body.style.cursor = 'pointer';
        event.stopPropagation();
        solapaContentAbrir(props.epigrafe, props.titulo);
      }}
      onPointerLeave={(event) => {
        props.startRunning();
        running = true;
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
};

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
  epigrafe: PropTypes.string,
  titulo: PropTypes.string,
};
