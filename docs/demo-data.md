# Datos y recorrido de demostración

Este escenario permite evaluar MatchPoint localmente con información coherente generada por los mismos servicios que usa la interfaz. El seeder no duplica el escenario si se ejecuta más de una vez.

## Instalación de los datos

Sobre una base ya migrada, ejecuta:

```bash
php artisan db:seed --class=DemoSeeder
```

Para cargar los datos automáticamente junto con los seeders base, configura `MATCHPOINT_SEED_DEMO=true` y ejecuta:

```bash
php artisan migrate --seed
```

Para generar llaves adicionales de 32, 48 y 64 participantes, además de un Mundial 48 completo:

```bash
php artisan db:seed --class=LargeBracketDemoSeeder
```

El seeder crea 64 participantes reutilizables y genera:

- llave de 32: 31 partidos;
- eliminación simple de 48: 63 espacios, 47 partidos reales y 16 pases automáticos;
- llave de 64: 63 partidos;
- Mundial 48 oficial: 12 grupos, 72 partidos de grupos, ranking de terceros y llave de 32 con 31 partidos.

Si existen selecciones nacionales, las asigna de forma rotativa.

La opción está desactivada de forma predeterminada para impedir que datos ficticios lleguen accidentalmente a producción.

## Cuentas disponibles

| Rol | Correo | Contraseña | Uso sugerido |
| --- | --- | --- | --- |
| Administrador | `admin@example.com` | `ChangeMe!123` | Recorrido completo, configuración y auditoría |
| Organizador | `organizer@example.com` | `DemoOrganizador!123` | Torneos, inscripciones, sorteos y reportes |
| Árbitro | `referee@example.com` | `DemoArbitro!123` | Consulta de llaves y registro de resultados |

La demostración asigna Costa Rica, Argentina, Brasil, Francia, España, Alemania, Japón y Marruecos a las inscripciones. Sus banderas, nombres y escudos aparecen en la llave mundialista y en el formulario de resultados.

Estas credenciales son exclusivamente locales. Configura `MATCHPOINT_ADMIN_PASSWORD` con una contraseña única antes de cualquier despliegue.

## Escenarios incluidos

### Copa MatchPoint 2026

- Estado finalizado.
- Ocho participantes.
- Eliminación simple completa.
- Siete partidos con marcadores y observaciones.
- Campeón histórico generado automáticamente.

### Liga Esports San José

- Estado en curso.
- Ocho participantes distribuidos en dos grupos.
- Seis resultados registrados, incluido un empate.
- Seis próximos partidos programados.
- Tablas de posiciones y estadísticas calculadas desde resultados reales.

### Clasificatorio FC Costa Rica

- Inscripciones abiertas.
- Seis de dieciséis cupos ocupados.
- Preparado para probar altas, bajas, búsqueda, CSV, Excel y generación del sorteo.
- Formulario público sin cuenta disponible en `/inscripcion/clasificatorio-fc-costa-rica`.

También se crean dieciséis jugadores centroamericanos y cuatro equipos con plantillas y capitanes.

## Recorrido recomendado

1. Inicia sesión como administrador y revisa las tarjetas, próximos partidos y campeón en el dashboard.
2. Abre **Torneos → Copa MatchPoint 2026 → Llave** para comprobar el avance completo.
3. Abre **Liga Esports San José → Grupos** para revisar jornadas y posiciones.
4. Consulta **Estadísticas** y **Campeones** para verificar agregados históricos.
5. Entra al **Centro de reportes** y exporta un PDF, XLSX o CSV.
6. Inicia sesión como árbitro para comprobar la autorización limitada al registro de resultados.

## Reinicio local

El siguiente comando elimina todos los datos de la base configurada y vuelve a crear el escenario. Úsalo únicamente en desarrollo:

```bash
MATCHPOINT_SEED_DEMO=true php artisan migrate:fresh --seed
```
