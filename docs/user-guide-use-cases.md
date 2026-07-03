# Guía de casos de uso y rutas de acceso

## 1. Objetivo

Este manual describe el uso funcional de MatchPoint desde la interfaz web. Incluye autenticación, roles, jugadores, equipos, torneos, inscripciones, sorteos, grupos, resultados, estadísticas, reportes, notificaciones, configuración y auditoría.

Las rutas se muestran usando esta base local:

```text
http://127.0.0.1:8000
```

En producción debe sustituirse por el dominio configurado. `{slug}` representa el identificador legible del torneo, por ejemplo `copa-matchpoint-2026`; `{id}` representa un identificador numérico.

## 2. Preparación de la demostración

### UC-00 — Cargar datos de prueba

**Actor:** desarrollador o administrador del servidor.

**Ruta:** consola del proyecto.

**Pasos:**

1. Ejecutar las migraciones con `php artisan migrate`.
2. Ejecutar `php artisan db:seed --class=DemoSeeder`.
3. Iniciar el servidor con `php artisan serve`.
4. Abrir `http://127.0.0.1:8000/login`.

**Resultado esperado:** se crean 16 jugadores, 4 equipos y 3 torneos de demostración sin duplicarlos si el comando vuelve a ejecutarse.

**Credencial administrativa local:** `admin@example.com` / `ChangeMe!123`.

Consulta también [Datos de demostración](demo-data.md).

## 3. Modelo de acceso

### 3.1 Consideraciones importantes

- No existe autorregistro público en la versión actual.
- El administrador crea las cuentas desde `/admin/users/create`.
- Una cuenta de usuario y un perfil competitivo de jugador son registros diferentes.
- El rol **Jugador** permite consultar la plataforma, pero el perfil competitivo se administra en `/players`.
- El rol **Invitado** es una cuenta autenticada con acceso de consulta; no equivale a un visitante anónimo.
- Todas las rutas funcionales, excepto login y recuperación de contraseña, requieren una cuenta activa.
- Una cuenta inactiva no puede continuar usando la plataforma.

### 3.2 Matriz de permisos

| Función | Administrador | Organizador | Árbitro | Jugador | Invitado |
| --- | :---: | :---: | :---: | :---: | :---: |
| Dashboard y consultas generales | Sí | Sí | Sí | Sí | Sí |
| Ver jugadores, equipos y torneos | Sí | Sí | Sí | Sí | Sí |
| Crear o editar jugadores | Sí | Sí | No | No | No |
| Crear o editar equipos | Sí | Sí | No | No | No |
| Crear, editar o duplicar torneos | Sí | Sí | No | No | No |
| Administrar inscripciones | Sí | Sí | No | No | No |
| Generar sorteos, grupos y clasificación | Sí | Sí | No | No | No |
| Registrar o corregir resultados | Sí | Sí | Sí | No | No |
| Consultar estadísticas y campeones | Sí | Sí | Sí | Sí | Sí |
| Exportar reportes | Sí | Sí | No | No | No |
| Administrar usuarios, ajustes y auditoría | Sí | No | No | No | No |
| Gestionar perfil y notificaciones propias | Sí | Sí | Sí | Sí | Sí |

Un usuario puede poseer varios roles. Los permisos se acumulan.

## 4. Autenticación y cuenta personal

### UC-01 — Iniciar sesión

**Actores:** todos los roles.

**Ruta:** `GET /login`.

**Pasos:**

1. Abrir `/login`.
2. Escribir el correo de la cuenta.
3. Escribir la contraseña.
4. Activar **Recordarme** si se desea conservar la sesión.
5. Presionar **Iniciar sesión**.

**Resultado esperado:** el sistema regenera la sesión y redirige al dashboard.

**Controles:** la cuenta debe estar activa y el login está limitado a cinco intentos por combinación de correo e IP.

### UC-02 — Recuperar una contraseña

**Actor:** usuario sin sesión.

**Rutas:** `GET /forgot-password` y `GET /reset-password/{token}`.

**Pasos:**

1. Abrir `/forgot-password` desde el enlace de la pantalla de acceso.
2. Escribir el correo registrado.
3. Presionar el botón para enviar el enlace.
4. Abrir el enlace recibido o registrado por el canal de correo configurado.
5. Escribir y confirmar la nueva contraseña.
6. Guardar el cambio e iniciar sesión.

**Resultado esperado:** la contraseña se actualiza y el token queda invalidado.

### UC-03 — Actualizar perfil propio

**Actores:** todos los usuarios activos.

**Ruta:** `GET /profile`.

**Pasos:**

1. Abrir el menú del usuario en la barra superior.
2. Seleccionar **Mi perfil**.
3. Modificar nombre o correo.
4. Presionar **Guardar perfil**.

Para cambiar la contraseña, completar contraseña actual, nueva contraseña y confirmación en la misma pantalla.

### UC-04 — Cambiar tema visual

**Actores:** todos los usuarios activos.

**Acceso:** botón de tema en la barra superior.

**Pasos:**

1. Presionar el botón circular de tema.
2. Alternar entre modo oscuro y claro.

**Resultado esperado:** la preferencia se conserva localmente en el navegador.

### UC-05 — Cerrar sesión

**Actores:** todos los usuarios activos.

**Ruta de acción:** `POST /logout`.

**Pasos:** abrir el menú del usuario y seleccionar **Cerrar sesión**.

## 5. Dashboard

### UC-06 — Consultar el panel operativo

**Actores:** todos los roles.

**Ruta:** `GET /dashboard`.

**Pasos:**

