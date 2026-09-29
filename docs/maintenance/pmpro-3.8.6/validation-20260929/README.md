# Validación de PMPro 3.8.6 — 29 de septiembre de 2026

Ejecutor: chat Codex autorizado por Aitor para staging y, si pasa la validación, producción. El informe distingue pruebas ejecutadas de revisiones independientes. No se realizan cargos reales ni cambios de suscripción de clientes.

## Paquete

PMPro oficial 3.8.6 (`e32b6af`), localización es_ES acotada (`6976124`) y recuperación de los tres PNG fuente usados por los correos (`ec8e06d`). WordPress permanece en 7.0.3. El staging conserva sus cambios propios de aislamiento y aplicación móvil; el despliegue clona cada release real y sustituye únicamente el paquete acotado.

## Staging

Host `stage.espaciosutil.org`, PHP CLI 8.4.25, release `20260929-pmpro386`, esquema PMPro 3.53 → 3.84. Los 1286 archivos del plugin coinciden con el manifiesto oficial. Respaldo privado `/home/web/pmpro-20260929/before.sql`, SHA256 `13155a6e755bf439e3ffd3f72398ebd164aefb59234d35af02d66bcbc30fd19e`; restaurado en una base temporal aislada con 73/73 checksums iguales y retirado después. Release anterior conservado: `20260905152352`.

Chrome real accede por túnel SSH con TLS válido. La ACL sigue privada. Las claves sandbox preexistentes se verificaron contra Stripe (`livemode=false`); una configuración temporal exclusiva de staging fuerza sandbox/EUR y vacía claves live, con caducidad. Correo, cron, runner asíncrono e integraciones no necesarias permanecen bloqueados; Bunny mantiene la excepción previa de solo lectura. No se crean credenciales ni endpoints de Stripe nuevos.

| Prueba ejecutada | Resultado |
| --- | --- |
| Trial/recovery en PHP remoto | 16 casos de trial y 5 resultados de recovery correctos; ninguna recuperación encolada en la migración |
| Acceso previo | Fixture activo permite; sin membresía, caducado y cancelado deniegan |
| Altas nuevas por checkout real | Mensual 5 EUR, semestral 25 EUR, anual 45 EUR; los tres muestran 7 días gratis y Stripe test en español |
| Cobro tras trial | Se adelanta exclusivamente el trial sintético; facturas sandbox pagadas por 500/2500/4500 céntimos; detalle en `billing-results.json` |
| Eventos de alta y pago | Eventos existentes recuperados por PMPro desde la API de Stripe; seis pedidos (tres iniciales de cero y tres cobros) |
| Idempotencia | Repetir los tres eventos de factura responde «ya procesado»; siguen exactamente seis pedidos |
| Cancelación desde la web | Stripe anual `canceled`; acceso conservado hasta 29/09/2027, fin del periodo sintético pagado |
| Tarjeta rechazada | Stripe muestra rechazo en español; usuario sintético sin membresía |
| Correos | 17 plantillas efectivas de BD/tema, sin variables pendientes; entrega bloqueada; detalle en `email-results.json` |
| Navegador | Sin errores JavaScript en los flujos ejecutados |

La primera prueba descubrió un fallo anterior de staging: faltaban las tres imágenes de correo en el manifiesto Vite, provocando HTTP 500 tras activar la membresía. Se copiaron los assets exactos presentes en producción y se corrigió el origen: las fuentes PNG estaban excluidas por `.gitignore`. Se versionan ahora y una prueba comprueba las referencias usadas por el tema. Los flujos siguientes y el render real de bienvenida funcionan.

Limitaciones: por la ACL, los eventos se entregaron al handler mediante túnel; el handler consulta su autenticidad/contenido en Stripe. Esto no acredita entrega pública automática de Stripe a staging. No se envía correo real desde staging. Las pruebas no sustituyen la comprobación posterior de configuración, integridad y endpoint productivos.

## Producción

Preparación a las 10:22 UTC: respaldo consistente privado `/home/web/pmpro-20260929/before.sql`, SHA256 `6de80bc3bdf6cc4410903731e032f3cf0b0e856a019ac09cd878cb1cad74467a`. Restauración aislada correcta de 68 tablas: 67 checksums idénticos; `wp_options` había cambiado con el sitio en servicio. La copia temporal se eliminó. Se tomará un respaldo final bajo mantenimiento antes de migrar. Release preparado pero todavía inactivo; versión activa en este punto: 3.6.5.

Reversión: conservar release anterior y respaldo final. Tras migrar, revertir solo código no basta. Detener escrituras/runners, preservar cualquier pedido nuevo y reconciliarlo antes de restaurar BD y release juntos.

## Evidencia adicional y limpieza de staging

La cuenta mensual creada por checkout accede a la lección publicada 2875: navegador 19814 caracteres de contenido/cuestionario; anónimo 1053 y aviso de restricción. Trial consumido y no elegible de nuevo. FPM 8.4.25, cero errores PHP/FastCGI posteriores a 10:05 UTC. El correo de bienvenida real, generado desde el pedido inicial sin completar tokens, contiene 7 días, primer cobro de 5 EUR el 6 de octubre y acceso a cuenta; se conserva su texto. Los 17 renders generales usan fixtures enriquecidos y no acreditan por sí solos los datos de todos los eventos reales.

La transcripción del replay explicita su origen y límites: el script solo exigía HTTP200, la revisión humana de la salida comprobó «already processed». No hay archivo autónomo del ledger anterior; backend-evidence.json conserva la lectura posterior y los tres eventos Stripe originales.

Limpieza: tres suscripciones sandbox canceladas, cuatro clientes Stripe sintéticos eliminados y sesión pendiente expirada. Cuatro usuarios y siete pedidos de prueba retirados de staging; quedan sus 23 usuarios originales. Helper y archivo de claves eliminados, ambas claves Stripe ausentes, sandbox, correo/cron/runner bloqueados. Se fija EUR para corregir el default previo USD. El harness de aislamiento global no pasa por la clave Bunny de solo lectura preexistente; todos sus checks críticos pasan. No se modifica esa integración ni la ACL.
