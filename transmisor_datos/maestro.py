"""Clase Maestro.

Ahora deriva de `DispositivoClienteBase` para compartir lógica con Esclavo.
Extiende funcionalidades para:
 - Forzar uso de la API key del dispositivo tras el registro (no seguir usando la general).
 - Consultar el dispositivo al que está enlazado (relaciones) usando la API key del dispositivo
     y guardar su IP en un atributo del objeto.
"""
from __future__ import annotations

import json
import socket
import os
from typing import Optional

from dispositivo_base import DispositivoClienteBase


class Maestro(DispositivoClienteBase):
    CLASS_ROL = "maestro"
    # IP del dispositivo al que este maestro está enlazado (si aplica)
    enlace_ip: Optional[str] = None
    # API key del dispositivo (plain), usada para consultas propias del equipo
    device_api_key: Optional[str] = None

    def _default_nombre(self) -> str:  # override
        return f"maestro-{socket.gethostname()}"

    def __init__(
        self,
        base_url: Optional[str] = None,
        nombre: Optional[str] = None,
        session=None,
        retry_creacion: int = 2,
        api_key: Optional[str] = None,
        device_api_key: Optional[str] = None,
        header_name: str = "X-API-KEY",
        timeout: int = 5,
    ) -> None:
        # Inicializamos primero atributos propios
        self.enlace_ip = None
        # Permite pasar la API key del dispositivo por parámetro o por entorno
        self.device_api_key = device_api_key or \
            os.environ.get("DEVICE_API_KEY") or \
            os.environ.get("API_KEY_DEVICE") or \
            os.environ.get("DISPOSITIVO_API_KEY")

        super().__init__(
            base_url=base_url,
            nombre=nombre,
            session=session,
            retry_creacion=retry_creacion,
            api_key=api_key,
            header_name=header_name,
            timeout=timeout,
        )
        # Tras __init__ de la base, ya se hizo registro/actualización de IP.
        # Forzar uso de la API key del dispositivo si está disponible.
        if self.device_api_key:
            self.set_device_api_key(self.device_api_key)
            # Y consultar la IP del equipo enlazado
            self.actualizar_enlace_info()

    # ---------- API Key del dispositivo ----------
    def set_device_api_key(self, token: str) -> None:
        """Establece la API key del dispositivo y la usa en cabeceras a futuro.

        Nota: La API general no debe usarse una vez registrado el dispositivo.
        """
        self.device_api_key = token
        # Actualizamos la cabecera por si en base estaba configurada la general
        self.session.headers.update({self.header_name: token})

    # ---------- Hooks específicos ----------
    def after_registered(self, existente: Optional[dict]) -> None:  # type: ignore[override]
        """Tras registrarse/actualizarse, usar token del dispositivo (si existe)
        y actualizar info del enlace.
        """
        if not self.device_api_key:
            # Intentar recuperar de entorno si no fue configurado antes
            token = os.environ.get("DEVICE_API_KEY") or \
                    os.environ.get("API_KEY_DEVICE") or \
                    os.environ.get("DISPOSITIVO_API_KEY")
            if token:
                self.set_device_api_key(token)

        # Si ya tenemos token propio, actualizar info del enlace
        if self.device_api_key:
            self.actualizar_enlace_info()

    # ---------- Consultas de relaciones ----------
    def actualizar_enlace_info(self) -> bool:
        """Consulta relaciones por mac+token y actualiza self.enlace_ip.

        Requiere que `device_api_key` esté configurada.
        """
        if not self.device_api_key:
            # Sin token del dispositivo no podemos consultar relaciones públicas
            return False

        url = f"{self.base_url}/api/dispositivos/relaciones/consulta"
        try:
            r = self.session.post(
                url,
                json={"mac": self.mac, "token": self.device_api_key},
                timeout=self.timeout,
            )
            if not r.ok:
                # 403 u otros => dejar enlace_ip como None
                self.enlace_ip = None
                return False
            payload = r.json()
            if not isinstance(payload, dict) or not payload.get("ok"):
                self.enlace_ip = None
                return False
            disp = payload.get("dispositivo") or {}
            enlace = disp.get("enlace") if isinstance(disp, dict) else None
            self.enlace_ip = enlace.get("ip") if isinstance(enlace, dict) else None
            return True
        except Exception:
            # No interrumpir el flujo del cliente por errores de red
            self.enlace_ip = None
            return False

    # ---------- Comunicación con Esclavo por TCP ----------
    def llamar_esclavo(
        self,
        endpoint: str,
        params: Optional[dict] = None,
        ip: Optional[str] = None,
        port: Optional[int] = None,
        timeout: Optional[float] = None,
    ) -> dict:
        """Llama a un endpoint del Esclavo vía TCP usando su IP.

        - endpoint: nombre del endpoint registrado en el Esclavo
        - params: diccionario opcional que se enviará en JSON
        - ip: IP del esclavo (por defecto usa self.enlace_ip)
        - port: puerto TCP (por defecto ESCLAVO_PORT del .env o 9999)
        - timeout: segundos (por defecto self.timeout)

        Devuelve dict parseado desde la respuesta JSON del Esclavo.
        En caso de error devuelve { ok: False, error: str }.
        """
        target_ip = (ip or self.enlace_ip or "").strip()
        if not target_ip:
            return {"ok": False, "error": "sin_ip_enlace"}

        if port is None:
            env_val = (os.environ.get("ESCLAVO_PORT") or "").strip()
            try:
                port = int(env_val) if env_val != "" else 9999
            except Exception:
                port = 9999

        use_timeout = float(timeout if timeout is not None else self.timeout)

        # Construir línea de protocolo
        try:
            line = endpoint.strip()
            if params is not None:
                line = f"{line} {json.dumps(params, ensure_ascii=False)}"
            payload = (line + "\n").encode("utf-8")
        except Exception as e:
            return {"ok": False, "error": f"encode_error: {e}"}

        try:
            with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                s.settimeout(use_timeout)
                s.connect((target_ip, int(port)))
                s.sendall(payload)

                # Leer hasta nueva línea o cierre
                buf = bytearray()
                while True:
                    chunk = s.recv(4096)
                    if not chunk:
                        break
                    buf.extend(chunk)
                    if b"\n" in chunk:
                        break
            # Decodificar y parsear JSON (considerando posible \n al final)
            text = buf.decode("utf-8", "ignore").strip().splitlines()[0] if buf else ""
            if not text:
                return {"ok": False, "error": "respuesta_vacia"}
            try:
                data = json.loads(text)
                if isinstance(data, dict):
                    return data
                return {"ok": False, "error": "respuesta_no_dict"}
            except Exception as e:
                return {"ok": False, "error": f"json_parse_error: {e}", "raw": text}
        except Exception as e:
            return {"ok": False, "error": str(e)}


def _demo():  # pragma: no cover
    # Se puede exportar DEVICE_API_KEY en .env para la consulta de relaciones
    m = Maestro()
    print(json.dumps(m.to_dict(), indent=2))
    if m.enlace_ip:
        print(f"IP del dispositivo enlazado: {m.enlace_ip}")
    if m.refresh():
        print("IP cambió y fue actualizada.")


if __name__ == "__main__":  # pragma: no cover
    _demo()