1. Abrir **Dashboard** en la barra lateral.
2. Revisar totales de jugadores, equipos, torneos, partidos y goles.
3. Consultar próximos partidos, resultados recientes y últimos campeones.
4. Aplicar modalidad, torneo y rango de fechas cuando corresponda.

**Resultado esperado:** los indicadores se calculan desde datos reales. La actualización dinámica usa `GET /dashboard/data`, limitada a 30 solicitudes por minuto.

La actividad de auditoría sensible sólo aparece para administradores.

## 6. Administración de usuarios y roles

### UC-07 — Crear una cuenta

**Actor:** administrador.

**Ruta:** `GET /admin/users/create`.

**Pasos:**

1. Abrir **Administración → Usuarios**.
2. Presionar **Nuevo usuario**.
3. Completar nombre y correo único.
4. Escribir una contraseña de al menos 10 caracteres con mayúsculas, minúsculas, números y símbolos.
5. Confirmar la contraseña.
6. Seleccionar uno o varios roles.
7. Mantener activa la cuenta.
8. Guardar.

**Resultado esperado:** la cuenta puede iniciar sesión con los permisos asignados.

### UC-08 — Editar, activar o desactivar una cuenta

**Actor:** administrador.

**Rutas:** `GET /admin/users/{id}` y `GET /admin/users/{id}/edit`.

**Pasos:**

1. Buscar la cuenta en `/admin/users`.
2. Abrir **Editar**.
3. Modificar datos, roles o estado.
4. Dejar la contraseña vacía si no debe cambiarse.
5. Guardar.

**Resultado esperado:** los nuevos permisos se aplican en solicitudes posteriores. Una cuenta desactivada pierde acceso.

### UC-09 — Eliminar una cuenta

**Actor:** administrador.

**Ruta de acción:** `DELETE /admin/users/{id}`.

**Restricción:** el administrador no puede eliminar su propia cuenta desde esta acción.

## 7. Jugadores

### UC-10 — Buscar y consultar jugadores

**Actores:** todos los roles.

**Rutas:** `GET /players` y `GET /players/{id}`.

**Opciones:** búsqueda por nombre, apodo o correo; filtros por país, nivel y estado; paginación.

### UC-11 — Crear un jugador

**Actores:** administrador u organizador.

**Ruta:** `GET /players/create`.

**Pasos:**

1. Abrir **Jugadores → Nuevo jugador**.
2. Completar nombre, apodo competitivo único, correo único y país.
3. Seleccionar control: PlayStation, Xbox, teclado u otro.
4. Seleccionar nivel: principiante, intermedio, avanzado o profesional.
5. Adjuntar opcionalmente una imagen JPG, PNG o WebP de hasta 2 MB.
6. Marcar el jugador como activo.
7. Guardar.

**Resultado esperado:** el jugador queda disponible para equipos e inscripciones individuales.

### UC-12 — Editar, activar, desactivar o eliminar un jugador

**Actores:** administrador u organizador.

**Rutas:**

- Editar: `GET /players/{id}/edit`.
- Alternar estado: `PATCH /players/{id}/status`.
- Eliminar: `DELETE /players/{id}`.

Un jugador inactivo permanece en el historial, pero no puede inscribirse en nuevos torneos.

La columna **Torneos inscritos** identifica las competencias de cada jugador y permite abrirlas directamente. Cada usuario sólo ve torneos autorizados para su rol.

## 8. Equipos

### UC-13 — Buscar y consultar equipos

**Actores:** todos los roles.

**Rutas:** `GET /teams` y `GET /teams/{id}`.

**Opciones:** búsqueda por nombre y filtro por estado.

### UC-14 — Crear un equipo y asignar plantilla

**Actores:** administrador u organizador.

**Ruta:** `GET /teams/create`.

**Pasos:**

1. Abrir **Equipos → Nuevo equipo**.
2. Completar nombre único y descripción.
3. Adjuntar opcionalmente un logo JPG, PNG o WebP de hasta 2 MB.
4. Buscar jugadores en la sección de plantilla.
5. Marcar los integrantes.
6. Seleccionar como capitán a uno de los integrantes marcados.
7. Mantener activo el equipo y guardar.

**Resultado esperado:** se crea el equipo y su relación con jugadores y capitán.

### UC-15 — Editar, activar, desactivar o eliminar un equipo

**Actores:** administrador u organizador.

**Rutas:**

- Editar plantilla: `GET /teams/{id}/edit`.
- Alternar estado: `PATCH /teams/{id}/status`.
- Eliminar: `DELETE /teams/{id}`.

Un equipo inactivo no puede inscribirse en nuevas competencias.

## 9. Torneos

### UC-16 — Buscar y consultar torneos

**Actores:** todos los roles.

**Rutas:** `GET /tournaments` y `GET /tournaments/{slug}`.

**Filtros:** texto, estado, juego, formato y modalidad individual/equipos.

### UC-17 — Crear un torneo

**Actores:** administrador u organizador.

**Ruta:** `GET /tournaments/create`.

**Pasos:**

1. Abrir **Torneos → Nuevo torneo**.
2. Escribir nombre y descripción.
3. Seleccionar modalidad individual o equipos.
4. Seleccionar juego: EA Sports FC, FIFA, PES u otro. Si se elige **Otro**, indicar su nombre.
5. Seleccionar cupos: 4, 8, 16, 32, 64 o 128.
6. Seleccionar formato: eliminación simple, eliminación doble, Round Robin, grupos más eliminación o liga.
7. Seleccionar serie al mejor de 1, 3 o 5.
8. Configurar inicio y fin de inscripciones.
9. Configurar inicio obligatorio y final estimado del torneo.
10. Guardar.

