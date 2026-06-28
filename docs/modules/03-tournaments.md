# Módulo 3: núcleo de torneos

## Objetivo

Administrar la configuración y el ciclo de vida de una competencia. Este módulo crea la entidad estable que utilizarán inscripciones, sorteos, llaves, partidos y estadísticas.

## Alcance

- Crear, consultar, editar, duplicar y eliminar torneos.
- Configurar juego, modalidad, cupos, formato, serie y calendario.
- Controlar estados mediante transiciones explícitas.
- Buscar y filtrar torneos.
- Auditar configuración, duplicación y cambios de estado.

No incluye participantes, importaciones, sorteo, grupos ni llaves. Esas funciones dependen de este núcleo y se implementan en módulos posteriores.

## Arquitectura

```text
TournamentController
    ↓
TournamentRequest / Filter / Transition → TournamentPolicy
    ↓
TournamentService
    ↓
TournamentRepositoryInterface
    ↓
EloquentTournamentRepository → Tournament → MySQL
```

`TournamentService` es la única autoridad para crear slugs, duplicar, editar configuración, eliminar y cambiar estados. El repositorio no contiene reglas de negocio.

## Modelo `Tournament`

| Campo | Descripción |
|---|---|
| `created_by` | usuario creador; queda nulo si la cuenta se elimina |
| `name` | nombre visible |
| `slug` | identificador único y estable usado en URL |
| `description` | información opcional del evento |
| `game` | juego tipado |
| `custom_game` | requerido únicamente para `other` |
| `participant_type` | individual o equipos |
| `max_participants` | 4, 8, 16, 32, 64 o 128 |
| `format` | estructura competitiva |
| `best_of` | mejor de 1, 3 o 5 |
| `status` | estado actual |
| fechas | periodo de inscripción, inicio y final |

La eliminación es lógica mediante `SoftDeletes`, preservando trazabilidad y futuras dependencias históricas.

## Enumeraciones

### Juegos

- EA Sports FC.
- FIFA.
- PES.
- Otro, con nombre personalizado obligatorio.

### Formatos

- Eliminación simple.
- Eliminación doble.
- Round Robin.
- Fase de grupos + eliminación.
- Liga.

### Modalidades

- Individual.
- Equipos.

## Ciclo de estados

| Estado actual | Transiciones admitidas |
|---|---|
| Borrador | Inscripciones, Cancelado |
| Inscripciones | Borrador, En curso, Cancelado |
| En curso | Finalizado, Cancelado |
| Finalizado | ninguna |
| Cancelado | Borrador |

La configuración sólo puede editarse en Borrador o Inscripciones. Únicamente Borrador y Cancelado pueden eliminarse. Al finalizar sin una fecha final registrada, el sistema asigna la fecha actual.

## Duplicación

Duplicar conserva juego, modalidad, cupos, formato, serie y descripción. La copia:

- recibe un nombre con prefijo `Copia de`;
- obtiene un slug único;
- vuelve a Borrador;
- elimina las fechas de inscripción;
- programa el inicio una semana después del original o una semana desde hoy;
- conserva la duración estimada si existía;
- registra creador y auditoría.

El slug original no cambia al editar el nombre para mantener URLs estables.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/tournaments` | listado y filtros |
| GET | `/tournaments/create` | formulario de alta |
| POST | `/tournaments` | creación en Borrador |
| GET | `/tournaments/{slug}` | detalle |
| GET | `/tournaments/{slug}/edit` | edición |
| PUT/PATCH | `/tournaments/{slug}` | actualización |
| POST | `/tournaments/{slug}/duplicate` | duplicación |
| PATCH | `/tournaments/{slug}/status` | transición |
| DELETE | `/tournaments/{slug}` | eliminación lógica |

## Permisos

| Operación | Administrador | Organizador | Otros roles |
|---|---:|---:|---:|
| Consultar | Sí | Sí | Sí |
| Crear/editar | Sí | Sí | No |
| Duplicar/transicionar | Sí | Sí | No |
| Eliminar | Sí | Sí | No |

Los Services vuelven a comprobar invariantes aunque la interfaz o Policy permitan alcanzar una ruta.

## Validaciones de calendario

- Inicio de torneo obligatorio.
- Las fechas de inscripción son opcionales como pareja; si se indica una, ambas son obligatorias.
- Inicio de inscripciones anterior o igual al cierre.
- Cierre de inscripciones anterior o igual al inicio del torneo.
- Final estimada posterior al inicio.

## Auditoría

El Observer registra creación, actualización y eliminación lógica. El Service agrega:

- `tournament.duplicated`.
- `tournament.status_changed`.

## Pruebas

La suite cubre CRUD, slug estable, duplicación, fechas reiniciadas, juego personalizado, normalización, transiciones completas, transiciones inválidas, final automático, bloqueo de edición/eliminación, permisos, filtros y auditoría.
