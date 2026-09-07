# API de cuenta nativa (staging)

Rama de implementación 0.6, autorizada por el usuario en la tarea de la app. Contrato canónico: repositorio `app-cde-movil`, `docs/api/openapi.json` y plan `docs/planes/2026-09-07-cuenta-nativa.md`.

`account.php` amplía el namespace móvil para perfil propio, avatar privado, verificación de correo, contraseña, documento de privacidad y soporte. Las rutas `/account/*` se resuelven tras autenticar Bearer y antes del control de membresía: una membresía caducada no impide gestionar la cuenta. Las rutas del curso mantienen su autorización existente.

No se acepta ID, rol, capacidades ni dirección de destinatario de soporte en el cliente. Nombre y correo usan los campos WordPress; avatar y desafío temporal usan usermeta privada sin exposición REST estándar. El avatar se recodifica a JPEG de 256 × 256, sin original o metadatos. Contraseña actual errónea devuelve 422 para no confundirse con una sesión caducada. Códigos con HMAC, 15 minutos y cinco intentos; no se devuelven por la API. Cambio de contraseña revoca sesiones WordPress y móviles.

Soporte utiliza `HTML_Forms\Submission` y los avisos email configurados para el formulario `contacto`. El recibo idempotente confirma persistencia, no entrega de email. El correo sigue bloqueado en staging; cualquier prueba de entrega debe distinguir un capturador sintético de transporte real.

Producción permanece excluida. Un despliegue debe respaldar `api.php`, `contract.json` y `espaciosutil-cde-mobile.php`, añadir `account.php` y verificar únicamente `espacio-sutil-staging` (138.68.135.185). El rollback restaura esos tres archivos y retira el módulo nuevo del cargador; no elimina datos de perfiles o solicitudes.

## Verificación del 7 de septiembre de 2026

54 comprobaciones HTTPS aprobadas contra staging y nueve pruebas PHP del normalizador, incluida eliminación efectiva de metadatos sintéticos. Se verifica aislamiento, concurrencia de perfil y del mismo correo entre dos cuentas, límites, códigos caducados/reutilizados, cambio de contraseña y revocación de acceso y refresh. Tabla de envíos HTML Forms comprobada como InnoDB y guardado habilitado. Copia de recuperación en `/srv/www/espaciosutil.org/shared/backups/mobile-0.6.0`.

Los tests sólo preparan `cde-test-account*` con direcciones `@example.invalid`. Durante la repetición se corrigió la preparación de usuarios existentes para restablecer su contraseña mediante `wp_set_password`; no afecta a la implementación de la API ni a cuentas reales. Los usuarios de prueba se restauran y el capturador de correo se elimina en la limpieza. No se ha habilitado el transporte real de correo en staging.

## Contacto sin sesión

`GET /support/challenge`, `POST /support` y `GET /privacy` son públicos y no alteran el acceso a las rutas privadas. Todas las entradas de contacto de la app reutilizan el mismo formulario y el mismo almacenamiento HTML Forms. Las consultas sin sesión incluyen nombre/correo aportados por el visitante y se identifican como identidad no verificada. El desafío firmado vence a los 15 minutos; se comprueba respuesta, UUID, firma y señuelo. Se limitan desafíos por IP y envíos por IP/correo. Los reintentos ya recibidos devuelven el mismo recibo y no duplican el envío.

20 comprobaciones HTTPS adicionales aprobadas: privacidad pública, rutas privadas cerradas, consentimiento, campos ajenos, firma alterada, respuesta errónea, caducidad, vinculación al UUID, límites, idempotencia y un único registro con origen no verificado. Las pruebas eliminan exclusivamente sus registros sintéticos. El bloqueo del correo real sigue vigente.
