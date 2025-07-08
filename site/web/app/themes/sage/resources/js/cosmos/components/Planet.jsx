import React from 'react';
import { forwardRef, useRef, useEffect, useContext, useState } from 'react';
import PropTypes from 'prop-types';
import { useTexture } from '@react-three/drei';
import { useFrame } from '@react-three/fiber';
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

export const Planet = forwardRef(function Planet(props, ref) {
  // Obtén la ruta de la textura según el tipo especificado en props.textureType.
  const texturePath = textureMap[props.textureType] || textureMap.sand;
  const texture = useTexture(texturePath);

  let rotationX;
  let rotationY;
  let rotationZ;

  if (props.name === 'planetFormaciones') {
    rotationX = 0;
    rotationY = -0.2;
    rotationZ = 90;
  } else {
    rotationX = Math.random();
    rotationY = Math.random();
    rotationZ = 0;
  }

  useEffect(() => {
    ref.current.rotation.x += rotationZ;
  }, []);

  useFrame((state, delta) => {
    ref.current.rotation.x += rotationX * delta;
    ref.current.rotation.y += rotationY * delta;
  });

  return (
    <mesh
      {...props}
      name={props.name}
      ref={ref}
      castShadow
      receiveShadow
      onPointerEnter={(event) => {
        props.stopRunning();
        document.body.style.cursor = 'pointer';
        solapaContentAbrir('Sección', props.linkToLabel);
        event.stopPropagation();
      }}
      onPointerLeave={(event) => {
        props.startRunning();
        document.body.style.cursor = 'default';
        solapaContentCerrar();
        event.stopPropagation();
      }}
      onClick={(event) => {
        barba.go(props.linkTo);
      }}
    >
      <sphereGeometry />
      <meshStandardMaterial map={texture} />
    </mesh>
  );
});

Planet.propTypes = {
  stopRunning: PropTypes.func,
  startRunning: PropTypes.func,
  textureType: PropTypes.oneOf([
    'haumea',
    'mars',
    'neptune',
    'venus',
    'mercury',
    'jupiter',
    'saturn',
  ]),
  userData: PropTypes.object,
  radius: PropTypes.number,
  linkTo: PropTypes.string,
  linkToLabel: PropTypes.string,
  name: PropTypes.string,
};
