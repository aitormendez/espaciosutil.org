import React from 'react';
import { useFrame } from '@react-three/fiber';
import { lazy, Suspense, useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { Sol } from './Sol.jsx';
import { Planet } from './Planet.jsx';
import { Satellite } from './Satellite.jsx';
import { Orbiter } from './utils/orbiter.js';
import { calculateOrbitalPeriod } from './utils/calculateOrbitalPeriod.js';
import {
  buildPlanetConfigs,
  createSessionSeed,
} from './utils/cosmosConfig.js';
import { getCosmosData } from './utils/getCosmosData.js';

const CosmosEffects = lazy(() => import('./CosmosEffects.jsx'));
const SUN_RADIUS = 0.5;
const EMPTY_COSMOS_DATA = {
  terapias: [],
  formaciones: [],
  reveladores: [],
  noticias: [],
};

function setObjectPosition(target, orbit) {
  target.position.x = orbit.x;
  target.position.y = orbit.y;
  target.position.z = orbit.z;
  target.angle = orbit.angle;
}

export default function Experience({ qualityProfile }) {
  const runningRef = useRef(true);
  const sunRef = useRef();
  const planetReveladoresRef = useRef();
  const planetNoticiasRef = useRef();
  const planetFormacionesRef = useRef();
  const planetDivulgacionesRef = useRef();
  const planetTerapiasRef = useRef();
  const sessionSeedRef = useRef(createSessionSeed());
  const [cosmosData, setCosmosData] = useState(EMPTY_COSMOS_DATA);
  const [isDataReady, setIsDataReady] = useState(false);

  const startRunning = useCallback(() => {
    runningRef.current = true;
  }, []);

  const stopRunning = useCallback(() => {
    runningRef.current = false;
  }, []);

  const planetRefs = useMemo(
    () => ({
      planetTerapias: planetTerapiasRef,
      planetDivulgaciones: planetDivulgacionesRef,
      planetReveladores: planetReveladoresRef,
      planetNoticias: planetNoticiasRef,
      planetFormaciones: planetFormacionesRef,
    }),
    []
  );

  const planetConfigs = useMemo(
    () =>
      buildPlanetConfigs(sessionSeedRef.current).map((planet) => ({
        ...planet,
        orbitalPeriod: calculateOrbitalPeriod(SUN_RADIUS, planet.orbitAlt),
      })),
    []
  );

  const orbitersRef = useRef(null);

  if (!orbitersRef.current) {
    orbitersRef.current = Object.fromEntries(
      planetConfigs.map((planet) => {
        const orbiter = new Orbiter();
        orbiter.setOrbitParameters(
          planet.orbitAlt,
          planet.initialPos,
          planet.inclination,
          planet.speed
        );

        return [planet.name, orbiter];
      })
    );
  }

  useEffect(() => {
    let cancelled = false;

    getCosmosData()
      .then((data) => {
        if (cancelled) {
          return;
        }

        setCosmosData(data);
        setIsDataReady(true);
      })
      .catch((error) => {
        console.error(error);

        if (!cancelled) {
          setIsDataReady(true);
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (!sunRef.current) {
      return;
    }

    planetConfigs.forEach((planet) => {
      const planetRef = planetRefs[planet.name];

      if (!planetRef?.current) {
        return;
      }

      const orbit = orbitersRef.current[planet.name].orbit(
        sunRef,
        planet.orbitalPeriod,
        0
      );

      setObjectPosition(planetRef.current, orbit);
    });
  }, [planetConfigs, planetRefs]);

  useEffect(() => {
    if (!isDataReady) {
      return;
    }

    const labelLoading = document.querySelector('.loading-label');

    if (!labelLoading) {
      return;
    }

    requestAnimationFrame(() => {
      labelLoading.classList.remove('xl:flex');
      labelLoading.classList.add('hidden');
    });
  }, [isDataReady]);

  const satellites = useMemo(
    () =>
      planetConfigs.flatMap((planet) => {
        const items = cosmosData[planet.key] ?? [];

        if (!items.length || !planet.epigrafe || !planet.getTitle) {
          return [];
        }

        return items.map((item, index) => (
          <Satellite
            key={item.id}
            centerPlanetRef={planetRefs[planet.name]}
            epigrafe={planet.epigrafe}
            itemIndex={index}
            itemJson={item}
            orbitName={planet.name}
            scale={0.1}
            sessionSeed={sessionSeedRef.current}
            startRunning={startRunning}
            stopRunning={stopRunning}
            textureType={planet.textureType}
            titulo={planet.getTitle(item)}
          />
        ));
      }),
    [cosmosData, planetConfigs, planetRefs, startRunning, stopRunning]
  );

  useFrame((state, delta) => {
    if (!runningRef.current || !sunRef.current) {
      return;
    }

    planetConfigs.forEach((planet) => {
      const planetRef = planetRefs[planet.name];

      if (!planetRef?.current) {
        return;
      }

      const orbit = orbitersRef.current[planet.name].orbit(
        sunRef,
        planet.orbitalPeriod,
        delta
      );

      setObjectPosition(planetRef.current, orbit);
    });
  });

  return (
    <>
      {qualityProfile?.enableEffects ? (
        <Suspense fallback={null}>
          <CosmosEffects multisampling={qualityProfile.multisampling} />
        </Suspense>
      ) : null}
      <Sol ref={sunRef} />
      {planetConfigs.map((planet) => (
        <Planet
          key={planet.name}
          linkTo={planet.linkTo}
          linkToLabel={planet.linkToLabel}
          name={planet.name}
          ref={planetRefs[planet.name]}
          scale={planet.scale}
          startRunning={startRunning}
          stopRunning={stopRunning}
          textureType={planet.textureType}
        />
      ))}
      {satellites}
    </>
  );
}
