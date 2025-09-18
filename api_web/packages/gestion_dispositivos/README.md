# Paquete: Gestión de Dispositivos

API para registrar dispositivos y gestionar enlaces entre ellos.

## Instalación en proyecto Laravel principal

1. Añadir el path en `composer.json` del proyecto si no existe:
```json
"repositories": [
  {"type": "path", "url": "packages/gestion_dispositivos"}
]
```
2. Requerir el paquete:
```bash
composer require ezparking/gestion_dispositivos:0.1.0
```
3. Publicar migraciones (opcional si deseas copiarlas):
```bash
php artisan vendor:publish --tag=gestion-dispositivos-migrations
```
4. Ejecutar migraciones:
```bash
php artisan migrate
```
OR
```bash
php artisan migrate --path=packages/gestion_dispositivos/database/migrations
```

## Endpoints

Base (ejemplo): `/api/dispositivos`

### 0. Listar dispositivos
GET `/api/dispositivos`
Parámetros query opcionales:
```
mac_prefijo=AA:BB   # Filtra por prefijo (case-insensitive, se normaliza a mayúsculas)
per_page=50         # Tamaño de página (1-100, default 15)
page=2              # Página
sort_by=nombre      # Campo de orden (mac|nombre|created_at) default mac
sort_dir=desc       # Dirección de orden (asc|desc) default asc
```
Respuesta ejemplo (formato actual con MAC como PK):
```json
{
  "ok": true,
  "data": [
    {
      "mac": "AA:BB:CC:DD:EE:01",
      "nombre": "Sensor 1",
      "ip": "192.168.0.10",
      "enlace_mac": null,
      "enlace": null,
      "created_at": "2025-09-13T14:10:11.000000Z",
      "updated_at": "2025-09-13T14:10:11.000000Z"
    }
  ],
  "pagination": {"current_page":1,"per_page":15,"total":1,"last_page":1}
}
```

### 1. Registrar dispositivo
POST `/api/dispositivos`
Body JSON:
```json
{
  "nombre": "Sensor 1",
  "mac": "AA:BB:CC:DD:EE:01",
  "ip": "192.168.0.10",
  "enlace": "AA:BB:CC:DD:EE:02" // opcional, mac de otro dispositivo
}
```
Respuesta 201 ejemplo:
```json
{
  "ok": true,
  "dispositivo": {
    "mac": "AA:BB:CC:DD:EE:01",
    "nombre": "Sensor 1",
    "ip": "192.168.0.10",
    "enlace_mac": "AA:BB:CC:DD:EE:02",
    "enlace": {
      "mac": "AA:BB:CC:DD:EE:02",
      "nombre": "Sensor 2",
      "ip": "192.168.0.11"
    },
    "created_at": "2025-09-13T14:10:11.000000Z",
    "updated_at": "2025-09-13T14:10:11.000000Z"
  }
}
```

### 2. Actualizar dispositivo
PUT `/api/dispositivos/{mac}`
Body JSON (uno o ambos campos):
```json
{ "ip": "192.168.0.20", "enlace": "AA:BB:CC:DD:EE:02" }
```
Para eliminar enlace: `"enlace": null`

### 3. Eliminar dispositivo
DELETE `/api/dispositivos/{mac}`
Respuesta:
```json
{ "ok": true, "mensaje": "Dispositivo eliminado" }
```

### 4. Establecer enlace
POST `/api/dispositivos/{mac}/enlace`
Body JSON:
```json
{ "enlace": "AA:BB:CC:DD:EE:02" }
```

### 5. Eliminar enlace
DELETE `/api/dispositivos/{mac}/enlace`
Respuesta:
```json
{ "ok": true, "dispositivo": { ... } }
```

### 6. Obtener un dispositivo
GET `/api/dispositivos/{mac}`
Respuesta:
```json
{ "ok": true, "dispositivo": { ... } }
```

### 7. Obtener relaciones (incluye enlazado_por)
GET `/api/dispositivos/{mac}/relaciones`
```json
{
  "ok": true,
  "dispositivo": {
    "mac": "AA:BB:CC:DD:EE:01",
    "nombre": "Gateway Central",
    "ip": "192.168.0.10",
    "enlace_mac": null,
    "enlace": null,
    "enlazado_por": [
      { "mac": "AA:BB:CC:DD:EE:02", "nombre": "Sensor Norte", "ip": "192.168.0.11", "enlace_mac": "AA:BB:CC:DD:EE:01" }
    ],
    "created_at": "...",
    "updated_at": "..."
  }
}
```

## Modelo de datos
Tabla: `dispositivos`
- mac (string, PK y único)  ← Clave primaria natural
- nombre (string)
- ip (string nullable)
- enlace_mac (string nullable, FK a `dispositivos.mac` con nullOnDelete)
- timestamps

