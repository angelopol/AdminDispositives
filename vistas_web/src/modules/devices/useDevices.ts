import { useCallback, useEffect, useState } from 'react';
import { crearDispositivo, eliminarDispositivo, establecerEnlace, listarDispositivos, obtenerRelaciones, actualizarDispositivo } from './api';
import { Dispositivo, DispositivoRelaciones, FiltrosDispositivos, PaginacionMeta } from './types';

interface State {
  dispositivos: Dispositivo[];
  cargando: boolean;
  error?: string;
  meta?: PaginacionMeta;
  seleccionado?: DispositivoRelaciones;
}

const DEFAULT_FILTROS: FiltrosDispositivos = { page: 1, per_page: 10, orden: 'nombre', direccion: 'asc' };

export function useDevices(initial: Partial<FiltrosDispositivos> = {}) {
  const [filtros, setFiltros] = useState<FiltrosDispositivos>({ ...DEFAULT_FILTROS, ...initial });
  const [state, setState] = useState<State>({ dispositivos: [], cargando: false });

  const cargar = useCallback(async () => {
    setState((s) => ({ ...s, cargando: true, error: undefined }));
    try {
      const resp = await listarDispositivos(filtros);
      setState((s) => ({ ...s, dispositivos: resp.data, meta: resp.meta, cargando: false }));
    } catch (e: any) {
      setState((s) => ({ ...s, error: e.message || 'Error cargando', cargando: false }));
    }
  }, [filtros]);

  useEffect(() => {
    cargar();
  }, [cargar]);

  const seleccionar = async (mac: string) => {
    try {
      const rel = await obtenerRelaciones(mac);
      setState((s) => ({ ...s, seleccionado: rel }));
    } catch (e: any) {
      setState((s) => ({ ...s, error: e.message || 'Error obteniendo relaciones' }));
    }
  };

  const crear = async (payload: { mac: string; nombre: string; ip?: string | null; activo?: boolean }) => {
    await crearDispositivo(payload);
    await cargar();
  };

  const actualizar = async (mac: string, payload: Partial<{ nombre: string; ip: string | null; activo: boolean }>) => {
    await actualizarDispositivo(mac, payload);
    await cargar();
  };

  const eliminar = async (mac: string) => {
    await eliminarDispositivo(mac);
    await cargar();
  };

  const cambiarEnlace = async (mac: string, enlace_mac: string | null) => {
    await establecerEnlace(mac, enlace_mac);
    await cargar();
    if (state.seleccionado?.mac === mac) {
      await seleccionar(mac); // refrescar relaciones si estaba abierto
    }
  };

  return {
    ...state,
    filtros,
    setFiltros,
    recargar: cargar,
    seleccionar,
    crear,
    actualizar,
    eliminar,
    cambiarEnlace,
  };
}
