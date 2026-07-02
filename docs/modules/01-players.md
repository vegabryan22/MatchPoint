# Módulo 1: jugadores

## Objetivo

Administrar perfiles competitivos independientes de las cuentas de acceso. Un jugador puede participar en torneos aunque todavía no tenga un usuario asociado.

## Arquitectura

```text
PlayerController
    ↓
FormRequests → PlayerPolicy
    ↓
PlayerService
    ↓
PlayerRepositoryInterface
    ↓
EloquentPlayerRepository → Player → MySQL/Storage
```

`PlayerService` concentra las transacciones, sustitución de fotos, activación y eliminación. El repositorio contiene únicamente consultas y persistencia. El Observer de auditoría registra automáticamente las mutaciones.

## Modelo

`Player` contiene:

- `user_id`: relación opcional y única con `users`.
- `name`: nombre civil.
- `nickname`: identificador competitivo único.
- `email`: correo de contacto único.
- `photo_path`: ruta relativa en el disco público.
- `country`: país usado también como filtro.
- `preferred_controller`: enum `ControllerType`.
- `level`: enum `PlayerLevel`.
- `is_active`: disponibilidad para futuras inscripciones.

La eliminación de un usuario conserva el jugador y establece `user_id` en `null`. La eliminación de un jugador borra su foto.

## Niveles

- Principiante (`beginner`).
- Intermedio (`intermediate`).
- Avanzado (`advanced`).
- Profesional (`professional`).

## Controles

- PlayStation.
- Xbox.
- Teclado.
- Otro.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/players` | listado, búsqueda y filtros |
| GET | `/players/create` | formulario de alta |
| POST | `/players` | creación |
| GET | `/players/{player}` | detalle |
| GET | `/players/{player}/edit` | formulario de edición |
| PUT/PATCH | `/players/{player}` | actualización |
| PATCH | `/players/{player}/status` | activar o desactivar |
| DELETE | `/players/{player}` | eliminación |

Todas las rutas requieren autenticación y una cuenta activa.

## Permisos

| Operación | Administrador | Organizador | Otros roles |
|---|---:|---:|---:|
| Consultar | Sí | Sí | Sí |
| Crear | Sí | Sí | No |
| Editar/activar | Sí | Sí | No |
| Eliminar | Sí | Sí | No |

La interfaz oculta acciones no autorizadas, pero la seguridad real reside en `PlayerPolicy` y los FormRequests.

## Fotos

- Disco: `public`.
- Directorio: `players/`.
- Formatos: imágenes reconocidas por Laravel.
- Tamaño máximo: 2 MB.
- Al reemplazar una foto, la anterior se elimina sólo después de confirmar la transacción.
- Si la persistencia falla, se elimina el archivo nuevo para evitar residuos.

`php artisan storage:link` debe ejecutarse en cada entorno desplegado.

## Filtros

El listado acepta búsqueda parcial por nombre, apodo o correo, además de país, nivel y estado. Los filtros se conservan durante la paginación.

## Auditoría

La creación, actualización, cambio de estado y eliminación generan eventos en `audit_logs`, incluyendo actor, IP, modelo y diferencias de valores.

## Torneos asociados

El listado general carga la relación `Player::tournaments` y presenta cada torneo como un enlace directo. Para administradores se muestran todas las inscripciones; para otros roles la consulta se limita a los torneos permitidos por `TournamentAccessService`, evitando revelar competencias ajenas.

Los jugadores sin inscripciones visibles muestran el estado **Sin torneo**.

## Pruebas

La suite valida:

- CRUD completo.
- almacenamiento, reemplazo y eliminación de fotos.
- activación y desactivación.
- permisos de administrador, organizador y usuario regular.
- búsqueda y combinación de filtros.
- unicidad de apodo y correo.
- auditoría automática.
