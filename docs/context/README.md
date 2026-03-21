# Contexto del Proyecto

Este directorio separa el contexto operativo del proyecto en documentos temáticos para cargar solo lo necesario en cada tarea.

## Cómo usarlo

- Leer primero `CONTEXT.md` para obtener el marco general del proyecto.
- Abrir después solo los documentos especializados que apliquen al trabajo en curso.
- Tratar estos archivos como contexto operativo vivo: si una implementación cambia, actualizar el documento temático correspondiente.

## Mapa de documentos

- `arquitectura-cde-y-frontend.md`
  - Reproductor CDE, selector vídeo/audio, subíndice jerárquico, quiz por lección e índice del curso.
  - Cargar cuando la tarea afecte a lecciones CDE, frontend React/Vidstack o estructura de contenidos del curso.

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

- `paginas-cde.md`
  - Panorama de plantillas editoriales CDE y componentes Blade reutilizables.
  - Cargar cuando la tarea afecte a landings/páginas editoriales del curso.

- `estrategia-comunicacion-multicanal.md`
  - Estrategia operativa para Telegram, Instagram, TikTok, YouTube Shorts, episodios de Iván y lecciones CDE.
  - Cargar cuando la tarea afecte a automatización editorial, fuentes de verdad, campañas o distribución por canales.
