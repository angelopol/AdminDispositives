from __future__ import annotations

import json
import os
import signal
import sys
import time
from typing import Dict, Any, Optional

from esclavo import Esclavo


# Endpoints de ejemplo

def ping(params: Optional[Dict[str, Any]] = None):
    return {"pong": True, "echo": params or {}}


def estado(params: Optional[Dict[str, Any]] = None):
    # Retorna algún estado simple del esclavo
    return {
        "uptime": time.time(),
        "params": params or {},
    }


def objeto_presente(params: Optional[Dict[str, Any]] = None):
    presente = bool((params or {}).get("presente", True))
    return {"presente": presente}


# Manejo de cierre limpio
_STOP = False


def _handle_sigint(signum, frame):  # pragma: no cover
    global _STOP
    _STOP = True


signal.signal(signal.SIGINT, _handle_sigint)
if hasattr(signal, "SIGTERM"):
    signal.signal(signal.SIGTERM, _handle_sigint)


def main():
    # Toma puerto del .env (ESCLAVO_PORT) si no hay parámetro
    endpoints = [["ping", ping], ["estado", estado], ["objeto_presente", objeto_presente]]

    esclavo = Esclavo(tcp_endpoints=endpoints)  # tcp_autostart=True por defecto

    print("Esclavo iniciado.")
    print(json.dumps(esclavo.to_dict(), indent=2))
    print(f"TCP escuchando en {esclavo.tcp_host}:{esclavo.tcp_port}")

    # Bucle para mantener vivo el proceso hasta que lo paren
    while not _STOP:
        time.sleep(0.5)
    print("Cerrando servidor TCP del Esclavo...")
    esclavo.stop_server()
    print("Terminado.")


if __name__ == "__main__":
    main()
