# Política de seguridad

## Versiones soportadas

Durante el desarrollo inicial sólo la rama `main` recibe correcciones de seguridad.

## Reporte responsable

No publiques vulnerabilidades en Issues. Informa privadamente al responsable del repositorio e incluye versión, impacto, reproducción mínima y mitigación sugerida. Se confirmará recepción y se coordinará la divulgación después de publicar una corrección.

## Secretos

- Nunca confirmes `.env`, claves, tokens, respaldos o datos personales.
- Rota inmediatamente cualquier secreto expuesto.
- Usa credenciales distintas por entorno y privilegios mínimos en MySQL.
- Mantén `APP_DEBUG=false` en producción.

## Operación

Ejecuta periódicamente `composer audit` y `npm audit`. Conserva respaldos cifrados, prueba su restauración y revisa los registros de auditoría y aplicación.
