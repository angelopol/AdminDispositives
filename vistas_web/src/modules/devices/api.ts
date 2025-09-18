import axios from 'axios';
import { Dispositivo, DispositivoRelaciones, FiltrosDispositivos, PaginacionResponse } from './types';

// Variables de entorno (Vite) esperadas en el proyecto React (prefijo VITE_).
// Mapeamos las variables del archivo .ENV proporcionado a las que Vite puede exponer.
// Asegúrate de renombrar en tu .env local a, por ejemplo:
//   VITE_GESTION_DISPOSITIVOS_API_URL=...
//   VITE_GESTION_DISPOSITIVOS_API_KEY=...
//   VITE_GESTION_DISPOSITIVOS_API_KEY_ADMIN=...
// Si no, puedes adaptar este código a un mecanismo de inyección alternativa.

const BASE_URL = (import.meta as any).env?.VITE_GESTION_DISPOSITIVOS_API_URL || 'http://localhost:8000/';
const API_KEY = (import.meta as any).env?.VITE_GESTION_DISPOSITIVOS_API_KEY || (window as any)?.GESTION_DISPOSITIVOS_API_KEY;
const API_KEY_ADMIN = (import.meta as any).env?.VITE_GESTION_DISPOSITIVOS_API_KEY_ADMIN || (window as any)?.GESTION_DISPOSITIVOS_API_KEY_ADMIN;

// Normalizamos base (asegurarnos que termina con /) y añadimos /api
const apiBase = BASE_URL.replace(/\/$/, '') + '/api';

const api = axios.create({
  baseURL: apiBase,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// Interceptor para añadir cabecera X-API-KEY si existe
api.interceptors.request.use((config) => {
  // Permite usar la admin si se pasa config.headers['X-Use-Admin-Key']
  const useAdmin = (config.headers as any)?.['X-Use-Admin-Key'];
  const key = useAdmin ? API_KEY_ADMIN : API_KEY;
  if (key) {
    config.headers['X-API-KEY'] = key;
  }
  // Limpia marcador interno
  if (useAdmin) {
    delete config.headers['X-Use-Admin-Key'];
  }
  return config;
});

// Exposición opcional para debugging en ventana
if (typeof window !== 'undefined') {
  (window as any).__gestionDispositivosConfig = { BASE_URL: apiBase, hasKey: !!API_KEY, hasAdminKey: !!API_KEY_ADMIN };
}

export async function listarDispositivos(filtros: FiltrosDispositivos): Promise<PaginacionResponse<Dispositivo>> {
  const params: Record<string, any> = {};
  if (filtros.search) params.search = filtros.search; // backend lo soporta
  if (filtros.orden) params.orden = filtros.orden; // alias de sort_by
  if (filtros.direccion) params.direccion = filtros.direccion; // alias de sort_dir
  if (typeof filtros.activo === 'boolean') params.activo = filtros.activo;
  if (filtros.page) params.page = filtros.page;
  if (filtros.per_page) params.per_page = filtros.per_page;
  const { data } = await api.get('/dispositivos', { params });
  const metaSource = data.meta || data.pagination || {};
  return {
    data: data.data as Dispositivo[],
    meta: {
      current_page: metaSource.current_page ?? 1,
      last_page: metaSource.last_page ?? 1,
      per_page: metaSource.per_page ?? (filtros.per_page || 10),
      total: metaSource.total ?? (data.data?.length || 0),
    }
  };
}

export async function crearDispositivo(payload: { mac: string; nombre: string; ip?: string | null; activo?: boolean }) {
  const { data } = await api.post('/dispositivos', payload);
  return data as Dispositivo;
}

export async function actualizarDispositivo(mac: string, payload: Partial<{ nombre: string; ip: string | null; activo: boolean }>) {
  const { data } = await api.put(`/dispositivos/${mac}`, payload);
  return data as Dispositivo;
}

export async function eliminarDispositivo(mac: string) {
  await api.delete(`/dispositivos/${mac}`);
}

export async function establecerEnlace(mac: string, enlace_mac: string | null) {
  const { data } = await api.post(`/dispositivos/${mac}/enlace`, { enlace_mac });
  return data as Dispositivo;
}

export async function obtenerRelaciones(mac: string) {
  const { data } = await api.get(`/dispositivos/${mac}/relaciones`);
  // Estructura backend: { ok: true, dispositivo: { ... , enlazado_por: [] } }
  const dispositivo = data.dispositivo as DispositivoRelaciones | undefined;
  if (!dispositivo) {
    console.warn('Respuesta relaciones sin clave dispositivo', data);
    throw new Error('Respuesta inválida del servidor');
  }
  // Debug temporal (se puede quitar luego)
  if (typeof window !== 'undefined') {
    (window as any).__lastRelaciones = dispositivo;
  }
  return dispositivo;
}
