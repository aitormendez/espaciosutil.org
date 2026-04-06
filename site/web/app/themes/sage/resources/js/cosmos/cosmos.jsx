import React from 'react';
import ReactDOM from 'react-dom/client';
import { Canvas } from '@react-three/fiber';
import { Suspense } from 'react';
import Experience from './components/Experience.jsx';
import { Loading } from './components/Loading.jsx';
import { ConfiguracionRenderer } from './components/ConfiguracionRenderer';

export function cosmos() {
  const mountNode = document.querySelector('#cosmos');

  if (!mountNode || mountNode.dataset.initialized === '1') {
    return;
  }

  mountNode.dataset.initialized = '1';

  const root = ReactDOM.createRoot(mountNode);

  const cameraSettings = {
    fov: 45,
    near: 0.1,
    far: 200,
    position: [0, 13, 0],
  };

  root.render(
    <Canvas
      camera={cameraSettings}
      dpr={[1, 1.5]}
      gl={{ antialias: true, powerPreference: 'high-performance' }}
      shadows={false}
    >
      <ConfiguracionRenderer />
      <Suspense fallback={<Loading />}>
        <Experience />
      </Suspense>
    </Canvas>
  );
}
