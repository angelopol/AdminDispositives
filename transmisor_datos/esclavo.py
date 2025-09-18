"""Esclavo (cliente) que reporta datos del dispositivo hacia la API.

Ahora hereda de `DispositivoClienteBase` para reutilizar la lógica común
compartida también con `Maestro`.
"""

from __future__ import annotations

import json
from typing import Optional

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

    # Si en el futuro se requiere lógica específica para Esclavo, se añade aquí.



def _demo():  # pragma: no cover
	inst = Esclavo()
	print(json.dumps(inst.to_dict(), indent=2))
	changed = inst.refresh()
	if changed:
		print("IP cambió y fue actualizada.")

# Aliases retrocompatibles (deprecated)
Emisor = Esclavo
Transmisor = Esclavo

if __name__ == "__main__":  # pragma: no cover
	_demo()