Relaciones:
- `enlace()`: belongsTo (self) usando `enlace_mac` → `mac`.
- `enlazadoPor()`: hasMany (self) inversa.

### Razonamiento sobre usar MAC como PK
- La MAC ya es única y estable en el dominio del problema.
- Evita joins adicionales para identificar un dispositivo; se usa directamente en URLs.
- Simplifica seeders y scripting externo (no se necesita traducir ID ↔ MAC).

### Estructura JSON estandarizada
Cada dispositivo en las respuestas incluye:
```jsonc
{
  "mac": "AA:BB:CC:DD:EE:01",
  "nombre": "Sensor 1",
  "ip": "192.168.0.10",
  "enlace_mac": "AA:BB:CC:DD:EE:02",   // puede ser null
  "enlace": {                            // objeto expandido o null
    "mac": "AA:BB:CC:DD:EE:02",
    "nombre": "Sensor 2",
    "ip": "192.168.0.11"
  },
  "created_at": "...",
  "updated_at": "..."
}
```
`enlace` se carga perezosamente en el backend y se incluye ya expandido para evitar una segunda llamada.

## Notas
- MAC validada estrictamente con regex: `^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$` (formato XX:XX:XX:XX:XX:XX)
- Todas las MAC se normalizan internamente a mayúsculas (ej: `aa:bb:cc:dd:ee:01` -> `AA:BB:CC:DD:EE:01`).
- `enlace_mac` permanece visible para facilitar diagnósticos rápidos (además del objeto `enlace`).
- Para quitar un enlace: enviar `"enlace": null` o usar DELETE `/api/dispositivos/{mac}/enlace`.

## Pendientes / Futuro
- Soft deletes.
- Eventos/broadcast de cambios.
- Exportación CSV / endpoints de métricas.

## Seeders

Publicar (opcional) los seeders del paquete:
```bash
php artisan vendor:publish --tag=gestion-dispositivos-seeders
```

Luego ejecutar el seeder (agregar al DatabaseSeeder o correr directo):
```bash
php artisan db:seed --class="Ezparking\GestionDispositivos\Database\Seeders\DispositivoSeeder"
```
OR
```bash
php artisan db:seed --class="Database\Seeders\DispositivoSeeder"
```

El seeder crea dispositivos de ejemplo y establece enlaces a un gateway central.

## Tests

El paquete incluye pruebas de características (Feature) usando Orchestra Testbench.

### Requisitos previos
En el proyecto raíz (que consume el paquete) debe existir `phpunit.xml` configurado (por defecto en Laravel) y el autoload de Composer actualizado.

### Ejecutar sólo los tests del paquete
Desde la raíz del proyecto principal:
```bash
vendor\bin\phpunit --testdox packages/gestion_dispositivos/tests
```

En Windows (PowerShell) también puedes:
```powershell
php vendor/bin/phpunit packages/gestion_dispositivos/tests
```

### Qué cubren las pruebas
- Registrar dispositivo (éxito y MAC inválida)
- Actualizar (nombre/IP) y gestión de enlace
- Listar con filtro por prefijo y ordenamiento
- Mostrar un dispositivo específico (incluye caso 404)
- Establecer y eliminar enlace entre dispositivos
- Eliminar dispositivo

Todas las pruebas usan base de datos en memoria (SQLite) proporcionada por Testbench, por lo que no afectan tus datos reales.

Si necesitas depurar una prueba específica puedes usar `--filter NombreDelTest`.

## Seguridad: API Keys

Las rutas de dispositivos (`/api/dispositivos/*`) están protegidas mediante un middleware `api_key.auth` incluido en el paquete. Además existe un nivel de privilegio “admin” requerido para gestionar las llaves (`/api/keys/*`).

### Cabeceras soportadas

Usa UNA de las dos formas (preferido `X-API-KEY`):

```
X-API-KEY: <TOKEN>
Authorization: ApiKey <TOKEN>
```

### Flujo de creación de una API key

1. Ejecuta el comando del paquete:
  ```bash
  php artisan gestion:apikey:generate "Nombre del Cliente"
  ```
2. Copia el valor completo mostrado (solo se imprime una vez). Ejemplo de salida:
  ```
  API Key creada. Guarda el token ahora:
  abcdEFGHijkLMNOPqrstuVWXYZ1234567890abcdEfGh
  +----+--------------------+--------------+--------+
  | ID | Name               | Preview      | Active |
  +----+--------------------+--------------+--------+
  |  1 | Nombre del Cliente | abcdEFGHijkL | yes    |
  +----+--------------------+--------------+--------+
  ```
3. Configura el cliente (frontend, script Python, etc.) para enviar la cabecera `X-API-KEY`.

