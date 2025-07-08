import React from 'react';
import { useFrame } from '@react-three/fiber';
import React, { useRef, useEffect, useState } from 'react';
import { PlanetSubsistema } from './PlanetSubsistema.jsx';
import { Satellite } from './Satellite.jsx';
import { Orbiter } from './utils/Orbiter.js';
import { calculateOrbitalPeriod } from './utils/calculateOrbitalPeriod.js';

import { getSubsistemaPlanetario } from './utils/getSubsistemaPlanetario.js';

export const SubsistemaPlanetario = function SubsistemaPlanetario(props) {
  let running = true;
  let stopRunning = () => (running = false);
  let startRunning = () => (running = true);

  const [subsistemaPlanetario, setSubsistemaPlanetario] = useState([]);
  const planetRefs = useRef({});

  useEffect(() => {
    async function fetchSubsistemaPlanetario() {
      try {
        const fetchedSubsistemaPlanetario = await getSubsistemaPlanetario();
        setSubsistemaPlanetario(fetchedSubsistemaPlanetario);

        // Inicializa las referencias una vez que subsistemaPlanetario está disponible
        fetchedSubsistemaPlanetario.forEach((planeta) => {
          console.log(planeta);
          const camelCaseSlug = planeta.padre.slug.replace(
            /-([a-z])/g,
            (_, letter) => letter.toUpperCase()
          );
          planetRefs.current[camelCaseSlug] = useRef();
        });
      } catch (error) {
        console.error(error);
      }
    }

    fetchSubsistemaPlanetario();
  }, []);

  return (
    <>
      {subsistemaPlanetario.map((planeta, index) => (
        <PlanetSubsistema
          key={index}
          scale={0.5}
          ref={planetRefs.current[index]}
          stopRunning={stopRunning}
          startRunning={startRunning}
          textureType="haumea"
          linkTo="areas"
          linkToLabel="Areas"
        />
      ))}
    </>
  );
};
