# Módulo 19: reglamento imprimible y avance clasificatorio

## Objetivo

Generar desde cada torneo de eliminación simple un reglamento listo para imprimir y explicar el avance de la ronda clasificatoria hacia la llave principal.

## Arquitectura

```text
TournamentRulesController
    ↓
TournamentPolicy
    ↓
TournamentRulesService
    ↓
TournamentRegistrationService / Tournament
    ↓
Vista Blade A4
```

El controlador autoriza y devuelve la vista. `TournamentRulesService` concentra el cálculo y el contenido competitivo. No requiere migraciones.

## Ruta y permisos

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/tournaments/{tournament}/rules/print` | Usuario autorizado para ver el torneo |

La acción **Reglamento imprimible** aparece en el detalle de los torneos de eliminación simple y abre una hoja independiente optimizada para papel A4.

## Reglas generadas

- Todos los inscritos juegan una ronda clasificatoria.
- El encuentro termina al alcanzar una diferencia de tres goles; el marcador oficial conserva esa diferencia máxima.
- Los ganadores avanzan a la llave principal.
- Los cupos restantes se completan con mejores perdedores por diferencia de goles, goles a favor, semilla e identificador de inscripción.
- Se evita una revancha inmediata cuando existe otra combinación posible.
- Los empates eliminatorios se resuelven con tiempo extra y penales.
- Incluye reglas de controles, puntualidad, ausencias, desconexiones y conducta.

Los totales de inscritos, partidos clasificatorios, mejores perdedores y tamaño de llave se calculan con las inscripciones actuales al abrir el documento.

## Actualización de llaves

Cada resultado guardado actualiza inmediatamente el partido y la versión de la llave en vivo. La cabecera muestra `resultados completados / total`.

La llave principal permanece **Por definir** hasta completar toda la clasificatoria. Esto es necesario porque MatchPoint sólo puede ordenar los mejores perdedores después de conocer todos los marcadores. Al guardarse el último resultado, coloca automáticamente ganadores y mejores perdedores; otras sesiones reciben el cambio mediante el refresco en vivo.

Cada participante individual conserva su apodo como identificación principal. Al pasar el cursor, enfocar con teclado o tocar su fila, un tooltip muestra el nombre completo para facilitar el llamado presencial. Los tooltips se reinicializan después de cada refresco en vivo.

## Pruebas

- Documento accesible y preparado para impresión.
- Cálculos basados en el número real de inscritos.
- Normalización automática de marcadores con diferencia superior a tres goles.
- Cambio de versión y actualización de la llave desde otra sesión.
- Colocación automática de ganadores y mejores perdedores al concluir la clasificatoria.