**Resultado esperado:** el torneo se crea en estado **Borrador** con un slug único.

### UC-18 — Editar un torneo

**Actores:** administrador u organizador.

**Ruta:** `GET /tournaments/{slug}/edit`.

**Restricción:** sólo puede editarse en **Borrador** o **Inscripciones**.

### UC-19 — Cambiar el estado del torneo

**Actores:** administrador u organizador.

**Acceso:** detalle `/tournaments/{slug}`.

| Estado actual | Transiciones permitidas |
| --- | --- |
| Borrador | Inscripciones, Cancelado |
| Inscripciones | Borrador, En curso, Cancelado |
| En curso | Finalizado, Cancelado |
| Finalizado | Ninguna |
| Cancelado | Borrador |

**Flujo recomendado:** Borrador → Inscripciones → En curso → Finalizado.

### UC-20 — Duplicar un torneo

**Actores:** administrador u organizador.

**Ruta de acción:** `POST /tournaments/{slug}/duplicate`.

**Resultado esperado:** se crea una copia en borrador, sin inscripciones ni resultados, con fechas futuras y slug único.

### UC-21 — Eliminar un torneo

**Actores:** administrador u organizador.

**Ruta de acción:** `DELETE /tournaments/{slug}`.

**Restricción:** sólo pueden eliminarse torneos en **Borrador** o **Cancelado**.

La columna **Inscritos** muestra la ocupación actual como `participantes / capacidad`. En el detalle se muestran además los cupos disponibles, una barra de ocupación y el botón **Ver inscritos**.

### UC-21A — Configurar consolas y generar horarios

**Actores:** administrador u organizador asignado. Los árbitros asignados pueden consultar.

**Ruta:** `GET /tournaments/{slug}/schedule`.

**Pasos:**

1. Abrir el torneo y presionar **Consolas y horarios**.
2. Definir minutos por partido y minutos de preparación.
3. Agregar cada consola indicando nombre, plataforma y ubicación.
4. Configurar opcionalmente desde qué hora y hasta qué hora está disponible.
5. Generar previamente la llave, grupos o liga.
6. Seleccionar la hora inicial y presionar **Generar horario**.
7. Revisar la consola, hora inicial y hora final asignadas a cada partido.

### Calcular duración antes de programar

1. Revisar participantes, partidos proyectados, rondas y duración con las consolas actuales.
2. Escribir la meta en horas y minutos.
3. Presionar **Calcular mínimo**.
4. Revisar el mínimo de consolas y la tabla comparativa.
5. Si aparece **Meta imposible**, ampliar el tiempo disponible o reducir duración y preparación; agregar más consolas no elimina dependencias entre rondas.

Con una llave o calendario ya generado se usa la estructura real. Sin estructura se aplica la fórmula correspondiente al formato del torneo.

**Resultado esperado:** los partidos se distribuyen en paralelo entre consolas activas y cada ronda dependiente inicia cuando termina la anterior.

**Corrección:** ajustar tiempos o estaciones y volver a generar. La opción **Limpiar horario pendiente** conserva resultados finalizados.

## 10. Inscripciones

### UC-22 — Abrir inscripciones

**Actores:** administrador u organizador.

**Pasos:**

1. Crear y revisar el torneo.
2. Desde el detalle, cambiar el estado de **Borrador** a **Inscripciones**.
3. Confirmar que la fecha actual esté dentro de la ventana configurada.
4. Abrir `/tournaments/{slug}/registrations`.

### UC-23 — Inscribir manualmente un jugador o equipo

**Actores:** administrador u organizador.

**Ruta:** `GET /tournaments/{slug}/registrations`.

**Pasos:**

1. Buscar un candidato activo.
2. Seleccionarlo en la lista.
3. Presionar **Inscribir**.
4. Confirmar que aparezca en la tabla de inscritos y disminuya el cupo disponible.

**Validaciones:** modalidad correcta, participante activo, no duplicado, cupo disponible, estado Inscripciones y ventana temporal vigente.

### UC-24 — Retirar una inscripción

**Actores:** administrador u organizador.

**Ruta de acción:** `DELETE /tournaments/{slug}/registrations/{id}`.

**Pasos:** localizar al participante, presionar **Retirar** y confirmar.

**Restricción:** no puede retirarse después de generar sorteo, grupos o calendario.

### UC-25 — Importar inscripciones por CSV

**Actores:** administrador u organizador.

**Ruta de acción:** `POST /tournaments/{slug}/registrations/import`.

**Individual:**

```csv
nickname,email
TicoGol,player01@example.com
```

**Equipos:**

```csv
name
Pura Vida Gaming
```

**Pasos:**

1. Crear previamente los jugadores o equipos en MatchPoint.
2. Preparar un CSV UTF-8 con los encabezados exactos.
3. Abrir las inscripciones del torneo.
4. Seleccionar el archivo de hasta 2 MB.
5. Presionar **Importar**.
6. Revisar total, importados y errores por fila.

El importador acepta hasta 5.000 filas, ignora filas vacías y conserva las filas válidas aunque otras fallen. Los archivos grandes se procesan mediante cola.

### UC-26 — Exportar inscripciones

**Actores:** todos los usuarios activos con acceso de consulta al torneo.

**Rutas:**

- CSV: `GET /tournaments/{slug}/registrations/export/csv`.
- Excel: `GET /tournaments/{slug}/registrations/export/xlsx`.

### UC-42 — Inscribirse públicamente sin cuenta

