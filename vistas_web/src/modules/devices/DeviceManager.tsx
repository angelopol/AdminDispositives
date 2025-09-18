import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useDevices } from './useDevices';
import { Dispositivo } from './types';
import { DeviceTable } from './components/DeviceTable';
import { DeviceForm } from './components/DeviceForm';
import { FiltersBar } from './components/FiltersBar';
import { DeviceRelations } from './components/DeviceRelations';
import { LinkForm } from './components/LinkForm';

export const DeviceManager: React.FC = () => {
  const { dispositivos, cargando, error, meta, filtros, setFiltros, crear, eliminar, seleccionar, seleccionado, cambiarEnlace, actualizar } = useDevices();
  const [form, setForm] = useState({ mac: '', nombre: '', ip: '' });
  const [editMac, setEditMac] = useState<string | null>(null);
  const [editForm, setEditForm] = useState<{ nombre: string; ip: string | null }>({ nombre: '', ip: '' });

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    await crear({ mac: form.mac, nombre: form.nombre, ip: form.ip || null });
    setForm({ mac: '', nombre: '', ip: '' });
  };

  const submitEdit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editMac) return;
    await actualizar(editMac, { nombre: editForm.nombre, ip: editForm.ip || null });
    setEditMac(null);
  };

  const toggleEdit = (d: Dispositivo) => {
    if (editMac === d.mac) {
      setEditMac(null);
    } else {
      setEditMac(d.mac);
      setEditForm({ nombre: d.nombre, ip: d.ip });
    }
  };

  // Debounce búsqueda
  const searchRef = useRef<HTMLInputElement | null>(null);
  const debounceTimer = useRef<number | null>(null);

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    if (debounceTimer.current) window.clearTimeout(debounceTimer.current);
    debounceTimer.current = window.setTimeout(() => {
      setFiltros({ ...filtros, search: value, page: 1 });
    }, 400);
  };

  // Orden dinámico al click en cabecera
  const toggleSort = useCallback((campo: string) => {
    if (filtros.orden === campo) {
      setFiltros({ ...filtros, direccion: filtros.direccion === 'asc' ? 'desc' : 'asc' });
    } else {
      setFiltros({ ...filtros, orden: campo as any, direccion: 'asc' });
    }
  }, [filtros, setFiltros]);

  const sortIcon = (campo: string) => {
    if (filtros.orden !== campo) return <span className="opacity-30">⇅</span>;
    return filtros.direccion === 'asc' ? <span>▲</span> : <span>▼</span>;
  };

  // Persistencia querystring (search, orden, direccion, page)
  useEffect(() => {
    const params = new URLSearchParams();
    if (filtros.search) params.set('search', filtros.search);
    if (filtros.orden) params.set('orden', filtros.orden);
    if (filtros.direccion) params.set('dir', filtros.direccion);
    if (filtros.page) params.set('page', String(filtros.page));
    const qs = params.toString();
    const newUrl = qs ? `${window.location.pathname}?${qs}` : window.location.pathname;
    window.history.replaceState(null, '', newUrl);
  }, [filtros.search, filtros.orden, filtros.direccion, filtros.page]);

  // Inicializar desde querystring en primer render
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const init: any = {};
    if (params.get('search')) init.search = params.get('search')!;
    if (params.get('orden')) init.orden = params.get('orden')!;
    if (params.get('dir')) init.direccion = params.get('dir')!;
    if (params.get('page')) init.page = parseInt(params.get('page')!, 10) || 1;
    const has = Object.keys(init).length > 0;
    if (has) setFiltros(f => ({ ...f, ...init }));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="grid gap-6">
      <section className="bg-white border border-gray-200 shadow-sm rounded-lg p-5 space-y-4">
        <h2 className="text-lg font-semibold">Nuevo dispositivo</h2>
        <DeviceForm form={form} setForm={setForm} onSubmit={submit} />
      </section>

      <section className="bg-white border border-gray-200 shadow-sm rounded-lg p-5 space-y-4">
        <h2 className="text-lg font-semibold">Listado</h2>
        <FiltersBar
          searchDefault={filtros.search || ''}
          onSearchChange={handleSearchChange}
          orden={filtros.orden || 'nombre'}
          direccion={filtros.direccion || 'asc'}
          setOrden={(v) => setFiltros({ ...filtros, orden: v as any })}
          setDireccion={(v) => setFiltros({ ...filtros, direccion: v })}
        />
        <DeviceTable
          dispositivos={dispositivos}
          cargando={cargando}
          error={error || null}
          filtros={filtros as any}
          meta={meta}
          editMac={editMac}
          editForm={editForm}
          onToggleSort={toggleSort}
          sortIcon={sortIcon}
          onSetFiltros={setFiltros}
          onSelect={seleccionar}
          onToggleEdit={toggleEdit}
          onSubmitEdit={() => submitEdit(new Event('submit') as any)}
          onDelete={eliminar}
          setEditForm={setEditForm}
        />
      </section>

      {seleccionado && (
        <DeviceRelations seleccionado={seleccionado as any} onUnlink={(mac) => cambiarEnlace(mac, null)} />
      )}
    </div>
  );
};
