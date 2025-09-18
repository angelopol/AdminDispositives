import React from 'react';

interface DeviceFormProps {
  form: { mac: string; nombre: string; ip: string };
  setForm: React.Dispatch<React.SetStateAction<{ mac: string; nombre: string; ip: string }>>;
  onSubmit: (e: React.FormEvent) => void;
}

export const DeviceForm: React.FC<DeviceFormProps> = ({ form, setForm, onSubmit }) => {
  return (
    <form onSubmit={onSubmit} className="flex flex-wrap gap-3 items-start">
      <input required placeholder="MAC" value={form.mac} onChange={(e) => setForm({ ...form, mac: e.target.value })} className="flex-1 min-w-[12rem]" />
      <input required placeholder="Nombre" value={form.nombre} onChange={(e) => setForm({ ...form, nombre: e.target.value })} className="flex-1 min-w-[12rem]" />
      <input placeholder="IP" value={form.ip} onChange={(e) => setForm({ ...form, ip: e.target.value })} className="flex-1 min-w-[10rem]" />
      <button type="submit" className="mt-1">Crear</button>
    </form>
  );
};