**Actor:** estudiante o participante sin sesión.

**Ruta:** `GET /inscripcion/{slug}`.

**Precondiciones:** torneo individual en Inscripciones, formulario público habilitado, fecha vigente, cupos disponibles y sin sorteo o grupos generados.

**Pasos:**

1. Abrir el enlace compartido por el organizador.
2. Escribir nombre completo y nombre de usuario competitivo.
3. Seleccionar el nivel habilitado: Sétimo 7, Octavo 8, Noveno 9, Décimo 10, Undécimo 11 o Duodécimo 12.
4. Seleccionar control PS4 o PS5.
5. Confirmar que llevará su propio control cargado y funcional.
6. Presionar **Confirmar inscripción**.
7. Guardar una captura del comprobante y su código.

**Resultado esperado:** se crea un jugador competitivo mínimo sin cuenta, correo ni contraseña y queda inscrito directamente en el torneo.

El organizador activa esta modalidad desde `/tournaments/{slug}/edit`, configura los niveles académicos y comparte el enlace mostrado en la administración de inscripciones.

### UC-29B — Proyectar una llave mientras se registran resultados

1. Iniciar sesión en el equipo conectado al proyector.
2. Abrir `/tournaments/{slug}/draw` y activar `Pantalla completa`.
3. Iniciar otra sesión como administrador, organizador o árbitro.
4. Abrir el partido correspondiente desde la llave y registrar el resultado.
5. La proyección consulta cambios cada cinco segundos y muestra automáticamente marcador, ganador y avance de ronda.
6. Si se corrige un resultado, la proyección refleja la corrección en el siguiente ciclo.

### UC-29C — Registrar un resultado rápidamente desde el celular

1. Abrir la llave con una cuenta de administrador, organizador o árbitro.
2. En móvil, desplazarse por la lista vertical `Modo árbitro` hasta la ronda correspondiente.
3. Utilizar los botones `−` y `+` o tocar el campo para abrir el teclado numérico.
4. En BO3 o BO5, desplegar la serie y completar únicamente los juegos necesarios.
5. Pulsar `Guardar resultado`; la tarjeta confirma mediante toast y actualiza ganador, marcador y siguiente ronda.
6. Para duración u observaciones, pulsar el botón `⋯` sin perder el acceso al formulario completo.
7. Para corregir, desplegar `Corregir marcador`, modificar y confirmar el recálculo.

### UC-30 — Asignar o transferir organizadores

1. Iniciar sesión como administrador y abrir el torneo.
2. Entrar en `Personal`.
3. Seleccionar una cuenta con rol Organizador.
4. Marcar `Organizador principal` para transferir la responsabilidad principal.
5. Agregar colaboradores adicionales o retirar asignaciones anteriores.
6. El organizador retirado deja de ver inmediatamente el torneo, dashboard, estadísticas y reportes relacionados.

### UC-31 — Asignar árbitros a un torneo

1. Como administrador u organizador asignado, abrir `Torneo → Personal`.
2. Seleccionar una cuenta activa con rol Árbitro.
3. Asignarla; el árbitro verá únicamente ese torneo y podrá registrar resultados.
4. Para revocar el acceso, pulsar `Quitar`; la capacidad se elimina inmediatamente.

### UC-43 — Asignar equipo, selección y escudo del videojuego

**Actor:** administrador u organizador.

**Pasos:**

1. Abrir `/game-clubs` y crear clubes o selecciones con nombre, tipo, país, videojuegos disponibles e imagen.
   Si no desea cargarlos manualmente, presionar **Importar catálogo** y elegir clubes populares, selecciones mundialistas o ambos.
2. Abrir `/tournaments/{slug}/registrations`.
3. Seleccionar el club o selección escogida junto a cada participante.
4. Presionar **Guardar**.
5. Abrir la llave o el formulario de resultado para comprobar nombre y escudo.

La selección pertenece al torneo actual; no modifica las elecciones históricas del jugador.

### UC-44 — Generar QR para publicidad

**Actor:** administrador u organizador.

**Precondición:** formulario público habilitado en el torneo.

**Pasos:**

1. Abrir `/tournaments/{slug}`.
2. Localizar **QR del formulario público**.
3. Probar **Abrir formulario** y confirmar la URL.
4. Elegir **Descargar PNG** para redes sociales o impresión convencional.
5. Elegir **Descargar SVG** para diseño gráfico sin pérdida de calidad.
6. Usar **Imprimir afiche** para obtener una página lista para publicidad.
7. Antes de publicar, confirmar que `APP_URL` contiene el dominio HTTPS y no `127.0.0.1`.

**Resultado esperado:** el QR dirige al formulario público exacto del torneo y no depende de servicios externos.

## 11. Sorteo y llaves de eliminación

### UC-27 — Generar un sorteo

**Actores:** administrador u organizador.

**Formatos:** eliminación simple o doble.

**Rutas:** `GET /tournaments/{slug}/draw/create` y `POST /tournaments/{slug}/draw/preview`.

**Precondiciones:** torneo en Inscripciones o En curso y al menos dos participantes inscritos.

**Pasos:**

1. Abrir el torneo y seleccionar **Sorteo/Llave**.
2. Elegir método: aleatorio, sembrado automático o semillas manuales.
3. Activar **Evitar enfrentamientos históricos** si corresponde.
4. Para semillas manuales, asignar posiciones sin repetir.
5. Presionar **Previsualizar**.
6. Revisar los cruces clasificatorios, el tamaño de la llave principal y cuántos mejores perdedores avanzarán.
7. Confirmar el sorteo.

