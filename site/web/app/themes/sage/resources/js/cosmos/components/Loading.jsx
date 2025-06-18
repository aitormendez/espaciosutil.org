import React from 'react';
import { useFrame } from '@react-three/fiber';
import { useRef } from 'react';

export function Loading() {
  useFrame((state, delta) => {
    loadingRef.current.rotation.y += delta / 2;
  });

  const loadingRef = useRef();

  return (
    <group position={[0, 0, 0]}>
      <mesh rotation-x={90} ref={loadingRef} scale={1.2}>
        <sphereGeometry args={[1, 10, 6]} />
        <meshBasicMaterial color={'white'} wireframe />
      </mesh>
    </group>
  );
}
