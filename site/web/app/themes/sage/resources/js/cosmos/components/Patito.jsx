import React from 'react';
import { useGLTF } from '@react-three/drei';
import { forwardRef } from 'react';

const homeUrl = jsData.homeUrl; // eslint-disable-line

export const Patito = forwardRef(function Planeta(props, ref) {
  const patito = useGLTF(
    `./app/themes/sage/public/models/patito/rubber_duck_toy_4k.137707.glb`
  );

  return <primitive {...props} ref={ref} object={patito.scene} />;
});

// useGLTF.preload(`/app/themes/sage/public/models/patito/rubber_duck_toy_4k.137707.glb`);
