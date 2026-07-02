# Módulo 4: inscripciones

## Objetivo

Administrar participantes individuales o equipos dentro de un torneo, respetando modalidad, estado, calendario, actividad y capacidad. Incluye carga manual, importación CSV y exportación CSV/XLSX.

## Dependencias

- Núcleo de Torneos.
- Jugadores y Equipos.
- OpenSpout 5.7 para escritura XLSX con memoria acotada.
- Laravel Queue para archivos CSV grandes.

## Arquitectura

```text
TournamentRegistrationController
    ↓
FormRequests → TournamentPolicy
    ↓
TournamentRegistrationService
    ├── TournamentRegistrationImportService → Queue Job
    └── TournamentRegistrationExportService → CSV / OpenSpout XLSX
    ↓
TournamentRegistrationRepositoryInterface
    ↓
tournament_players / tournament_teams
```

El Controller sólo selecciona la respuesta HTTP. Las reglas de cupo, ventana, modalidad y actividad residen en `TournamentRegistrationService`. El repositorio resuelve las diferencias entre jugadores y equipos.

## Tablas

`tournament_players` y `tournament_teams` almacenan torneo, participante, usuario que inscribió, origen manual/CSV, semilla futura, fecha efectiva y timestamps. Las combinaciones torneo/participante y torneo/semilla son únicas.

## Reglas

1. El torneo debe estar en estado Inscripciones.
2. La fecha actual debe estar dentro de la ventana configurada.
3. El participante debe existir, estar activo y corresponder a la modalidad.
4. No se permiten duplicados ni sobrecupo.
5. Altas y retiros bloquean la fila del torneo durante la transacción.

## Importación CSV

Individual:

```csv
nickname,email
Tico10,tico10@example.com
```

Equipos:

```csv
name
Ticos Elite
```

El importador acepta hasta 5.000 filas, ignora filas vacías, conserva filas válidas aunque otras fallen y devuelve un reporte detallado. Archivos mayores a `MATCHPOINT_IMPORT_QUEUE_THRESHOLD_BYTES` se procesan con `ImportTournamentRegistrations` y se eliminan al terminar.

## Exportaciones

- CSV UTF-8 con BOM para Excel.
- XLSX real generado con OpenSpout.
- Columnas adaptadas a modalidad.
- Archivos temporales eliminados después de descargar.
- Requiere extensión PHP `zip`.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/tournaments/{slug}/registrations` | listado y candidatos |
| POST | `/tournaments/{slug}/registrations` | inscripción manual |
| DELETE | `/tournaments/{slug}/registrations/{id}` | retiro |
| POST | `/tournaments/{slug}/registrations/import` | importar CSV |
| GET | `/tournaments/{slug}/registrations/export/csv` | exportar CSV |
| GET | `/tournaments/{slug}/registrations/export/xlsx` | exportar Excel |

## Indicadores de ocupación

El listado de torneos muestra `inscritos / capacidad`. El detalle repite el total, calcula los cupos disponibles y presenta una barra de ocupación con acceso directo a la tabla de inscritos. El cálculo usa `TournamentRegistrationService`, que selecciona jugadores o equipos según la modalidad.

## Permisos

Todos los usuarios activos pueden consultar y exportar. Administradores y organizadores pueden agregar, retirar e importar.

## Auditoría

- `registration.created`.
- `registration.removed`.
- `registration.imported`.

## Pruebas

La suite cubre modalidades, inactivos, duplicados, capacidad, estado, ventana temporal, retiro, auditoría, importación parcial, Job grande, CSV, XLSX, permisos y búsqueda.
