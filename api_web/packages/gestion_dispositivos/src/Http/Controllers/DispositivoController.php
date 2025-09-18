<?php

namespace Ezparking\GestionDispositivos\Http\Controllers;

use Ezparking\GestionDispositivos\Models\Dispositivo;
use Ezparking\GestionDispositivos\Models\ApiKey;
use Ezparking\GestionDispositivos\Security\DeviceTokenVerifier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DispositivoController extends Controller
{
    /**
     * Formatea un dispositivo para respuesta JSON incluyendo relación 'enlace'.
     */
    private function formatDispositivo(Dispositivo $d): array
    {
        $d->loadMissing('enlace');
        return [
            'mac' => $d->mac,
            'nombre' => $d->nombre,
            'ip' => $d->ip,
            'activo' => (bool)($d->activo ?? true),
            'enlace_mac' => $d->enlace_mac,
            'enlace' => $d->enlace ? [
                'mac' => $d->enlace->mac,
                'nombre' => $d->enlace->nombre,
                'ip' => $d->enlace->ip,
            ] : null,
            'created_at' => $d->created_at,
            'updated_at' => $d->updated_at,
        ];
    }
    /**
     * Listar dispositivos con filtros y paginación.
     * Parámetros query opcionales:
     *  - mac_prefijo: filtra por prefijo de MAC (case-insensitive)
     *  - page: página (int)
     *  - per_page: tamaño de página (1-100, default 15)
     */
    public function index(Request $request)
    {
        // Soportamos parámetros legacy y nuevos: (search|mac_prefijo), (orden|sort_by), (direccion|sort_dir)
        $validated = $request->validate([
            'mac_prefijo' => 'nullable|string|max:17',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:mac,nombre,created_at,ip,activo',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'orden' => 'nullable|string|in:mac,nombre,created_at,ip,activo', // alias
            'direccion' => 'nullable|string|in:asc,desc', // alias
            'activo' => 'nullable|boolean',
        ]);

        $query = Dispositivo::query()->with('enlace')->withCount('enlazadoPor');

        // Filtro por prefijo MAC (legacy)
        if (!empty($validated['mac_prefijo'])) {
            $pref = strtoupper($validated['mac_prefijo']);
            $query->where('mac', 'LIKE', $pref . '%');
        }

    // Búsqueda general (nombre, mac, ip)
        if (!empty($validated['search'])) {
            $term = trim($validated['search']);
            $query->where(function ($q) use ($term) {
                $like = '%' . str_replace('%', '\%', $term) . '%';
                $q->where('nombre', 'LIKE', $like)
                  ->orWhere('mac', 'LIKE', $like)
                  ->orWhere('ip', 'LIKE', $like);
            });
        }

        // Filtro por estado activo
        if (array_key_exists('activo', $validated) && $validated['activo'] !== null) {
            $query->where('activo', (bool)$validated['activo']);
        }

        $sortBy = $validated['sort_by'] ?? $validated['orden'] ?? 'mac';
        $sortDir = $validated['sort_dir'] ?? $validated['direccion'] ?? 'asc';

        // Aseguramos que el campo ip existe aunque no estaba en validación legacy
        if (!in_array($sortBy, ['mac', 'nombre', 'created_at', 'ip', 'activo'], true)) {
            $sortBy = 'mac';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $perPage = $validated['per_page'] ?? 15;
        $result = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        $items = array_map(function($d) {
            $arr = $this->formatDispositivo($d);
            // Añadimos conteo de inversos si está cargado
            if (isset($d->enlazado_por_count)) {
                $arr['enlazado_por_count'] = $d->enlazado_por_count;
            }
            return $arr;
        }, $result->items());
        return response()->json([
            'ok' => true,
            'data' => $items,
            // Mantener 'pagination' para compatibilidad y duplicar como 'meta' para el frontend actual
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ]
        ]);
    }

    /** Obtener un dispositivo por MAC */
    public function show($mac)
    {
        $dispositivo = Dispositivo::with('enlace')->where('mac', strtoupper($mac))->firstOrFail();
        return response()->json([
            'ok' => true,
            'dispositivo' => $this->formatDispositivo($dispositivo),
        ]);
    }
    /**
     * Registrar dispositivo
     * Campos requeridos: nombre, mac
     * Opcionales: ip, enlace (mac del dispositivo a enlazar)
     */
    public function registrar(Request $request)
    {
        $macRegex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'mac' => ['required','string','max:255',"unique:dispositivos,mac","regex:$macRegex"],
            'ip' => 'nullable|ip',
            'enlace' => [ 'nullable','string','exists:dispositivos,mac',"regex:$macRegex" ],
            'activo' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($data) {
            $dispositivo = new Dispositivo();
            $dispositivo->nombre = $data['nombre'];
            $dispositivo->mac = strtoupper($data['mac']);
            $dispositivo->ip = $data['ip'] ?? null;
            if (array_key_exists('activo', $data)) {
                $dispositivo->activo = (bool)$data['activo'];
            }

            if (!empty($data['enlace'])) {
                $enlace = Dispositivo::where('mac', strtoupper($data['enlace']))->first();
                if ($enlace) {
                    $dispositivo->enlace_mac = $enlace->mac;
                }
            }
            $dispositivo->save();
            $dispositivo->refresh();

            // Generar token asociado al dispositivo (no admin)
            $plain = bin2hex(random_bytes(24));
            $key = new ApiKey();
            $key->name = 'device:' . $dispositivo->mac;
            $key->key_hash = Hash::make($plain);
            $key->plain_preview = substr($plain, 0, 8);
            $key->is_admin = false;
            $key->active = true;
            // Asociar a dispositivo si existe la columna
            if (Schema::hasColumn('api_keys', 'dispositivo_mac')) {
                $key->dispositivo_mac = $dispositivo->mac;
            }
            $key->save();

            return response()->json([
                'ok' => true,
                'dispositivo' => $this->formatDispositivo($dispositivo),
                'token' => [
                    'plain' => $plain, // mostrar solo una vez
                    'preview' => $key->plain_preview,
                    'id' => $key->id,
                ],
            ], 201);
        });
    }

    /**
     * Actualizar dispositivo
     * Campos permitidos: ip, enlace
     */
    public function actualizar($mac, Request $request)
    {
    $dispositivo = Dispositivo::where('mac', strtoupper($mac))->firstOrFail();

        $macRegex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';
        $data = $request->validate([
            'ip' => 'nullable|ip',
            'enlace' => [ 'nullable','string','exists:dispositivos,mac',"regex:$macRegex" ],
            'activo' => 'nullable|boolean',
        ]);

        if (array_key_exists('ip', $data)) {
            $dispositivo->ip = $data['ip'];
        }
        if (array_key_exists('enlace', $data)) {
            if ($data['enlace']) {
                $enlace = Dispositivo::where('mac', strtoupper($data['enlace']))->first();
                $dispositivo->enlace_mac = $enlace?->mac;
            } else {
                $dispositivo->enlace_mac = null; // eliminar enlace si null
            }
        }
        if (array_key_exists('activo', $data)) {
            $dispositivo->activo = (bool)$data['activo'];
        }
        $dispositivo->save();

        return response()->json([
            'ok' => true,
            'dispositivo' => $this->formatDispositivo($dispositivo),
        ]);
    }

    /** Eliminar dispositivo (y su enlace si existe) */
    public function eliminar($mac)
    {
    $dispositivo = Dispositivo::where('mac', strtoupper($mac))->firstOrFail();
        // Desactivar claves vinculadas
        if (Schema::hasColumn('api_keys', 'dispositivo_mac')) {
            ApiKey::where('dispositivo_mac', $dispositivo->mac)->update(['active' => false]);
        }
        $dispositivo->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Dispositivo eliminado'
        ]);
    }

    /** Establecer enlace (mac origen en ruta) */
    public function establecerEnlace($mac, Request $request)
    {
        $macRegex = '/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/';
        $data = $request->validate([
            'enlace' => ['required','string','exists:dispositivos,mac',"regex:$macRegex"],
        ]);

    $dispositivo = Dispositivo::where('mac', strtoupper($mac))->firstOrFail();
    $enlace = Dispositivo::where('mac', strtoupper($data['enlace']))->firstOrFail();
    $dispositivo->enlace_mac = $enlace->mac;
        $dispositivo->save();

        return response()->json([
            'ok' => true,
            'dispositivo' => $this->formatDispositivo($dispositivo),
        ]);
    }

    /** Eliminar enlace */
    public function eliminarEnlace($mac)
    {
    $dispositivo = Dispositivo::where('mac', strtoupper($mac))->firstOrFail();
    $dispositivo->enlace_mac = null;
        $dispositivo->save();

        return response()->json([
            'ok' => true,
            'dispositivo' => $this->formatDispositivo($dispositivo),
        ]);
    }

    /**
     * Obtener dispositivo + lista de dispositivos que lo enlazan (enlazado_por)
     */
    public function relaciones($mac)
    {
        $dispositivo = Dispositivo::with(['enlace','enlazadoPor'])->where('mac', strtoupper($mac))->firstOrFail();
        $payload = $this->formatDispositivo($dispositivo);
        $payload['enlazado_por'] = $dispositivo->enlazadoPor->map(fn($d) => [
            'mac' => $d->mac,
            'nombre' => $d->nombre,
            'ip' => $d->ip,
            'enlace_mac' => $d->enlace_mac,
        ])->values();
        return response()->json([
            'ok' => true,
            'dispositivo' => $payload,
        ]);
    }

    /**
     * Consulta pública de relaciones por mac + token del dispositivo (solo lectura).
     * Body: { mac: string, token: string }
     */
    public function relacionesPorToken(Request $request)
    {
        $data = $request->validate([
            'mac' => ['required','string','max:255'],
            'token' => ['required','string','min:8'],
        ]);

        $mac = $data['mac'];
        $token = $data['token'];

        if (!DeviceTokenVerifier::verify($mac, $token)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'MAC o token inválidos, o dispositivo inactivo',
            ], 403);
        }

        $dispositivo = Dispositivo::with(['enlace','enlazadoPor'])->where('mac', strtoupper($mac))->firstOrFail();
        $payload = $this->formatDispositivo($dispositivo);
        $payload['enlazado_por'] = $dispositivo->enlazadoPor->map(fn($d) => [
            'mac' => $d->mac,
            'nombre' => $d->nombre,
            'ip' => $d->ip,
            'enlace_mac' => $d->enlace_mac,
        ])->values();

        return response()->json([
            'ok' => true,
            'dispositivo' => $payload,
        ]);
    }
}