**Resultado esperado:** se generan rondas y partidos; las inscripciones quedan bloqueadas.

### UC-28 — Consultar o eliminar una llave

**Actores de consulta:** todos los roles.

**Ruta:** `GET /tournaments/{slug}/draw`.

**Eliminación:** administrador u organizador mediante `DELETE /tournaments/{slug}/draw`.

La llave sólo puede regenerarse antes de registrar resultados. Eliminarla desbloquea inscripciones cuando no hay resultados finalizados.

## 12. Grupos, Round Robin y liga

### UC-29 — Generar grupos o calendario de liga

**Actores:** administrador u organizador.

**Ruta:** `GET /tournaments/{slug}/groups`.

**Formatos:** Round Robin, liga o grupos más eliminación.

**Precondiciones:** torneo en Inscripciones o En curso, al menos tres participantes y sin resultados previos.

**Pasos:**

1. Abrir **Grupos/Calendario** desde el torneo.
2. En grupos más eliminación, indicar cantidad de grupos y clasificados por grupo.
3. En liga o Round Robin, usar una tabla general.
4. Presionar **Generar grupos y calendario**.
5. Revisar distribución serpentina, jornadas y partidos.

Cada grupo debe tener al menos dos participantes. En grupos más eliminación, el total de clasificados debe formar una potencia de dos.

### UC-29A — Generar Mundial 48

**Actores:** administrador u organizador.

**Precondiciones:** formato `Mundial 48`, capacidad 48 y exactamente 48 participantes inscritos.

**Pasos:**

1. Crear el torneo con formato **Mundial 48 · 12 grupos + eliminación** y 48 cupos.
2. Abrir **Grupos/Calendario**.
3. Revisar el contador `Inscritos 48/48`.
4. Generar automáticamente 12 grupos de cuatro y tres jornadas.
5. Registrar los 72 resultados de grupos.
6. Revisar el ranking global de terceros.
7. Clasificar a los 24 participantes directos y los ocho mejores terceros.
8. Abrir la llave de 32 generada.

Con menos de 48 inscritos, MatchPoint muestra cuántos faltan y bloquea la generación del formato Mundial 48. En eliminación simple con 48 inscritos todos juegan 24 clasificatorios; avanzan 24 ganadores y los 8 mejores perdedores a una llave principal de 32.

### UC-30 — Clasificar a la fase eliminatoria

**Actores:** administrador u organizador.

**Ruta de acción:** `POST /tournaments/{slug}/groups/qualify`.

**Precondiciones:** torneo de grupos en curso, todas las jornadas finalizadas y sin llave eliminatoria existente.

**Pasos:**

1. Confirmar todos los resultados de grupo.
2. Revisar las posiciones calculadas.
3. Presionar **Generar fase eliminatoria**.
4. Confirmar la acción.

**Resultado esperado:** se cruzan clasificados evitando enfrentar participantes del mismo grupo en la primera ronda.

## 13. Resultados y avance competitivo

### UC-31 — Iniciar la competencia

**Actores:** administrador u organizador.

**Pasos:**

1. Completar sorteo o calendario.
2. Volver al detalle del torneo.
3. Cambiar el estado de **Inscripciones** a **En curso**.

Los resultados sólo pueden registrarse cuando el torneo está en curso.

### UC-32 — Registrar un resultado

**Actores:** administrador, organizador o árbitro.

**Ruta:** `GET /matches/{id}/result`.

**Pasos:**

1. Abrir la llave o grupos del torneo.
2. Seleccionar un partido pendiente con ambos participantes definidos.
3. Escribir el marcador de cada juego de la serie.
4. Añadir duración en minutos y observaciones opcionales.
5. Guardar.

**Reglas:**

- Mejor de 1 requiere una victoria; mejor de 3 requiere dos; mejor de 5 requiere tres.
- No pueden agregarse juegos después de definir la serie.
- Los empates sólo se permiten en BO1 de grupos, Mundial 48, Round Robin o liga.
- Cada marcador admite valores entre 0 y 99.
- La duración admite entre 1 y 600 minutos.

**Resultado esperado:** se guardan marcadores, ganador, responsable y fecha; la llave avanza automáticamente y puede coronar al campeón.

### UC-33 — Corregir un resultado

**Actores:** administrador, organizador o árbitro.

**Ruta de acción:** `PUT /matches/{id}/result`.

**Restricción:** sólo puede corregirse un partido finalizado cuyos partidos dependientes aún no hayan sido finalizados o alterados de forma incompatible.

### UC-34 — Finalizar el torneo

**Actores:** administrador u organizador.

**Pasos:**

1. Confirmar que el último partido o la liga tenga campeón.
2. Revisar estadísticas y campeón histórico.
3. Desde el detalle, cambiar **En curso** a **Finalizado**.

## 14. Estadísticas y campeones

### UC-35 — Consultar ranking y ficha competitiva

**Actores:** todos los roles.

**Rutas:** `GET /statistics` y `GET /statistics/{type}/{id}`.

**Pasos:**

1. Abrir **Estadísticas**.
2. Filtrar por modalidad, torneo, juego o fechas.
3. Revisar jugados, victorias, derrotas, empates, goles, diferencia, promedio y racha.
4. Abrir un participante para consultar su historial y rivales.

`{type}` utiliza `individual` o `team`.

### UC-36 — Consultar campeones históricos

**Actores:** todos los roles.

**Ruta:** `GET /champions`.

**Opciones:** filtros por modalidad, juego y año.

## 15. Reportes

