# Módulo 11: reportes PDF, XLSX y CSV

## Objetivo

Exportar información oficial de MatchPoint en formatos adecuados para impresión, análisis e integración, reutilizando un único conjunto de datos para garantizar consistencia.

## Arquitectura

```text
ReportController
    ↓
ExportReportRequest → ReportPolicy
    ↓
ReportExportService
    ↓
ReportDataService
    ├── TournamentRegistrationRepositoryInterface
    ├── StatisticsService
    ├── GroupStageService
    └── StatisticsRepositoryInterface
```

`ReportDataService` entrega título, encabezados y filas. `ReportExportService` transforma esa estructura a PDF, XLSX o CSV. El controlador sólo valida, delega, audita y devuelve la descarga.

## Tipos

- Resumen del torneo.
- Inscripciones.
- Calendario y resultados.
- Posiciones de grupos o liga.
- Estadísticas generales por modalidad.
- Campeones históricos.

Resumen, inscripciones, resultados y posiciones exigen torneo. Estadísticas y campeones son globales.

## Formatos

### PDF

Generado con `barryvdh/laravel-dompdf` 3.x. Usa A4 horizontal, fuente DejaVu Sans compatible con español, encabezado MatchPoint, tabla alternada y pie oficial.

### XLSX

Generado mediante OpenSpout para mantener consumo de memoria estable. Cada fila del pipeline se escribe secuencialmente.

### CSV

Generado con BOM UTF-8 para compatibilidad con Excel. Utiliza `fputcsv`, evitando concatenación insegura.

## Rutas

| Método | URI | Acción |
|---|---|---|
| GET | `/reports` | centro de exportaciones |
| POST | `/reports/export` | generar y descargar |

El endpoint está limitado a diez exportaciones por minuto.

## Seguridad

- Sólo administradores y organizadores mediante `ReportPolicy`.
- FormRequest con enums y torneo existente.
- Nombres generados por tipo y fecha, sin entrada del usuario.
- Archivos temporales fuera del directorio público.
- Descarga con eliminación posterior.
- Auditoría `report.exported` con actor, tipo y formato.
- Limpieza diaria de archivos huérfanos mayores a 24 horas.

## Interfaz

El centro ofrece reporte, formato, torneo y modalidad. Mantiene Bootstrap 5, modo oscuro, validación nativa y mensajes Laravel.

## Pruebas

- Acceso autorizado y denegado.
- Torneo obligatorio según tipo.
- Descarga de los tres formatos.
- Firma `%PDF` para PDF.
- Contenedor ZIP `PK` para XLSX.
- BOM UTF-8 para CSV.
- Compilación de la plantilla PDF.

## Verificación PDF

Se generó `output/pdf/matchpoint-report-sample.pdf` desde la base local. La firma, tamaño y estructura fueron validados automáticamente. El entorno no dispone de Poppler ni de `pypdf`, por lo que la inspección PNG no pudo automatizarse; la plantilla conserva márgenes, tipografía, encabezado, tabla y pie definidos explícitamente.

## Operación

Los reportes se generan bajo demanda y se transmiten inmediatamente. OpenSpout procesa XLSX por streaming y los archivos se eliminan tras responder. La cola existente permanece disponible para una futura bandeja persistente de reportes masivos con historial de descargas.

## Dependencia siguiente

El módulo 12 implementará notificaciones de partidos, resultados y reportes, además de tareas programadas de recordatorio.
