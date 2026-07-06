# Control de asistencia

## Objetivo

El módulo distingue a las personas inscritas de quienes realmente participaron. La asistencia se registra sobre la inscripción del torneo y queda disponible para consultas, reportes y estadísticas.

## Estados

- `Pendiente`: todavía no se confirmó la llegada.
- `Presente`: la persona o equipo asistió al torneo.
- `Ausente`: estaba inscrito, pero no se presentó.

## Flujo operativo

1. Abrir el torneo y entrar en **Inscripciones**.
2. Localizar al participante mediante búsqueda o filtro.
3. Pulsar **Presente**, **Ausente** o **Pendiente** en su fila.
4. Verificar los contadores superiores de presentes, ausentes y pendientes.
5. Exportar CSV o Excel cuando se necesite una lista de control.
6. Finalizar el torneo cuando la asistencia esté confirmada.

Al guardar el resultado de un partido, MatchPoint confirma automáticamente como presentes a sus dos participantes. La migración de instalación también aplica esta regla a los partidos finalizados antes de habilitar el módulo.
Al cambiar el torneo a **Finalizado**, todas las inscripciones que continúen pendientes se cierran automáticamente como ausentes.
Una migración de datos aplica la misma regla a los torneos que ya estaban finalizados antes de esta funcionalidad.

## Permisos y cierre

Administradores y organizadores asignados pueden registrar asistencia mientras el torneo no esté finalizado ni cancelado. Después del cierre, la asistencia queda visible en modo lectura y no puede modificarse.

## Estadísticas

Cuando un torneo todavía no tiene controles de asistencia, sus resultados históricos se conservan sin cambios. Desde la primera confirmación de asistencia, las estadísticas del torneo incluyen únicamente partidos cuyos dos participantes estén marcados como presentes.

## Auditoría

Cada cambio registra el usuario responsable, la fecha, el estado anterior, el estado nuevo y la inscripción afectada mediante la acción `registration.attendance_updated`.

Las fechas se almacenan en UTC y se muestran en la zona configurada por `APP_DISPLAY_TIMEZONE`. El valor predeterminado es `America/Costa_Rica`.
