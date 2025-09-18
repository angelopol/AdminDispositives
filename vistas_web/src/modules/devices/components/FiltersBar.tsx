import React, { ChangeEvent } from 'react';

interface FiltersBarProps {
  searchDefault: string;
  onSearchChange: (e: ChangeEvent<HTMLInputElement>) => void;
  orden: string;
  direccion: string;
  setOrden: (v: string) => void;
  setDireccion: (v: 'asc' | 'desc') => void;
}

export const FiltersBar: React.FC<FiltersBarProps> = ({ searchDefault, onSearchChange, orden, direccion, setOrden, setDireccion }) => {
  return (
    <div className="flex flex-wrap gap-3 mb-2">
      <input placeholder="Buscar" defaultValue={searchDefault} onChange={onSearchChange} className="w-40" />
      <select value={orden} onChange={(e) => setOrden(e.target.value)}>
        <option value="nombre">Nombre</option>
        <option value="mac">MAC</option>
        <option value="ip">IP</option>
      </select>
      <select value={direccion} onChange={(e) => setDireccion(e.target.value as 'asc' | 'desc')}>
        <option value="asc">Asc</option>
        <option value="desc">Desc</option>
      </select>
    </div>
  );
};
