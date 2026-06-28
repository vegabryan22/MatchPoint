# Módulo 10: grupos, Round Robin y liga

## Objetivo

Completar los formatos no eliminatorios con distribución equilibrada, calendario sin cruces repetidos, resultados con empates, posiciones 3/1/0 y clasificación automática hacia una llave eliminatoria.

## Arquitectura

```text
TournamentGroupController
    ↓
GenerateGroupStageRequest / QualifyGroupStageRequest
    ↓
TournamentPolicy
    ↓
GroupStageService
    ├── RoundRobinScheduleService
    ├── StandingsService
    ├── BracketGenerationService
    └── GroupStageRepositoryInterface
            ↓
groups / group_participants / rounds / matches / scores
```

Los algoritmos puros no conocen Eloquent. `RoundRobinScheduleService` genera jornadas y `StandingsService` calcula posiciones. `GroupStageService` coordina transacciones, persistencia, validaciones y auditoría.

## Modelos

- `TournamentGroup`: grupo o tabla única de liga.
- `GroupParticipant`: participante genérico, modalidad y semilla.
- `Tournament`: grupos, formato e inscripciones.
- `Round`: jornada compartida entre todos los grupos.
- `GameMatch`: partido asociado opcionalmente a `group_id`.
- `Score`: marcador con ganador nullable para empates.

## Migración

`2026_06_27_000007_create_groups_and_link_matches.php`:

- Crea `groups` con torneo, nombre, posición y clasificados.
- Crea `group_participants` con tipo, participante y semilla.
- Agrega `group_id` nullable a `matches`.
- Permite `winner_id` nullable en `scores`.
- Mantiene índices y restricciones únicas.

Los grupos y participantes se eliminan en cascada. Un partido de grupo también desaparece al eliminar su grupo.

## Distribución

Las inscripciones se ordenan por semilla y se reparten mediante serpentina. En dos grupos, el orden sigue `A, B, B, A`; así las semillas consecutivas se equilibran sin repetir código específico por cantidad de grupos.

Round Robin y Liga siempre generan una tabla única. Grupos + Eliminación admite entre 2 y 16 grupos siempre que cada uno reciba al menos dos participantes.

## Calendario circular

Para `N` participantes pares se generan `N - 1` jornadas y `N × (N - 1) / 2` partidos. Para `N` impar se agrega un bye virtual sólo durante el cálculo: se generan `N` jornadas, pero nunca se persiste un partido contra ese marcador.

Después de cada jornada se mantiene fijo el primer participante y se rota el resto. La orientación A/B alterna para equilibrar posiciones. Cada pareja aparece exactamente una vez.

## Jornadas

Cada fecha es una fila de `rounds` con `bracket = group`. Los partidos de todos los grupos comparten esa jornada y se distinguen mediante `group_id`. La secuencia es única dentro de la ronda.

## Resultados y empates

`MatchResultService` permite empate únicamente cuando:

- La ronda es de grupos.
- El formato es Round Robin, Liga o Grupos + Eliminación.
- La serie es BO1.

Las eliminatorias continúan rechazando empates. Un empate guarda `winner_id = null`, completa el partido y despacha `MatchCompleted`. `MatchAdvancementService` reconoce rondas de grupo y no intenta propagar participantes.

## Posiciones

`StandingsService` calcula desde resultados oficiales:

- PJ, V, E y D.
- GF, GC y diferencia.
- Puntos: victoria 3, empate 1, derrota 0.

Desempates:

1. Puntos.
2. Diferencia de goles.
3. Goles a favor.
4. Nombre como orden estable.

No se guardan acumulados; una corrección de resultado actualiza inmediatamente la tabla.

## Clasificación

Grupos + Eliminación exige que:

- El torneo esté en curso.
- Todas las jornadas estén finalizadas.
- No exista una llave principal previa.
- El total de clasificados sea potencia de dos.

Se toman los primeros `qualifiers_count` de cada grupo. El emparejador selecciona oponentes desde el extremo contrario del ranking y evita cruces del mismo grupo. `BracketGenerationService` genera todas las rondas eliminatorias reutilizando el generador de eliminación simple.

## Campeón de liga

Todos los resultados de grupo despachan el evento existente. Cuando finaliza el último partido de Round Robin o Liga, `TournamentChampionService` toma el líder de la tabla y registra la coronación. Grupos + Eliminación espera al ganador de la llave posterior.

## Inscripciones

Crear grupos bloquea altas y retiros igual que un sorteo eliminatorio. `TournamentRegistrationService` comprueba tanto `draw` como `groups` antes de modificar participantes.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/tournaments/{slug}/groups` | grupos, posiciones y jornadas |
| POST | `/tournaments/{slug}/groups` | generar o regenerar calendario |
| POST | `/tournaments/{slug}/groups/qualify` | crear fase eliminatoria |

## Controller y Requests

- `TournamentGroupController::show`: consulta.
- `TournamentGroupController::store`: generación.
- `TournamentGroupController::qualify`: clasificación.
- `GenerateGroupStageRequest`: cantidades y autorización.
- `QualifyGroupStageRequest`: autorización sin entrada adicional.

El controlador no contiene lógica de negocio.

## Policies

- `viewGroups`: todos los usuarios activos.
- `manageGroups`: administradores y organizadores.
- `recordResult`: administradores, organizadores y árbitros mediante `GameMatchPolicy`.

## Interfaz

- Formulario de configuración.
- Reglas 3/1/0 visibles.
- Tabla responsive por grupo.
- Resaltado de posiciones clasificatorias.
- Jornadas en acordeón Bootstrap.
- Captura/corrección de resultados desde cada partido.
- Llave eliminatoria horizontal dentro de la misma pantalla.
- Soporte para jugadores y equipos.

## Auditoría

- `groups.generated`: configuración, participantes y grupos.
- `groups.qualified`: lista de clasificados.
- Resultados y campeones conservan sus eventos de auditoría existentes.

## Pruebas

La suite cubre:

- Método circular con cantidades pares e impares.
- Ausencia de cruces duplicados.
- Generación de Round Robin.
- Distribución equilibrada en grupos.
- Bloqueo de inscripciones.
- Empates y puntos.
- Clasificación con cruces entre grupos distintos.
- Llave completa posterior.
- Campeón automático de liga.
- Consulta pública autenticada y gestión autorizada.

## Dependencia siguiente

El módulo 11 implementará reportes PDF, Excel y CSV para torneos, resultados, posiciones, estadísticas y campeones.
