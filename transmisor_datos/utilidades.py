"""Funciones utilitarias compartidas entre Esclavo y Maestro.

Incluye:
 - obtener_mac_formateada(): retorna la MAC en formato AA:BB:CC:DD:EE:FF
 - obtener_ip_local(): intenta determinar la IP LAN preferida

Si se requieren más helpers (por ejemplo hashing, timestamps, validaciones), se añaden aquí.
"""
from __future__ import annotations

import socket
import uuid
from typing import List, Tuple

__all__ = ["obtener_mac_formateada", "obtener_ip_local"]

def obtener_mac_formateada() -> str:
    raw = uuid.getnode()
    mac_hex = f"{raw:012X}"  # 12 hex chars upper
    return ":".join(mac_hex[i:i+2] for i in range(0, 12, 2))

def obtener_ip_local(timeout: float = 1.0) -> str:
    test_targets: List[Tuple[str, int]] = [("8.8.8.8", 80), ("1.1.1.1", 80)]
    for host, port in test_targets:
        try:
            with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as s:
                s.settimeout(timeout)
                s.connect((host, port))  # no se envía nada realmente
                ip = s.getsockname()[0]
                if ip and not ip.startswith("127."):
                    return ip
        except Exception:
            continue
    try:
        ip = socket.gethostbyname(socket.gethostname())
        if ip:
            return ip
    except Exception:
        pass
    return "127.0.0.1"
