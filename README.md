## EZPARKING · Microservicio de gestión de dispositivos en LAN

Plataforma ligera para descubrir, registrar y gestionar dispositivos conectados en una misma red local (LAN) usando su dirección MAC como identificador primario y autenticación por API Keys. El sistema se compone de tres apps que trabajan en conjunto:

- api_web: API REST en Laravel 12 con un paquete interno de gestión de dispositivos y API keys.
- transmisor_datos: clientes Python (Esclavo/Maestro) que se auto‑registran y reportan cambios de IP.
- vistas_web: panel React + Vite + Tailwind para administración visual (dispositivos y claves).


## Arquitectura y flujo

```
[ Esclavo / Maestro (Python) ] --X-API-KEY--> [ API Laravel (/api) ] <--REST--> [ UI React (Vite) ]
																				 │
																				 └── SQLite / DB (dispositivos, api_keys)
```

- Identidad de dispositivo: MAC (PK, normalizada AA:BB:CC:DD:EE:FF). Opcionalmente se enlaza a otro dispositivo por `enlace_mac`.
- Seguridad: cabecera X-API-KEY obligatoria. Endpoints de claves requieren “admin key”.
- Persistencia: por defecto SQLite (archivo `database/database.sqlite`). Puedes cambiar a MySQL/PostgreSQL editando `api_web/.env`.


## Módulos

### 1) api_web (Laravel + paquete `ezparking/gestion_dispositivos`)

API JSON con rutas bajo `/api`. El paquete registra:

- Middlewares: alias `api_key.auth` (+ variante `api_key.auth:admin` para endpoints admin).
- Migraciones: tablas `dispositivos` y `api_keys` (publicables vía tag).
- Rutas: se agrupan en `/api/dispositivos` y `/api/keys`.
- Comandos Artisan: generar/listar/rotar API keys (consulta `php artisan list` para ver los nombres concretos).

Esquema de datos (resumen):

- dispositivos: mac (PK string), nombre, ip, enlace_mac (FK a dispositivos.mac), timestamps.
- api_keys: name, key_hash, plain_preview, is_admin (bool), active (bool), last_used_at.

Endpoints principales:

- Autenticación: cabecera X-API-KEY en todas las peticiones.
- Dispositivos (requiere key normal):
	- GET `/api/dispositivos` — listar. Query params: `mac_prefijo`, `search`, `per_page` (1–100), `sort_by` in [mac,nombre,created_at,ip], `sort_dir` in [asc,desc]. Aliases legacy: `orden`, `direccion`.
	- GET `/api/dispositivos/{mac}` — detalle.
	- GET `/api/dispositivos/{mac}/relaciones` — detalle + lista de dispositivos que lo enlazan.
	- POST `/api/dispositivos` — crear. Campos: nombre (req), mac (req, formato AA:BB:CC:DD:EE:FF), ip (opt), enlace (opt, MAC existente).
	- PUT `/api/dispositivos/{mac}` — actualizar. Campos: ip (opt), enlace (opt, null para quitar).
	- DELETE `/api/dispositivos/{mac}` — eliminar.
- API Keys (requiere admin):
	- GET `/api/keys/` — listar claves.
	- POST `/api/keys/` — crear.
	- PUT `/api/keys/{id}` — actualizar (activar/desactivar, etc.).
	- DELETE `/api/keys/{id}` — eliminar.

Utilidades del paquete:

- Verificación de token por dispositivo: `Ezparking\GestionDispositivos\Security\DeviceTokenVerifier::verify($mac, $token) : bool`
	- Reglas: el dispositivo debe existir y estar activo; el token debe estar activo y vinculado a `dispositivo_mac`.
	- Devuelve `true` si todo es válido; `false` en caso contrario.

Formato de respuesta típico (listado):

```json
{
	"ok": true,
	"data": [
		{
			"mac": "AA:BB:CC:DD:EE:01",
			"nombre": "Gateway",
			"ip": "192.168.0.10",
			"enlace_mac": null,
			"enlace": null,
			"enlazado_por_count": 2,
			"created_at": "2025-09-12T10:00:00Z",
			"updated_at": "2025-09-12T10:05:00Z"
		}
	],
	"meta": { "current_page": 1, "per_page": 15, "total": 1, "last_page": 1 },
	"sorting": { "sort_by": "mac", "sort_dir": "asc" }
}
```


### 2) transmisor_datos (Python)

Clientes “Esclavo” y “Maestro” basados en `requests` que:

- Obtienen su MAC e IP locales.
- GET `/api/dispositivos/{mac}`; si no existe, POST para crearlo; si la IP cambió, PUT para actualizar.
- Incluyen helper `refresh()` para revalidar IP dinámicamente.
- Autenticación por API Key vía cabecera X-API-KEY (configurable). Variables `.env` soportadas:
	- `API_BASE_URL` (por defecto `http://localhost:8000`)
	- `API_KEY` (clave normal o admin según el uso)

