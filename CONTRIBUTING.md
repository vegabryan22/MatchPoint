# Contribuir a MatchPoint

## Flujo

1. Crea una rama desde `main`: `feature/<descripcion>` o `fix/<descripcion>`.
2. Mantén cada cambio limitado a un módulo o problema.
3. Añade o actualiza pruebas y documentación.
4. Ejecuta formatter, pruebas y build antes de abrir el Pull Request.
5. Explica motivación, solución, riesgos, migraciones y pasos de verificación.

## Estándares

- PHP PSR-12 validado con Laravel Pint.
- Controllers sin lógica de negocio.
- Entradas mutables mediante FormRequest.
- Casos de uso en Services y persistencia detrás de contratos.
- Policies para autorización de recursos.
- Migraciones reversibles y factories para datos de prueba.
- Comentarios sólo para explicar decisiones no evidentes; PHPDoc para contratos y tipos complejos.

## Verificación

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
npm ci
npm run build
```

No confirmes `.env`, credenciales, dumps, logs, `vendor`, `node_modules` ni artefactos locales.
