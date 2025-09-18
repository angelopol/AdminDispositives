import React from 'react';
import './index.css';
import { createRoot } from 'react-dom/client';
import { App } from './modules/App';

const containerId = 'gestion-dispositivos-root';

export function mount(selector: string = `#${containerId}`) {
  const el = document.querySelector(selector);
  if (!el) {
    throw new Error(`Elemento contenedor no encontrado: ${selector}`);
  }
  const root = createRoot(el as HTMLElement);
  root.render(<App />);
}

if (document.getElementById(containerId)) {
  mount();
}
