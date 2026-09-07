# Membresías móviles: verificador privado y comprobaciones

Estado del 2026-09-07: código preparado y desplegado en staging, proveedores deshabilitados. Ninguna prueba de este directorio demuestra una transacción auténtica de Apple, Google o Stripe.

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
