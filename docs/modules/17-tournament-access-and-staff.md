# Módulo 17 — Acceso y personal por torneo

## Objetivo

Aislar la información competitiva por torneo y sustituir los permisos globales de organizador y árbitro por asignaciones explícitas. El administrador conserva acceso total, puede mover torneos entre organizadores y administrar cualquier árbitro.

## Arquitectura

```text
TournamentStaffController
    → AssignTournamentOrganizerRequest / AssignTournamentOfficialRequest
    → TournamentStaffService
        → TournamentOrganizer / TournamentOfficial
        → AuditService

Policies y repositorios
    → TournamentAccessService
        → organizadores asignados
        → árbitros activos
        → jugador participante
```

`TournamentAccessService` es la fuente única para determinar visibilidad, administración y registro de marcadores. Las Policies bloquean acceso directo por URL y los repositorios filtran listados, métricas y reportes.

## Modelos y migración

- `tournament_organizers`: torneo, usuario, administrador que asignó, indicador principal y fecha.
- `tournament_officials`: torneo, usuario, asignador, función, estado y fecha.
- `players.managed_by` y `teams.managed_by`: conservan acceso para el organizador que creó registros todavía no inscritos.
- La migración incorpora automáticamente como organizador principal al creador histórico que tenga rol `Organizer`.

## Permisos

| Perfil | Visibilidad | Administración |
| --- | --- | --- |
| Administrador | Todos los torneos y datos | Asigna, transfiere y retira organizadores y árbitros |
| Organizador | Torneos asignados | Configura sus torneos y administra sus árbitros |
| Árbitro | Torneos asignados como oficial activo | Registra y corrige resultados |
| Jugador | Torneos donde está inscrito | Consulta únicamente |

`created_by` permanece como dato de auditoría. La propiedad operativa se determina mediante `tournament_organizers`.

## Rutas

| Método | Ruta | Capacidad |
| --- | --- | --- |
| GET | `/tournaments/{slug}/staff` | Administrar personal |
| POST | `/tournaments/{slug}/staff/organizers` | Sólo administrador |
| DELETE | `/tournaments/{slug}/staff/organizers/{user}` | Sólo administrador |
| POST | `/tournaments/{slug}/staff/officials` | Administrador u organizador asignado |
| DELETE | `/tournaments/{slug}/staff/officials/{user}` | Administrador u organizador asignado |

## Aislamiento de datos

La visibilidad se aplica a torneos, dashboard, próximos partidos, resultados, campeones, estadísticas, reportes, jugadores y equipos. Los filtros enviados manualmente no pueden seleccionar un torneo fuera del alcance del usuario.

## Auditoría

Se registran `tournament.organizer_assigned`, `tournament.organizer_removed`, `tournament.referee_assigned` y `tournament.referee_removed`, incluyendo actor y usuario afectado.

## Pruebas

`TournamentStaffAccessTest` cubre asignación, transferencia, eliminación, organizador principal, árbitro asignado, árbitro ajeno, listado de torneos, dashboard y reportes aislados. Las pruebas de Policies verifican acceso directo a llaves, grupos, inscripciones, jugadores y equipos.
