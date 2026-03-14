# Plan de implantación: Stripe + Paid Memberships Pro

Fecha: 2026-03-12
Estado: Propuesto
Alcance actual: entornos `development` y `staging`

## 1. Objetivo

Dejar preparada la pasarela de pago con Stripe para el sistema de membresías del sitio, de forma que:

- pueda probarse de extremo a extremo antes de existir producción,
- el cambio a producción requiera el menor número posible de ajustes,
- el flujo de alta, renovación, cancelación y gestión de pagos quede validado antes del lanzamiento.

## 2. Contexto actual del proyecto

- El sitio usa WordPress sobre Bedrock/Sage.
- La lógica de membresías se apoya en Paid Memberships Pro (PMPro).
- La página de suscripción ya existe y enlaza al checkout de PMPro por nivel.
- El modelo comercial ya está decidido:
  - una única suscripción con acceso a todo el contenido,
  - tres frecuencias de pago: mensual, semestral y anual.
- A día de hoy sólo existen:
  - `development` local,
  - `staging` remoto.
- La web todavía no tiene dominio/entorno de producción operativo.

## 3. Decisión técnica recomendada para el MVP

Para la primera salida, la opción más simple y robusta es:

- usar `PMPro + Stripe Checkout`,
- aceptar pago con tarjeta,
- dejar medios adicionales como SEPA para una fase posterior,
- evitar integraciones personalizadas mientras no aporten una necesidad real de negocio.

Motivo:
- el proyecto ya está orientado a membresías recurrentes con PMPro,
- la UI pública de suscripción ya está construida,
- Stripe Checkout reduce superficie de error PCI y de mantenimiento.

## 4. Qué se puede dejar listo antes de producción

## Sí se puede dejar listo

- La estructura de niveles de PMPro.
- Los precios y ciclos de cobro.
- La conexión de Stripe en modo test.
- Los webhooks de prueba contra `staging`.
- Las pruebas funcionales completas de suscripción.
- La documentación operativa del cambio a `live`.

## No queda cerrado hasta producción

- El webhook de `live` con la URL definitiva.
- La conexión `live` de Stripe desde el sitio final.
- Las URLs públicas definitivas ligadas al dominio de producción.
- La validación final con un pago real en entorno `live`.

Conclusión operativa:
- sí, es viable dejar el sistema casi completamente preparado antes de tener producción,
- pero el día de salida habrá que ejecutar una fase corta de activación y validación final.

## 5. Fases del plan

## Fase 0. Descubrimiento y decisiones de negocio

Objetivo:
- cerrar las condiciones comerciales restantes antes de tocar la configuración de cobro.

Tareas:
- Confirmar si la cuenta de Stripe del cliente ya existe y quién tiene acceso.
- Confirmar país fiscal del negocio, titular legal y cuenta bancaria asociada.
- Tomar como base cerrada el modelo ya aprobado:
  - una sola membresía,
  - acceso completo,
  - tres niveles/frecuencias de cobro: mensual, semestral y anual.
- Confirmar si habrá:
  - periodo de prueba,
  - cupones,
  - promociones de lanzamiento,
  - cambios de plan,
  - cancelación inmediata o al final del periodo.
- Definir si el precio se mostrará con IVA incluido o no.
- Definir el texto legal y de soporte que debe aparecer en el flujo de pago.

Salida:
- matriz simple de planes y reglas comerciales aprobada.

### Estado validado de Fase 0 (2026-03-12)

Decisiones ya confirmadas en reunión:
- Existe cuenta de Stripe y tanto cliente como equipo tienen acceso.
- Están disponibles los datos de la empresa que facturará.
- El modelo comercial es una única suscripción con acceso a todo el contenido.
- La suscripción tendrá tres frecuencias de pago:
  - mensual,
  - semestral,
  - anual.
- Habrá periodo de prueba de `7 días`.
- Habrá cupones, pero su implementación queda para una fase posterior.
- Habrá promoción de lanzamiento del `50%` sobre el cobro aplicable durante la ventana comercial de lanzamiento.
- La cancelación será al final del periodo ya pagado.
- El precio se mostrará con IVA incluido.

Cierre operativo de Fase 0:
- IVA confirmado: `21%`.
- Frecuencias confirmadas:
  - mensual,
  - semestral,
  - anual.
