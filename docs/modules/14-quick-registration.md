# Módulo 14 — Inscripción pública rápida

## Objetivo

Permitir que estudiantes se inscriban en un torneo individual sin crear cuenta, proporcionar correo ni completar el perfil administrativo completo. El formulario solicita únicamente nombre completo, nombre de usuario, nivel académico, control PS4/PS5 y aceptación de llevar el control propio.

## Arquitectura

```text
QuickRegistrationController
    → StoreQuickRegistrationRequest
    → QuickRegistrationService
        → Player
        → TournamentPlayer
        → AuditService
```

El controlador sólo adapta HTTP. Las reglas de apertura, cupo y persistencia viven en `QuickRegistrationService`. El participante se integra al modelo competitivo existente, por lo que puede entrar inmediatamente en sorteos, grupos, resultados y estadísticas.

## Flujo

1. Administrador u organizador activa la inscripción rápida al editar un torneo individual.
2. Marca los niveles permitidos mediante casillas y configura el aviso sobre el control.
3. Comparte `/inscripcion/{slug}`.
4. El estudiante completa el formulario sin autenticarse.
5. El servicio bloquea el torneo, valida ventana, cupo y estado.
6. Se crea un `Player` mínimo sin usuario, correo ni país.
7. Se crea `TournamentPlayer` con nivel académico, plataforma, aceptación y referencia pública.
8. Se presenta un comprobante que puede guardarse mediante captura.

## Modelos y migración

- `Tournament`: habilitación, niveles académicos JSON y aviso configurable.
- `Player`: correo y país opcionales; indicador `is_quick_entry`.
- `TournamentPlayer`: nivel académico, PS4/PS5, aceptación y referencia pública única.
- `AcademicLevel`: enum con Sétimo 7, Octavo 8, Noveno 9, Décimo 10, Undécimo 11 y Duodécimo 12.
- Migración: `2026_06_28_000009_add_quick_registration_fields.php`.

## Rutas

| Método | Ruta | Límite |
| --- | --- | --- |
| GET | `/inscripcion/{slug}` | 60/minuto |
| POST | `/inscripcion/{slug}` | 10/minuto |
| GET | `/inscripcion/{slug}/confirmacion/{reference}` | 60/minuto |

## Validación y seguridad

- CSRF obligatorio.
- Honeypot oculto contra bots básicos.
- Nombre de usuario único.
- Nivel restringido a los configurados por el organizador.
- Control limitado a PS4 o PS5.
- Aceptación obligatoria de llevar control propio.
- Transacción y bloqueo pesimista para impedir sobrecupo concurrente.
- Formulario cerrado por estado, fechas, cupo, sorteo o calendario generado.
- Referencia aleatoria para consultar únicamente el comprobante correspondiente.
- Auditoría automática sin correo, contraseña ni otros datos de contacto.

## Roles

- Público: crear su propia inscripción y consultar su comprobante mediante referencia.
- Administrador/organizador: configurar el formulario y administrar inscripciones.
- Árbitro/jugador/invitado: sin capacidad de modificación pública.

## Vistas

- `quick-registrations/create.blade.php`: formulario responsive.
- `quick-registrations/confirmation.blade.php`: comprobante.
- La pantalla administrativa de inscripciones muestra enlace, nivel académico, control y origen.

## Pruebas

`QuickRegistrationTest` cubre alta sin cuenta, persistencia mínima, comprobante, validaciones, duplicados, nivel académico, control, aceptación, honeypot, cierre por disponibilidad y rate limiting.

## Migración desde secciones

`2026_06_28_000014_rename_quick_registration_sections_to_levels.php` renombra la configuración del torneo y el dato de inscripción. Los valores anteriores se normalizan conservando el grado: `7-1` y `7-2` pasan a `7`; el mismo criterio aplica hasta undécimo. Duodécimo queda disponible como nivel nuevo.
