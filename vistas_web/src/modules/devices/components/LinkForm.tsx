import React, { useState } from 'react';

interface LinkFormProps {
  currentMac: string;
  onLink: (macOrigen: string, macDestino: string | null) => void;
}

// Placeholder de formulario de enlace (futuro: autocompletar / selector)
export const LinkForm: React.FC<LinkFormProps> = ({ currentMac, onLink }) => {
  const [target, setTarget] = useState('');
  return (
    <form onSubmit={(e) => { e.preventDefault(); onLink(currentMac, target || null); }} className="flex gap-2 mt-2">
      <input placeholder="MAC destino (vacío para quitar)" value={target} onChange={(e) => setTarget(e.target.value)} className="w-64" />
      <button type="submit" className="text-xs">Aplicar</button>
    </form>
  );
};
