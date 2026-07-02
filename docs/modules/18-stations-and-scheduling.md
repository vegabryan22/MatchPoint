# Módulo 18: consolas, estaciones y programación

## Objetivo

Registrar la capacidad real de juego de cada torneo y convertir la llave, grupos o liga en un horario ejecutable. El módulo distribuye partidos entre consolas activas, reserva preparación entre encuentros y evita que una ronda dependiente comience antes de finalizar la anterior.

## Arquitectura

```text
TournamentScheduleController / TournamentStationController
    ↓
FormRequests → TournamentPolicy
    ↓
TournamentScheduleService
    ↓
TournamentScheduleRepositoryInterface
    ↓
EloquentTournamentScheduleRepository
    ↓
Tournament / TournamentStation / GameMatch → MySQL
```

Los controladores sólo autorizan, reciben datos validados y devuelven respuestas. `TournamentScheduleService` contiene configuración, reglas de pertenencia, auditoría y algoritmo. El repositorio concentra consultas y persistencia.

## Modelos y migración

La migración `2026_06_30_000016_create_tournament_stations_and_schedule_fields.php` incorpora:

- `tournaments.match_duration_minutes`: duración estimada del partido, 15 minutos por defecto.
- `tournaments.turnaround_minutes`: preparación y cambio de jugadores, 5 minutos por defecto.
- `tournament_stations`: consola, plataforma, ubicación, estado y ventana de disponibilidad.
- `matches.tournament_station_id`: estación asignada.
- `matches.scheduled_end_at`: final estimado; `scheduled_at` conserva el inicio.

Las fechas se crean como `DATETIME` para compatibilidad con MySQL 8 y servidores Plesk con modo SQL estricto.

## Plataformas

`GamingPlatform` admite PlayStation 5, PlayStation 4, Xbox Series, Xbox One, PC, Nintendo Switch y otra plataforma.

## Flujo

1. El administrador u organizador abre **Consolas y horarios** desde el torneo.
2. Configura duración y preparación.
3. Registra cada consola y, opcionalmente, su ubicación y ventana disponible.
4. Genera primero la llave, los grupos o la liga.
5. Indica la hora inicial y genera la programación.
6. Revisa consola, inicio, fin estimado y enfrentamiento.
7. Los árbitros consultan el horario sin capacidad de modificarlo.

## Algoritmo

- Excluye pases automáticos, cancelados, finalizados y finales condicionales.
- Utiliza únicamente estaciones activas.
- Para cada ronda selecciona la consola que queda disponible primero.
- Cada reserva dura `match_duration_minutes`.
- La siguiente reserva de esa consola inicia después de `turnaround_minutes`.
- La siguiente ronda espera el cierre completo de la ronda anterior.
- Respeta `available_from` y `available_until` de cada estación.
- Si la disponibilidad no alcanza, no persiste cambios y devuelve un error de validación.
- La generación es transaccional y reemplaza solamente horarios de partidos pendientes.

## Calculador de capacidad

La misma pantalla ofrece una proyección antes de generar el horario. Sus entradas son participantes inscritos, formato, duración por partido, preparación, consolas activas y tiempo objetivo.

Cuando ya existen rondas, el cálculo usa todos los partidos jugables de la estructura real. Antes del sorteo utiliza estas proyecciones:

- Eliminación simple: `n - 1` partidos distribuidos por rondas.
- Eliminación doble: hasta `2n - 2` partidos sin final de reinicio condicional.
- Round Robin o liga: `n(n - 1) / 2` partidos.
- Grupos más eliminación: grupos proyectados de cuatro participantes y fase eliminatoria posterior.
- Mundial 48: 72 partidos de grupos y 31 eliminatorios, para 103 partidos.

Para cada cantidad `C` de consolas, cada ronda `r` con `Mr` partidos requiere `ceil(Mr / C)` bloques. Si `D` es la duración y `B` la preparación:

```text
duración = suma(ceil(Mr / C) × (D + B)) - B
```

El descuento final evita agregar preparación después del último partido. El mínimo de consolas se obtiene evaluando desde una consola hasta el paralelismo máximo de la ronda más grande. Si la meta es menor que la ruta crítica de rondas, se informa que es imposible aunque se agreguen consolas.

La estimación es operativa, no contractual: pausas institucionales, atrasos y ventanas particulares de estaciones se aplican al generar el horario definitivo.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/tournaments/{slug}/schedule` | Consultar estaciones y horario |
| PUT | `/tournaments/{slug}/schedule/configuration` | Configurar tiempos |
| POST | `/tournaments/{slug}/schedule/generate` | Generar programación |
| DELETE | `/tournaments/{slug}/schedule` | Limpiar partidos pendientes |
| POST | `/tournaments/{slug}/stations` | Crear estación |
| PUT | `/tournaments/{slug}/stations/{station}` | Editar estación |
| DELETE | `/tournaments/{slug}/stations/{station}` | Retirar estación |

## Permisos

| Operación | Administrador | Organizador asignado | Árbitro asignado | Otros |
|---|---:|---:|---:|---:|
| Consultar horario | Sí | Sí | Sí | Según visibilidad del torneo |
| Configurar y generar | Sí | Sí | No | No |
| Gestionar estaciones | Sí | Sí | No | No |

La pertenencia de una estación al torneo se comprueba en el servicio para impedir manipulación cruzada de identificadores.

## Interfaz

- Resumen de consolas activas, duración, preparación, partidos programados y final estimada.
- Formularios Bootstrap responsive para configuración y estaciones.
- Tabla cronológica con consola, fase, enfrentamiento y estado.
- Hora y consola visibles también en llaves y jornadas.

## Auditoría

Se registran `tournament.schedule_configured`, `tournament.schedule_generated`, `tournament.schedule_cleared`, `tournament.station_created`, `tournament.station_updated` y `tournament.station_deleted`.

## Pruebas

`TournamentScheduleTest` valida distribución paralela, separación entre rondas, cálculo de final, proyección para 32 participantes, los 103 partidos del Mundial 48, consolas mínimas, precondiciones, permisos y protección contra estaciones de otro torneo.