- Promoción de lanzamiento definida en términos comerciales:
  - descuento del `50%`,
  - fecha de inicio: cuando salga la web,
  - fecha concreta de lanzamiento: pendiente,
  - se aplica al cobro,
  - aplica como porcentaje, sin reglas distintas por frecuencia.
- Regla comercial confirmada:
  - las ofertas no pueden superponerse,
  - el usuario deberá acogerse a una sola oferta.
- Pendiente único de Fase 0:
  - redactar el texto legal y comercial.
  - ese texto debe explicar prueba de 7 días, cobro posterior, cancelación al final del periodo e IVA incluido.

Nota operativa sobre la promoción:
- A efectos de implementación, se tomará como hipótesis de trabajo que durante la ventana de lanzamiento habrá un único descuento del `50%` sobre el cobro aplicable a la suscripción contratada, independientemente de la frecuencia elegida.
- La fecha exacta de inicio y fin de la campaña queda pendiente hasta fijar la salida.

Estado de Fase 0:
- Cerrada a falta de redacción legal/comercial final.
- El proyecto puede avanzar ya a Fase 1: preparación de Stripe.

## Fase 1. Preparación de Stripe

Objetivo:
- dejar la cuenta preparada para aceptar cobros reales cuando llegue el momento.

Tareas:
- Acceder al panel de Stripe y verificar que la cuenta está activa o en proceso de activación.
- Completar:
  - datos del negocio,
  - verificación de identidad/KYC,
  - cuenta bancaria,
  - teléfono y correo de soporte,
  - descriptor bancario.
- Activar `2FA` para la cuenta del cliente.
- Revisar si la cuenta trabaja como empresa, autónomo u otra figura legal.
- Confirmar que el cliente entiende la diferencia entre:
  - `Test mode`,
  - `Live mode`.

Validación:
- Stripe debe mostrar la cuenta como apta para operar o indicar únicamente pendientes menores no bloqueantes.

### Estado de Fase 1 (2026-03-12)

Situación actual:
- Se ha iniciado la preparación de Stripe.
- Se están completando los datos del negocio en la cuenta.

## Fase 2. Preparación de PMPro en WordPress

Objetivo:
- alinear la configuración de PMPro con el modelo de cobro ya decidido.

Tareas:
- Revisar que PMPro represente una única membresía comercial mediante tres niveles técnicos de cobro:
  - mensual,
  - semestral,
  - anual.
- Confirmar que cada uno de esos tres niveles tiene:
  - nombre correcto,
  - precio correcto,
  - ciclo correcto,
  - `allow_signups` activo.
- Revisar la landing de suscripción para asegurar que presenta esta estructura como una sola suscripción con tres frecuencias, no como productos distintos.
- Confirmar que todos los niveles conceden exactamente el mismo acceso al contenido.
- Revisar páginas base de PMPro:
  - checkout,
  - confirmación,
  - cuenta,
  - facturación,
  - cancelación,
  - login.
- Revisar los emails automáticos de PMPro.
- Comprobar que las restricciones de acceso por membresía están bien aplicadas en el contenido.

Validación:
- La oferta visible en la web debe coincidir exactamente con una única suscripción y sus tres frecuencias de cobro.

## Fase 3. Configuración técnica en `development`

Objetivo:
- entender el flujo y documentarlo, sin depender todavía de URLs públicas.

Tareas:
- Conectar Stripe en modo test dentro de PMPro en el entorno local.
- Revisar qué opciones de PMPro/Stripe va a usar el proyecto:
  - Stripe Checkout recomendado,
  - campos de facturación,
  - impuestos,
  - cupones,
  - gestión de suscripción.
- Documentar todas las pantallas y valores a configurar.
- Hacer pruebas locales de navegación y checkout sabiendo que:
  - los webhooks no deben depender de local,
  - local sirve para revisión funcional, no como endpoint permanente de Stripe.

Validación:
- Documento interno de configuración completado.
- Flujo de checkout entendido por el equipo.

Nota:
- si hace falta probar webhooks desde local, usar Stripe CLI sólo como apoyo puntual.

## Fase 4. Configuración funcional completa en `staging`

Objetivo:
- usar `staging` como réplica operativa de producción en modo test.

Tareas:
- Configurar PMPro con Stripe en `Test/Sandbox`.
- Conectar la cuenta de Stripe test desde el panel de WordPress.
- Crear o validar el webhook de test apuntando a `staging`.
- Verificar que `staging` usa `HTTPS` y URLs estables.
- Revisar el comportamiento del checkout retornando a las páginas de PMPro del sitio.
- Validar posibles conflictos con caché, redirecciones o reglas del servidor.