### UC-37 — Exportar un reporte

**Actores:** administrador u organizador.

**Ruta:** `GET /reports`.

**Pasos:**

1. Abrir **Reportes**.
2. Elegir: resumen, inscripciones, resultados, posiciones, estadísticas o campeones.
3. Elegir PDF, XLSX o CSV.
4. Seleccionar torneo para resumen, inscripciones, resultados o posiciones.
5. Seleccionar modalidad cuando aplique.
6. Presionar **Exportar**.

**Resultado esperado:** se descarga un archivo temporal y se registra la exportación en auditoría. La acción está limitada a 10 solicitudes por minuto.

## 16. Notificaciones

### UC-38 — Consultar notificaciones

**Actores:** todos los usuarios activos.

**Ruta:** `GET /notifications`.

**Pasos:**

1. Presionar el indicador de notificaciones de la barra superior.
2. Revisar la bandeja paginada.
3. Marcar una notificación como leída.

### UC-39 — Configurar preferencias

**Actores:** todos los usuarios activos.

**Ruta:** `GET /notifications`.

**Preferencias:** correo, bandeja interna, recordatorios de partidos, resultados y campeones.

Los recordatorios se preparan para 24 horas y 1 hora antes del partido mediante cola y scheduler.

## 17. Configuración y auditoría

### UC-40 — Modificar configuración

**Actor:** administrador.

**Ruta:** `GET /admin/settings`.

**Pasos:** abrir **Administración → Configuración**, modificar valores y guardar.

### UC-41 — Consultar auditoría

**Actor:** administrador.

**Ruta:** `GET /admin/audit`.

**Filtros:** acción, usuario, fecha inicial y fecha final.

**Información registrada:** usuario, fecha, IP, acción, modelo afectado y valores anteriores/nuevos cuando aplica.

## 18. Recorridos completos recomendados

### 18.1 Administrador — Puesta en marcha

1. Iniciar sesión en `/login`.
2. Crear cuentas en `/admin/users/create`.
3. Revisar ajustes en `/admin/settings`.
4. Crear jugadores en `/players/create`.
5. Crear equipos en `/teams/create` si habrá modalidad por equipos.
6. Crear el torneo en `/tournaments/create`.
7. Abrir inscripciones desde `/tournaments/{slug}`.
8. Inscribir participantes en `/tournaments/{slug}/registrations`.
9. Cambiar el torneo a En curso para cerrar altas y retiros.
10. Generar llave o grupos con la lista definitiva.
11. Delegar resultados a árbitros.
12. Revisar campeón, estadísticas, reportes y auditoría.
13. Finalizar el torneo.

### 18.2 Organizador — Operación del torneo

1. Preparar jugadores o equipos.
2. Crear y configurar el torneo.
3. Gestionar inscripciones manuales o CSV.
4. Exportar la lista para validación.
5. Generar sorteo o calendario.
6. Iniciar el torneo.
7. Supervisar resultados y correcciones.
8. Generar reportes finales.

### 18.3 Árbitro — Jornada competitiva

1. Iniciar sesión.
2. Abrir `/tournaments` y seleccionar el torneo.
3. Consultar llave o grupos.
4. Abrir el partido asignado.
5. Registrar marcador, duración y observaciones.
6. Confirmar el avance automático.
7. Corregir sólo antes de que avance la dependencia competitiva.

### 18.4 Jugador o invitado — Consulta

1. Iniciar sesión con la cuenta creada por el administrador.
2. Revisar próximos partidos en `/dashboard`.
3. Consultar participantes en `/players` y `/teams`.
4. Abrir torneos, llaves y grupos en `/tournaments`.
5. Consultar ranking en `/statistics` y campeones en `/champions`.
6. Ajustar perfil y notificaciones propias.

## 19. Índice de rutas de interfaz

| Módulo | Consulta principal | Creación/gestión |
| --- | --- | --- |
| Acceso | `/login`, `/forgot-password` | `/reset-password/{token}` |
| Dashboard | `/dashboard` | `/dashboard/data` |
| Perfil | `/profile` | `/profile/password` |
| Notificaciones | `/notifications` | `/notifications/preferences` |
| Usuarios | `/admin/users` | `/admin/users/create`, `/admin/users/{id}/edit` |
| Jugadores | `/players`, `/players/{id}` | `/players/create`, `/players/{id}/edit` |
| Equipos | `/teams`, `/teams/{id}` | `/teams/create`, `/teams/{id}/edit` |
| Torneos | `/tournaments`, `/tournaments/{slug}` | `/tournaments/create`, `/tournaments/{slug}/edit` |
| Inscripciones | `/tournaments/{slug}/registrations` | misma pantalla |
| Sorteo y llave | `/tournaments/{slug}/draw` | `/tournaments/{slug}/draw/create` |
| Grupos y liga | `/tournaments/{slug}/groups` | misma pantalla |
| Resultados | `/matches/{id}/result` | misma pantalla |
| Estadísticas | `/statistics` | `/statistics/{type}/{id}` |
| Campeones | `/champions` | consulta |
| Reportes | `/reports` | misma pantalla |
| Configuración | `/admin/settings` | misma pantalla |
| Auditoría | `/admin/audit` | consulta |

### 19.1 Catálogo HTTP completo

Las rutas `POST`, `PUT`, `PATCH` y `DELETE` se ejecutan desde formularios protegidos por CSRF. No deben abrirse directamente en la barra del navegador.

#### Acceso público

