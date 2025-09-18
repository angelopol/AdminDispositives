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
}
