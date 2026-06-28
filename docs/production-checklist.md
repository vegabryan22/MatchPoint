# Checklist de producción

## Preparación

- Ejecutar Composer Validate, Pint, PHPUnit y `npm run build`.
- Usar `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`.
- Habilitar `mbstring`, `dom`, `fileinfo`, `gd`, `pdo_mysql` y `zip`.
- Respaldar MySQL y `storage/app`; verificar SMTP, cola y scheduler.
- Mantener permisos `775` sólo en `storage` y `bootstrap/cache`.

## Lanzamiento

1. Instalar dependencias sin desarrollo y compilar frontend.
2. Ejecutar migraciones con `--force`, `storage:link` y `optimize`.
3. Reiniciar la cola y comprobar `/up`.
4. Probar login, torneo, resultado, dashboard, reportes y notificaciones.

## Rollback

1. Activar mantenimiento y restaurar código anterior.
2. Restaurar la base si la migración no admite rollback seguro.
3. Reinstalar dependencias, compilar, optimizar y reiniciar la cola.
4. Revisar logs y repetir el smoke test antes de reabrir.
