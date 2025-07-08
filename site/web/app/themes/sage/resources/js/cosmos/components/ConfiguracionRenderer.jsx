import { useThree } from '@react-three/fiber';
import { useEffect } from 'react';
import * as THREE from 'three';

export function ConfiguracionRenderer() {
  const { gl } = useThree();

  useEffect(() => {
    gl.outputEncoding = THREE.sRGBEncoding;
    gl.toneMapping = THREE.ACESFilmicToneMapping;
    gl.toneMappingExposure = 1.0;
  }, [gl]);

  return null;
}
