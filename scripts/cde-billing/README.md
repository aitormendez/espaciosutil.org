# Membresías móviles: verificador privado y comprobaciones

Actualización 2026-09-19: prueba compartida Google/web implementada y validada con cuentas sintéticas en staging; proveedores deshabilitados. Ninguna prueba de este directorio demuestra una transacción auténtica de Apple, Google o Stripe.

## Arquitectura

La app envía únicamente una referencia de compra. WordPress controla la cuenta, el producto permitido, el entorno y la persistencia; `store_verify.py` consulta al proveedor mediante su biblioteca o API autenticada. Una notificación autenticada provoca otra consulta al proveedor y no concede acceso por sí misma. Las referencias se cifran con libsodium y se vinculan a una única cuenta, también después de eliminarla. No se crea una suscripción Stripe para una compra de tienda.

Los derechos nativos se incorporan al filtro de niveles de PMPro. Una revocación retira ese derecho, conservando cualquier membresía web independiente. La reconciliación usa el evento `cde_mobile_reconcile_memberships`, un máximo de veinte filas por ejecución y vencimientos verificados; un fallo de red no prolonga el acceso. Las operaciones de usuario comparten el bloqueo del módulo de cuenta para evitar carreras con el borrado o cambio de contraseña.

## Configuración privada

Copiar `config.example.json` fuera del directorio web y guardar allí las claves con permisos restrictivos. Dejar `enabled: false` hasta disponer de cuentas, productos, precios aprobados y credenciales sandbox. PHP y el cron necesitan `CDE_BILLING_CONFIG_FILE`, `CDE_BILLING_PYTHON` y `CDE_BILLING_VERIFIER`. Instalar `requirements.txt` en un entorno Python aislado; el proceso verificador no expone un servicio HTTP ni registra claves o recibos.

Apple necesita bundle, grupo de suscripción, productos, clave de App Store Server API y certificados raíz oficiales. Google necesita package, productos/base plans, cuenta de servicio de Play y configuración OIDC de Pub/Sub. El catálogo relaciona cada producto con los niveles PMPro 11, 12 o 13; no fijar precios en cliente. Los avisos usan `/wp-json/cde-mobile/v1/membership/notifications/apple` y `/google` respectivamente; sus rutas requieren acceso de red autorizado y TLS además de validación de firma.

Stripe utiliza una credencial separada del mismo entorno. Actualmente sólo permite consultar una suscripción propia y cambiar `cancel_at_period_end`, con contraseña actual. La excepción de red en staging no admite cargos, reembolsos ni modificación de precios. El cambio de plan web queda pendiente del criterio comercial y de su integración comprobada con PMPro. No usar la clave live de producción para resolver la falta de sandbox.

## Comprobaciones reproducibles

Desde la raíz del repositorio:

```sh
php scripts/cde-billing/test_domain.php
PYTHONDONTWRITEBYTECODE=1 /ruta/venv/bin/python -m unittest discover -s scripts/cde-billing -p 'test_*.py'
```

`staging-test.php` y `stripe-staging-test.php` se ejecutan exclusivamente con `wp eval-file` en `/srv/www/espaciosutil.org/current` del host `espacio-sutil-staging`. Ambos rechazan otros entornos. Crean alumnos temporales, limpian sus datos y no operan sobre suscripciones existentes. El primero captura correo y verifica rutas REST, acceso, persistencia y eliminación; el segundo sustituye el transporte HTTP por respuestas sintéticas y no conecta con Stripe. Copiar los guiones temporalmente fuera de `web/` y eliminarlos después de la ejecución.

Resultados registrados: 27 comprobaciones PHP, 9 Python, 34 integración WordPress y 8 Stripe simulado. La integración Python carga el verificador JWS real de Apple para rechazar un payload no firmado, pero no aporta una firma válida sandbox.

Antes de activar compras faltan transacciones auténticas de tiendas, notificaciones, restauración, renovaciones, cambios de plan, reembolsos y revocaciones, además del envío real del código de correo. El cliente debe probarse en dispositivos físicos. No hay APK nueva ni distribución mediante tiendas o TestFlight en esta preparación.

## Despliegue y reversión

