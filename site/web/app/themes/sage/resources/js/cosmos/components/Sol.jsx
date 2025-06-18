import React from 'react';
import { forwardRef } from 'react';

export const Sol = forwardRef(function Sol(props, ref) {
  const { sunB, sunG, sunR, sunLightColor, sunIntensity } = {
    sunLightColor: '#ffffff',
    sunIntensity: 100,
    sunR: 2,
    sunG: 1,
    sunB: 0.4,
  };

  return (
    <>
      <pointLight
        intensity={sunIntensity}
        color={sunLightColor}
        castShadow
        shadow-mapSize={[512, 512]}
      />
      <mesh {...props} ref={ref}>
        <sphereGeometry args={[1, 32, 16]} />
        <meshStandardMaterial
          emissive={[sunR, sunG, sunB]}
          color={[sunR, sunG, sunB]}
        />
      </mesh>
    </>
  );
});
