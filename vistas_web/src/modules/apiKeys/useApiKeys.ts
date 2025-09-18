import { useCallback, useEffect, useState } from 'react';
import { ApiKey, CreateApiKeyPayload, UpdateApiKeyPayload } from './types';
import { listApiKeys, createApiKey, updateApiKey, deleteApiKey } from './api';

interface State {
  keys: ApiKey[];
  loading: boolean;
  error?: string;
  createdToken?: string; // token completo mostrado solo una vez tras crear/rotar
}

export function useApiKeys() {
  const [state, setState] = useState<State>({ keys: [], loading: false });

  const cargar = useCallback(async () => {
    setState((s) => ({ ...s, loading: true, error: undefined }));
    try {
      const keys = await listApiKeys();
      setState((s) => ({ ...s, keys, loading: false }));
    } catch (e: any) {
      setState((s) => ({ ...s, error: e.message || 'Error cargando llaves', loading: false }));
    }
  }, []);

  useEffect(() => { cargar(); }, [cargar]);

  const crear = async (payload: CreateApiKeyPayload) => {
    const { key, token } = await createApiKey(payload);
    setState((s) => ({ ...s, keys: [key, ...s.keys], createdToken: token }));
  };

  const actualizar = async (id: string | number, payload: UpdateApiKeyPayload) => {
    const { key, token } = await updateApiKey(id, payload);
    setState((s) => ({ ...s, keys: s.keys.map(k => k.id === key.id ? key : k), createdToken: token || (payload.rotate ? undefined : s.createdToken) }));
  };

  const eliminar = async (id: string | number) => {
    await deleteApiKey(id);
    setState((s) => ({ ...s, keys: s.keys.filter(k => k.id !== id) }));
  };

  const clearToken = () => setState((s) => ({ ...s, createdToken: undefined }));

  return { ...state, cargar, crear, actualizar, eliminar, clearToken };
}
