<?php

namespace Ezparking\GestionDispositivos\Security;

use Ezparking\GestionDispositivos\Models\ApiKey;
use Ezparking\GestionDispositivos\Models\Dispositivo;
use Illuminate\Support\Facades\Hash;

/**
 * Verificador de vínculo dispositivo-token.
 *
 * Contrato:
 *  - Entradas: $mac (string MAC AA:BB:CC:DD:EE:FF o variantes), $token (plain)
 *  - Reglas: el dispositivo debe existir y estar activo; el token debe existir, estar activo y
 *            estar vinculado al dispositivo (api_keys.dispositivo_mac = dispositivos.mac)
 *  - Salida: bool (true si todo es válido; false en cualquier otro caso)
 */
class DeviceTokenVerifier
{
    /**
     * Verifica si un token pertenece a un dispositivo y si este está activo.
     */
    public static function verify(string $mac, string $token): bool
    {
        $normMac = self::normalizeMac($mac);
        if ($normMac === null || $token === '') {
            return false;
        }

        $dispositivo = Dispositivo::query()->where('mac', $normMac)->first();
        if (!$dispositivo) {
            return false; // Dispositivo no existe
        }
        // Si el campo no existe o es null, consideramos activo por compatibilidad; si es false, inválido
        if (property_exists($dispositivo, 'activo')) {
            $activo = (bool)($dispositivo->activo ?? true);
            if (!$activo) return false;
        }

        // Buscar cualquier api_key activa vinculada a este dispositivo que haga match con el token
        $keys = ApiKey::query()
            ->where('active', true)
            ->where('dispositivo_mac', $normMac)
            ->get();

        foreach ($keys as $key) {
            if (Hash::check($token, $key->key_hash)) {
                return true;
            }
        }
        return false;
    }

    /** Normaliza MAC a formato AA:BB:CC:DD:EE:FF; devuelve null si no parece válida */
    private static function normalizeMac(string $mac): ?string
    {
        $m = strtoupper(trim($mac));
        $m = str_replace('-', ':', $m);
        // Completar con separadores si el usuario pasó sin ':' (12 hex)
        if (preg_match('/^[0-9A-F]{12}$/', $m)) {
            $m = implode(':', str_split($m, 2));
        }
        if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $m)) {
            return null;
        }
        return $m;
    }
}
