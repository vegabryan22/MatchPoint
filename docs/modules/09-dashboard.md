# Módulo 9: dashboard operativo

## Objetivo

Centralizar el estado competitivo de MatchPoint con métricas reales, próximos partidos, resultados recientes, campeones y actividad administrativa autorizada. La pantalla se actualiza periódicamente sin recargar y reutiliza una única capa de servicio para HTML y JSON.

## Arquitectura

```text
DashboardController
    ↓
DashboardFilterRequest → DashboardPolicy
    ↓
DashboardService
    ├── DashboardRepositoryInterface
    └── StatisticsRepositoryInterface
            ↓
players / teams / tournaments / matches / scores
tournament_champions / audit_logs

GET /dashboard/data
    ↓
DashboardService
    ↓
JSON: métricas + parcial HTML
```

El controlador sólo obtiene datos validados, delega en el servicio y devuelve una vista o JSON. Las consultas están encapsuladas en `EloquentDashboardRepository` y la resolución genérica de jugadores/equipos reutiliza `StatisticsRepositoryInterface`.

## Flujo

1. El middleware exige autenticación y cuenta activa.
2. `DashboardFilterRequest` autoriza y valida filtros.
3. `DashboardService` solicita métricas y colecciones al repositorio.
4. Los identificadores genéricos se resuelven por modalidad en consultas agrupadas.
5. La vista inicial renderiza cards y el parcial operativo.
6. JavaScript consulta `/dashboard/data` cada 30 segundos.
7. El endpoint devuelve métricas y el mismo parcial renderizado.
8. La interfaz reemplaza únicamente la región dinámica.

## Modelos involucrados

- `Player`: totales y participantes individuales.
- `Team`: totales y participantes colectivos.
- `Tournament`: filtros y contexto.
- `GameMatch`: pendientes, finalizados y agenda.
- `Score`: total de goles y marcadores.
- `TournamentChampion`: últimos campeones.
- `AuditLog`: actividad visible exclusivamente para administradores.

## Migraciones

El módulo no agrega tablas ni columnas. Consume el esquema normalizado de los módulos 0 a 8.

## Métricas

El dashboard muestra:

- Total de jugadores.
- Total de equipos.
- Total de torneos según modalidad o torneo seleccionado.
- Partidos pendientes.
- Partidos finalizados.
- Goles registrados.

La respuesta JSON también conserva `active_players` y `active_teams` para integraciones futuras.

Los goles se suman desde cada juego de `scores`. Los contadores de partido se calculan desde `matches`; no se almacenan acumulados.

## Contenido operativo

### Próximos partidos

Incluye hasta seis partidos pendientes con ambos participantes definidos y torneo en curso. Los programados aparecen primero; los no programados se muestran después como “Por programar”.

### Resultados recientes

Incluye participantes, torneo, suma de goles de la serie y ganador. Se ordena por fecha de finalización e identificador.

### Últimos campeones

Presenta hasta cinco coronaciones con participante, torneo y fecha. Jugadores y equipos eliminados conservan un marcador textual sin romper la vista.

### Actividad de siete días

El repositorio genera una serie diaria portable entre MySQL y SQLite. La vista dibuja barras CSS sin dependencias adicionales.

### Auditoría

`DashboardService` consulta `AuditLogPolicy`. Sólo administradores reciben actividad administrativa; organizadores, árbitros, jugadores e invitados no obtienen esos registros ni siquiera dentro del HTML JSON.

## Filtros

`DashboardFilterRequest` valida:

- Modalidad individual o equipos.
- Torneo existente.
- Fecha inicial.
- Fecha final posterior o igual a la inicial.

Los filtros afectan partidos, goles, campeones y serie diaria. Las fechas usan `completed_at` para resultados y `scheduled_at` para pendientes.

## Repository

`DashboardRepositoryInterface` define operaciones pequeñas:

- `metrics`.
- `upcomingMatches`.
- `recentResults`.
- `recentChampions`.
- `tournaments`.
- `recentActivity`.
- `completedByDay`.

`EloquentDashboardRepository` aplica filtros comunes mediante un builder privado para evitar consultas duplicadas.

## Service

`DashboardService::summary`:

- Coordina repositorios.
- Resuelve participantes por lotes.
- Aplica autorización de auditoría.
- Entrega exactamente la misma estructura al HTML inicial y al endpoint dinámico.

No ejecuta consultas Eloquent directas.

## Controller y rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/dashboard` | vista completa filtrada |
| GET | `/dashboard/data` | métricas y parcial JSON |

`DashboardController` mantiene dos métodos pequeños: `__invoke` y `data`.

## Seguridad

- Middleware `auth` y `active`.
- `DashboardPolicy::view` para cuentas activas.
- FormRequest para todos los filtros.
- Auditoría condicionada por `AuditLogPolicy`.
- Endpoint JSON limitado a 30 peticiones por minuto.
- HTML dinámico generado exclusivamente por Blade en el servidor.
- Sin datos sensibles dentro de las métricas públicas internas.

## Interfaz

- Seis cards responsive.
- Filtros Bootstrap 5.
- Agenda en cards.
- Tabla de resultados.
- Minigráfico CSS de siete días.
- Lista de campeones.
- Actividad administrativa condicional.
- Región `aria-live` para actualización accesible.
- Modo oscuro, reducción de movimiento y diseño móvil.

## Actualización automática

Cada 30 segundos el cliente solicita el endpoint con los filtros actuales. Si la red falla o el servidor responde con error, la vista existente permanece intacta y el siguiente ciclo vuelve a intentarlo. No se muestran errores invasivos por actualizaciones secundarias.

## Pruebas

La suite verifica:

- Totales de jugadores, equipos y torneos.
- Pendientes, finalizados y goles.
- Próximos partidos y resultados.
- Resolución de jugadores y equipos.
- Últimos campeones.
- Filtros por modalidad, torneo y fechas.
- Privacidad de auditoría.
- Estado vacío.
- Respuesta JSON estructurada.
- Validación de fechas.
- Presencia del rate limiting.

## Integración con grupos y liga

El módulo 10 implementa Round Robin, liga y fase de grupos. Sus jornadas, resultados y campeones alimentan automáticamente este dashboard y el módulo estadístico.
