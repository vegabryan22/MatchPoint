# Módulo 6: llaves y avance automático

## Presentación estilo Copa del Mundo

La ruta `/tournaments/{slug}/draw` presenta la llave principal como un cuadro simétrico de competición:

- rondas iniciales distribuidas entre los extremos izquierdo y derecho;
- avance visual de ambos lados hacia la final central;
- copa dorada permanente en el centro y nombre del campeón cuando se define;
- líneas y troncos visuales de avance;
- participante A/B y marcador acumulado por serie;
- ganador resaltado;
- cuadro de campeón;
- secciones separadas para llave principal, perdedores y finales;
- zoom entre 70% y 140%;
- desplazamiento por arrastre;
- modo de pantalla completa;
- adaptación responsive con desplazamiento horizontal.

`BracketPresentationService` transforma modelos y resultados en datos de presentación. Para la llave principal divide cada ronda previa a la final en dos mitades, conserva el orden de secuencia del lado izquierdo e invierte el orden de las columnas derechas para que ambos recorridos converjan en el centro. La vista Blade conserva únicamente composición visual y autorización de acciones.

## Objetivo

Construir la llave completa de eliminación simple o doble y representar cada avance como un grafo persistente. Un partido finalizado envía automáticamente al ganador y, cuando corresponde, al perdedor a su siguiente espacio sin recalcular el torneo.

## Arquitectura

```text
TournamentDrawController
    ↓
TournamentDrawService
    ↓
BracketGenerationService
    ├── BracketGeneratorResolver
    │   ├── SingleEliminationBracketGenerator
    │   └── DoubleEliminationBracketGenerator
    ├── BracketBlueprint
    └── TournamentDrawRepositoryInterface

MatchCompleted
    ↓
AdvanceCompletedMatch
    ↓
MatchAdvancementService
    ↓
GameMatchRepositoryInterface
```

Los generadores sólo describen rondas, partidos y conexiones. `BracketGenerationService` persiste ese blueprint. `MatchAdvancementService` es el único responsable de mover participantes, resolver pases automáticos y activar una final de reinicio.

## Flujo de generación

1. `TournamentDrawService` valida estado, formato, participantes y orden de semillas.
2. El resolver selecciona el generador según `TournamentFormat`.
3. El generador produce un `BracketBlueprint` sin consultar ni escribir la base de datos.
4. Se crean todas las rondas y todos los partidos vacíos.
5. Se conectan destinos de ganador y perdedor por identificador y espacio `a` o `b`.
6. Los partidos iniciales reciben participantes, estado y ganador de bye cuando aplica.
7. El motor propaga los byes únicamente cuando todos los partidos alimentadores están resueltos.
8. Toda la operación permanece dentro de la transacción y bloqueo del torneo del módulo de sorteo.

## Modelos involucrados

- `Tournament`: formato, participantes, rondas y partidos.
- `TournamentDraw`: versión y metadata reproducible del sorteo.
- `Round`: etapa numerada de la llave principal, perdedores o finales.
- `GameMatch`: participantes, ganador, estado y conexiones del grafo.
- `User`: actor que genera la llave o completa un partido.
- `AuditLog`: trazabilidad del avance.

## Migración

`2026_06_27_000004_add_bracket_graph_to_matches_table.php` amplía `matches` con:

| Campo | Uso |
|---|---|
| `winner_next_match_id` | partido que recibe al ganador |
| `winner_next_slot` | espacio `a` o `b` de destino |
| `loser_next_match_id` | partido que recibe al perdedor |
| `loser_next_slot` | espacio `a` o `b` de destino |
| `is_conditional` | identifica la final de reinicio |

Las referencias son anuladas al eliminar el destino. Los partidos continúan eliminándose en cascada con su ronda y torneo.

## Eliminación simple

Para una llave de tamaño `N`, se generan `log2(N)` rondas y `N - 1` partidos. Cada par de partidos alimenta los espacios A y B del partido correspondiente en la ronda siguiente. Los nombres de las últimas fases se presentan como cuartos, semifinales y final.

