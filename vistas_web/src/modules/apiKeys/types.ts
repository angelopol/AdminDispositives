export interface ApiKey {
  id: number | string;
  name: string;
  plain_preview: string; // últimos caracteres visibles
  active: boolean;
  is_admin: boolean;
  last_used_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface CreateApiKeyPayload {
  name: string;
  is_admin?: boolean;
  active?: boolean;
}

export interface UpdateApiKeyPayload {
  name?: string;
  active?: boolean;
  is_admin?: boolean;
  rotate?: boolean; // si true rota la key
}
