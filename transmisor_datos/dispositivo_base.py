"""Clase base para clientes de dispositivo (Esclavo / Maestro / futuros roles).

Centraliza la lógica común:
 - Resolución de configuración (base_url, api_key, nombre, session)
 - Obtención de MAC e IP inicial
 - Registro inicial (consulta -> creación -> actualización IP si cambió)
 - Reintentos controlados en creación
 - Helper refresh() para revalidar IP

Puntos de extensión para subclases:
 - CLASS_ROL: cadena opcional para incluir en to_dict (None = no incluir)
 - nombre_prefijo(): método que puede modificar el nombre por defecto
 - build_creation_payload(): personalizar payload POST
 - build_update_payload(): personalizar payload PUT
 - after_registered(existente: dict | None): hook cuando ya está registrado (nuevo o existente)

Compatibilidad: Esclavo antes no devolvía 'rol' en to_dict, por ello CLASS_ROL = None
para Esclavo. Maestro fija CLASS_ROL = "maestro".
"""
from __future__ import annotations

import os
import time
import socket
from typing import Optional, Dict, Any

try:  # Carga perezosa .env si existe
    from dotenv import load_dotenv  # type: ignore
    load_dotenv()
except Exception:  # pragma: no cover
    pass

try:
    import requests  # type: ignore
except ImportError as e:  # pragma: no cover
    raise RuntimeError("Falta dependencia 'requests'. Instala con: pip install requests") from e

from utilidades import obtener_mac_formateada, obtener_ip_local


class DispositivoClienteBase:
    CLASS_ROL: Optional[str] = None  # Subclases pueden sobreescribir

    def __init__(
        self,
        base_url: Optional[str] = None,
        nombre: Optional[str] = None,
        session: Optional[requests.Session] = None,
        retry_creacion: int = 2,
        api_key: Optional[str] = None,
        header_name: str = "X-API-KEY",
        timeout: int = 5,
    ) -> None:
        self._log_prefix = f"[{self.__class__.__name__}]"
        self.timeout = timeout
        base_url_final = base_url or os.environ.get("API_BASE_URL") or "http://localhost:8000"
        self.base_url = base_url_final.rstrip("/")
        self.api_dispositivos = f"{self.base_url}/api/dispositivos"
        self.session = session or requests.Session()
        self.retry_creacion = retry_creacion
        self.mac = obtener_mac_formateada()
        self.nombre = nombre or self._default_nombre()
        self.ip_actual = obtener_ip_local()
        self._registrado = False
        self.api_key = api_key or os.environ.get("API_KEY")
        self.header_name = header_name
        if self.api_key:
            self.session.headers.update({self.header_name: self.api_key})
        self._init_registro()

    # ---------- Métodos potencialmente sobreescribibles ----------
    def _default_nombre(self) -> str:
        return socket.gethostname()

    def build_creation_payload(self) -> Dict[str, Any]:
        return {"mac": self.mac, "nombre": self.nombre, "ip": self.ip_actual}

    def build_update_payload(self) -> Dict[str, Any]:
        return {"nombre": self.nombre, "ip": self.ip_actual}

    def after_registered(self, existente: Optional[Dict[str, Any]]) -> None:
        """Hook vacío: se invoca después de marcar _registrado=True.
        existente = datos devueltos por la API si era existente, o None si se acaba de crear.
        """
        return None

    # ----------------- HTTP helpers -----------------
    def _url_dispositivo(self) -> str:
        return f"{self.api_dispositivos}/{self.mac}"

    def _get(self, url: str):
        return self.session.get(url, timeout=self.timeout)

    def _post(self, url: str, data: Dict[str, Any]):
        return self.session.post(url, json=data, timeout=self.timeout)

    def _put(self, url: str, data: Dict[str, Any]):
        return self.session.put(url, json=data, timeout=self.timeout)

    # ----------------- Registro / actualización -----------------
    def _init_registro(self) -> None:
        try:
            r = self._get(self._url_dispositivo())
            if r.status_code == 404:
                self._crear_dispositivo()
            elif r.ok:
                data = r.json().get("data") or r.json()
                disp = data.get("dispositivo") if isinstance(data, dict) else None
                existente = disp if isinstance(disp, dict) else data
                ip_remota = existente.get("ip") if isinstance(existente, dict) else None
                if ip_remota and ip_remota != self.ip_actual:
                    self._actualizar_ip(existente)
                else:
                    self._registrado = True
                    self.after_registered(existente if isinstance(existente, dict) else None)
            else:
                print(f"{self._log_prefix} Error GET existente: {r.status_code} {r.text}")
        except Exception as e:  # pragma: no cover
            print(f"{self._log_prefix} Excepción en _init_registro: {e}")

    def _crear_dispositivo(self) -> None:
        payload = self.build_creation_payload()
        intento = 0
        while intento <= self.retry_creacion:
            try:
                resp = self._post(self.api_dispositivos, payload)
                if resp.status_code in (200, 201):
                    self._registrado = True
                    self.after_registered(None)
                    return
                elif resp.status_code == 422:
                    print(f"{self._log_prefix} Validación falló al crear: {resp.text}")
                    return
                else:
                    print(f"{self._log_prefix} Fallo creación (status {resp.status_code}) intento {intento}: {resp.text}")
            except Exception as e:  # pragma: no cover
                print(f"{self._log_prefix} Excepción creando dispositivo (intento {intento}): {e}")
            intento += 1
            time.sleep(1)

    def _actualizar_ip(self, existente: Optional[Dict[str, Any]] = None) -> None:
        payload = self.build_update_payload()
        try:
            resp = self._put(self._url_dispositivo(), payload)
            if resp.ok:
                print(f"{self._log_prefix} IP actualizada en API.")
                self._registrado = True
                self.after_registered(existente)
            else:
                print(f"{self._log_prefix} No se pudo actualizar IP: {resp.status_code} {resp.text}")
        except Exception as e:  # pragma: no cover
            print(f"{self._log_prefix} Excepción actualizando IP: {e}")

    # ----------------- Operaciones públicas -----------------
    def refresh(self) -> bool:
        nueva_ip = obtener_ip_local()
        if nueva_ip != self.ip_actual:
            self.ip_actual = nueva_ip
            self._actualizar_ip()
            return True
        return False

    def to_dict(self) -> Dict[str, Any]:
        data: Dict[str, Any] = {
            "mac": self.mac,
            "nombre": self.nombre,
            "ip": self.ip_actual,
            "registrado": self._registrado,
        }
        if self.CLASS_ROL is not None:
            data["rol"] = self.CLASS_ROL
        return data
