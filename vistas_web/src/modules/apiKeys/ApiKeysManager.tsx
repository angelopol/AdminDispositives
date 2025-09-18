import React, { useEffect, useState } from 'react';
import { useApiKeys } from './useApiKeys';
import { ApiKey } from './types';

function Badge({ active, admin }: { active: boolean; admin: boolean }) {
  return (
    <div className="flex gap-1 text-xs font-medium">
      <span className={`px-2 py-0.5 rounded-full border ${active ? 'bg-green-600/20 text-green-400 border-green-600/50' : 'bg-zinc-600/20 text-zinc-300 border-zinc-600/50'}`}>{active ? 'ACTIVA' : 'INACTIVA'}</span>
      <span className={`px-2 py-0.5 rounded-full border ${admin ? 'bg-indigo-600/20 text-indigo-300 border-indigo-600/50' : 'bg-slate-600/20 text-slate-300 border-slate-600/50'}`}>{admin ? 'ADMIN' : 'NORMAL'}</span>
    </div>
  );
}

interface EditState { id: ApiKey['id']; name: string; }

export const ApiKeysManager: React.FC = () => {
  const { keys, loading, error, crear, actualizar, eliminar, createdToken, clearToken } = useApiKeys();
  const [form, setForm] = useState({ name: '', is_admin: false });
  const [edit, setEdit] = useState<EditState | null>(null);
  const [filter, setFilter] = useState('');

  const reset = () => { setForm({ name: '', is_admin: false }); setEdit(null); };

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (edit) {
      await actualizar(edit.id, { name: form.name, is_admin: form.is_admin });
      reset();
    } else {
      await crear({ name: form.name, is_admin: form.is_admin });
      reset();
    }
  };

  const rotate = async (k: ApiKey) => {
    await actualizar(k.id, { rotate: true });
  };

  const toggleActive = async (k: ApiKey) => {
    await actualizar(k.id, { active: !k.active });
  };

  const toggleAdmin = async (k: ApiKey) => {
    await actualizar(k.id, { is_admin: !k.is_admin });
  };

  const filtered = keys.filter(k => k.name.toLowerCase().includes(filter.toLowerCase()));

  // Mostrar alerta nativa cuando se obtiene un token nuevo (creación o rotación)
  useEffect(() => {
    if (createdToken) {
      try {
        alert('Nueva API Key (cópiala ahora, no se mostrará de nuevo):\n\n' + createdToken);
      } catch (_) {
        // noop si alert bloqueado
      }
    }
  }, [createdToken]);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-semibold tracking-tight">API Keys</h2>
        <p className="text-sm text-zinc-400">Administra llaves (crear, rotar, activar/desactivar, promover/demover admin).</p>
      </div>

      {createdToken && (
        <div className="p-4 rounded-lg bg-amber-900/30 border border-amber-600/40 flex items-start gap-4">
          <div className="flex-1">
            <p className="text-sm font-medium text-amber-300">Token recién generado/rotado (copia ahora, no volverá a mostrarse):</p>
            <pre className="mt-2 p-2 bg-zinc-900/60 rounded text-xs overflow-x-auto">{createdToken}</pre>
          </div>
          <button onClick={clearToken} className="text-xs px-2 py-1 rounded bg-amber-700/40 hover:bg-amber-700/60">Ocultar</button>
        </div>
      )}

      <form onSubmit={submit} className="grid gap-4 md:grid-cols-4 items-end bg-zinc-900/40 p-4 rounded-lg border border-zinc-700/40">
        <div className="md:col-span-2">
          <label className="text-xs font-medium text-zinc-300 flex flex-col gap-1">
            Nombre
            <input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} required className="px-3 py-2 rounded bg-zinc-800 border border-zinc-700 focus:outline-none focus:ring focus:ring-indigo-600/40 text-sm" placeholder="Servicio X" />
          </label>
        </div>
        <div>
          <label className="text-xs font-medium text-zinc-300 flex items-center gap-2 select-none">
            <input type="checkbox" checked={form.is_admin} onChange={e => setForm(f => ({ ...f, is_admin: e.target.checked }))} className="w-4 h-4" /> Admin
          </label>
        </div>
        <div className="flex gap-2">
          <button type="submit" className="px-4 py-2 rounded text-sm font-medium bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50">{edit ? 'Guardar' : 'Crear'}</button>
          {edit && <button type="button" onClick={reset} className="px-4 py-2 rounded text-sm font-medium bg-zinc-700 hover:bg-zinc-600">Cancelar</button>}
        </div>
      </form>

      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div className="flex items-center gap-2">
          <input value={filter} onChange={e => setFilter(e.target.value)} placeholder="Filtrar por nombre" className="px-3 py-2 rounded bg-zinc-800 border border-zinc-700 focus:outline-none focus:ring focus:ring-indigo-600/40 text-sm w-60" />
          {loading && <span className="text-xs text-zinc-400 animate-pulse">Cargando...</span>}
          {error && <span className="text-xs text-red-400">{error}</span>}
        </div>
        <div className="text-xs text-zinc-400">Total: {filtered.length}</div>
      </div>

      <div className="overflow-auto border border-zinc-700/40 rounded-lg">
        <table className="w-full text-sm">
          <thead className="bg-zinc-900/60 text-xs uppercase text-zinc-400 tracking-wide">
            <tr>
              <th className="text-left px-3 py-2 font-medium">Nombre</th>
              <th className="text-left px-3 py-2 font-medium">Preview</th>
              <th className="text-left px-3 py-2 font-medium">Estado</th>
              <th className="text-left px-3 py-2 font-medium">Último uso</th>
              <th className="text-left px-3 py-2 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(k => (
              <tr key={k.id} className="border-t border-zinc-800/60 hover:bg-zinc-900/40">
                <td className="px-3 py-2 align-middle">
                  {edit?.id === k.id ? (
                    <input value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} className="px-2 py-1 rounded bg-zinc-800 border border-zinc-600 text-xs" />
                  ) : (
                    <span className="font-medium text-zinc-200">{k.name}</span>
                  )}
                </td>
                <td className="px-3 py-2 align-middle font-mono text-xs text-zinc-400">{k.plain_preview}</td>
                <td className="px-3 py-2 align-middle"><Badge active={k.active} admin={k.is_admin} /></td>
                <td className="px-3 py-2 align-middle text-xs text-zinc-400">{k.last_used_at ? new Date(k.last_used_at).toLocaleString() : '—'}</td>
                <td className="px-3 py-2 align-middle">
                  <div className="flex flex-wrap gap-2 text-xs">
                    {edit?.id === k.id ? (
                      <button onClick={() => submit(new Event('submit') as any)} className="px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-500">Guardar</button>
                    ) : (
                      <button onClick={() => { setEdit({ id: k.id, name: k.name }); setForm({ name: k.name, is_admin: k.is_admin }); }} className="px-2 py-1 rounded bg-zinc-700 hover:bg-zinc-600">Editar</button>
                    )}
                    <button onClick={() => rotate(k)} className="px-2 py-1 rounded bg-amber-600 hover:bg-amber-500">Rotar</button>
                    <button onClick={() => toggleActive(k)} className="px-2 py-1 rounded bg-blue-600 hover:bg-blue-500">{k.active ? 'Desactivar' : 'Activar'}</button>
                    <button onClick={() => toggleAdmin(k)} className="px-2 py-1 rounded bg-fuchsia-600 hover:bg-fuchsia-500">{k.is_admin ? 'Quitar Admin' : 'Hacer Admin'}</button>
                    <button onClick={() => { if (confirm('Eliminar llave?')) eliminar(k.id); }} className="px-2 py-1 rounded bg-red-600 hover:bg-red-500">Eliminar</button>
                  </div>
                </td>
              </tr>
            ))}
            {!loading && filtered.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-sm text-zinc-400">Sin resultados</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
