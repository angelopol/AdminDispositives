import React from 'react';
import { DispositivoRelaciones } from '../types';

interface DeviceRelationsProps {
  seleccionado: DispositivoRelaciones;
  onUnlink: (mac: string) => void;
}

export const DeviceRelations: React.FC<DeviceRelationsProps> = ({ seleccionado, onUnlink }) => {
  return (
    <section className="bg-white border border-gray-200 shadow-sm rounded-lg p-5 space-y-2">
      <h2 className="text-lg font-semibold">Relaciones de {seleccionado.nombre}</h2>
      <p className="text-sm"><span className="font-medium text-gray-600">MAC:</span> {seleccionado.mac}</p>
      <p className="text-sm"><span className="font-medium text-gray-600">Enlace:</span> {seleccionado.enlace ? `${seleccionado.enlace.nombre} (${seleccionado.enlace.mac})` : '—'}</p>
  <p className="text-sm"><span className="font-medium text-gray-600">Enlazado por:</span> {seleccionado.enlazado_por && seleccionado.enlazado_por.length > 0 ? seleccionado.enlazado_por.map((e) => `${e.nombre} (${e.mac})`).join(', ') : 'Nadie'}</p>
      <div className="pt-2">
        <button type="button" onClick={() => onUnlink(seleccionado.mac)} className="bg-orange-500 hover:bg-orange-600 text-xs px-2 py-1">Quitar enlace</button>
      </div>
    </section>
  );
};