| Método | Ruta | Propósito |
| --- | --- | --- |
| GET | `/` | Redirigir al dashboard |
| GET | `/login` | Mostrar acceso |
| POST | `/login` | Autenticar cuenta |
| GET | `/forgot-password` | Solicitar recuperación |
| POST | `/forgot-password` | Enviar enlace de recuperación |
| GET | `/reset-password/{token}` | Mostrar cambio de contraseña |
| POST | `/reset-password` | Guardar nueva contraseña |
| GET | `/inscripcion/{tournament}` | Mostrar inscripción rápida |
| POST | `/inscripcion/{tournament}` | Registrar participante; 10 solicitudes/minuto |
| GET | `/inscripcion/{tournament}/confirmacion/{reference}` | Mostrar comprobante |

#### Sesión, panel y cuenta personal

| Método | Ruta | Permiso |
| --- | --- | --- |
| POST | `/logout` | Usuario activo |
| GET | `/dashboard` | Usuario activo |
| GET | `/dashboard/data` | Usuario activo; 30 solicitudes/minuto |
| GET | `/profile` | Usuario activo |
| PUT | `/profile` | Propietario de la cuenta |
| PUT | `/profile/password` | Propietario de la cuenta |
| GET | `/notifications` | Usuario activo |
| PUT | `/notifications/preferences` | Propietario de la cuenta |
| PATCH | `/notifications/{notification}/read` | Propietario de la notificación |
| GET | `/statistics` | Usuario activo |
| GET | `/statistics/{type}/{participant}` | Usuario activo |
| GET | `/champions` | Usuario activo |
| GET | `/reports` | Administrador u organizador |
| POST | `/reports/export` | Administrador u organizador; 10 solicitudes/minuto |

#### Equipos y selecciones del videojuego

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/game-clubs` | Usuario activo |
| GET | `/game-clubs/create` | Administrador u organizador |
| POST | `/game-clubs` | Administrador u organizador |
| GET | `/game-clubs/{game_club}/edit` | Administrador u organizador |
| PUT/PATCH | `/game-clubs/{game_club}` | Administrador u organizador |
| DELETE | `/game-clubs/{game_club}` | Administrador u organizador |
| POST | `/game-clubs/import/popular` | Administrador u organizador; 2 solicitudes/minuto |

#### QR de formularios públicos

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/tournaments/{tournament}/public-forms/{form}/qr` | Administrador u organizador |
| GET | `/tournaments/{tournament}/public-forms/{form}/poster` | Administrador u organizador |

#### Jugadores

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/players` | Usuario activo |
| GET | `/players/create` | Administrador u organizador |
| POST | `/players` | Administrador u organizador |
| GET | `/players/{player}` | Usuario activo |
| GET | `/players/{player}/edit` | Administrador u organizador |
| PUT/PATCH | `/players/{player}` | Administrador u organizador |
| PATCH | `/players/{player}/status` | Administrador u organizador |
| DELETE | `/players/{player}` | Administrador u organizador |

#### Equipos

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/teams` | Usuario activo |
| GET | `/teams/create` | Administrador u organizador |
| POST | `/teams` | Administrador u organizador |
| GET | `/teams/{team}` | Usuario activo |
| GET | `/teams/{team}/edit` | Administrador u organizador |
| PUT/PATCH | `/teams/{team}` | Administrador u organizador |
| PATCH | `/teams/{team}/status` | Administrador u organizador |
| DELETE | `/teams/{team}` | Administrador u organizador |

#### Torneos y estados

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/tournaments` | Usuario activo |
| GET | `/tournaments/create` | Administrador u organizador |
| POST | `/tournaments` | Administrador u organizador |
| GET | `/tournaments/{tournament}` | Usuario activo |
| GET | `/tournaments/{tournament}/edit` | Administrador u organizador |
| PUT/PATCH | `/tournaments/{tournament}` | Administrador u organizador |
| DELETE | `/tournaments/{tournament}` | Administrador u organizador |
| POST | `/tournaments/{tournament}/duplicate` | Administrador u organizador |
| PATCH | `/tournaments/{tournament}/status` | Administrador u organizador |

`{tournament}` se resuelve mediante el slug del torneo.

#### Inscripciones

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/tournaments/{tournament}/registrations` | Usuario activo |
| POST | `/tournaments/{tournament}/registrations` | Administrador u organizador |
| DELETE | `/tournaments/{tournament}/registrations/{participant}` | Administrador u organizador |
| POST | `/tournaments/{tournament}/registrations/import` | Administrador u organizador |
| GET | `/tournaments/{tournament}/registrations/export/csv` | Usuario activo |
| GET | `/tournaments/{tournament}/registrations/export/xlsx` | Usuario activo |
| PATCH | `/tournaments/{tournament}/registrations/{participant}/game-club` | Administrador u organizador |

#### Sorteos y llaves

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/tournaments/{tournament}/draw` | Usuario activo |
| GET | `/tournaments/{tournament}/draw/create` | Administrador u organizador |
| POST | `/tournaments/{tournament}/draw/preview` | Administrador u organizador |
| POST | `/tournaments/{tournament}/draw` | Administrador u organizador |
| DELETE | `/tournaments/{tournament}/draw` | Administrador u organizador |

#### Grupos, calendario y resultados

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/tournaments/{tournament}/groups` | Usuario activo |
| POST | `/tournaments/{tournament}/groups` | Administrador u organizador |
| POST | `/tournaments/{tournament}/groups/qualify` | Administrador u organizador |
| GET | `/matches/{match}/result` | Administrador, organizador o árbitro |
| POST | `/matches/{match}/result` | Administrador, organizador o árbitro |
| PUT | `/matches/{match}/result` | Administrador, organizador o árbitro |