Requisitos: Python 3.10+ recomendado.

Instalación y prueba rápida (PowerShell):

```powershell
cd transmisor_datos
python -m venv .venv; .\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
Copy-Item .env.example .env -ErrorAction SilentlyContinue
# Edita .env con API_BASE_URL y API_KEY
python .\esclavo.py   # demo: imprime su estado y fuerza un refresh si cambió la IP
```


### 3) vistas_web (React + Vite + Tailwind)

Panel con dos pestañas: Dispositivos y API Keys.

- Dispositivos: CRUD completo, búsqueda con debounce, orden, paginación, conteo `enlazado_por_count`, panel de relaciones.
- API Keys: administración básica (requiere admin Key).
- Proxy de desarrollo a `/api` configurable en `vite.config.ts`.
- Inyección automática de cabeceras desde variables `.env` con prefijo VITE_:
	- `VITE_GESTION_DISPOSITIVOS_API_URL` (base, ej. http://localhost:8000)
	- `VITE_GESTION_DISPOSITIVOS_API_KEY` (key normal)
	- `VITE_GESTION_DISPOSITIVOS_API_KEY_ADMIN` (key admin, opcional)
	- Para forzar el uso de la admin en una petición puntual, el cliente Axios admite `headers: { 'X-Use-Admin-Key': true }`.

Instalación (PowerShell):

```powershell
cd vistas_web
npm install
Copy-Item .env.example .env -ErrorAction SilentlyContinue
# Edita .env con las VITE_* necesarias
npm run dev
```


## Puesta en marcha (desarrollo)

Requisitos:

- PHP 8.2+, Composer
- Node.js 18+ (para assets y/o UI)
- Python 3.10+

API Laravel:

```powershell
cd api_web
composer install
Copy-Item .env.example .env -ErrorAction SilentlyContinue
php artisan key:generate
# Si usas SQLite por defecto:
if (!(Test-Path .\database\database.sqlite)) { New-Item .\database\database.sqlite -ItemType File | Out-Null }
php artisan migrate
php artisan serve
```

Desarrollo integrado (Laravel + Vite + colas + logs) vía script Composer:

```powershell
cd api_web
composer run dev
```

UI React (opcional en carpeta aparte): ver sección “vistas_web”.

Clientes Python (opcional): ver sección “transmisor_datos”.


## Seguridad y claves (aspectos clave)

- Siempre envía X-API-KEY. Sin ella, los endpoints responden 401/403.
- Distingue “admin key” (para gestionar claves) de “key normal” (para dispositivos/rutas estándar).
- El paquete incluye comandos Artisan para generar/listar/rotar claves. Ejecuta `php artisan list` para verlos y su sintaxis.
- Las claves se almacenan como hash (`key_hash`) y un campo `plain_preview` para mostrar los primeros caracteres al usuario.
- Rotación: crea una nueva key, distribúyela y desactiva la anterior (`active = false`) para minimizar impacto.


## Buenas prácticas y notas

- Formato MAC: usa siempre mayúsculas y separador `:` (el backend normaliza, pero valida formato).
- Búsqueda: usa `search` para buscar en nombre/MAC/IP; `mac_prefijo` para filtrar por prefijo exacto de MAC.
- Ordenación segura: limita `sort_by` a [mac,nombre,created_at,ip] y `sort_dir` a [asc,desc].
- SQLite en desarrollo es práctico; en producción prefiere MySQL/PostgreSQL.
- Vite UI: si sirves la UI desde otro origen, configura el proxy o CORS en Laravel.


## Roadmap corto (ideas)

- Rate limiting por API key.
- Métricas de latencia y healthchecks por dispositivo.
- Eventos/broadcast para cambios en tiempo real (p. ej., Laravel Echo).
- Reintentos exponenciales en el cliente Python.


## Troubleshooting rápido

| Problema | Causa común | Acción |
|---|---|---|
| 401/403 en llamadas | Falta X-API-KEY o key inactiva | Añade la cabecera; verifica `active` y permisos de la key |
| 404 al crear/consultar | MAC mal formateada | Asegura formato AA:BB:CC:DD:EE:FF |
| La IP no se actualiza | Cliente sin permisos o red sin salida | Usa key válida; revisa `obtener_ip_local()` y conectividad |
| UI no ve la API | Proxy Vite o backend caído | Revisa `vite.config.ts` y que Laravel esté corriendo |
| Migraciones fallan | DB no inicializada | Crea `database.sqlite` o ajusta conexión en `.env` |


## Licencia

El paquete `ezparking/gestion_dispositivos` declara licencia MIT. El resto del repositorio se rige por la licencia que definas para el proyecto.


—
Mantenido internamente para EZPARKING. Acepta PRs y sugerencias.

