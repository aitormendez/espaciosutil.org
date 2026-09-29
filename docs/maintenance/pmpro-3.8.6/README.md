# PMPro 3.8.6 — preparación ESP-1896

Fecha: 2026-09-29. Responsable: CTO. Revisión funcional: QA en ESP-1897.
Preparación autorizada por ESP-1894; no autoriza merge, despliegue ni migración productiva.

## Procedencia y alcance

- Base: `origin/main` `8d3aaed3fea77d2433baa08dd311ae19d4f9a6b8`, consultada mediante fetch.
- Rama: `chore/esp-1896-pmpro-3-8-6`. El core y su lock no cambian.
- [Release oficial 3.8.6](https://github.com/strangerstudios/paid-memberships-pro/releases/tag/3.8.6), 08/09/2026.
- Commit upstream: `3b1b0a15f011bdf99f703d643635c4499d0b3237`.
- ZIP GitHub: `https://codeload.github.com/strangerstudios/paid-memberships-pro/zip/refs/tags/3.8.6`.
- SHA-256 ZIP: `62693f10e623144ed772e186f11fd201875013e1233d1efc9adeebabe9e12c22`.
- `upstream-manifest.json`: los 1.286 archivos del paquete completo con hashes individuales. No se ejecutó build ni Composer sobre el plugin; continúa versionado.
- `inventory.json`: diferencias completas. Base: 1.718 archivos, todos iguales al upstream 3.6.5 `167d728836ec9da65a6b6525bb192ee8d70edd49`; solo faltaban tres imágenes de add-ons respecto al archivo upstream. No hay modificaciones ni archivos locales añadidos que portar. Cambio: 32 altas, 464 bajas y 159 modificaciones respecto a la copia local.
- Los mu-plugins propios y las plantillas `site/web/app/themes/sage/paid-memberships-pro/email/` se conservan byte a byte. No se tocan vaults ni el worktree de core.
- Gran parte de las bajas son traducciones incluidas antiguamente: upstream las retiró expresamente en 3.7 (#3605). No se reintroducen catálogos antiguos dentro del paquete íntegro. QA debe comprobar español y disponibilidad de catálogos externos en `web/app/languages/plugins`; si faltan, preparar traducciones compatibles verificadas como entrega separada antes de activar. La mera identidad del ZIP no garantiza conservación del idioma de todas las pantallas.

## Dictamen QA consumido

[ESP-1895](/ESP/issues/ESP-1895), documento `qa-pmpro`, revisión `29f3cf8f-0a55-4935-965c-9d5b97f605c1`: parches ausentes en 32 archivos revisados, sin evidencia de agravamiento causal por WP 7.1.2. Recomienda remediación mantenida y regresión aislada. No se repite esa auditoría ni se presenta como sign-off funcional.

## Observación de producción, solo lectura

- `wp @production db query` desde `site`, SELECT de opciones explícitas, sin bootstrap del plugin: activo, `pmpro_db_version=3.53`, `pmpro_updates` vacío, gateway Stripe, entorno live.
- Hash de cada archivo remoto mediante PHP independiente por SSH con verificación estricta de host: 1.718/1.718 iguales a upstream 3.6.5, mismas tres imágenes ausentes. Esto identifica contenido desplegado, no prueba el mecanismo histórico de instalación.
- PHP CLI remoto: 8.4.18. Trellis declara PHP 8.4; no se afirma verificación del proceso FPM.
- El código conserva el guard de getfile apagado por defecto y la protección del fallback de webhook incluida en 3.6.5. No se encontraron habilitaciones en config/mu-plugins/Sage versionados; configuración efectiva de getfile, filtros ajenos, WAF y reglas de servidor no verificados. No dar por mitigado ni afirmar explotación.
- Antes de activar: CTO verificará configuración efectiva y protección perimetral con lectura autorizada; PlatformSecurity resolverá solo obstáculos de permisos/runtime. No son condición para entregar este PR, sí incertidumbre a resolver para el sign-off.
- No se consultaron pedidos ni miembros reales, ni se invocaron endpoints de pago/correo. No se ejecutó PMPro nuevo contra base compartida o productiva.

## Migraciones: revisar antes de cualquier bootstrap

`paid-memberships-pro.php` llama `pmpro_checkForUpgrades()` en admin o cuando está definida `WP_CLI`. Un simple comando WP-CLI que cargue plugins puede migrar. No usarlo para inspeccionar la nueva copia en producción.

Desde el esquema observado `3.53` se ejecutan:

| Umbral | Escrituras / efecto | Riesgo de reversión |
|---|---|---|
| 3.7001 | `pmpro_db_delta()` vuelve a conciliar todas las tablas del plugin y crea `pmpro_email_log`; actualiza opción de versión. El nuevo log puede almacenar destinatarios, cabeceras y cuerpos. | Revertir archivos no elimina esquema ni datos de correo. Revisar retención/permisos antes de activación. |
| 3.71 | Si `paypal` está en gateways no deprecados, cambia a `paypalwpp` en pedidos, suscripciones y opción de gateway. Preserva PayPal Express detectado mediante opciones/consultas. Escribe `pmpro_undeprecated_gateways`. | Stripe por defecto no demuestra ausencia histórica de PayPal; restaurar mapeos desde respaldo si aplica. |
| 3.8 | Borra relaciones de niveles inexistentes en categorías, páginas, descuentos-niveles, grupos-niveles y metadatos de niveles; invalida caché. No está diseñada para borrar historia de pedidos o membresías. | Borrados requieren respaldo para recuperar. |
| 3.84 | Consulta candidatos Stripe exitosos con session ID y ambos IDs de transacción vacíos; registra callback para `action_scheduler_init`, encola recuperación y sube versión. | No basta con desactivar WP-Cron: Action Scheduler también tiene runners asíncronos/manuales. |

La recuperación procesa lotes de 20 y programa siguiente lote a +5 minutos. Consulta sesiones, payment intents y suscripciones en Stripe con las credenciales del entorno del pedido. Escribe IDs y notas directamente en pedidos, marca metadatos de recuperación (`no_credentials`, `failed`, `nothing_to_recover`, `recovered`) y puede crear `PMPro_Subscription`, que sincroniza estado/fechas/importes con Stripe. Revisar hooks y efectos de esa creación por separado: evitar `saveOrder()` solo suprime los hooks de actualización de pedidos, no garantiza ausencia universal de eventos.

3.8.6 modifica precisamente ese worker para no llamar `saveOrder()` durante la recuperación. No es una garantía de operación sin llamadas externas, sin escrituras o sin hooks de suscripción. No borrar marcadores ni reejecutar recuperaciones fallidas a ciegas. Un timeout puede dejar un lote parcial y otra ejecución pendiente.

## Verificación reproducible sin WordPress ni BD

Desde la raíz de la rama (en QA utilizar un binario PHP 8.4 real y comprobar `PHP_VERSION`):

```sh
php site/tests/pmpro-upgrade-trials.php
php site/tests/pmpro-upgrade-recovery.php
php site/tests/atlas-cde-membership-endpoint.php
php site/tests/cde-listmonk-sync.php
```

`verification.json` registra versión del intérprete, número de archivos lintados y resultados. Los dos últimos scripts terminan silenciosamente con código cero. El host realmente ejecuta PHP 8.5.1: sus rutas Homebrew `php@8.4` y `php@8.3` son enlaces a 8.5.1. No se afirma validación local con PHP 8.4. Lint de 1.011 archivos: 1.010 pasan en PHP 8.5.1; CyberSource falla por firma SOAP tanto con configuración normal como con `-n`. Ese archivo concreto pasa `php -l` por stdin en el PHP 8.4.18 remoto, sin ejecutar código ni cargar WordPress/BD o escribir archivos. QA debe repetir las suites con PHP 8.4 real.

`git diff --check` del paquete produce 608 diagnósticos de whitespace upstream; se conservan para garantizar identidad del paquete. Documentos y fixtures propios se comprueban separadamente y no presentan errores de whitespace.

- Trial: clase `MemberOrder` real del paquete y hooks propios, 16 combinaciones de niveles 11/12/13/control, elegible/consumido, descuento/ninguno; cero inicial, siete días y conservación del importe recurrente; recordatorios 2/7 días y registro de plantilla.
- Recuperación: implementación upstream real con dobles de BD, scheduler y Stripe; sin candidato/con candidato, cinco resultados, IDs recuperados, creación local simulada y exclusión de repetidos. `saveOrder`, eventos o email inesperados fallan el test. Los dobles no ejercitan SQL real, SDK Stripe ni hooks de creación de suscripción.
- Atlas/acceso CDE y Listmonk: suites existentes con fixtures y dobles, sin peticiones HTTP ni eventos reales. No equivalen a smoke de UI ni de la integración completa de PMPro.
- Emails: permanece la resolución de overrides del tema y los hooks `pmpro_email_data`/`pmproet_templates`; registro de template y recordatorios probado. Render Liquid completo, precedencia de opciones persistidas, aspecto y rutas de checkout se reservan a QA aislado. No se emitió correo.
- Limitación encontrada: lint con PHP 8.5.1 falla en la firma de `CyberSourceSoapClient::__doRequest`, incluso usando `-n`. El archivo es idéntico en 3.6.5 y 3.8.6, y pasa lint remoto con 8.4.18; no se parchea upstream ni se declara compatible con PHP 8.5. Mantener runtime objetivo 8.4 y no combinar este cambio con upgrade PHP.

## Handoff ejecutable a QA: WordPress 7.1.2

Responsable: QA, [ESP-1897](/ESP/issues/ESP-1897), ya creada y dependiente de esta entrega. Consumir SHA exacto del PR registrado en ESP-1896. QA trabaja en un checkout propio, sin mutar ninguno compartido:

1. Crear worktree propio desde el SHA entregado. Aplicar en esa rama de QA el commit exclusivo de core `04cee4d30adcb45ff5f7ae5b90d4361b7e5cc6f5` (solo composer.json/lock) para combinar WP 7.1.2 con PMPro 3.8.6 sin mezclar los PR.
2. Preparar PHP 8.4, base nueva y usuario con permisos limitados exclusivamente a ella; NO importar producción ni copiar `.env`, credenciales, uploads o colas reales. Mantener `DB_HOST=127.0.0.1`. Confirmar el nombre/base propia antes de cualquier arranque. Instalar Composer desde lock en el nuevo `site`, con scripts revisados antes de ejecutarlos y secretos privados fuera de logs.
3. Antes del primer bootstrap bloquear egress a nivel de proceso/contenedor, SMTP externo y webhooks; usar dominio `.test`, sin claves Stripe/Mailgun/Atlas/Listmonk reales. Desactivar cron del sistema y WP-Cron; impedir runners asíncronos de Action Scheduler. Flag Listmonk deshabilitado. Las constantes HTTP de WordPress solas no bloquean el SDK Stripe.
4. Crear exclusivamente fixtures sintéticos: niveles 11/12/13, usuarios de prueba, acceso activo/inactivo y relaciones válidas/huérfanas. Preparar fixture de esquema 3.53 mediante PMPro 3.6.5 en esa base desechable. Hacer snapshot ANTES de arrancar el paquete nuevo; conservar fixture y snapshot para repetir.
5. Activar el nuevo código solo en esa base y registrar DDL, DELETE/UPDATE, versión final 3.84 y tareas pendientes. Probar el worker con dobles o aislamiento efectivo de Stripe; no usar pedidos reales ni claves live. No ejecutar checkout completo, pagos, suscripciones o envíos reales. Verificar que los rechazos de permisos/nonce llegan antes de efectos usando la matriz de ESP-1895.
6. Revisar premium/Atlas, campos/readonly, getfile con archivo sintético, templates y trials; renderizar email en memoria con transporte bloqueado. Comparar estado antes/después y cero eventos externos. Restaurar el snapshot y repetir para demostrar rollback.

Development compartido y confianza SSH staging siguen sujetos a las habilitaciones de ESP-1892; no duplicar solicitudes ni desactivar verificación SSH. Si impiden el smoke, QA registra dependencia y owner/acción en su issue. Estas pruebas offline no acreditan WP 7.1.2 arrancado con el plugin nuevo.

## Activación y rollback: ejecución posterior autorizada

CTO decide con el padre ESP-1894 tras cierre satisfactorio de ESP-1897 y autorización explícita de despliegue. Secuencia: entorno aislado → development → staging → producción, conservando contexto/configuración propia de staging y gates de core. No hay despliegue programado por este PR.

Antes de activar, registrar SHA/release anterior y runtime; respaldo consistente completo de BD y código/configuración privada; verificar restauración en base aislada. Inventariar conteos de candidatos, relaciones huérfanas y gateways históricos con consultas agregadas, sin volcar PII. Documentar la decisión de ejecutar o diferir recovery Stripe y sus llamadas externas.

Para ventana productiva autorizada, controlar tráfico, escrituras, webhooks y todos los runners con procedimiento de mantenimiento y posterior reconciliación; no perder pedidos durante un restore. Monitorizar migración y colas con criterio de parada por error SQL, acceso incorrecto, envío inesperado o divergencia de facturación. QA aporta evidencia y CTO conserva la decisión.

Rollback ANTES de bootstrap: restaurar release anterior. DESPUÉS de migraciones: detener workers y tráfico mutador, preservar evidencia y estado posterior; restaurar release y respaldo consistente bajo ventana controlada, o reparación selectiva validada de datos. No rebajar solo `pmpro_db_version`, no dejar tareas de recuperación huérfanas, y reconciliar eventos/datos nuevos antes de reabrir. Restaurar BD antigua sin reconciliación puede perder altas y pagos nuevos; la aprobación debe contemplar ese riesgo.
