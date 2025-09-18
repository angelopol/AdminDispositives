import React, { useState } from 'react';
import { DeviceManager } from './devices/DeviceManager';
import { ApiKeysManager } from './apiKeys/ApiKeysManager';

export const App: React.FC = () => {
  const [tab, setTab] = useState<'devices' | 'keys'>('devices');
  return (
    <div className="max-w-7xl mx-auto p-6 space-y-8">
      <header className="flex flex-col gap-4">
        <h1 className="text-2xl font-bold tracking-tight">Panel Gestión</h1>
        <nav className="flex gap-2">
          <button
            onClick={() => setTab('devices')}
            className={`px-4 py-2 rounded-md text-sm font-medium border ${tab==='devices' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-zinc-800 border-zinc-700 text-zinc-300 hover:bg-zinc-700'}`}
          >Dispositivos</button>
          <button
            onClick={() => setTab('keys')}
            className={`px-4 py-2 rounded-md text-sm font-medium border ${tab==='keys' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-zinc-800 border-zinc-700 text-zinc-300 hover:bg-zinc-700'}`}
          >API Keys</button>
        </nav>
      </header>
      <main>
        {tab === 'devices' && <DeviceManager />}
        {tab === 'keys' && <ApiKeysManager />}
      </main>
    </div>
  );
};
