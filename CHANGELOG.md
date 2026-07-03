# Changelog

## 2026-07-03 — Generación competitiva En curso

- Las llaves, grupos y calendarios pueden generarse después de cerrar inscripciones y pasar a En curso.
- La regeneración continúa bloqueada cuando existen resultados para proteger la integridad competitiva.
- Eliminación simple ajusta la llave a los inscritos mediante preliminares compactos, sin partidos `BYE` vacíos.
- Con 38 inscritos se generan 6 preliminares, 26 pases directos y 37 partidos totales.

## 2026-06-30 — Consolas y programación automática

- Registro de consolas por torneo con plataforma, ubicación, estado y disponibilidad.
- Duración estimada y tiempo de preparación configurables.
- Distribución automática de partidos en paralelo respetando rondas y ventanas horarias.
- Consola y hora visibles en programación, llaves y jornadas.
- Policies, auditoría, migración MySQL y pruebas de aislamiento incluidas.
- Calculador de duración según inscritos, formato, rondas, duración y consolas activas.
- Comparador de escenarios y mínimo de consolas requerido para una meta de tiempo.
- Detección de metas imposibles por la ruta crítica entre rondas dependientes.

## 2026-06-29 — Visibilidad de inscripciones

- El listado y el detalle de torneos muestran inscritos, capacidad y cupos disponibles.
- El total de inscritos enlaza directamente con la administración de inscripciones.
- El listado general de jugadores identifica los torneos en los que participa cada persona.
- Los organizadores sólo ven asociaciones con torneos bajo su administración.

## 2026-06-29 — Aislamiento y personal por torneo

- Organizadores limitados a torneos asignados y registros propios.
- Administradores pueden asignar, transferir y retirar organizadores.
- Árbitros vinculados explícitamente a cada torneo.
- Dashboard, estadísticas, campeones, reportes, jugadores y equipos aplican visibilidad por torneo.
- Nuevas Policies, auditoría, migración y pruebas contra acceso cruzado.

## 2026-06-29 — Ingreso móvil de resultados

- Nuevo modo árbitro móvil con partidos agrupados verticalmente por ronda.
- Marcadores BO1 directamente en la tarjeta y series BO3/BO5 desplegables.
- Controles táctiles, teclado numérico, guardado AJAX, errores inline y toast de confirmación.
- Las correcciones recalculan la llave sin abandonar la pantalla.
- El refresco en vivo se pausa mientras existan cambios locales sin guardar.

## 2026-06-28 — Llaves en vivo y niveles académicos

- Las llaves proyectadas se actualizan automáticamente cada cinco segundos desde otra sesión autenticada.
- El refresco conserva pantalla completa, zoom y posición horizontal.
- La inscripción rápida reemplaza secciones por niveles de Sétimo 7 a Duodécimo 12.
- La migración normaliza las secciones existentes al nivel académico correspondiente.
- Exportaciones y administración distinguen nivel académico de nivel de juego.

El formato sigue Keep a Changelog y el proyecto utiliza versionado semántico.

## [Unreleased]

### Changed

- Los campos de calendario usan un selector visual de fecha y hora en español, independiente del componente nativo defectuoso de Edge.
- La configuración de secciones usa casillas predefinidas en lugar de un campo de texto con validación dinámica.
- La llave principal ahora avanza desde los extremos izquierdo y derecho hacia una final central con copa y campeón.
- Las llaves no simétricas de perdedores y finales mantienen su lectura horizontal.
- El catálogo consolida cada equipo en una sola fila y muestra sus videojuegos como disponibilidades, eliminando registros repetidos.

### Fixed

- Se eliminaron filtros gráficos GPU globales que podían bloquear pestañas de Microsoft Edge con `STATUS_ILLEGAL_INSTRUCTION`.
- La eliminación de clubes con escudo local limpia el archivo sin depender de Flysystem o `fileinfo`.
- El importador de clubes permite cargar el catálogo para EA Sports FC, FIFA y PES.
- Los escudos se renderizan sin inicializar Flysystem, evitando errores cuando el servidor local no carga `fileinfo`.
- Los formularios de torneo muestran y enfocan el primer campo inválido en lugar de aparentar un bloqueo.
- El formulario de resultados respeta el ancho disponible junto al sidebar y mantiene visibles rival, marcadores y acciones.

### Added

- Formato estricto Mundial 48 con 12 grupos, ranking de terceros y clasificación automática a una llave de 32.
- Capacidad 48 para eliminación simple mediante 16 preliminares y llave principal compacta de 32.
- Seeder idempotente con llaves de 32, 48 y 64 participantes, además de un Mundial 48 completo.
- QR automáticos para formularios públicos con copia de enlace, descargas PNG/SVG y afiche imprimible.
- Selecciones mundialistas con tipo, código de país, bandera, escudo y asignación en inscripciones, llaves y resultados.
- Importación configurable de clubes populares y selecciones nacionales desde TheSportsDB.
- Catálogo de clubes del videojuego con escudos y asignación independiente por participante y torneo.
- Llaves estilo Copa del Mundo con columnas conectadas, marcadores, campeón, zoom y pantalla completa.
- Inscripción pública rápida sin cuenta, correo ni contraseña para torneos escolares.
- Secciones configurables, selección PS4/PS5, aceptación de control propio y comprobante público.
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
