<?php

namespace Ezparking\GestionDispositivos\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ezparking\GestionDispositivos\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    /**
     * @param string|null $mustBeAdmin Pass 'admin' para requerir que la llave sea admin
     */
    public function handle(Request $request, Closure $next, ?string $mustBeAdmin = null): Response
    {
        $provided = $this->extractKey($request);
        if (!$provided) {
            return response()->json(['ok' => false, 'message' => 'API key missing'], 401);
        }
        foreach (ApiKey::where('active', true)->get() as $key) {
            if (Hash::check($provided, $key->key_hash)) {
                if ($mustBeAdmin === 'admin' && ! $key->is_admin) {
                    return response()->json(['ok' => false, 'message' => 'Admin API key required'], 403);
                }
                $key->forceFill(['last_used_at' => now()])->saveQuietly();
                $request->attributes->set('api_key_id', $key->id);
                $request->attributes->set('api_key_is_admin', (bool)$key->is_admin);
                if (!empty($key->dispositivo_mac) && !$key->is_admin) {
                    // Token ligado a dispositivo: restringir alcance a su propio recurso
                    if (!$this->authorizeDeviceScopedToken($request, $key->dispositivo_mac)) {
                        return response()->json(['ok' => false, 'message' => 'Device-scoped token not allowed for this operation'], 403);
                    }
                }
                return $next($request);
            }
        }
        return response()->json(['ok' => false, 'message' => 'Invalid API key'], 401);
    }

    private function extractKey(Request $request): ?string
    {
        if ($h = $request->header('X-API-KEY')) return trim($h);
        $auth = $request->header('Authorization');
        if ($auth && str_starts_with($auth, 'ApiKey ')) return trim(substr($auth, 7));
        return null;
    }

    /**
     * Restringe el uso de un token vinculado a un dispositivo a:
     *  - POST /api/dispositivos   (crear) con body.mac == dispositivo_mac
     *  - GET  /api/dispositivos/{mac}  (ver su propio registro)
     *  - PUT  /api/dispositivos/{mac}  (actualizar su propio registro)
     * Todo lo demás -> 403
     */
    private function authorizeDeviceScopedToken(Request $request, string $deviceMac): bool
    {
        $normMac = $this->normalizeMac($deviceMac);
        if ($normMac === null) return false;

        // Bloquear acceso a endpoints de keys o cualquier otro no previsto
        if ($request->is('api/keys*')) return false;

        // Crear propio dispositivo
        if ($request->is('api/dispositivos') && strtoupper($request->method()) === 'POST') {
            $mac = $this->normalizeMac((string) $request->input('mac', ''));
            return $mac !== null && $mac === $normMac;
        }

        // Acceso a su propio recurso
        if ($request->is('api/dispositivos/*')) {
            $routeMac = $this->normalizeMac((string) $request->route('mac'));
            if ($routeMac === null || $routeMac !== $normMac) return false;

            $method = strtoupper($request->method());
            // Permitir solo GET (show) y PUT (actualizar)
            if (in_array($method, ['GET', 'PUT'], true)) {
                // Restringir sub-rutas: no permitir /relaciones ni /enlace
                $path = $request->path();
                if (preg_match('#/relaciones$#', $path) || preg_match('#/enlace$#', $path)) {
                    return false;
                }
                return true;
            }
            return false; // DELETE, POST sub-rutas, etc.
        }

        // Bloquear índice y cualquier otro recurso
        return false;
    }

    private function normalizeMac(string $mac): ?string
    {
        $m = strtoupper(trim($mac));
        $m = str_replace('-', ':', $m);
        if (preg_match('/^[0-9A-F]{12}$/', $m)) {
            $m = implode(':', str_split($m, 2));
        }
        if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $m)) {
            return null;
        }
        return $m;
    }
}