Validación:
- Los eventos de Stripe test deben llegar correctamente a `staging`.
- PMPro debe registrar pedidos y cambios de membresía sin intervención manual.

## Fase 5. Pruebas de extremo a extremo en `staging`

Objetivo:
- certificar que el sistema se comporta bien en casos normales y en incidencias habituales.

Tareas:
- Alta nueva en cada frecuencia activa.
- Comprobación de creación de usuario y pedido.
- Comprobación de acceso al contenido restringido.
- Renovación automática en test si el flujo lo permite.
- Cancelación de membresía desde la cuenta.
- Cambio de tarjeta o método de pago, si se habilita.
- Simulación de pago fallido.
- Simulación de webhook relevante no recibido o retrasado.
- Revisión de emails de alta, confirmación y avisos.
- Pruebas en móvil y escritorio.

Casos mínimos a cubrir:
- usuario nuevo compra plan mensual,
- usuario nuevo compra plan semestral,
- usuario nuevo compra plan anual,
- usuario cancela,
- pago recurrente falla,
- administrador puede identificar el problema en WordPress y en Stripe.

Salida:
- checklist de QA firmada como válida para lanzamiento.

## Fase 6. Preparación del cambio a producción

Objetivo:
- dejar una secuencia breve y controlada para el día del lanzamiento.

Tareas:
- Confirmar dominio final de producción.
- Preparar las credenciales `live` que se usarán en WordPress.
- Preparar las variables/secrets necesarias para el entorno de producción.
- Anotar la URL exacta del endpoint de webhook que tendrá producción.
- Revisar enlaces públicos asociados a Stripe:
  - soporte,
  - términos,
  - política de reembolsos,
  - portal de cliente si se usa.
- Decidir quién ejecuta el cambio y quién valida el primer cobro real.

Salida:
- runbook corto de lanzamiento.

## Fase 7. Activación en producción

Objetivo:
- pasar de entorno probado en test a cobro real con riesgo controlado.

Tareas:
- Desplegar la web de producción.
- Configurar PMPro con Stripe en `Live`.
- Crear o validar el webhook `live` apuntando al dominio de producción.
- Verificar que las páginas de cuenta, checkout y confirmación responden bien en producción.
- Ejecutar un pago real de importe bajo.
- Confirmar:
  - pedido en WordPress,
  - cargo en Stripe,
  - alta o cambio de membresía,
  - acceso al contenido,
  - emails emitidos correctamente.

Validación:
- No se abre el lanzamiento al público hasta superar esta compra real de prueba.

## 6. Checklist por entorno

## `development`

- Revisar configuración y entender el flujo.
- Documentar decisiones.
- Probar UI y navegación.
- No depender de él para webhooks permanentes.

## `staging`

- Debe ser el entorno principal de integración.
- Debe tener Stripe en modo test.
- Debe recibir webhooks de test reales.
- Debe validar la operativa completa de membresías.

## `production`

- Sólo debe asumir la activación `live`.
- Debe heredar la configuración funcional ya verificada.
- Debe ejecutar una prueba real final antes de abrir tráfico.

## 7. Riesgos y controles

## Riesgos principales

- Niveles de PMPro mal configurados respecto a los precios reales.
- Diferencias entre `test` y `live` no replicadas el día del lanzamiento.
- Webhook `live` apuntando a una URL incorrecta.
- Cuenta de Stripe no completamente activada.
- Correos, accesos o cancelaciones no verificados antes de salir.

## Controles recomendados

- No lanzar sin QA completa en `staging`.
- No lanzar sin checklist de cambio a producción.
- No lanzar sin una compra real de prueba.
- Mantener trazabilidad entre pedido de WordPress y pago en Stripe.

## 8. Orden recomendado de ejecución

1. Confirmar planes y reglas comerciales.
2. Revisar y activar la cuenta de Stripe.
3. Alinear niveles y páginas de PMPro.
4. Configurar Stripe test en `development`.
5. Configurar Stripe test y webhooks en `staging`.
6. Ejecutar QA completa en `staging`.
7. Preparar runbook de salida.
8. Activar `live` el día de producción y validar con compra real.

## 9. Siguiente paso sugerido

Convertir este plan en una checklist operativa más detallada para `staging`, con pasos concretos dentro de:

- panel de Stripe,
- panel de WordPress / PMPro,
- validaciones funcionales posteriores.
