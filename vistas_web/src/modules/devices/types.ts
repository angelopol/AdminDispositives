export interface Dispositivo {
  mac: string;
  nombre: string;
  ip: string | null;
  enlace_mac?: string | null; // oculto en API final, pero útil internamente
  enlace?: Dispositivo | null;
  enlazado_por_count?: number; // conteo de dispositivos que lo enlazan (index)
}

export interface DispositivoRelaciones extends Dispositivo {
  enlazado_por: Dispositivo[];
}

export interface PaginacionMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginacionResponse<T> {
  data: T[];
  meta: PaginacionMeta;
}

export interface FiltrosDispositivos {
  search?: string;
  orden?: 'nombre' | 'mac' | 'ip';
  direccion?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
}
