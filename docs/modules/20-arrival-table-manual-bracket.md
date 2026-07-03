# Módulo 20: mesa de llegada y llave manual

## Objetivo

Permitir que la organización arme la competencia con quienes realmente llegan al evento, sin esperar a completar el cupo inscrito. La llave puede regenerarse al incorporar nuevos presentes mientras todavía no exista ningún resultado oficial.

## Flujo

1. Abrir **Sorteo y llave**.
2. Presionar **Armar llave con presentes** o **Agregar llegadas / regenerar**.
3. Marcar cada participante que ya llegó.
4. Seleccionar **Semillas manuales**.
5. Ordenar las posiciones: `1 vs 2`, `3 vs 4`, `5 vs 6` y así sucesivamente.
6. Revisar la vista previa y confirmar.
7. Si llegan más participantes antes de jugar, volver a la mesa, marcarlos y regenerar.

## Reglas

- Se requieren al menos dos participantes presentes.
- En eliminación simple se requiere una cantidad par para garantizar que todos jueguen.
- Sólo pueden seleccionarse participantes inscritos en el torneo.
- Los ausentes permanecen inscritos, pero no forman parte de la llave activa.
- La última selección queda guardada en los metadatos del sorteo y aparece marcada al regresar.
- La regeneración reemplaza la estructura anterior únicamente si no existen resultados.
- Después del primer resultado, no se permiten cambios de participantes ni regeneración para proteger la integridad competitiva.

## Arquitectura

- `PreviewTournamentDrawRequest`: valida selección, modo y posiciones.
- `TournamentDrawService`: filtra inscritos, valida presentes y persiste participantes activos.
- `ManualSeedingStrategy`: ordena las posiciones manuales.
- `RematchAwarePairingService`: empareja posiciones consecutivas en modo manual.
- `TournamentDrawController`: recupera la selección activa para continuar la llegada.
- Blade y JavaScript: contador, selección rápida, bloqueo de cantidades impares y posiciones dinámicas.

## Pruebas

- Generación con un subconjunto de inscritos.
- Respeto exacto de cruces manuales.
- Incorporación de nuevos presentes antes de resultados.
- Rechazo de participantes externos y cantidades impares.
