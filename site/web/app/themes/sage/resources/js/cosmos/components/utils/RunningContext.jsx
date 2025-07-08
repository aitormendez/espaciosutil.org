import React from 'react';
import { createContext, useContext } from 'react';
import PropTypes from 'prop-types';

const RunningContext = createContext();

export function useRunning() {
  return useContext(RunningContext);
}

let running = true;

export function RunningProvider({ children }) {
  // Inicializa la variable running

  const stopRunning = () => {
    running = false;
    console.log(running);
  };

  const startRunning = () => {
    running = true;
    console.log(running);
  };

  return (
    <RunningContext.Provider value={{ running, stopRunning, startRunning }}>
      {children}
    </RunningContext.Provider>
  );
}

RunningProvider.propTypes = {
  children: PropTypes.node,
};
