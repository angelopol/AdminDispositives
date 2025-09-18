import { ApiKey, CreateApiKeyPayload, UpdateApiKeyPayload } from './types';
import axios from 'axios';

// Instancia específica para API Keys con cabecera X-API-KEY (admin) directa.
// Motivo: la instancia original (devices/api.ts) posee interceptor; aquí existía otra instancia SIN interceptor → 401.
// Leemos la admin key directamente para evitar dependencia cruzada.
const ADMIN_KEY = (import.meta as any).env?.VITE_GESTION_DISPOSITIVOS_API_KEY_ADMIN || (window as any)?.GESTION_DISPOSITIVOS_API_KEY_ADMIN;
const BASE_URL = (import.meta as any).env?.VITE_GESTION_DISPOSITIVOS_API_URL || 'http://localhost:8000/';
const base = BASE_URL.replace(/\/$/, '') + '/api';

const client = axios.create({
  baseURL: base,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  }
});

client.interceptors.request.use((config) => {
  if (ADMIN_KEY) {
    config.headers['X-API-KEY'] = ADMIN_KEY;
  }
  return config;
});

export async function listApiKeys(): Promise<ApiKey[]> {
  const { data } = await client.get('/keys');
  // Se asume respuesta { data: [...] } o arreglo directo
  const arr = Array.isArray(data) ? data : (data.data || []);
  return arr as ApiKey[];
}

// El backend devuelve:
// POST /keys => { ok, api_key: {...}, token_plain }
// PUT /keys/{id} con rotate => { ok, api_key: {...}, rotated_token_plain }
// Alineamos nombres a { key, token }
export async function createApiKey(payload: CreateApiKeyPayload): Promise<{ key: ApiKey; token?: string }> {
  const { data } = await client.post('/keys', payload);
  const key: ApiKey = data.api_key || data.key || data.data || data;
  const token: string | undefined = data.token_plain || data.token;
  return { key, token };
}

export async function updateApiKey(id: string | number, payload: UpdateApiKeyPayload): Promise<{ key: ApiKey; token?: string }> {
  const { data } = await client.put(`/keys/${id}`, payload);
  const key: ApiKey = data.api_key || data.key || data.data || data;
  const token: string | undefined = data.rotated_token_plain || data.token_plain || data.token;
  return { key, token };
}

export async function deleteApiKey(id: string | number): Promise<void> {
  await client.delete(`/keys/${id}`);
}
