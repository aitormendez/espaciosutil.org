# Contexto del Proyecto

Este directorio separa el contexto operativo del proyecto en documentos temáticos para cargar solo lo necesario en cada tarea.

## Cómo usarlo

- Leer primero `AGENTS.md` para obtener el marco general del proyecto.
- Para **todo trabajo de UI**, leer las [convenciones de estilos: Tailwind por defecto y excepción AJAX del índice CDE](arquitectura-cde-y-frontend.md#convenciones-de-estilos-para-todo-trabajo-de-interfaz), aunque la tarea no afecte al CDE ni a un visor.
- Abrir después solo los documentos especializados que apliquen al trabajo en curso.
- Tratar estos archivos como contexto operativo vivo: si una implementación cambia, actualizar el documento temático correspondiente.

## Mapa de documentos

- [Convenciones de UI y arquitectura CDE](arquitectura-cde-y-frontend.md)
  - **Para cualquier interfaz del proyecto:** prioridad de utilidades Tailwind en plantillas/componentes, clases detectables al compilar, separación de responsabilidades y excepción de CSS centralizado para Temas AJAX del índice CDE.
  - Además: reproductor CDE, selector vídeo/audio, subíndice jerárquico, quiz por lección e índice del curso. Cargar las secciones de arquitectura cuando la tarea afecte a esos sistemas.

- [practicas-cde.md](practicas-cde.md)
  - Estado vigente de catálogo, ficha e incrustado; datos ACF, voz, índice adaptable, fondo y separadores opcionales, código, pruebas y aislamiento.
  - Cargar cuando la tarea afecte a prácticas o a su convivencia con presentación y cuestionario; distingue implementación local de integración pendiente.

- [Editor de presentaciones CDE](../cde-presentaciones-editor.md)
  - Repetidor ACF, imágenes privadas, persistencia y sustitución de importaciones.
  - Cargar para modificar el visor o la edición de presentaciones junto a prácticas.

- [Modalidad de producción CDE](cde-tipo-produccion.md)
  - Campo ACF obligatorio Voz humana/Voz sintética, contrato con NocoDB, transporte al publicador y límites de activación.
  - Cargar para `tipo_produccion`, validación del editor y su sincronización; el frontend requiere diseño previo.

- Familias de series CDE:
  - [Backend y administración](cde-familias.md): taxonomía, selector humano, herencia, API y pruebas.
  - [Automatizaciones](cde-familias-automatizaciones.md): contrato de cuatro familias, campo NocoDB `familia_serie` y condiciones de integración/activación.
  - [Layouts actuales en OpenPencil](cde-familias-openpencil.md): ubicación de las réplicas editables, persistencia y limitación visual del editor tras guardar.
  - Cargar cuando la tarea afecte a clasificación familiar, inventario, publicación automatizada o preparación de su identidad visual.

- `navegacion-y-layout.md`
  - Contextos ES/CDE, color de fondo por sección, contrato Barba, estado activo del menú y estilos por contexto.
  - Cargar cuando la tarea afecte a cabecera, navegación, transiciones o layout global.

- `suscripcion-pmpro-y-emails.md`
  - Landing de suscripción, trial gratuito personalizado, checkout/membresía y emails transaccionales de PMPro.
  - Cargar cuando la tarea afecte a suscripción, Stripe/PMPro o copy/flujo de emails.

- `email-transaccional-mailgun.md`
  - Implementación SMTP con Mailgun, override del remitente transaccional y validación operativa de entrega.
  - Cargar cuando la tarea afecte a Mailgun, `ssmtp`, remitente `From`, `.env` de correo o pruebas de entrega.

- `legal-y-cookies.md`
  - Estructura legal publicada, integración en formularios/checkout y banner de cookies propio.
  - Cargar cuando la tarea afecte a textos legales, consentimiento o páginas legales.

- `matomo.md`
  - Aprovisionamiento con Trellis, dominios por entorno, DNS, variables de entorno e integración del tracking.
  - Cargar cuando la tarea afecte a analítica, infraestructura Matomo o tracking.

- `monitorizacion-operativa.md`
  - Monitorizacion externa de disponibilidad publica del sitio con HetrixTools y criterio de actuacion ante alerta.
  - Cargar cuando la tarea afecte a disponibilidad externa, alertas de caida o monitorizacion operativa del sitio.

- `paginas-cde.md`
  - Panorama de plantillas editoriales CDE y componentes Blade reutilizables.
  - Cargar cuando la tarea afecte a landings/páginas editoriales del curso.

- `estrategia-comunicacion-multicanal.md`
  - Estrategia operativa para Telegram, Instagram, TikTok, YouTube Shorts, episodios de Iván y lecciones CDE.
  - Cargar cuando la tarea afecte a automatización editorial, fuentes de verdad, campañas o distribución por canales.
