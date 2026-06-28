# Arquitectura de MatchPoint

## Objetivos

La arquitectura prioriza módulos desacoplados, controladores pequeños, reglas de negocio comprobables y sustitución de infraestructura sin afectar los casos de uso.

## Capas

1. **Presentación**: rutas, Controllers, FormRequests y Blade convierten HTTP en llamadas de aplicación.
2. **Aplicación**: Services implementan casos de uso, transacciones y coordinación.
3. **Dominio**: Models, Enums y Policies expresan entidades, vocabulario y permisos.
4. **Infraestructura**: repositorios Eloquent, correo, colas, almacenamiento y base de datos.

Los Controllers no escriben directamente en modelos. Las consultas de apoyo exclusivamente visuales deberán migrarse a Services cuando incorporen reglas de negocio.

## Dependencias

Los Services dependen de interfaces en `app/Repositories/Contracts`. `AppServiceProvider` enlaza cada contrato con su implementación Eloquent. Esto permite sustituir persistencia y aislar pruebas.

## Flujo HTTP

```text
Route → Middleware → FormRequest → Controller → Service → Repository → Model/MySQL
                                      ↓
                                Event/Notification
```

1. Middleware autentica y comprueba que la cuenta siga activa.
2. FormRequest autoriza y valida la entrada.
3. Controller delega los datos validados.
4. Service aplica reglas y abre transacciones cuando corresponde.
5. Repository ejecuta persistencia Eloquent.
6. Observers y Listeners generan trazabilidad.

## Decisiones

- Los roles son entidades muchos-a-muchos; un usuario puede cumplir varias funciones.
- Las Policies son la fuente de verdad de autorización, no los elementos visibles del menú.
- La auditoría es append-only desde la interfaz y excluye secretos.
- Los ajustes se almacenan tipados para crecer sin añadir columnas por cada preferencia.
- Bootstrap se compila localmente con Vite; producción no depende de CDN.
- Las notificaciones se encolan para no bloquear solicitudes web.

## Convenciones

- PSR-12 y Laravel Pint.
- Clases `final` cuando no constituyen puntos de extensión.
- Inyección por constructor.
- FormRequests por operación mutable.
- Textos de interfaz en español; identificadores técnicos en inglés.
- Migraciones reversibles e índices en campos de búsqueda o estado.

## Evolución modular

Cada módulo debe presentar y aprobar arquitectura, flujo, modelos, migraciones, rutas, Controllers, Requests, Services, Policies, vistas y pruebas. No se inicia el siguiente hasta compilar, probar y documentar el actual.