Instalar dependencias PHP antes del cargador y de las rutas que las utilizan; validar sintaxis, conservar copia previa y comprobar hashes. El verificador y su entorno Python permanecen fuera de `web/`. `Membership::install()` añade una tabla propia sólo desde WP-CLI y sólo en el staging autorizado. No ejecutar esta migración en producción sin preparar y autorizar su despliegue.

Una reversión debe recuperar el conjunto PHP anterior y el verificador correspondiente, desactivar los proveedores y detener únicamente el evento de reconciliación de este módulo. Conservar la tabla y sus referencias para auditoría; no borrar compras o registros financieros como parte del rollback. Los recibos de despliegue, arquitectura, contrato y pruebas están documentados en el repositorio de la app, `docs/api/membresias-v1.md` y `docs/entregas/membresias-preparacion-2026-09-07.md`.

## Prueba compartida (19 de septiembre)

El catálogo Google requiere `trial_offer_id`, `billing_period` y los identificadores de producto/plan/nivel. La oferta en Play debe tener siete días gratis y el precio recurrente aprobado. La configuración de ejemplo permanece desactivada y no contiene credenciales. El catálogo devuelto conserva productos sin política válida para permitir restauraciones, pero `trial: null` impide iniciar compras. Las altas Apple quedan bloqueadas hasta implementar y validar una elegibilidad equivalente; restauración y gestión se conservan.

La selección publicada es `trial: {eligible, days: 7, offer_id}`. La intención exige el `offer_id` explícito de Google; vacío significa plan normal. No se sustituye una prueba por pago cuando cambia la elegibilidad. El verificador autenticado conserva `offer_id` y `trial_used`; esta última requiere que la compra con la oferta configurada haya comenzado. Una operación pendiente o cancelada antes de comenzar no consume nada.

`SharedTrial` usa la misma meta `espaciosutil_pmpro_trial_used` que PMPro y una huella de referencia adicional para restaurar sin repetir la prueba. El consumo y el derecho nativo se confirman dentro de la misma transacción InnoDB y bajo el bloqueo de usuario. Se rechaza otra referencia gratuita, incluso si caducó o se revocó la anterior. Las operaciones web se comprueban con la firma real de los hooks PMPro: primero un argumento; después de autenticar/crear usuario, `pmpro_checkout_order_creation_checks` reserva antes de crear la orden.

La reserva no consume la prueba. Durante 24 horas sólo se puede reintentar la misma elección nativa; otro plan/proveedor o un segundo checkout web requieren comprobar primero el intento. Una orden web `token` o `review` mantiene el bloqueo aunque caduque la reserva; debe reconciliarse antes de retirar ese bloqueo. No eliminar reservas/órdenes para esquivar un pago cuyo resultado sea incierto. Un diálogo cancelado no marca la prueba usada, aunque la reserva temporal puede seguir bloqueando el cambio de proveedor. Estas defensas no convierten las ofertas determinadas por el desarrollador en autorizaciones criptográficas de Google: el servidor deniega un segundo derecho no autorizado; el ensayo real de compra y recuperación sigue pendiente.

## Ejecución del verificador y notificaciones Google

El verificador debe ejecutarse mediante el lanzador fijo `python-runner`, instalado junto al entorno privado fuera de `web/`. Esto permite conservar la restricción `open_basedir` de PHP-FPM. Los diagnósticos se capturan en memoria; una salida no JSON se trata como fallo y no se devuelve al cliente.

El endpoint exacto `/wp-json/cde-mobile/v1/membership/notifications/google` admite avisos POST HTTPS con OIDC y audiencia configurada. El include Nginx de staging limita el cuerpo a 32 KiB y fija la ruta REST para impedir desvíos por parámetros; el resto del sitio conserva su control de acceso. La identidad, audiencia y credenciales se configuran fuera de Git. La excepción se habilita únicamente con `staging_cde_google_notifications`.

`voidedPurchaseNotification` de suscripciones provoca una nueva consulta autenticada; el aviso por sí solo no concede ni revoca acceso. Los productos de pago único no generan derechos. Los tipos desconocidos se rechazan. [Formato oficial de Google](https://developer.android.com/google/play/billing/rtdn-reference).

Las pruebas unitarias usan dobles de los proveedores. Antes de activar compras se necesitan pruebas reales en sandbox de contratación, renovación, restauración, cancelación y notificaciones; integrar este código no acredita esos recorridos ni activa los proveedores. Los recibos operativos y detalles de configuración del servicio se conservan de forma privada.
