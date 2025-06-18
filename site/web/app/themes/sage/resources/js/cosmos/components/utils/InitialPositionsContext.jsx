import React from 'react';
import { createContext, useContext, useState } from 'react';
import PropTypes from 'prop-types';

const defaultOrbitParameters = {
  planetAreas: {
    radius: 5,
    inclination: 0,
    angle: 30,
    speed: 1,
  },
  planetReveladores: {
    radius: 3,
    inclination: 90,
    angle: 60,
    speed: 1,
  },
  planetNoticias: {
    radius: 4,
    inclination: 300,
    angle: 95,
    speed: 1,
  },
};

const InitialPositionsContext = createContext(defaultOrbitParameters);

export function useInitialPositions() {
  return useContext(InitialPositionsContext);
}

export function InitialPositionsProvider({ children, initialOrbitParameters }) {
  const [orbitParameters, setOrbitParameters] = useState(
    initialOrbitParameters || defaultOrbitParameters
  );

  const updateOrbitParameters = (newParameters) => {
    setOrbitParameters(newParameters);
  };

  return (
    <InitialPositionsContext.Provider
      value={{ orbitParameters, updateOrbitParameters }}
    >
      {children}
    </InitialPositionsContext.Provider>
  );
}

InitialPositionsProvider.propTypes = {
  children: PropTypes.node,
  initialOrbitParameters: PropTypes.object,
};
