# Módulo 12: notificaciones y recordatorios

## Arquitectura

`NotificationController` gestiona bandeja y preferencias mediante FormRequest. `MatchReminderService` detecta partidos a 24 horas y 1 hora, registra cada entrega de forma única y despacha `MatchReminderNotification` mediante cola. `SendMatchReminders` se ejecuta cada cinco minutos desde el scheduler.

## Persistencia

- `notifications`: canal database de Laravel.
- `notification_preferences`: correo, bandeja, recordatorios, resultados y campeones por usuario.
- `match_reminders`: garantía idempotente por partido, usuario y ventana.

## Destinatarios y canales

Los recordatorios alcanzan al organizador y a jugadores vinculados con cuenta activa. Cada usuario puede desactivar correo, bandeja o recordatorios. Las notificaciones implementan `ShouldQueue`.

## Rutas

- `GET /notifications`: bandeja y configuración.
- `PUT /notifications/preferences`: guardar preferencias.
- `PATCH /notifications/{id}/read`: marcar como leída, limitada al propietario.

## Seguridad y pruebas

Todas las rutas requieren cuenta activa. Las consultas parten del usuario autenticado, evitando acceso cruzado. La suite verifica preferencias, bandeja, envío en la ventana correcta, cola e idempotencia.

## Operación

El worker debe permanecer activo con `php artisan queue:work`. El cron ejecuta `php artisan schedule:run` cada minuto; Laravel despacha `SendMatchReminders` cada cinco minutos sin solapamiento.
