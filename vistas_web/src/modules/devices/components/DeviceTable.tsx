import React from 'react';
import { Dispositivo } from '../types';

export interface DeviceTableProps {
  dispositivos: Dispositivo[];
  cargando: boolean;
  error: string | null;
  filtros: { orden: string; direccion: 'asc' | 'desc'; page?: number };
  meta: any;
  editMac: string | null;
  editForm: { nombre: string; ip: string | null; activo?: boolean };
  onToggleSort: (campo: string) => void;
  sortIcon: (campo: string) => React.ReactNode;
  onSetFiltros: (fn: any) => void;
  onSelect: (mac: string) => void;
  onToggleEdit: (d: Dispositivo) => void;
  onSubmitEdit: () => void;
  onDelete: (mac: string) => void;
  setEditForm: React.Dispatch<React.SetStateAction<{ nombre: string; ip: string | null; activo?: boolean }>>;
  onQuickToggleActivo?: (mac: string, current: boolean) => void;
}

export const DeviceTable: React.FC<DeviceTableProps> = ({
  dispositivos,
  cargando,
  error,
  filtros,
  meta,
  editMac,
  editForm,
  onToggleSort,
  sortIcon,
  onSetFiltros,
  onSelect,
  onToggleEdit,
  onSubmitEdit,
  onDelete,
  setEditForm,
  onQuickToggleActivo,
}) => {
  return (
    <div className="space-y-2">
      {cargando && <p className="text-sm text-gray-500">Cargando...</p>}
      {error && <p className="text-sm text-red-600">{error}</p>}
      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="border-b border-gray-200">
              <th className="text-left py-2 pr-3 font-medium text-gray-700 cursor-pointer select-none" onClick={() => onToggleSort('mac')}>MAC <span className="inline-block ml-1 text-xs align-middle">{sortIcon('mac')}</span></th>
              <th className="text-left py-2 pr-3 font-medium text-gray-700 cursor-pointer select-none" onClick={() => onToggleSort('nombre')}>Nombre <span className="inline-block ml-1 text-xs align-middle">{sortIcon('nombre')}</span></th>
              <th className="text-left py-2 pr-3 font-medium text-gray-700 cursor-pointer select-none" onClick={() => onToggleSort('ip')}>IP <span className="inline-block ml-1 text-xs align-middle">{sortIcon('ip')}</span></th>
              <th className="text-left py-2 pr-3 font-medium text-gray-700 cursor-pointer select-none" onClick={() => onToggleSort('activo')}>Activo <span className="inline-block ml-1 text-xs align-middle">{sortIcon('activo')}</span></th>
              <th className="text-left py-2 pr-3 font-medium text-gray-700">Enlace</th>
              <th className="text-left py-2 pr-3 font-medium text-gray-700">Enlazado por</th>
              <th className="text-left py-2 pr-3 font-medium text-gray-700">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {cargando && dispositivos.length === 0 && Array.from({ length: 5 }).map((_, i) => (
              <tr key={`skeleton-${i}`} className="border-b last:border-0 border-gray-100 animate-pulse">
                <td className="py-1.5 pr-3"><div className="h-3 w-28 bg-gray-200 rounded" /></td>
                <td className="py-1.5 pr-3"><div className="h-3 w-32 bg-gray-200 rounded" /></td>
                <td className="py-1.5 pr-3"><div className="h-3 w-20 bg-gray-200 rounded" /></td>
                <td className="py-1.5 pr-3"><div className="h-3 w-32 bg-gray-200 rounded" /></td>
                <td className="py-1.5 pr-3"><div className="h-3 w-12 bg-gray-200 rounded" /></td>
                <td className="py-1.5 pr-3"><div className="h-3 w-40 bg-gray-200 rounded" /></td>
              </tr>
            ))}
            {dispositivos.map((d) => (
              <tr key={d.mac} className="border-b last:border-0 border-gray-100 hover:bg-gray-50">
                <td className="py-1.5 pr-3 font-mono text-xs text-gray-700">{d.mac}</td>
                <td className="py-1.5 pr-3">
                  {editMac === d.mac ? (
                    <input value={editForm.nombre} onChange={(e) => setEditForm({ ...editForm, nombre: e.target.value })} className="w-40" />
                  ) : (
                    <span className="inline-flex items-center gap-2">
                      <span>{d.nombre}</span>
                      <span className={`text-[10px] px-1.5 py-0.5 rounded-full border ${d.activo ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-300'}`}>
                        {d.activo ? 'Activo' : 'Inactivo'}
                      </span>
                    </span>
                  )}
                </td>
                <td className="py-1.5 pr-3">{editMac === d.mac ? <input value={editForm.ip || ''} onChange={(e) => setEditForm({ ...editForm, ip: e.target.value })} className="w-36" /> : (d.ip || '')}</td>
                <td className="py-1.5 pr-3">{d.enlace ? `${d.enlace.nombre} (${d.enlace.mac})` : <span className="text-gray-400">—</span>}</td>
                <td className="py-1.5 pr-3">{editMac === d.mac ? (
                  <select value={String(editForm.activo ?? d.activo)} onChange={(e) => setEditForm({ ...editForm, activo: e.target.value === 'true' })}>
                    <option value="true">Sí</option>
                    <option value="false">No</option>
                  </select>
                ) : (
                  <span className={d.activo ? 'text-green-700' : 'text-gray-400'}>{d.activo ? 'Sí' : 'No'}</span>
                )}</td>
                <td className="py-1.5 pr-3">{typeof d.enlazado_por_count === 'number' ? d.enlazado_por_count : <span className="text-gray-400">—</span>}</td>
                <td className="py-1.5 pr-3">
                  <div className="flex flex-wrap gap-1.5">
                    <button type="button" onClick={() => onSelect(d.mac)} className="bg-gray-200 text-gray-800 hover:bg-gray-300 px-2 py-1 text-xs">Relaciones</button>
                    {typeof onQuickToggleActivo === 'function' && (
                      <button type="button" onClick={() => onQuickToggleActivo!(d.mac, d.activo)} className="bg-indigo-600 hover:bg-indigo-700 px-2 py-1 text-xs text-white">
                        {d.activo ? 'Desactivar' : 'Activar'}
                      </button>
                    )}
                    <button type="button" onClick={() => onToggleEdit(d)} className="bg-brand-500 hover:bg-brand-600 px-2 py-1 text-xs">{editMac === d.mac ? 'Cancelar' : 'Editar'}</button>
                    {editMac === d.mac && <button type="button" onClick={onSubmitEdit} className="bg-green-600 hover:bg-green-700 px-2 py-1 text-xs">Guardar</button>}
                    <button type="button" onClick={() => onDelete(d.mac)} className="bg-red-600 hover:bg-red-700 px-2 py-1 text-xs">Eliminar</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pagination filtros={filtros} meta={meta} onSetFiltros={onSetFiltros} />
    </div>
  );
};

interface PaginationProps {
  filtros: { page?: number };
  meta: any;
  onSetFiltros: (fn: any) => void;
}

const Pagination: React.FC<PaginationProps> = ({ filtros, meta, onSetFiltros }) => (
  <div className="flex items-center gap-3 pt-2">
    <button disabled={filtros.page === 1} onClick={() => onSetFiltros((prev: any) => ({ ...prev, page: (prev.page || 1) - 1 }))} className="disabled:opacity-40">Prev</button>
    <span className="text-xs text-gray-600">{meta?.current_page} / {meta?.last_page}</span>
    <button disabled={meta && filtros.page === meta.last_page} onClick={() => onSetFiltros((prev: any) => ({ ...prev, page: (prev.page || 1) + 1 }))} className="disabled:opacity-40">Next</button>
  </div>
);
