import React from 'react';
import { useFrame } from '@react-three/fiber';
// import {OrbitControls} from '@react-three/drei';
// import {useControls} from 'leva';
// import {Perf} from 'r3f-perf';
import { useRef, useEffect, useState, useMemo } from 'react';
import { Bloom, EffectComposer } from '@react-three/postprocessing';
import { Sol } from './Sol.jsx';
import { Planet } from './Planet.jsx';
import { Satellite } from './Satellite.jsx';
import { Orbiter } from './utils/orbiter.js';
import { calculateOrbitalPeriod } from './utils/calculateOrbitalPeriod.js';
import { getReveladores } from './utils/getReveladores.js';
import { getNoticias } from './utils/getNoticias.js';
import { getFormaciones } from './utils/getFormaciones.js';
import { getTerapias } from './utils/getTerapias.js';

export default function Experience() {
  let running = true;
  let stopRunning = () => (running = false);
  let startRunning = () => (running = true);

  // Crea referencias para el sol y los planetas
  const sunRef = useRef();
  const planetReveladoresRef = useRef();
  const planetNoticiasRef = useRef();
  const planetFormacionesRef = useRef();
  const planetDivulgacionesRef = useRef();
  const planetTerapiasRef = useRef();

  // Usamos useMemo para calcular y almacenar los valores aleatorios una vez
  const randomValues = useMemo(() => {
    const values = [];
    for (let i = 0; i < 10; i++) {
      values.push(Math.random());
    }
    return values;
  }, []); // El array de dependencias vacío asegura que se calcule solo una vez

  // Crea objetos Orbiter para los planetas
  const planetTerapiasOrbiter = new Orbiter();
  planetTerapiasOrbiter.setOrbitParameters(
    3.5,
    randomValues[0] * 360,
    randomValues[1] * 360,
    1
  );

  const planetFormacionesOrbiter = new Orbiter();
  planetFormacionesOrbiter.setOrbitParameters(
    4,
    randomValues[2] * 360,
    randomValues[3] * 360,
    1
  );

  const planetDivulgacionesOrbiter = new Orbiter();
  planetDivulgacionesOrbiter.setOrbitParameters(
    1.5,
    randomValues[4] * 360,
    randomValues[5] * 360,
    1
  );

  const planetReveladoresOrbiter = new Orbiter();
  planetReveladoresOrbiter.setOrbitParameters(
    3,
    randomValues[6] * 360,
    randomValues[7] * 360,
    1
  );

  const planetNoticiasOrbiter = new Orbiter();
  planetNoticiasOrbiter.setOrbitParameters(
    2,
    randomValues[8] * 360,
    randomValues[9] * 360,
    1
  );

  // Calcula el período orbital una vez antes del bucle
  const [orbitalPeriodFormaciones, setOrbitalPeriodFormaciones] =
    useState(null);
  const [orbitalPeriodReveladores, setOrbitalPeriodReveladores] =
    useState(null);
  const [orbitalPeriodNoticias, setOrbitalPeriodNoticias] = useState(null);
  const [orbitalPeriodDivulgaciones, setOrbitalPeriodDivulgaciones] =
    useState(null);
  const [orbitalPeriodTerapias, setOrbitalPeriodTerapias] = useState(null);

  useEffect(() => {
    setOrbitalPeriodFormaciones(calculateOrbitalPeriod(sunRef, 4)); // el segundo parámetro debe ser iguale que la altura del orbiter
    setOrbitalPeriodReveladores(calculateOrbitalPeriod(sunRef, 3));
    setOrbitalPeriodNoticias(calculateOrbitalPeriod(sunRef, 2));
    setOrbitalPeriodDivulgaciones(calculateOrbitalPeriod(sunRef, 1.5));
    setOrbitalPeriodTerapias(calculateOrbitalPeriod(sunRef, 3.5));
  }, []);

  // obtener lista de reveladores
  const [reveladores, setReveladores] = useState([]);
  const [noticias, setNoticias] = useState([]);
  const [formaciones, setFormaciones] = useState([]);
  const [terapias, setTerapias] = useState([]);
  // const [divulgaciones, setDivulgaciones] = useState([]);

  useEffect(() => {
    // Obtener la lista de "formaciones" cuando el componente se monta
    async function fetchTerapias() {
      try {
        const fetchedTerapias = await getTerapias();
        setTerapias(fetchedTerapias);
      } catch (error) {
        console.error(error);
      }
    }

    fetchTerapias();

    // Obtener la lista de "formaciones" cuando el componente se monta
    async function fetchFormaciones() {
      try {
        const fetchedFormaciones = await getFormaciones();
        setFormaciones(fetchedFormaciones);
      } catch (error) {
        console.error(error);
      }
    }

    fetchFormaciones();

    // Obtener la lista de "reveladores" cuando el componente se monta
    async function fetchReveladores() {
      try {
        const fetchedReveladores = await getReveladores();
        setReveladores(fetchedReveladores);
      } catch (error) {
        console.error(error);
      }
    }

    fetchReveladores();

    // Obtener la lista de "noticias" cuando el componente se monta
    async function fetchNoticias() {
      try {
        const fetchedReveladores = await getNoticias();
        setNoticias(fetchedReveladores);
      } catch (error) {
        console.error(error);
      }
    }

    fetchNoticias();
  }, []);

  let satellitesTerapias = null; // Inicialmente, no hay susbistema

  if (terapias.length > 0) {
    // Si hay noticias disponibles, crea satélites para cada uno
    satellitesTerapias = terapias.map((terapia) => (
      <Satellite
        key={terapia.id}
        centerPlanetRef={planetTerapiasRef}
        scale={0.1}
        textureType="venus"
        itemJson={terapia}
        epigrafe="Terapia"
        titulo={terapia.title.rendered}
        stopRunning={stopRunning}
        startRunning={startRunning}
      />
    ));
  }

  let satellitesFormaciones = null; // Inicialmente, no hay susbistema

  if (formaciones.length > 0) {
    // Si hay noticias disponibles, crea satélites para cada uno
    satellitesFormaciones = formaciones.map((formacion) => (
      <Satellite
        key={formacion.id}
        centerPlanetRef={planetFormacionesRef}
        scale={0.1}
        textureType="jupiter"
        itemJson={formacion}
        epigrafe="Formación"
        titulo={formacion.title.rendered}
        stopRunning={stopRunning}
        startRunning={startRunning}
      />
    ));
  }

  let satellitesNoticias = null; // Inicialmente, no hay susbistema

  if (noticias.length > 0) {
    // Si hay noticias disponibles, crea satélites para cada uno
    satellitesNoticias = noticias.map((noticia) => (
      <Satellite
        key={noticia.id}
        centerPlanetRef={planetNoticiasRef}
        scale={0.1}
        textureType="neptune"
        itemJson={noticia}
        epigrafe="Noticia"
        titulo={noticia.title.rendered}
        stopRunning={stopRunning}
        startRunning={startRunning}
      />
    ));
  }

  let satellitesReveladores = null; // Inicialmente, no hay satélites

  if (reveladores.length > 0) {
    // Si hay reveladores disponibles, crea satélites para cada uno
    satellitesReveladores = reveladores.map((revelador) => (
      <Satellite
        key={revelador.id}
        centerPlanetRef={planetReveladoresRef}
        scale={0.1}
        textureType="mars"
        itemJson={revelador}
        epigrafe="Revelador"
        titulo={revelador.name}
        stopRunning={stopRunning}
        startRunning={startRunning}
      />
    ));
  }

  useFrame((state, delta) => {
    if (running === true) {
      // Actualiza las órbitas y posiciones de los planetas
      const planetReveladoresOrbit = planetReveladoresOrbiter.orbit(
        sunRef,
        orbitalPeriodReveladores
      );
      const planetNoticiasOrbit = planetNoticiasOrbiter.orbit(
        sunRef,
        orbitalPeriodNoticias
      );
      const planetFormacionesOrbit = planetFormacionesOrbiter.orbit(
        sunRef,
        orbitalPeriodFormaciones
      );
      const planetDivulgacionesOrbit = planetDivulgacionesOrbiter.orbit(
        sunRef,
        orbitalPeriodDivulgaciones
      );
      const planetTerapiasOrbit = planetTerapiasOrbiter.orbit(
        sunRef,
        orbitalPeriodTerapias
      );

      // Actualiza las posiciones de los planetas en la escena
      planetReveladoresRef.current.position.x = planetReveladoresOrbit.x;
      planetReveladoresRef.current.position.y = planetReveladoresOrbit.y;
      planetReveladoresRef.current.position.z = planetReveladoresOrbit.z;
      planetReveladoresRef.current.angle = planetReveladoresOrbit.angle;

      planetNoticiasRef.current.position.x = planetNoticiasOrbit.x;
      planetNoticiasRef.current.position.y = planetNoticiasOrbit.y;
      planetNoticiasRef.current.position.z = planetNoticiasOrbit.z;
      planetNoticiasRef.current.angle = planetNoticiasOrbit.angle;

      planetFormacionesRef.current.position.x = planetFormacionesOrbit.x;
      planetFormacionesRef.current.position.y = planetFormacionesOrbit.y;
      planetFormacionesRef.current.position.z = planetFormacionesOrbit.z;
      planetFormacionesRef.current.angle = planetFormacionesOrbit.angle;

      planetDivulgacionesRef.current.position.x = planetDivulgacionesOrbit.x;
      planetDivulgacionesRef.current.position.y = planetDivulgacionesOrbit.y;
      planetDivulgacionesRef.current.position.z = planetDivulgacionesOrbit.z;
      planetDivulgacionesRef.current.angle = planetDivulgacionesOrbit.angle;

      planetTerapiasRef.current.position.x = planetTerapiasOrbit.x;
      planetTerapiasRef.current.position.y = planetTerapiasOrbit.y;
      planetTerapiasRef.current.position.z = planetTerapiasOrbit.z;
      planetTerapiasRef.current.angle = planetTerapiasOrbit.angle;
    }
  });

  const labelLoading = document.querySelector('.loading-label');

  useEffect(() => {
    labelLoading.classList.remove('lg:flex');
  }, []);

  return (
    <>
      <EffectComposer>
        <Bloom luminanceSmoothing={0.5} intensity={1} luminanceThreshold={1} />
      </EffectComposer>
      {/* <Perf position="bottom-right" /> */}
      {/* <OrbitControls makeDefault /> */}
      <Sol ref={sunRef} />
      <Planet
        name={'planetTerapias'}
        scale={0.3}
        ref={planetTerapiasRef}
        stopRunning={stopRunning}
        startRunning={startRunning}
        textureType="venus"
        linkTo="orientacion-terapeutica"
        linkToLabel="Acompañamiento terapéutico"
      />
      <Planet
        name={'planetDivulgaciones'}
        scale={0.3}
        ref={planetDivulgacionesRef}
        stopRunning={stopRunning}
        startRunning={startRunning}
        textureType="haumea"
        linkTo="divulgacio"
        linkToLabel="Divulgación"
      />
      <Planet
        name={'planetReveladores'}
        scale={0.5}
        ref={planetReveladoresRef}
        stopRunning={stopRunning}
        startRunning={startRunning}
        textureType="mars"
        linkTo="reveladores"
        linkToLabel="Reveladores"
      />
      <Planet
        name={'planetNoticias'}
        scale={0.5}
        ref={planetNoticiasRef}
        stopRunning={stopRunning}
        startRunning={startRunning}
        textureType="neptune"
        linkTo="noticias"
        linkToLabel="Noticias"
      />
      <Planet
        name={'planetFormaciones'}
        scale={0.7}
        ref={planetFormacionesRef}
        stopRunning={stopRunning}
        startRunning={startRunning}
        textureType="jupiter"
        linkTo="formacion"
        linkToLabel="Formación"
      />
      {satellitesReveladores}
      {satellitesNoticias}
      {satellitesFormaciones}
      {satellitesTerapias}
    </>
  );
}
