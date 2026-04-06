import React from 'react';
import { Bloom, EffectComposer } from '@react-three/postprocessing';
import * as THREE from 'three';

export default function CosmosEffects({ multisampling = 4 }) {
  return (
    <EffectComposer
      multisampling={multisampling}
      frameBufferType={THREE.HalfFloatType}
    >
      <Bloom
        luminanceSmoothing={0.7}
        intensity={0.35}
        luminanceThreshold={1.05}
      />
    </EffectComposer>
  );
}
