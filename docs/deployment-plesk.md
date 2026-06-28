# Despliegue en Plesk Obsidian

## Preparación

1. Crea el dominio y activa un certificado Let's Encrypt.
2. Selecciona PHP 8.4 en modo FPM servido por Apache.
3. Habilita extensiones requeridas por Laravel, `pdo_mysql` y `zip` para exportaciones XLSX.
4. Crea la base MySQL 8 y un usuario exclusivo con privilegios sólo sobre esa base.
5. Configura el document root del dominio hacia `httpdocs/public`.

El repositorio puede residir en `httpdocs`; únicamente `public` debe ser accesible desde HTTP.

## Instalación

Desde la terminal de Plesk:

```bash
cd /var/www/vhosts/example.com/httpdocs
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
```

Configura `.env` con `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, MySQL, SMTP, Redis o base de datos para colas y una contraseña administrativa fuerte.

## Permisos

El usuario de la suscripción Plesk debe poseer el proyecto. Apache/PHP requiere escritura en:

```bash
chmod -R 775 storage bootstrap/cache
```

No uses `777`. Si el proveedor separa usuario web y usuario de despliegue, asigna ambos al grupo apropiado.

## Apache

Laravel incluye `public/.htaccess`. Verifica que `mod_rewrite` esté habilitado y que Apache permita overrides para `public`. Fuerza HTTPS desde Plesk; evita reglas duplicadas si Plesk ya gestiona la redirección.

## Cola

Crea una tarea programada que mantenga el worker o utiliza Supervisor si el plan lo permite:

```bash
php /var/www/vhosts/example.com/httpdocs/artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600
```

Tras cada despliegue ejecuta `php artisan queue:restart`.

## Scheduler

Crea una tarea Plesk cada minuto:

```bash
cd /var/www/vhosts/example.com/httpdocs && php artisan schedule:run
```

## Despliegues posteriores

```bash
git pull --ff-only
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

Activa mantenimiento con `php artisan down --retry=60` sólo si una migración no es compatible con la versión anterior. Finaliza con `php artisan up`.

## Verificación

- Abre `/up` y confirma HTTP 200.
- Comprueba login, envío SMTP y ejecución de un job.
- Revisa `storage/logs/laravel.log`.
- Confirma que `.env`, `vendor` y archivos fuera de `public` no sean descargables.
- Programa respaldos cifrados de base de datos y archivos persistentes.