#### Administración

| Método | Ruta | Permiso |
| --- | --- | --- |
| GET | `/admin/users` | Administrador |
| GET | `/admin/users/create` | Administrador |
| POST | `/admin/users` | Administrador |
| GET | `/admin/users/{user}` | Administrador |
| GET | `/admin/users/{user}/edit` | Administrador |
| PUT/PATCH | `/admin/users/{user}` | Administrador |
| DELETE | `/admin/users/{user}` | Administrador; no puede eliminarse a sí mismo |
| GET | `/admin/audit` | Administrador |
| GET | `/admin/settings` | Administrador |
| PUT | `/admin/settings` | Administrador |

La ruta técnica `GET` `/up` se utiliza para verificar la salud del servicio. Las rutas internas de `/storage/{path}` pertenecen al controlador de almacenamiento local de Laravel y no forman parte del flujo de usuario.

## 20. Errores operativos frecuentes

| Mensaje o situación | Causa probable | Solución |
| --- | --- | --- |
| Acceso 403 | El rol no posee el permiso | Solicitar al administrador el rol correcto |
| Redirección al login | Sesión vencida o no autenticada | Iniciar sesión nuevamente |
| Cuenta inactiva | El administrador desactivó el usuario | Reactivar en `/admin/users/{id}/edit` |
| No aparece un participante | Está inactivo, ya inscrito o no corresponde a la modalidad | Revisar jugador/equipo y filtros |
| Inscripciones bloqueadas | Ya existe sorteo o grupo | Eliminar la estructura antes de resultados o usar otro torneo |
| Periodo no iniciado/finalizado | Fecha actual fuera de la ventana | Corregir fechas mientras el torneo sea editable |
| No se puede editar torneo | Está En curso o Finalizado | La configuración sólo cambia en Borrador o Inscripciones |
| No se puede registrar resultado | Torneo fuera de En curso, partido incompleto o ya finalizado | Revisar estado y participantes |
| Serie incompleta | No se alcanzaron las victorias requeridas | Completar BO3 o BO5 correctamente |
| No se puede corregir | Un partido dependiente ya avanzó o finalizó | Revisar la cadena; la corrección está protegida |
| CSV rechazado | Encabezados, tamaño o codificación incorrectos | Usar los ejemplos exactos en UTF-8 y máximo 2 MB |
| Exportación XLSX falla | Extensión PHP ZIP ausente | Habilitar `zip` y reiniciar PHP |
| Notificación no enviada | Cola o scheduler detenidos | Ejecutar `php artisan queue:work` y `php artisan schedule:work` |

## 21. Imprimir el reglamento del torneo

**Rol:** administrador u organizador con acceso al torneo.

1. Abrir **Torneos** y seleccionar el torneo.
2. Presionar **Reglamento imprimible** en las acciones superiores.
3. Revisar inscritos, clasificatorios, mejores perdedores y tamaño de la llave.
4. Presionar **Imprimir reglamento**.
5. Elegir una impresora o **Guardar como PDF** en el diálogo del navegador.

El documento incluye la regla de diferencia máxima de tres goles, criterios de mejores perdedores, desempates, controles, puntualidad y conducta. Sus cantidades se recalculan con las inscripciones actuales.

## 22. Verificar el avance automático de la llave

**Roles:** árbitro registra; administrador, organizador o usuario autorizado proyecta.

1. Abrir la llave en la pantalla de proyección.
2. Desde otra sesión, ingresar un marcador y presionar **Guardar resultado**.
3. Confirmar que el partido cambia a **Finalizado** y aumenta el contador de la clasificatoria.
4. Completar todos los partidos clasificatorios.
5. Confirmar que la llave principal reemplaza **Por definir** por ganadores y mejores perdedores.

La fase principal no se completa parcialmente: debe esperar el último marcador para comparar de forma justa a todos los perdedores.

## 23. Armar la llave conforme llegan participantes

**Rol:** administrador u organizador asignado.

1. Entrar al torneo y abrir **Sorteo y llave**.
2. Elegir **Armar llave con presentes**.
3. Marcar los jugadores que ya se encuentran físicamente en el evento.
4. Elegir **Semillas manuales** y revisar las posiciones: `1 vs 2`, `3 vs 4`, etc.
5. Generar la vista previa y confirmar la llave.
6. Si llegan más jugadores, usar **Crear nueva tanda**, marcarlos y confirmar otra llave independiente.
7. Usar las pestañas **Tanda 1**, **Tanda 2**, etc. para operar cada llave.
8. Cuando finalicen al menos dos tandas, seleccionar **Crear final entre ganadores**.

La cantidad de cada tanda debe ser par para que todos jueguen. Las tandas nuevas no modifican partidos ni resultados existentes. Un jugador ya asignado queda bloqueado en la mesa de nuevas llegadas.

## 24. Lista de aceptación de una competencia

- [ ] Usuarios y roles creados.
- [ ] Jugadores o equipos activos y completos.
- [ ] Torneo revisado en borrador.
- [ ] Fechas de inscripción vigentes.
- [ ] Cupos y modalidad correctos.
- [ ] Participantes inscritos y exportación validada.
- [ ] Sorteo o calendario confirmado.
- [ ] Torneo cambiado a En curso.
- [ ] Árbitros con acceso comprobado.
- [ ] Resultados y observaciones registrados.
- [ ] Estadísticas y campeón verificados.
- [ ] Reportes finales descargados.
- [ ] Torneo cambiado a Finalizado.
- [ ] Auditoría revisada.
