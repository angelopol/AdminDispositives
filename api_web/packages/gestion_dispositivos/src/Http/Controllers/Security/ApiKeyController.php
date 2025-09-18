<?php

namespace Ezparking\GestionDispositivos\Http\Controllers\Security;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Ezparking\GestionDispositivos\Models\ApiKey;

class ApiKeyController extends Controller
{
    public function index()
    {
        $keys = ApiKey::orderByDesc('id')->get()->map(fn($k) => [
            'id' => $k->id,
            'name' => $k->name,
            'plain_preview' => $k->plain_preview,
            'is_admin' => $k->is_admin,
            'active' => $k->active,
            'last_used_at' => $k->last_used_at,
            'created_at' => $k->created_at,
            'updated_at' => $k->updated_at,
        ]);
        return response()->json(['ok' => true, 'data' => $keys]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'is_admin' => ['sometimes','boolean']
        ]);
        $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = substr($raw, 0, 48);
        $key = ApiKey::create([
            'name' => $data['name'],
            'key_hash' => Hash::make($token),
            'plain_preview' => substr($token, 0, 12),
            'is_admin' => (bool)($data['is_admin'] ?? false),
            'active' => true,
        ]);
        return response()->json([
            'ok' => true,
            'api_key' => [
                'id' => $key->id,
                'name' => $key->name,
                'plain_preview' => $key->plain_preview,
                'is_admin' => $key->is_admin,
                'active' => $key->active,
                'created_at' => $key->created_at,
                'updated_at' => $key->updated_at,
            ],
            'token_plain' => $token
        ], 201);
    }

    public function update($id, Request $request)
    {
        $key = ApiKey::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes','string','max:120'],
            'active' => ['sometimes','boolean'],
            'rotate' => ['sometimes','boolean'],
            'is_admin' => ['sometimes','boolean']
        ]);
        if (array_key_exists('name', $data)) $key->name = $data['name'];
        if (array_key_exists('active', $data)) $key->active = $data['active'];
        if (array_key_exists('is_admin', $data)) $key->is_admin = $data['is_admin'];
        $rotated = null;
        if (!empty($data['rotate'])) {
            $raw = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $token = substr($raw, 0, 48);
            $key->key_hash = Hash::make($token);
            $key->plain_preview = substr($token, 0, 12);
            $rotated = $token;
        }
        $key->save();
        return response()->json([
            'ok' => true,
            'api_key' => [
                'id' => $key->id,
                'name' => $key->name,
                'plain_preview' => $key->plain_preview,
                'is_admin' => $key->is_admin,
                'active' => $key->active,
                'last_used_at' => $key->last_used_at,
                'created_at' => $key->created_at,
                'updated_at' => $key->updated_at,
            ],
            'rotated_token_plain' => $rotated
        ]);
    }

    public function destroy($id)
    {
        $key = ApiKey::findOrFail($id);
        $key->delete();
        return response()->json(['ok' => true, 'message' => 'Api key deleted']);
    }
}
