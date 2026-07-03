# Módulo 5: sorteo, sembrado y primera ronda

## Objetivo

Convertir las inscripciones cerradas en un orden de semillas y enfrentamientos iniciales reproducibles. El módulo crea la base persistente de rondas y partidos para eliminación simple y doble.

## Arquitectura

```text
TournamentDrawController
    ↓
Draw FormRequests → TournamentPolicy
    ↓
TournamentDrawService
    ├── SeedingStrategyResolver
    │   ├── RandomSeedingStrategy
    │   ├── AutomaticSeedingStrategy
    │   └── ManualSeedingStrategy
    └── RematchAwarePairingService
    ↓
TournamentDrawRepositoryInterface
    ↓
tournament_draws / rounds / matches / semillas de inscripción
```

## Tablas

- `tournament_draws`: método, prevención de repeticiones, versión, autor, fecha y metadata reproducible.
- `rounds`: número, nombre, tipo de llave y fecha.
- `matches`: participantes genéricos, ganador, estado, serie y programación.

El modelo PHP de `matches` se llama `GameMatch` porque `match` es palabra reservada en PHP 8.

## Estrategias

### Aleatoria

Mezcla todos los participantes una sola vez durante la vista previa. El orden resuelto se envía al confirmar para evitar que cambie entre previsualización y persistencia.

### Automática

Ordena jugadores por nivel: profesional, avanzado, intermedio y principiante. Los equipos usan el promedio de nivel de su plantilla. Los empates se resuelven alfabéticamente.

### Manual

Exige exactamente una semilla consecutiva entre 1 y N para cada inscrito. Rechaza faltantes, duplicados y participantes ajenos.

## Prevención de repeticiones

El optimizador consulta partidos finalizados de torneos anteriores con la misma modalidad. Mantiene la prioridad de semillas, pero selecciona el oponente con menos cruces históricos cuando existe alternativa. Un criterio de proximidad conserva el emparejamiento estándar en caso de empate.

## Byes

En eliminación simple todos los inscritos juegan una ronda clasificatoria completa. Los ganadores avanzan y los mejores perdedores necesarios completan la siguiente potencia de dos. Nadie recibe un pase directo sin haber disputado un partido.

Ejemplo: 38 inscritos generan 19 partidos clasificatorios. Avanzan los 19 ganadores y los 13 mejores perdedores para formar una llave principal de 32. El torneo contiene 50 partidos en total. En eliminación doble se conserva la estructura completa requerida por los cuadros de ganadores y perdedores.

Los mejores perdedores se ordenan por diferencia de goles, goles a favor, semilla y finalmente identificador. MatchPoint espera a que finalice toda la ronda clasificatoria y evita una revancha inmediata cuando distribuye los clasificados. Para garantizar que todos jueguen frente a un rival, la cantidad inicial debe ser par.

## Flujo

1. Torneo en estado Inscripciones o En curso y formato de eliminación.
2. Al menos dos participantes.
3. Selección de método y opción de evitar repeticiones.
4. Vista previa de semillas y primera ronda.
5. Confirmación del orden exacto.
6. Transacción que reemplaza artefactos anteriores, persiste semillas, sorteo, ronda y partidos.
7. Bloqueo automático de altas y retiros.

El sorteo puede eliminarse o regenerarse mientras no exista ningún partido finalizado. Eliminarlo limpia semillas, rondas y partidos, y desbloquea inscripciones.

## Estados de partido

- `pending`: pendiente de resultado.
- `bye`: reservado para estructuras que necesitan propagación automática, principalmente eliminación doble.
- `completed`: finalizado; bloquea regeneración.
- `cancelled`: cancelado.

## Integración con llaves

Este módulo conserva la responsabilidad de ordenar semillas, evitar repeticiones y producir los pares iniciales. El módulo 6 consume ese plan y genera todas las rondas de eliminación simple o doble mediante generadores desacoplados. Round Robin, grupos y liga se programarán en el módulo específico de grupos/calendario.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/tournaments/{slug}/draw` | visualizar sorteo |
| GET | `/tournaments/{slug}/draw/create` | configurar |
| POST | `/tournaments/{slug}/draw/preview` | vista previa |
| POST | `/tournaments/{slug}/draw` | confirmar |
| DELETE | `/tournaments/{slug}/draw` | eliminar y desbloquear |

## Permisos y auditoría

Todos los usuarios activos pueden consultar. Administradores y organizadores generan, regeneran y eliminan. Se registran `draw.generated` y `draw.reset` con método, versión, orden y actor.

## Pruebas

La suite cubre semillas aleatorias, manuales y automáticas, ronda clasificatoria completa, ranking de mejores perdedores, cantidades impares, historial, bloqueo de inscripciones, reinicio, resultados existentes, auditoría y permisos.
