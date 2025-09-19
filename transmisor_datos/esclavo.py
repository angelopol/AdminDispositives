"""Esclavo (cliente) que reporta datos del dispositivo hacia la API.

Ahora hereda de `DispositivoClienteBase` para reutilizar la lógica común
compartida también con `Maestro`.

Además, puede levantar un servidor TCP/IP interno con endpoints definibles
por el usuario al construir el objeto: [["nombre_endpoint", funcion], ...].

Protocolo simple por conexión:
 - El cliente envía una línea: "endpoint {json_opcional}"
 - Se invoca la función asociada al endpoint con un dict de parámetros si
     hay JSON, o sin parámetros si no hay JSON. La función puede devolver
     cualquier valor serializable a JSON.
 - Respuesta JSON: { ok: bool, result?: any, error?: str }
"""

from __future__ import annotations

import os
import json
import socketserver
import threading
from typing import Optional, Callable, Dict, Any, List, Tuple

from dispositivo_base import DispositivoClienteBase


class Esclavo(DispositivoClienteBase):
    CLASS_ROL = None  # Mantener compatibilidad: no añadir 'rol' en to_dict

    def __init__(
        self,
        base_url: Optional[str] = None,
        nombre: Optional[str] = None,
        session=None,
        retry_creacion: int = 2,
        api_key: Optional[str] = None,
        header_name: str = "X-API-KEY",
        timeout: int = 5,
        tcp_endpoints: Optional[List[Tuple[str, Callable[..., Any]]]] = None,
        tcp_host: str = "0.0.0.0",
        tcp_port: Optional[int] = None,
        tcp_autostart: bool = True,
    ) -> None:
        # ---- Config TCP antes de init base ----
        self._endpoint_funcs: Dict[str, Callable[..., Any]] = {}
        self.tcp_host = tcp_host
        # Resolver puerto: parámetro > .env(ESCLAVO_PORT) > 9999
        if tcp_port is not None:
            resolved_port = int(tcp_port)
        else:
            env_val = (os.environ.get("ESCLAVO_PORT") or "").strip()
            try:
                resolved_port = int(env_val) if env_val != "" else 9999
            except Exception:
                resolved_port = 9999
        self.tcp_port = resolved_port
        self._tcp_server: Optional[socketserver.ThreadingTCPServer] = None
        self._tcp_thread: Optional[threading.Thread] = None

        super().__init__(
            base_url=base_url,
            nombre=nombre,
            session=session,
            retry_creacion=retry_creacion,
            api_key=api_key,
            header_name=header_name,
            timeout=timeout,
        )
        # ---- Normalizar y registrar endpoints TCP ----
        if tcp_endpoints:
            self.set_endpoints(tcp_endpoints)
        if tcp_autostart and self._endpoint_funcs:
            self.start_server()

    # ----------------- Gestión de endpoints TCP -----------------
    def set_endpoints(self, endpoints: List[Tuple[str, Callable[..., Any]]]) -> None:
        """Registra múltiples endpoints de una sola vez.
        Cada elemento debe ser (nombre: str, funcion: callable).
        """
        for item in endpoints:
            if not isinstance(item, (list, tuple)) or len(item) < 2:
                continue
            name, func = item[0], item[1]
            if isinstance(name, str) and callable(func):
                self._endpoint_funcs[name] = func

    def register_endpoint(self, name: str, func: Callable[..., Any]) -> None:
        self._endpoint_funcs[name] = func

    def unregister_endpoint(self, name: str) -> None:
        self._endpoint_funcs.pop(name, None)

    # ----------------- Servidor TCP -----------------
    def _build_handler(self):
        endpoints = self._endpoint_funcs

        class Handler(socketserver.BaseRequestHandler):
            def handle(self):
                resp: Dict[str, Any]
                try:
                    raw = self.request.recv(4096)
                    line = raw.decode("utf-8", "ignore").strip().splitlines()[0] if raw else ""
                    if not line:
                        return
                    cmd, _, rest = line.partition(" ")
                    cmd = cmd.strip()
                    params: Dict[str, Any] = {}
                    if rest.strip():
                        try:
                            params = json.loads(rest)
                        except Exception:
                            params = {"_raw": rest.strip()}
                    func = endpoints.get(cmd)
                    if not func:
                        resp = {"ok": False, "error": "unknown_endpoint", "endpoint": cmd}
                    else:
                        try:
                            if params:
                                result = func(params)
                            else:
                                result = func()
                            resp = {"ok": True, "result": result}
                        except Exception as e:  # pragma: no cover
                            resp = {"ok": False, "error": str(e)}
                except Exception as e:  # pragma: no cover
                    resp = {"ok": False, "error": str(e)}

                try:
                    out = (json.dumps(resp) + "\n").encode("utf-8")
                    self.request.sendall(out)
                except Exception:
                    pass

        return Handler

    def start_server(self) -> bool:
        if self._tcp_server is not None:
            return True
        try:
            handler = self._build_handler()
            self._tcp_server = socketserver.ThreadingTCPServer((self.tcp_host, self.tcp_port), handler)
            self._tcp_server.daemon_threads = True
            self._tcp_thread = threading.Thread(target=self._tcp_server.serve_forever, daemon=True, name="EsclavoTCP")
            self._tcp_thread.start()
            return True
        except Exception as e:
            print(f"[Esclavo] No se pudo iniciar servidor TCP en {self.tcp_host}:{self.tcp_port}: {e}")
            self._tcp_server = None
            self._tcp_thread = None
            return False

    def stop_server(self) -> None:
        if self._tcp_server is None:
            return
        try:
            self._tcp_server.shutdown()
        except Exception:
            pass
        try:
            self._tcp_server.server_close()
        except Exception:
            pass
        self._tcp_server = None
        self._tcp_thread = None



def _demo():  # pragma: no cover
    # Ejemplo de endpoints: retorna valores simples
    def objeto_presente(params: Dict[str, Any] | None = None):
        return {"presente": True, "params": params or {}}

    def vehiculo_presente(params: Dict[str, Any] | None = None):
        return {"vehiculo": bool(params.get("detected", True)) if params else True}

    # Si no pasas tcp_port, tomará ESCLAVO_PORT del .env o 9999 por defecto
    inst = Esclavo(
        tcp_endpoints=[["objeto_presente", objeto_presente], ["vehiculo_presente", vehiculo_presente]],
    )
    print(json.dumps(inst.to_dict(), indent=2))
    changed = inst.refresh()
    if changed:
        print("IP cambió y fue actualizada.")

# Aliases retrocompatibles (deprecated)
Emisor = Esclavo
Transmisor = Esclavo

if __name__ == "__main__":  # pragma: no cover
	_demo()

