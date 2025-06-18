import React from 'react';
import ReactDOM from 'react-dom/client';
import { Canvas } from '@react-three/fiber';
import { StrictMode, Suspense } from 'react';
import Experience from './components/Experience.jsx';
import { Loading } from './components/Loading.jsx';
import { RunningProvider } from './components/utils/RunningContext.jsx';
import { InitialPositionsProvider } from './components/utils/InitialPositionsContext.jsx';
import { PlanetRefsProvider } from './components/utils/PlanetRefsContext.jsx';

export function cosmos() {
  console.log('🚀 Ejecutando cosmos()');
  const root = ReactDOM.createRoot(document.querySelector('#cosmos'));

  const cameraSettings = {
    fov: 45,
    near: 0.1,
    far: 200,
    position: [0, 13, 0],
  };

  root.render(
    <StrictMode>
      <InitialPositionsProvider>
        <RunningProvider>
          <PlanetRefsProvider>
            <Canvas camera={cameraSettings} shadows>
              <Suspense fallback={<Loading />}>
                <Experience />
              </Suspense>
            </Canvas>
          </PlanetRefsProvider>
        </RunningProvider>
      </InitialPositionsProvider>
    </StrictMode>
  );
}