## Eliminación doble

Para `N` participantes se crean:

- `log2(N)` rondas de ganadores.
- `2 × log2(N) - 2` rondas de perdedores cuando `N` es mayor que dos.
- Gran final y final de reinicio condicional.

Cada derrota en la llave principal apunta a la etapa correcta de perdedores. La final de reinicio se cancela si gana el campeón invicto; se activa con ambos finalistas si gana el campeón de perdedores.

## Avance automático

`MatchCompleted` contiene únicamente identificadores serializables. El listener delega en `MatchAdvancementService`, que:

1. Bloquea el partido de origen.
2. Verifica estado finalizado o bye y valida al ganador.
3. Bloquea cada destino antes de escribir.
4. Impide reemplazar un espacio ya ocupado por otro participante.
5. Espera a que todos los alimentadores estén resueltos.
6. Marca como bye y continúa recursivamente cuando sólo llega un participante.
7. Mantiene la operación idempotente ante eventos repetidos.
8. Registra `match.advanced` con actor y destinos modificados.

El módulo 7 guarda el marcador y despacha `MatchCompleted` después de confirmar el resultado.

## Rutas y controlador

No se agregan rutas mutables nuevas. La llave completa reutiliza:

| Método | URI | Acción |
|---|---|---|
| GET | `/tournaments/{slug}/draw` | visualizar todas las llaves |
| POST | `/tournaments/{slug}/draw` | generar el grafo completo |
| DELETE | `/tournaments/{slug}/draw` | eliminarlo si no existen resultados |

`TournamentDrawController` permanece pequeño: autoriza, delega y devuelve la respuesta. Los `PreviewTournamentDrawRequest` y `GenerateTournamentDrawRequest` existentes siguen validando la entrada.

## Policies

- Todos los usuarios activos pueden consultar la llave.
- Administradores y organizadores pueden generar, regenerar o eliminar el sorteo.
- Administradores, organizadores y árbitros tienen `manageMatches`.
- Un árbitro no puede modificar semillas ni estructura.

## Interfaz

La vista agrupa llave principal, perdedores y finales. La llave principal usa una composición izquierda-centro-derecha inspirada en cuadros mundialistas; la final y la copa ocupan el eje central. Las llaves de perdedores y finales de eliminación doble conservan una disposición horizontal porque no forman un árbol simétrico. Cada sección permite desplazamiento táctil, identifica estados, resalta ganadores y explica cuándo se activa la final de reinicio. Mantiene modo oscuro y diseño responsive.

### Proyección en vivo

La vista `/tournaments/{slug}/draw` consulta cada cinco segundos el endpoint autenticado `/tournaments/{slug}/draw/live`. Cuando otra sesión registra o corrige un resultado, el ganador, marcador y siguiente enfrentamiento se actualizan sin recargar la página. La sincronización conserva zoom, desplazamiento horizontal y pantalla completa. Si la conexión falla, la interfaz informa que está reconectando y reintenta en el siguiente ciclo.

El endpoint requiere un usuario activo, aplica la Policy `viewDraw` y tiene límite de 30 consultas por minuto. No permite modificar resultados ni expone una llave privada a usuarios anónimos.

## Pruebas

La suite verifica:

- Número total de rondas y partidos en eliminación simple.
- Conexiones A/B entre partidos.
- Propagación de byes.
- Avance idempotente y auditoría.
- Estructura completa de ganadores, perdedores y finales.
- Destinos separados para ganador y perdedor.
- Activación de la final de reinicio.
- Permisos específicos del árbitro.
- Distribución simétrica de rondas, final central, copa y ausencia de partidos duplicados.

## Integración con resultados

El módulo 7 registra marcador, duración y observaciones mediante endpoints autorizados. Al confirmar o corregir un resultado despacha el evento definido por este módulo.
