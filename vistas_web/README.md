# UI React Gestión de Dispositivos

Interfaz en React + TypeScript + Tailwind CSS para consumir la API `/api/dispositivos` (Laravel). Incluye CRUD, ordenamiento, búsqueda con debounce, paginación, visualización de relaciones y skeleton loading.

## Requisitos
- Node.js 18+
- API Laravel ejecutándose (mismo dominio u origin con proxy Vite). La app usa rutas relativas (`/api`).
 - Variables de entorno con prefijo `VITE_` para exponer claves al frontend.

## Instalación
```powershell
cd gestion_dispositivos/vistas
npm install
```

Crear archivo `.env` (copiando de `.env.example`) y completar:
```bash
VITE_GESTION_DISPOSITIVOS_API_URL="http://localhost:8000/"
VITE_GESTION_DISPOSITIVOS_API_KEY="<llave normal>"
VITE_GESTION_DISPOSITIVOS_API_KEY_ADMIN="<llave admin opcional>"
```
Estas se inyectan automáticamente en todas las peticiones mediante un interceptor Axios (`api.ts`) usando la cabecera `X-API-KEY`.

## Scripts
```powershell
npm run dev      # Servidor desarrollo (Vite)
npm run build    # Compila a producción (dist/)
npm run preview  # Sirve la build localmente
```
Dev por defecto: http://localhost:5173

## Tailwind CSS
Configurado vía `tailwind.config.js` + `postcss.config.cjs`. Fuente principal de estilos en `src/index.css` con directivas:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```
Puedes extender el tema (colores, spacing) editando `tailwind.config.js`.

## Arquitectura de Componentes
- `DeviceManager`: Orquesta estado y compone sub-componentes.
- `components/DeviceForm`: Formulario creación.
- `components/FiltersBar`: Búsqueda + selects de orden/dirección (búsqueda con debounce 400ms implementada en el contenedor para control centralizado).
- `components/DeviceTable`: Tabla de dispositivos + edición inline + skeleton + paginación interna.
- `components/DeviceRelations`: Panel lateral (condicional) que muestra enlace y dispositivos que enlazan.
- `components/LinkForm`: Placeholder para futura asignación manual de enlaces.
- `hooks/useDevices`: Lógica de datos (listar, crear, actualizar, eliminar, cambiar enlace, seleccionar).
- `api.ts`: Cliente Axios; traduce filtros y normaliza metadatos de paginación.
	- Añade automáticamente la cabecera `X-API-KEY` usando `VITE_GESTION_DISPOSITIVOS_API_KEY`.
	- Si se establece `config.headers["X-Use-Admin-Key"] = true` en una petición manual, usará `VITE_GESTION_DISPOSITIVOS_API_KEY_ADMIN`.
- `types.ts`: Interfaces TypeScript (`Dispositivo`, `DispositivoRelaciones`, meta paginación, filtros).

## Funcionalidades Clave
- CRUD completo sobre dispositivos (MAC como PK).
- Edición inline (nombre, IP).
- Búsqueda con debounce (espera 400ms tras última tecla antes de solicitar datos).
- Orden dinámico clicando cabeceras (iconos ▲ / ▼ / ⇅).
- Persistencia de filtros en querystring (`search`, `orden`, `dir`, `page`).
- Paginación básica (Prev / Next) usando meta devuelta por API.
- Conteo `enlazado_por_count` y detalle completo al seleccionar un dispositivo.
- Skeleton loading (5 filas animadas) en primera carga/listados vacíos mientras se consulta.

## Flujo de Datos (resumen)
1. `DeviceManager` inicializa filtros leyendo `window.location.search`.
2. `useDevices` dispara fetch al cambiar filtros (search, orden, direccion, page).
3. API responde con `{ data: Dispositivo[], meta }` y cada dispositivo puede incluir `enlace` + `enlazado_por_count`.
4. Al seleccionar un dispositivo se obtiene detalle/relaciones (enlace + enlazado_por[]).

## Integración con Laravel
### Opción A: Vite integrado (recomendado en dev)
En una Blade:
```blade
<div id="gestion-dispositivos-root"></div>
@vite('gestion_dispositivos/vistas/src/main.tsx')
```

### Opción B: Build estática
1. Ejecutar `npm run build`.
2. Copiar carpeta `dist/` a `public/gestion-dispositivos` (o similar):
	 - `public/gestion-dispositivos/assets/*`
3. Incluir en Blade (ajusta hashes reales):
```blade
<link rel="stylesheet" href="{{ asset('gestion-dispositivos/assets/index-XXXXXXXX.css') }}">
<div id="gestion-dispositivos-root"></div>
<script type="module" src="{{ asset('gestion-dispositivos/assets/index-XXXXXXXX.js') }}"></script>
```
4. (Opcional) Añade una ruta dedicada que sirva el Blade contenedor.

### Proxy de API en desarrollo
`vite.config.ts` define un proxy para `/api` hacia tu backend (ej. `http://localhost:8000`). Ajusta si usas otro puerto.

### Autenticación con API Key
El interceptor agrega `X-API-KEY` a cada request si la variable existe. Asegúrate de no commitear `.env` con claves reales.
Para usar la llave admin en un request específico:
```ts
api.get('/dispositivos', { headers: { 'X-Use-Admin-Key': true } });
```

## Formato de Datos Esperado (API)
Listado `/api/dispositivos` (ejemplo simplificado):
```json
{
	"data": [
		{
			"mac": "AA:BB:CC:DD:EE:01",
			"nombre": "Gateway",
			"ip": "192.168.0.10",
			"enlace": null,
			"enlazado_por_count": 2
		}
	],
	"meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

Relaciones `/api/dispositivos/{mac}/relaciones`:
```json
{
	"data": {
		"dispositivo": {
			"mac": "AA:BB:CC:DD:EE:01",
			"nombre": "Gateway",
			"ip": "192.168.0.10",
			"enlace": null,
			"enlazado_por": [ { "mac": "AA:BB:CC:DD:EE:02", "nombre": "Sensor 1", "ip": null } ]
		}
	}
}
```

## Buenas Prácticas / Extensiones Futuras
- Reemplazar estado local de edición por un form modal para cambios más extensos.
- Añadir control de errores global (toasts) y spinners granulares.
- Implementar LinkForm real con autocompletado de MAC.
- Testear hook `useDevices` con mocks Axios.
- Añadir invalidación selectiva (React Query) si el tráfico crece.

## Troubleshooting
| Problema | Posible causa | Solución |
|----------|---------------|----------|
| No carga estilos | Falta build Tailwind | Asegúrate de importar `index.css` y correr `npm run dev`/`build` |
| 404 al llamar /api | Proxy no configurado / backend apagado | Verificar `vite.config.ts` y que Laravel esté levantado |
| 401 / 403 en peticiones | Falta variable `VITE_GESTION_DISPOSITIVOS_API_KEY` | Crear `.env` y reiniciar `npm run dev` |
| Iconos de orden no cambian | Estado filtros no muta | Revisar consola por errores y que `setFiltros` no esté bloqueado |

## Comandos Útiles (PowerShell)
```powershell
npm run dev        # Desarrollo con HMR
npm run build      # Build producción
npm run preview    # Servir build
```

## Notas
TypeScript y ESLint se apoyan en Vite para el build. Si cambias rutas o estructura, actualiza `tailwind.config.js` (contenido `content: []`).

---
Autoría interna. Uso libre dentro del proyecto.
