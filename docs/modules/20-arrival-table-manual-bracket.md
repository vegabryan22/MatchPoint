# Módulo 20: mesa de llegada y llave manual

## Objetivo

Permitir que la organización arme la competencia con quienes realmente llegan al evento, sin esperar a completar el cupo inscrito. Cada grupo de llegadas genera una tanda independiente y las llaves anteriores continúan jugando sin ser reemplazadas.

## Flujo

1. Abrir **Sorteo y llave**.
2. Presionar **Armar llave con presentes** o **Agregar llegadas / regenerar**.
3. Marcar cada participante que ya llegó.
4. Seleccionar **Semillas manuales**.
5. Ordenar las posiciones: `1 vs 2`, `3 vs 4`, `5 vs 6` y así sucesivamente.
6. Revisar la vista previa y confirmar la primera tanda.
7. Cuando lleguen más participantes, usar **Crear nueva tanda** y seleccionar únicamente las nuevas llegadas.
8. Cambiar entre tandas mediante las pestañas superiores de la llave.
9. Cuando existan dos o más ganadores, usar **Crear final entre ganadores**.

## Reglas

- Se requieren al menos dos participantes presentes.
- En eliminación simple se requiere una cantidad par para garantizar que todos jueguen.
- Sólo pueden seleccionarse participantes inscritos en el torneo.
- Los ausentes permanecen inscritos y pueden incorporarse en una tanda posterior.
- Un participante puede competir nuevamente en otra tanda. La mesa lo identifica con **Ya jugó en Tanda X**, pero permite seleccionarlo.
- Las repeticiones quedan registradas en metadatos y auditoría de la tanda nueva.
- Cada tanda conserva rondas, partidos, resultados, programación y ganador propios.
- Crear una tanda nunca elimina, regenera ni modifica llaves anteriores, aunque ya tengan resultados.
- Sólo puede eliminarse una tanda individual sin resultados.
- La final de tandas incluye exactamente a los ganadores de todas las tandas finalizadas.
- El ganador de la final de tandas se registra como campeón general del torneo.

## Arquitectura

- `PreviewTournamentDrawRequest`: valida selección, modo y posiciones.
- `TournamentDrawService`: filtra inscritos, valida nuevas llegadas, crea tandas y genera la final.
- `ManualSeedingStrategy`: ordena las posiciones manuales.
- `RematchAwarePairingService`: empareja posiciones consecutivas en modo manual.
- `TournamentDrawController`: recupera la selección activa para continuar la llegada.
- Blade y JavaScript: contador, selección rápida, bloqueo de cantidades impares y posiciones dinámicas.

## Pruebas

- Generación con un subconjunto de inscritos.
- Respeto exacto de cruces manuales.
- Incorporación de nuevos presentes mientras otras tandas tienen resultados.
- Rechazo de participantes externos y cantidades impares.
- Aislamiento de resultados entre tandas.
- Final entre ganadores y coronación del campeón general.

## Migración

`2026_07_03_000017_add_independent_draw_batches.php` elimina la restricción de un único sorteo por torneo, identifica cada ronda y partido con `tournament_draw_id`, conserva datos existentes como **Tanda 1** y añade ganador y finalización por tanda.
