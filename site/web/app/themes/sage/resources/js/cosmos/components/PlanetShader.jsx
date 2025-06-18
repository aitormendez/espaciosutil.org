import React from 'react';
import * as THREE from 'three';
import { shaderMaterial } from '@react-three/drei';
import { useFrame, extend } from '@react-three/fiber';
import { useRef, forwardRef } from 'react';
import planetVertexShader from './shaders/planet/vertex.glsl';
import planetFragmentShader from './shaders/planet/fragment.glsl';

const PlanetMaterial = shaderMaterial(
  {
    uTime: 0,
    uColorStart: new THREE.Color('#ff7400'),
    uColorEnd: new THREE.Color('#451439'),
  },
  planetVertexShader,
  planetFragmentShader
);

extend({ PlanetMaterial });

export const PlanetShader = forwardRef(function Planeta(props, ref) {
  const planetMaterial = useRef();
  useFrame((state, delta) => {
    planetMaterial.current.uniforms.uTime.value += delta * 0.7;
    // console.log(planetMaterial);
  });

  return (
    <mesh {...props} ref={ref}>
      <sphereGeometry />
      <planetMaterial
        ref={planetMaterial}
        vertexShader={planetVertexShader}
        fragmentShader={planetFragmentShader}
      />
    </mesh>
  );
});
