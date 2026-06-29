# MatchPoint

MatchPoint es una aplicación Laravel para administrar torneos competitivos de videojuegos. El primer módulo entrega el núcleo de identidad: autenticación, usuarios, roles, configuración, auditoría y una interfaz administrativa Bootstrap 5.

## Estado

- Módulo 0 — Núcleo, identidad y acceso: implementado.
- Módulo 1 — Jugadores: implementado.
- Módulo 2 — Equipos: implementado.
- Módulo 3 — Núcleo de torneos: implementado.
- Módulo 4 — Inscripciones: implementado.
- Módulo 5 — Sorteo, sembrado y primera ronda: implementado.
- Módulo 6 — Llaves y avance automático: implementado.
- Módulo 7 — Registro de resultados: implementado.
- Módulo 8 — Estadísticas y campeones históricos: implementado.
- Módulo 9 — Dashboard operativo: implementado.
- Módulo 10 — Grupos, Round Robin, liga y Mundial 48: implementado.
- Módulo 11 — Reportes PDF, XLSX y CSV: implementado.
- Módulo 12 — Notificaciones y recordatorios: implementado.
- Módulo 13 — Cierre de producción: implementado.
- Módulo 14 — Inscripción pública rápida sin cuenta: implementado.
- Módulo 15 — Catálogo sin duplicados de clubes y selecciones, banderas, escudos y asignación por torneo: implementado.
- Módulo 16 — QR descargable e imprimible para formularios públicos: implementado.

Consulta [Núcleo](docs/modules/00-core-identity.md), [Jugadores](docs/modules/01-players.md), [Equipos](docs/modules/02-teams.md), [Torneos](docs/modules/03-tournaments.md), [Inscripciones](docs/modules/04-registrations.md), [Sorteo](docs/modules/05-draw-seeding.md), [Llaves](docs/modules/06-brackets.md), [Resultados](docs/modules/07-match-results.md), [Estadísticas](docs/modules/08-statistics-champions.md), [Dashboard](docs/modules/09-dashboard.md), [Grupos y liga](docs/modules/10-groups-leagues.md), [Reportes](docs/modules/11-reports.md), [Inscripción rápida](docs/modules/14-quick-registration.md), [Equipos y selecciones](docs/modules/15-game-clubs.md), [QR públicos](docs/modules/16-public-form-qrs.md) y [la arquitectura](docs/architecture.md).

El recorrido funcional completo por roles, casos de uso y rutas está disponible en la [Guía de casos de uso](docs/user-guide-use-cases.md).

## Requisitos

- PHP 8.4 con `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml` y `zip`.
- Composer 2.8 o superior.
- MySQL 8.
- Node.js 22 o superior y npm 10 o superior.
- Git.

## Instalación local

```bash
git clone <URL_DEL_REPOSITORIO> matchpoint
cd matchpoint
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Crea una base de datos MySQL con codificación `utf8mb4` y configura `.env`:

```dotenv
APP_NAME=MatchPoint
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=matchpoint
DB_USERNAME=matchpoint
DB_PASSWORD=contraseña_segura

MATCHPOINT_ADMIN_NAME="Administrador MatchPoint"
MATCHPOINT_ADMIN_EMAIL=admin@example.com
MATCHPOINT_ADMIN_PASSWORD="Cambia!Esta123"
MATCHPOINT_AUDIT_RETENTION_DAYS=365
```

Prepara la aplicación:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

### Demostración local

Carga jugadores, equipos, torneos, grupos, resultados y un campeón reproducible:

```bash
php artisan db:seed --class=DemoSeeder
```

Inicia sesión con `admin@example.com` y `ChangeMe!123`. El inventario completo de cuentas, escenarios y el recorrido funcional está documentado en [docs/demo-data.md](docs/demo-data.md).

En desarrollo también puedes ejecutar servidor, cola, logs y Vite juntos:

```bash
composer run dev
```

## Comandos de calidad

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
npm run build
```

## Colas y scheduler

Las notificaciones de cuentas nuevas implementan `ShouldQueue`.

```bash
php artisan queue:work --tries=3 --timeout=90
php artisan schedule:work
```

En producción, configura un cron cada minuto:

```cron
* * * * * cd /var/www/vhosts/example.com/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

El scheduler elimina diariamente registros de auditoría que superen `MATCHPOINT_AUDIT_RETENTION_DAYS`.

## Estructura

```text
app/
├── Enums/                 Valores de dominio estables
├── Http/Controllers/      Adaptadores HTTP pequeños
├── Http/Requests/         Validación y autorización de entradas
├── Listeners/             Reacciones a eventos de Laravel
├── Models/                Entidades y relaciones Eloquent
├── Notifications/         Mensajes asíncronos
├── Observers/             Auditoría automática de modelos
├── Policies/              Reglas de autorización
├── Repositories/          Contratos y persistencia Eloquent
└── Services/              Casos de uso y lógica de negocio
```

Las fotos de jugadores y logos de equipos se guardan en el disco `public`; ejecuta `php artisan storage:link` después de cada instalación.

## Seguridad

- CSRF activo en todas las rutas web mutables.
- Sesión regenerada al iniciar y cerrar sesión.
- Login limitado a cinco intentos por combinación correo/IP.
- Contraseñas de al menos 10 caracteres con mayúsculas, minúsculas, números y símbolos.
- Policies para usuarios, configuración y auditoría.
- Cuentas inactivas expulsadas mediante middleware.
- Valores de contraseña y tokens excluidos de la auditoría.
- Cambios, accesos e IP registrados automáticamente.

Consulta [SECURITY.md](SECURITY.md) para reportar vulnerabilidades.

## Despliegue

La guía completa para Plesk Obsidian, Apache, SSL, permisos, colas y tareas programadas está en [docs/deployment-plesk.md](docs/deployment-plesk.md).

## Capturas de pantalla

Los marcadores y la convención para incorporar capturas se encuentran en [docs/screenshots/README.md](docs/screenshots/README.md). Las imágenes definitivas se capturarán al cerrar visualmente cada módulo.

## Colaboración

Lee [CONTRIBUTING.md](CONTRIBUTING.md). Los cambios relevantes se registran en [CHANGELOG.md](CHANGELOG.md).

## Licencia

Distribuido bajo la licencia MIT. Consulta [LICENSE](LICENSE).
