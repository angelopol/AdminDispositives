"""Clase Maestro.

Ahora deriva de `DispositivoClienteBase` para compartir lógica con Esclavo.
Se reserva como punto de extensión para coordinación de múltiples Esclavos
o agregación de métricas.
"""
from __future__ import annotations

import json
import socket
from typing import Optional

from dispositivo_base import DispositivoClienteBase


class Maestro(DispositivoClienteBase):
    CLASS_ROL = "maestro"

    def _default_nombre(self) -> str:  # override
        return f"maestro-{socket.gethostname()}"

    def __init__(
        self,
        base_url: Optional[str] = None,
        nombre: Optional[str] = None,
        session=None,
        retry_creacion: int = 2,
        api_key: Optional[str] = None,
        header_name: str = "X-API-KEY",
        timeout: int = 5,
    ) -> None:
        super().__init__(
            base_url=base_url,
            nombre=nombre,
            session=session,
            retry_creacion=retry_creacion,
            api_key=api_key,
            header_name=header_name,
            timeout=timeout,
        )

    # Hooks futuros específicos para Maestro se pueden añadir aquí.


def _demo():  # pragma: no cover
    m = Maestro()
    print(json.dumps(m.to_dict(), indent=2))
    if m.refresh():
        print("IP cambió y fue actualizada.")


if __name__ == "__main__":  # pragma: no cover
    _demo()