### Tipos de llaves

| Tipo     | Campo `is_admin` | Accesos |
|----------|------------------|---------|
| Normal   | false            | Uso de endpoints de dispositivos (`/api/dispositivos/*`). |
| Admin    | true             | Todo lo anterior + administrar llaves (`/api/keys/*`). |

Para crear una llave admin desde consola añade `--admin` al comando. Vía HTTP incluye `"is_admin": true` al crear.

### Migración a llaves admin (upgrade)
Si actualizaste desde una versión previa sin columna `is_admin`, ejecuta:
```bash
php artisan migrate
```
Luego genera al menos una llave admin:
```bash
php artisan gestion:apikey:generate "Master" --admin
```
Sin una llave admin no podrás consumir `/api/keys/*` después de esta actualización.

### Listado y administración vía HTTP (requiere llave admin)

Las rutas `/api/keys` ahora están protegidas con `api_key.auth:admin` y sólo aceptan llaves con `is_admin=true`.

| Método | Ruta             | Descripción                                       |
|--------|------------------|---------------------------------------------------|
| GET    | /api/keys        | Listar claves (sin mostrar token completo)        |
| POST   | /api/keys        | Crear clave (normal o admin) devuelve token una vez |
| PUT    | /api/keys/{id}   | Actualizar nombre / activar / desactivar / rotar / cambiar is_admin |
| DELETE | /api/keys/{id}   | Eliminar clave                                    |

Ejemplo creación vía HTTP:
```bash
curl -X POST -H "Content-Type: application/json" -d '{"name":"Script Sensor"}' http://localhost:8000/api/keys
```
Respuesta (creación normal):
```json
{
  "ok": true,
  "api_key": { "id": 2, "name": "Script Sensor", "plain_preview": "Q1w2E3r4T5y6", "active": true, ... },
  "token_plain": "Q1w2E3r4T5y6AbCdEfGhIjKlMnOpQrStUvWxYz0123456789"
}
```

### Crear una API key admin vía HTTP
```bash
curl -X POST \
  -H "X-API-KEY: <ADMIN_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin Secundaria","is_admin":true}' \
  http://localhost:8000/api/keys
```

### Rotar una API key (requiere admin)

Envía `rotate=true`:
```bash
curl -X PUT -H "Content-Type: application/json" -d '{"rotate":true}' http://localhost:8000/api/keys/2
```
Respuesta incluye `rotated_token_plain` (solo una vez). Sustituye inmediatamente en los clientes.

### Activar / desactivar (requiere admin)

```bash
curl -X PUT -H "Content-Type: application/json" -d '{"active":false}' http://localhost:8000/api/keys/2
```

### Formato de almacenamiento

- Las claves se almacenan con hash bcrypt (`key_hash`).
- Se guarda un `plain_preview` (primeros 12 caracteres) para identificación en listados.
- Campo `last_used_at` se actualiza automáticamente en cada petición válida.

### Buenas prácticas

- Usa llaves admin únicamente para tareas de gestión y mantenlas fuera de clientes embebidos.
- Crea una sola llave admin “master” y deriva llaves normales específicas para cada servicio.
- Rota primero llaves normales antes de rotar la admin.
- Nunca registres el token completo en logs.
- Rota claves periódicamente según políticas de seguridad (ej. cada 90 días).
- Usa variables de entorno para distribuir tokens a servicios (no commit en repositorios).

### Ejemplo de uso (curl) contra rutas protegidas (dispositivos)
```bash
curl -H "X-API-KEY: <TOKEN>" http://localhost:8000/api/dispositivos
```

### Integración rápida en Python (script Transmisor)

En `.env` del script:
```
API_KEY=<TOKEN>
```

En el constructor (si no está ya):
```python
api_key = os.getenv("API_KEY")
if api_key:
   self.session.headers.update({"X-API-KEY": api_key})
```

### Ejemplos de consola con llaves admin

Crear llave admin:
```bash
php artisan gestion:apikey:generate "Master" --admin
```

Crear llave normal:
```bash
php artisan gestion:apikey:generate "Sensor Patio"
```

Salida actualizada del comando (con columna Admin):
```
API Key creada. Guarda el token ahora:
<TOKEN>
+----+-------+--------------+-------+--------+
| ID | Name  | Preview      | Admin | Active |
+----+-------+--------------+-------+--------+
|  3 | Master| AbCdEfGhIjKl | yes   | yes    |
+----+-------+--------------+-------+--------+
```

### Futuras mejoras sugeridas
- Cache interna de hashes activos (optimizar validaciones masivas).
- Rate limiting por API key.
- Scope/roles por key (lectura, escritura, sólo métricas, etc.).
- Auditoría histórica de uso (tabla separada).


