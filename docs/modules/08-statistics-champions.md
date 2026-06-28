# Módulo 8: estadísticas y campeones históricos

## Objetivo

Transformar resultados oficiales en indicadores competitivos sin duplicar datos y conservar un historial estable de campeones. Las estadísticas siempre se recalculan desde `matches` y `scores`; únicamente la coronación se persiste porque representa un hito de dominio auditable.

## Arquitectura

```text
StatisticsController
    ↓
StatisticsFilterRequest → StatisticsPolicy
    ↓
StatisticsService
    ↓
StatisticsRepositoryInterface
    ↓
matches / scores / players / teams / tournaments

MatchCompleted
    ├── AdvanceCompletedMatch
    └── UpdateTournamentChampion
            ↓
        TournamentChampionService
            ↓
        TournamentChampionRepositoryInterface
            ↓
        tournament_champions
```

El listener de avance se registra antes que el listener de coronación. Así, la final de reinicio ya está cancelada o activada cuando se decide si existe un campeón.

## Modelos involucrados

- `GameMatch`: resultado agregado, participantes, ganador y fecha.
- `Score`: goles de cada juego de una serie.
- `Player`: participante individual.
- `Team`: participante colectivo.
- `Tournament`: juego, formato y contexto competitivo.
- `TournamentChampion`: snapshot del campeón y partido decisivo.

## Migración

`2026_06_27_000006_create_tournament_champions_table.php` crea:

| Campo | Descripción |
|---|---|
| `tournament_id` | torneo único al que pertenece la coronación |
| `participant_type` | jugador o equipo |
| `participant_id` | identificador genérico del campeón |
| `deciding_match_id` | final que determinó el título |
| `crowned_at` | fecha oficial de coronación |

El torneo es único para impedir dos campeones activos. La eliminación del torneo borra el historial asociado; eliminar el partido decisivo conserva la coronación y anula su referencia.

## Estadísticas calculadas

Para cada participante se obtienen:

- Partidos jugados.
- Victorias.
- Derrotas.
- Goles a favor.
- Goles en contra.
- Diferencia de goles.
- Promedio de goles por partido.
- Porcentaje de victorias.
- Racha actual de victorias o derrotas.

Los byes y partidos cancelados no cuentan. Cada serie finalizada cuenta como un partido, mientras todos sus juegos contribuyen al total de goles.

## Ranking

El orden utiliza:

1. Mayor cantidad de victorias.
2. Mayor diferencia de goles.
3. Mayor cantidad de goles a favor.
4. Nombre alfabético como orden visual estable.

Participantes con las tres métricas competitivas iguales comparten posición. La siguiente posición respeta ranking de competición; por ejemplo: `1, 1, 3`.

## Rachas

Los partidos se procesan por `completed_at` e identificador. Si el último resultado fue victoria, la salida es `nV`; si fue derrota, `nD`. Un cambio de resultado reinicia el contador. Las fechas nulas mantienen un orden estable por identificador.

## Filtros

`StatisticsFilterRequest` valida:

- Modalidad individual o equipos.
- Torneo.
- Juego.
- Fecha inicial.
- Fecha final posterior o igual a la inicial.

Los filtros se aplican antes de agregar resultados, por lo que ranking, promedio, porcentaje, racha e historial representan exactamente el subconjunto seleccionado.

## Ficha individual

La ficha reutiliza la misma agregación del ranking y muestra una cronología invertida con:

- Fecha.
- Torneo.
- Rival.
- Goles a favor y en contra.
- Victoria o derrota.

No se guarda ningún contador en `players` o `teams`, evitando inconsistencias al corregir resultados.

## Coronación automática

### Eliminación simple

Un partido finalizado de la llave principal sin destino de ganador es decisivo. Su ganador se registra inmediatamente.

### Eliminación doble

- Si el campeón invicto gana la gran final, el avance cancela el reinicio y se registra al ganador.
- Si gana la llave de perdedores, el reinicio queda pendiente y no se registra campeón todavía.
- Cuando finaliza el reinicio, su ganador se convierte en campeón.

### Correcciones

La sincronización es idempotente:

- Una corrección de final simple actualiza al campeón.
- Si una corrección de gran final activa el reinicio, se revoca temporalmente la coronación.
- Al finalizar o corregir el reinicio, se actualiza nuevamente el campeón.

## Eventos y auditoría

`UpdateTournamentChampion` escucha `MatchCompleted` después de `AdvanceCompletedMatch`. Registra:

- `champion.crowned`: primera coronación.
- `champion.updated`: cambio del participante o partido decisivo.
- `champion.revoked`: el torneo vuelve a quedar sin campeón por una corrección válida.

Cada acción conserva actor, IP, URL y valores anteriores/nuevos.

## Repositories

### `StatisticsRepositoryInterface`

- Consulta partidos finalizados con relaciones necesarias.
- Resuelve jugadores o equipos en una única consulta por tipo.
- Entrega torneos disponibles para filtros.

### `TournamentChampionRepositoryInterface`

- Busca con bloqueo por torneo.
- Crea o actualiza idempotentemente.
- Revoca por torneo.
- Pagina y filtra el historial.
- Obtiene años disponibles.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/statistics` | ranking general filtrado |
| GET | `/statistics/{type}/{participant}` | ficha competitiva |
| GET | `/champions` | salón de la fama |

## Controllers y Requests

- `StatisticsController`: entrega ranking y ficha individual.
- `TournamentChampionController`: entrega historial paginado.
- `StatisticsFilterRequest`: filtros de rendimiento.
- `ChampionFilterRequest`: modalidad, juego y año.

Los controladores no realizan consultas ni cálculos.

## Policies

- `StatisticsPolicy::viewAny`: permite consulta a usuarios activos.
- `TournamentChampionPolicy::viewAny`: permite consultar el historial a usuarios activos.
- `TournamentChampionPolicy::manage`: reserva cualquier gestión administrativa futura a administradores y organizadores.

La coronación actual no tiene endpoint manual y sólo puede producirse desde resultados oficiales.

## Interfaz

- Sidebar con accesos a estadísticas y campeones.
- Filtros responsive.
- Cards de resumen.
- Tabla de ranking con podio, promedio, diferencia y racha.
- Ficha con indicadores e historial de partidos.
- Galería esports de campeones con torneo, juego, modalidad y fecha.
- Compatibilidad con modo oscuro y Bootstrap 5.

## Pruebas

La suite verifica:

- Cálculo exacto de todos los indicadores.
- Ranking y posiciones compartidas.
- Filtros por torneo, juego y fecha.
- Estadísticas e historial de equipos.
- Coronación en eliminación simple.
- Campeón invicto en eliminación doble.
- Espera obligatoria del reinicio.
- Coronación desde el reinicio.
- Revocación por corrección de gran final.
- Auditoría y renderizado de vistas.

## Integración con dashboard

El módulo 9 utiliza los resultados y campeones de este módulo para mostrar métricas reales, próximos partidos, actividad competitiva y últimas coronaciones.
