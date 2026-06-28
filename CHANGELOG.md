# Changelog

El formato sigue Keep a Changelog y el proyecto utiliza versionado semántico.

## [Unreleased]

### Added

- Seeder idempotente de demostración con jugadores, equipos y tres escenarios competitivos completos.
- Guía reproducible de cuentas, datos de prueba y recorrido funcional local.
- Manual operativo con matriz de roles, casos de uso paso a paso, rutas y resolución de errores.
- Laravel 12 con PHP 8.4, Bootstrap 5 y Vite.
- Autenticación, recuperación de contraseña y perfil.
- Administración de usuarios y roles mediante Policies.
- Configuración tipada y editable.
- Auditoría automática de cambios, login y logout.
- Notificaciones en cola, scheduler de retención y dashboard base.
- Suite de pruebas, documentación de arquitectura y despliegue Plesk.
- CRUD de jugadores con búsqueda, filtros, niveles y controles tipados.
- Carga, reemplazo y eliminación segura de fotografías de jugadores.
- Autorización de jugadores para administradores y organizadores.
- Documentación y pruebas funcionales del módulo de jugadores.
- CRUD de equipos con estado, descripción y logos.
- Plantillas muchos-a-muchos, capitán y búsqueda instantánea de integrantes.
- Autorización y auditoría específica de cambios de roster.
- Documentación y pruebas funcionales del módulo de equipos.
- Núcleo de torneos con juegos, modalidades, cupos, formatos, series y calendario.
- Ciclo de estados validado y eliminación lógica.
- Duplicación segura con slug estable y agenda reiniciada.
- Filtros, Policies, auditoría y documentación del módulo de torneos.
- Inscripciones individuales y por equipos con cupos transaccionales.
- Importación CSV y Job para archivos grandes.
- Exportaciones CSV UTF-8 y XLSX mediante OpenSpout.
- Búsqueda, Policies, auditoría y documentación de inscripciones.
- Estrategias de sembrado aleatorio, automático y manual.
- Prevención de enfrentamientos repetidos basada en historial.
- Sorteos versionados, byes, rondas y primera ronda de partidos.
- Bloqueo de inscripciones, regeneración segura y documentación del sorteo.
- Generadores desacoplados para llaves completas de eliminación simple y doble.
- Grafo persistente de destinos para ganadores y perdedores.
- Avance transaccional e idempotente mediante evento y listener.
- Propagación automática de byes y final de reinicio condicional.
- Vista esports horizontal, permisos de árbitro, auditoría y documentación de llaves.
- Marcadores normalizados por juego para series BO1, BO3 y BO5.
- Cálculo seguro del ganador, duración, observaciones y responsable del resultado.
- Correcciones transaccionales con protección de partidos dependientes.
- Formulario esports con validación AJAX progresiva y mensajes seguros.
- Policies, auditoría, pruebas y documentación del registro de resultados.
- Ranking calculado para jugadores y equipos con victorias, goles, promedio y racha.
- Filtros por modalidad, torneo, juego y fechas, más fichas con historial competitivo.
- Coronación automática para eliminación simple, doble y final de reinicio.
- Revocación y actualización idempotente de campeones ante correcciones.
- Salón de la fama, Policies, auditoría, pruebas y documentación de estadísticas.
- Dashboard operativo con totales, pendientes, finalizados y goles reales.
- Agenda de próximos partidos, resultados recientes y últimos campeones.
- Actividad semanal mediante gráfico CSS y auditoría restringida por Policy.
- Filtros por modalidad, torneo y fechas.
- Refresco AJAX limitado, pruebas y documentación integral del dashboard.
- Grupos, Round Robin y Liga mediante algoritmo circular par/impar.
- Distribución serpentina, jornadas compartidas y tablas 3/1/0.
- Empates restringidos a fase de grupos y estadísticas con rachas de empate.
- Clasificación automática a una llave eliminatoria sin cruces del mismo grupo.
- Campeón de liga, Policies, pruebas y documentación completa del módulo.
- Centro de reportes para torneos, inscripciones, resultados, posiciones, estadísticas y campeones.
- Exportadores consistentes para PDF, XLSX y CSV.
- Plantilla PDF A4 profesional mediante Laravel DOMPDF.
- Storage temporal privado, limpieza programada, rate limiting y auditoría de exportaciones.
- Bandeja interna, preferencias personales y contador de no leídas.
- Recordatorios en cola a 24 horas y 1 hora con garantía idempotente.
- Job programado cada cinco minutos y persistencia de entregas.
