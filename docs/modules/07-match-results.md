# Módulo 7: registro de resultados

## Objetivo

Registrar series BO1, BO3 y BO5 con marcador por juego, calcular el ganador sin confiar en datos enviados por el cliente y actualizar automáticamente la llave. El módulo también permite correcciones controladas mientras ningún partido dependiente haya avanzado.

## Arquitectura

```text
MatchResultController
    ↓
StoreMatchResultRequest / UpdateMatchResultRequest
    ↓
GameMatchPolicy
    ↓
MatchResultService
    ├── GameMatchRepositoryInterface
    ├── MatchResultRepositoryInterface
    ├── AuditService
    └── MatchCompleted
            ↓
        MatchAdvancementService
```

El controlador sólo autoriza por medio del FormRequest, delega el caso de uso y construye una respuesta HTML o JSON. Toda validación de la serie, persistencia, corrección y auditoría reside en `MatchResultService`.

## Flujo de registro

1. El torneo debe estar en estado `in_progress`.
2. El partido debe estar pendiente y tener ambos participantes definidos.
3. El árbitro captura los goles de cada juego, duración y observaciones.
4. `StoreMatchResultRequest` valida estructura, tipos y límites.
5. `MatchResultService` bloquea el partido dentro de una transacción.
6. El servicio elimina filas vacías, rechaza empates y calcula victorias por juego.
7. La serie sólo finaliza al alcanzar 1, 2 o 3 victorias según BO1, BO3 o BO5.
8. Se persisten los juegos en `scores` y el resumen en `matches`.
9. Se registra `match.result_recorded` en auditoría.
10. `MatchCompleted` actualiza los destinos de la llave dentro de la misma operación.

## Modelos

### `GameMatch`

Conserva el estado agregado del encuentro:

- Ganador de la serie.
- Duración total en segundos.
- Observaciones.
- Usuario que confirmó el resultado.
- Fecha de finalización.
- Relación ordenada con los juegos de la serie.

### `Score`

Representa un juego individual:

- Número de juego.
- Goles del participante A.
- Goles del participante B.
- Ganador calculado.
- Usuario que registró el dato.

Los identificadores de participante no tienen llave foránea porque el partido puede corresponder a un jugador o a un equipo según `participant_type`.

## Migración

`2026_06_27_000005_create_scores_and_add_result_fields_to_matches.php` crea `scores` y agrega a `matches`:

| Campo | Descripción |
|---|---|
| `duration_seconds` | duración normalizada |
| `observations` | notas del árbitro |
| `completed_by` | usuario responsable |
| `completed_at` | fecha de confirmación |

La combinación `match_id + game_number` es única. Los juegos se eliminan en cascada con el partido.

## Reglas de serie

- BO1 exige una victoria y admite un juego.
- BO3 exige dos victorias y admite hasta tres juegos.
- BO5 exige tres victorias y admite hasta cinco juegos.
- Cada juego debe contener ambos marcadores.
- No se permiten empates.
- Se rechazan juegos posteriores al momento en que la serie quedó definida.
- El ganador general siempre se calcula en el servidor.

## Correcciones

Una corrección reemplaza todos los juegos y recalcula el ganador. Antes de modificar:

1. Se bloquean los destinos de ganador y perdedor.
2. Cada destino debe continuar pendiente y sin ganador.
3. Se retira al participante previamente propagado de su espacio exacto.
4. Se reemplaza el resultado y se vuelve a despachar `MatchCompleted`.

La corrección se rechaza si un destino se convirtió en bye, avanzó o finalizó. En la gran final también se bloquea cuando la final de reinicio ya fue completada. Esta restricción evita reconstrucciones ambiguas de múltiples rondas.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/matches/{match}/result` | formulario de alta o corrección |
| POST | `/matches/{match}/result` | registrar resultado |
| PUT | `/matches/{match}/result` | corregir resultado |

## Requests

- `StoreMatchResultRequest`: autorización y reglas para juegos, duración y observaciones.
- `UpdateMatchResultRequest`: reutiliza exactamente las mismas reglas para evitar divergencias.

Los errores se devuelven como sesión en peticiones tradicionales o JSON `422` en AJAX.

## Policies

`GameMatchPolicy::recordResult` delega en la capacidad `manageMatches` del torneo:

- Administrador: permitido.
- Organizador: permitido.
- Árbitro: permitido.
- Jugador e invitado: denegado.

## Interfaz

La pantalla muestra participantes, formato de serie, victorias requeridas, filas de marcador, duración y observaciones. La llave presenta el acceso para registrar o corregir únicamente cuando el torneo está en curso y el partido es elegible.

El formulario usa validación HTML como respaldo y envío AJAX progresivo. Los mensajes del servidor se insertan mediante `textContent`, evitando interpretar contenido como HTML. Si JavaScript no está disponible, el formulario funciona con redirecciones y errores de Laravel.

## Auditoría

- `match.result_recorded`: primera confirmación.
- `match.result_corrected`: reemplazo autorizado.
- `match.advanced`: propagación de ganador o perdedor realizada por el módulo 6.

Cada registro incluye actor, IP, URL, valores anteriores y nuevos.

## Pruebas

La suite cubre:

- Registro BO1 y avance del ganador.
- Series BO3 y BO5.
- Juegos vacíos opcionales después de definir la serie.
- Empates, series incompletas y juegos excedentes.
- Estado del torneo y participantes requeridos.
- Permisos de administrador, árbitro y usuario regular.
- Corrección segura y reemplazo de `scores`.
- Bloqueo tras finalizar un partido dependiente.
- Respuestas AJAX de validación.
- Auditoría, duración y autor del resultado.

## Integración con estadísticas

El módulo 8 utiliza `scores` y partidos finalizados para calcular estadísticas de jugadores y equipos: jugados, victorias, derrotas, goles, diferencia, promedio y racha. El evento `MatchCompleted` también sincroniza automáticamente al campeón cuando el partido es una final decisiva.
## Definición por penales

En partidos eliminatorios, el marcador oficial puede terminar empatado si se registra una tanda de penales con ganador. MatchPoint conserva los goles oficiales separados de los penales y utiliza estos últimos únicamente para definir quién avanza. Los penales no alteran goles a favor, goles en contra ni diferencia de gol.
En la llave, cada tanda se resume como subíndice del marcador oficial, por ejemplo `1₍₃₎ – 1₍₄₎`, evitando una fila adicional.
