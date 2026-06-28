# Módulo 2: equipos

## Objetivo

Administrar organizaciones competitivas, sus logos y plantillas de jugadores. El módulo prepara una entidad reutilizable para torneos individuales o por equipos.

## Dependencias

- Núcleo de usuarios, roles, Policies y auditoría.
- Jugadores activos para formar nuevas plantillas.
- Laravel Storage para logos.

## Arquitectura

```text
TeamController
    ↓
TeamFilterRequest / TeamRequest → TeamPolicy
    ↓
TeamService ← PlayerService
    ↓
TeamRepositoryInterface
    ↓
EloquentTeamRepository → Team / player_team / Storage
```

`TeamRequest` centraliza las reglas compartidas de alta y edición. `TeamService` coordina transacciones, archivos, plantilla y auditoría. La consulta de jugadores se reutiliza desde `PlayerService`.

## Modelo de datos

### `teams`

- `name`: nombre único, máximo 120 caracteres.
- `logo_path`: ruta relativa y opcional en el disco público.
- `description`: descripción opcional de hasta 2.000 caracteres.
- `is_active`: disponibilidad para futuras competencias.
- timestamps.

### `player_team`

- clave primaria compuesta por `team_id` y `player_id`.
- `is_captain`: identifica al capitán actual.
- timestamps para conocer la incorporación.
- eliminación en cascada al borrar equipo o jugador.

Un jugador puede pertenecer a varios equipos. La capa de aplicación garantiza que el capitán forme parte de la plantilla seleccionada y que cada edición envíe como máximo uno.

## Flujo de escritura

1. FormRequest valida identidad, logo, estado, integrantes y capitán.
2. TeamService guarda temporalmente el logo nuevo.
3. Una transacción crea o actualiza el equipo y sincroniza `player_team`.
4. Se registra `roster.updated` con plantilla anterior, nueva y capitán.
5. Tras confirmar la transacción se elimina el logo sustituido.
6. Si la transacción falla, se elimina el archivo nuevo.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/teams` | listado y filtros |
| GET | `/teams/create` | alta |
| POST | `/teams` | creación |
| GET | `/teams/{team}` | perfil y plantilla |
| GET | `/teams/{team}/edit` | edición |
| PUT/PATCH | `/teams/{team}` | actualización y sincronización |
| PATCH | `/teams/{team}/status` | activación/desactivación |
| DELETE | `/teams/{team}` | eliminación |

## Permisos

| Operación | Administrador | Organizador | Otros roles |
|---|---:|---:|---:|
| Consultar | Sí | Sí | Sí |
| Crear | Sí | Sí | No |
| Editar plantilla/estado | Sí | Sí | No |
| Eliminar | Sí | Sí | No |

Todas las rutas requieren autenticación y cuenta activa.

## Interfaz

- Listado paginado con búsqueda y filtro de estado.
- Formulario compartido de alta y edición.
- Búsqueda instantánea de jugadores en la plantilla.
- Selección independiente de integrantes y capitán.
- Perfil con logo, estado, descripción y roster enlazado a jugadores.
- Acciones visibles según Policy.

## Archivos

- Disco: `public`.
- Directorio: `teams/`.
- Tamaño máximo: 2 MB.
- Tipos: imágenes reconocidas por Laravel.
- La eliminación del equipo también elimina su logo.

## Auditoría

El Observer registra creación, edición, estado y eliminación. TeamService agrega el evento específico `roster.updated` porque los cambios de pivote no disparan eventos Eloquent del equipo.

## Pruebas

La suite cubre CRUD, reemplazo y eliminación de logo, sincronización de plantilla, capitán válido, nombre único, cambio de estado, permisos, filtros y auditoría del roster.
