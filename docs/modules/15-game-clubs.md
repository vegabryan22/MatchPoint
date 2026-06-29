# Módulo 15 — Equipos, selecciones y escudos

## Objetivo

Administrar un catálogo sin duplicados de clubes y selecciones nacionales disponibles en uno o más videojuegos. La organización asigna una entidad a cada inscripción y MatchPoint muestra nombre, bandera o escudo en inscripciones, llave, resultados y exportaciones.

## Arquitectura

```text
GameClubController
    → GameClubService
        → GameClubRepositoryInterface
            → GameClub / GameClubAvailability

TheSportsDbClubImportService
    → GameClubRepositoryInterface

TournamentRegistrationController
    → TournamentRegistrationService
        → GameClub::supportsGame
```

El controlador recibe solicitudes y devuelve respuestas. `GameClubService` administra archivos y delega persistencia. El repositorio sincroniza disponibilidades dentro de transacciones. El importador consulta TheSportsDB concurrentemente y no conoce detalles de Eloquent.

## Modelos y datos

- `game_clubs`: identidad única, tipo, código de país, imagen, proveedor externo y estado.
- `game_club_availabilities`: videojuegos compatibles con cada entidad.
- `tournament_players.game_club_id`: equipo escogido por un jugador en ese torneo.
- `tournament_teams.game_club_id`: equipo escogido por un equipo en ese torneo.

`GameClubType` diferencia `club` y `national_team`. `GameClubAvailability` convierte `game` a `GameType`. Una entidad puede estar disponible simultáneamente en EA Sports FC, FIFA, PES u otro juego sin duplicar sus datos.

## Migración y consolidación

`2026_06_28_000013_normalize_game_club_availabilities.php`:

1. Agrega tipo y código ISO del país.
2. Crea la tabla de disponibilidades.
3. Copia el videojuego de cada registro anterior.
4. Identifica duplicados por proveedor e identificador externo o por nombre.
5. Reasigna inscripciones al registro canónico.
6. Elimina duplicados y la columna antigua `game`.
7. Aplica unicidad por tipo/nombre y proveedor/identificador.

## Flujo operativo

1. Administrador u organizador abre `/game-clubs`.
2. Filtra por texto, tipo o videojuego.
3. Crea manualmente un club o selección y marca sus videojuegos compatibles.
4. Como alternativa, abre **Importar catálogo** y selecciona clubes populares, selecciones mundialistas o ambos.
5. Abre las inscripciones de un torneo y asigna el equipo escogido.
6. MatchPoint valida actividad y compatibilidad con el videojuego del torneo.
7. Llave y resultados muestran escudo; si no existe, usan la bandera del país.

## Catálogo mundialista

El importador incluye 24 selecciones: Costa Rica, Argentina, Brasil, Uruguay, Colombia, Ecuador, México, Estados Unidos, Canadá, España, Francia, Alemania, Inglaterra, Portugal, Países Bajos, Bélgica, Croacia, Marruecos, Senegal, Nigeria, Japón, Corea del Sur, Australia y Arabia Saudita.

Las imágenes se consultan desde TheSportsDB. El código ISO permite mostrar una bandera incluso si el proveedor no devuelve escudo.

## Rutas

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/game-clubs` | Catálogo y filtros |
| GET/POST | `/game-clubs/create`, `/game-clubs` | Crear |
| GET/PUT | `/game-clubs/{id}/edit`, `/game-clubs/{id}` | Editar |
| DELETE | `/game-clubs/{id}` | Eliminar |
| POST | `/game-clubs/import/popular` | Importar clubes y selecciones |
| PATCH | `/tournaments/{slug}/registrations/{participant}/game-club` | Asignar |

## Validaciones

- Nombre único dentro de su tipo.
- Tipo válido mediante `GameClubType`.
- Código ISO de dos letras obligatorio para selecciones.
- Al menos un videojuego disponible.
- Imagen JPG, PNG o WebP de hasta 2 MB.
- URL externa HTTP o HTTPS.
- Asignación limitada a entidades activas y compatibles.

## Seguridad

- Policies limitan la gestión a administrador y organizador.
- `TournamentPolicy::manageRegistrations` protege asignaciones.
- CSRF protege formularios.
- La importación tiene rate limiting.
- TLS se verifica en producción.
- Archivos reemplazados o eliminados se limpian de forma segura.

## Pruebas

La suite comprueba importación sin duplicados, selecciones y códigos de país, tres disponibilidades por entidad, almacenamiento de imagen, eliminación segura, filtros, permisos, compatibilidad, asignación y renderizado en llave y resultados.
