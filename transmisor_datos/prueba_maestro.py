from __future__ import annotations

import json
import os
import time
from typing import Optional

from maestro import Maestro


def main():
    # Si tienes DEVICE_API_KEY en .env, el Maestro la usará automáticamente
    m = Maestro()

    # Puedes fijar manualmente la IP del Esclavo si ya la conoces
    # m.enlace_ip = "127.0.0.1"

    # Intervalo de consultas en segundos
    intervalo = float(os.environ.get("MAESTRO_INTERVALO", "2"))

    print("Maestro iniciado.")
    print(json.dumps(m.to_dict(), indent=2))

    # Bucle de comunicación
    count = 0
    while True:
        count += 1
        # Si no hay IP del enlace, intenta refrescar
        if not m.enlace_ip:
            m.actualizar_enlace_info()

        if m.enlace_ip:
            # Ping sin params
            resp_ping = m.llamar_esclavo("ping")
            print(f"[{count}] ping -> {resp_ping}")

            # Estado con params
            resp_estado = m.llamar_esclavo("estado", {"solicitud": count})
            print(f"[{count}] estado -> {resp_estado}")

            # Ejemplo con parámetros
            resp_obj = m.llamar_esclavo("objeto_presente", {"presente": (count % 2 == 0)})
            print(f"[{count}] objeto_presente -> {resp_obj}")
        else:
            print(f"[{count}] Aún sin enlace_ip. Reintentando...")

        time.sleep(intervalo)


if __name__ == "__main__":
    main()
