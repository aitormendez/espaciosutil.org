import React from 'react';
import { createContext, useContext, useRef } from 'react';
import PropTypes from 'prop-types';

const PlanetRefsContext = createContext();

export function usePlanetRefs() {
  return useContext(PlanetRefsContext);
}

export function PlanetRefsProvider({ children }) {
  // Define las referencias a los planetas
  const planetAreasRef = useRef(null);
  const planetReveladoresRef = useRef(null);
  const planetNoticiasRef = useRef(null);

  // Agrupa las referencias en un objeto
  const planetRefs = {
    planetAreasRef,
    planetReveladoresRef,
    planetNoticiasRef,
  };

  return (
    <PlanetRefsContext.Provider value={planetRefs}>
      {children}
    </PlanetRefsContext.Provider>
  );
}
PlanetRefsProvider.propTypes = {
  children: PropTypes.node,
};
