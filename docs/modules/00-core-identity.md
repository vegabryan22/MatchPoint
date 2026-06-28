# Módulo 0: núcleo, identidad y acceso

## Alcance

Proporciona la plataforma mínima segura de la que dependen jugadores y torneos: autenticación, recuperación de contraseña, perfil, usuarios, roles, configuración, auditoría, layout y dashboard base.

## Modelos

| Modelo | Responsabilidad | Relaciones |
|---|---|---|
| `User` | Identidad autenticable y estado | muchos roles, muchos eventos de auditoría |
| `Role` | Capacidad funcional estable | muchos usuarios |
| `Setting` | Parámetro tipado de aplicación | independiente |
| `AuditLog` | Evidencia inmutable de una acción | usuario y modelo polimórfico opcionales |

## Esquema

- `users`: nombre, correo, contraseña, verificación, actividad y último acceso.
- `roles`: nombre, slug único y descripción.
- `role_user`: pivote con clave primaria compuesta.
- `settings`: clave única, valor, tipo, grupo, etiqueta, descripción y visibilidad.
- `audit_logs`: actor, acción, modelo, valores, IP, agente, URL y fecha.
- Laravel también administra sesiones, tokens de contraseña, caché, jobs y jobs fallidos.

## Casos de uso

### Inicio de sesión

`LoginRequest` valida formato. `AuthenticationService` aplica rate limiting, exige cuenta activa, autentica, regenera sesión y registra último acceso. Los eventos nativos activan `RecordSuccessfulLogin`.

### Administración de usuarios

`UserService` crea o actualiza dentro de una transacción, sincroniza roles y encola `UserAccountCreated`. Eliminar la cuenta propia está prohibido por `UserPolicy` y por defensa adicional en el Service.

### Configuración

`SettingService` normaliza valores según su tipo y actualiza el lote de forma atómica.

### Auditoría

`AuditableObserver` registra creación, edición y eliminación de usuarios y ajustes. Listeners registran login y logout. `AuditService` elimina secretos y recopila contexto HTTP.

## Rutas

- Invitado: login, solicitud y ejecución del restablecimiento.
- Autenticado: dashboard, logout, perfil y contraseña.
- Administrador: recurso de usuarios, auditoría y configuración.

Ejecuta `php artisan route:list` para consultar métodos, nombres y middleware efectivos.

## Autorización

- Administrador: usuarios, configuración y auditoría.
- Otros roles: dashboard y perfil propios en este módulo.
- Las capacidades específicas de organizador, árbitro y jugador se incorporan con sus módulos.

## Interfaz

- Layout responsive con sidebar colapsable.
- Tema claro/oscuro persistido en `localStorage`.
- Cards, tablas, modales, badges, paginación y toasts Bootstrap.
- Validación de servidor y validación HTML progresiva.
- Respeto de `prefers-reduced-motion`.

## Pruebas

La suite cubre autenticación activa/inactiva, logout, restablecimiento, protección de administración, CRUD de usuarios, roles, notificación, auditoría, secretos y configuración.

## Variables

| Variable | Uso |
|---|---|
| `MATCHPOINT_ADMIN_NAME` | Nombre del administrador sembrado |
| `MATCHPOINT_ADMIN_EMAIL` | Correo único del administrador |
| `MATCHPOINT_ADMIN_PASSWORD` | Contraseña inicial; obligatoria en producción |
| `MATCHPOINT_AUDIT_RETENTION_DAYS` | Retención diaria de auditoría |

En local, si no se define contraseña, el Seeder utiliza temporalmente `ChangeMe!123`. Nunca debe usarse esa omisión en producción.
