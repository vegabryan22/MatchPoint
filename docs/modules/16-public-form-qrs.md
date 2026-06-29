# Módulo 16 — QR para formularios públicos

## Objetivo

Generar material compartible para cada formulario público de MatchPoint sin depender de servicios externos. El organizador puede abrir el formulario, copiar su URL, descargar QR en PNG o SVG e imprimir un afiche.

## Arquitectura

```text
TournamentController
    → PublicFormQrService::shareData
        → PublicFormType

PublicFormQrController
    → PublicFormQrRequest
    → PublicFormQrService::render
        → Endroid QR Code
```

- `PublicFormType` registra formularios públicos y su ruta nombrada.
- `PublicFormQrService` verifica disponibilidad, construye la URL absoluta y genera imágenes.
- `PublicFormQrController` autoriza y devuelve imagen o afiche.
- `x-public-form-share` compone la tarjeta reutilizable.
- No se agregan tablas ni migraciones.

## Flujo

1. El organizador habilita la inscripción pública rápida.
2. Abre el detalle del torneo.
3. MatchPoint muestra el QR y la URL del formulario.
4. El organizador copia el enlace, descarga PNG/SVG o abre el afiche.
5. El participante escanea y accede directamente al formulario público.

## Rutas

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/tournaments/{tournament}/public-forms/{form}/qr` | Mostrar o descargar QR |
| GET | `/tournaments/{tournament}/public-forms/{form}/poster` | Afiche imprimible |

Parámetros permitidos para QR:

- `format`: `svg` o `png`.
- `size`: `256`, `512` o `1024`.
- `download`: `0` o `1`.

## Seguridad

- Sólo administradores y organizadores pueden generar publicidad.
- El formulario público mantiene sus límites de solicitudes.
- El servicio deriva la URL desde rutas conocidas; no acepta URLs arbitrarias.
- Un formulario deshabilitado devuelve `404`.
- La solicitud valida formato, tamaño y modo de descarga.

## Entornos

En local, un QR con `127.0.0.1` sólo funciona en la misma computadora. Para teléfonos de la red debe usarse una IP accesible. Para publicidad real, `APP_URL` debe contener el dominio HTTPS de Plesk, por ejemplo:

```env
APP_URL=https://torneos.ejemplo.com
```

PNG requiere la extensión PHP `gd`; SVG requiere `SimpleXML`. Ambas deben estar activas en Plesk.

## Pruebas

La suite verifica tarjeta, URL, SVG, PNG de alta resolución, descarga, afiche, permisos y comportamiento de formularios deshabilitados.
