# Contexto del Proyecto: Espacio Sutil

## 1. Resumen del Proyecto

- **Nombre:** Espacio Sutil
- **Tipo:** Sitio web profesional con WordPress.
- **Funcionalidad principal:** Portal editorial + membresías y curso online (Curso de Desarrollo Espiritual).
- **Stack tecnológico:**
  - **Trellis:** provisión de servidores y despliegue.
  - **Bedrock:** estructura moderna de WordPress.
  - **Sage 11:** tema personalizado con Blade, Tailwind CSS y Vite.
  - **Plugins clave:** Paid Memberships Pro.

## 2. Entorno de Desarrollo

- **Host:** macOS
- **Máquina virtual:** gestionada por **Trellis** con el proveedor **Lima**.
- **Directorio raíz del proyecto:** `/Volumes/D/Desarrollo/Sites/espaciosutil.org`
- **Directorio del sitio WordPress:** `/Volumes/D/Desarrollo/Sites/espaciosutil.org/site`

## 3. Instrucciones de Trabajo

### Estilos de interfaz: Tailwind por defecto

**Regla obligatoria para todo trabajo de UI del proyecto, no sólo CDE:** priorizar utilidades Tailwind directamente en las plantillas y componentes para layout, espaciado, tipografía, adaptación responsive y estados. No crear hojas CSS paralelas para lo que puede expresarse con utilidades por mera preferencia del agente.

- Usar nombres de clases completos y detectables por la compilación; no construirlos concatenando fragmentos dinámicos. Se permiten propiedades CSS personalizadas para valores procedentes de datos, consumidas por utilidades completas.
- No duplicar la misma propiedad entre utilidades y CSS separado. No usar `@apply` simplemente para ocultar las utilidades fuera del HTML y eludir esta regla.
- **Excepción aprobada del índice CDE:** mantener CSS separado y centralizado para el contenido de **Temas cargado mediante AJAX**: árbol de lecciones, filas, niveles anidados, controles y estados. Es una decisión de mantenimiento, no una incapacidad técnica de Tailwind.
- Esa excepción **no incluye la estructura estática** del índice: hero, bandas familiares, listas de series/bloques, encabezados ni layout de los contenedores de paneles; todo ello usa utilidades Tailwind.
- Conservar los estilos globales, tokens y estilos de terceros existentes: esta norma no ordena una migración masiva ajena al encargo. Cualquier otra excepción requiere justificación y documentación explícitas.

Leer [las convenciones de estilos y el alcance de la excepción AJAX](docs/context/arquitectura-cde-y-frontend.md#convenciones-de-estilos-para-todo-trabajo-de-interfaz) antes de crear o modificar una interfaz.

### Idioma de respuesta

Siempre debes responder en español.

### Ejecución de `wp-cli`

Para ejecutar `wp-cli`:

1. No intentar ejecutarlo dentro de la VM con `trellis exec` o `limactl`.
2. Ejecutarlo desde el sistema anfitrión.
3. Usar como directorio de trabajo relativo `site`.

Ejemplo:

```python
default_api.run_shell_command(
    command="wp post list --post_type=page",
    directory="site"
)
```

### Base de datos y conectividad

- Mantener `DB_HOST='127.0.0.1'` en `site/.env` y `db_user_host: '127.0.0.1'` en Trellis para asegurar conectividad desde el host.

### Git

- El flujo completo de Git funciona en este repositorio.
- Los mensajes de commit deben seguir **Conventional Commits**.
- La descripción del commit debe ir en español.
- El `scope` es obligatorio.
- La primera línea del commit debe seguir el formato `tipo(scope): descripcion en espanol`.
- Se permite añadir un cuerpo descriptivo debajo cuando ayude a explicar cambios complejos.

### Vaults

Antes de añadir o commitear archivos `vault.yml`, verificar su estado. Si están modificados y desencriptados, re-encriptarlos con `trellis vault encrypt <entorno>` antes de continuar.

Existe además un hook `pre-commit` que bloquea el commit si algún `trellis/group_vars/*/vault.yml` staged no está cifrado con Ansible Vault.

## 4. Estado Operativo Actual

- El entorno local está estable y operativo.
- El acceso a `wp-admin` funciona.
- Los comandos `wp-cli` funcionan desde el host.
- La base de datos y Trellis están funcionando en el estado actual del proyecto.

## 5. Despliegue

- Los workflows `deploy-staging.yml` y `deploy-production.yml` usan entornos de GitHub Actions.
- En `production` hay que configurar el secreto `TRELLIS_DEPLOY_SSH_KNOWN_HOSTS`.

## 6. Resumen Funcional Actual

- El sitio combina contexto editorial `ES` y contexto de curso `CDE`.
- La navegación y parte del layout ya están organizados por contextos.
- El curso tiene frontend especializado para medios, subíndice, quiz e índice jerárquico.
- La suscripción usa landing propia, trial personalizado en PMPro y emails transaccionales adaptados.
- La estructura legal ya está publicada y el consentimiento de cookies se implementa en el tema.
- Matomo está integrado técnicamente y condicionado al consentimiento analítico.

## 7. Contexto Especializado

`AGENTS.md` debe actuar como punto de entrada general. Para trabajo específico, cargar después únicamente los documentos necesarios en `docs/context/`.

Índice principal:

- `docs/context/README.md`

### Convenciones de UI y arquitectura CDE

- [Convenciones de estilos para todo trabajo de interfaz](docs/context/arquitectura-cde-y-frontend.md#convenciones-de-estilos-para-todo-trabajo-de-interfaz): cargar para **cualquier trabajo de UI**, también fuera del CDE.
- El mismo documento contiene la arquitectura especializada de reproductor, medios, subíndice, quiz e índice del curso; cargar esas secciones cuando corresponda.

### Prácticas CDE

- [Referencia vigente de prácticas](docs/context/practicas-cde.md).
- Cargar para catálogo, CPT `practica`, Relationship, pestaña ACF, visor, narración, índice y composición con presentación/cuestionario.
- Para incorporar prácticas a `cde-lesson-full-flow`, leer el [traspaso de automatización](docs/relevo/practicas-automatizacion-lecciones.md). Distingue código existente, acuerdos editoriales y voz, y adaptaciones pendientes del flujo; no acredita instalación de esa capacidad.
- Conservar el Relationship `cde_practicas` en la pestaña «Prácticas» de «Lección CDE» y un único visor/índice compartido entre guía y lectura. Los bloques opcionales no deben producir secciones vacías ni separadores duplicados.
- El desarrollo de prácticas está en `/Users/aitor/.codex/worktrees/cde-practicas/espaciosutil.org`, rama `codex/cde-practicas`. No integrar en el checkout compartido, recertificar Contenidos ni instalar/actualizar skills de Paperclip sin coordinación explícita. La copia local de previsualización puede contener trabajo de otros hilos: no sobrescribirla en bloque.
- Staging recibió la entrega conjunta de prácticas y presentaciones el 2026-10-16 desde `codex/cde-staging-practicas-presentaciones`; consultar [release, pruebas y reversión](docs/infraestructura/staging-practicas-presentaciones-2026-10-16.md). No confundirla con una integración en main, producción o Contenidos.
- Producción recibió la entrega conjunta autorizada el 2026-10-19 como release `20261019094954`; leer [despliegue y límites](docs/infraestructura/produccion-practicas-presentaciones-2026-10-19.md). El puente del motor 3.5.0 está verificado en lectura; el primer piloto con audio revisado y publicación real sigue pendiente.
- La adaptación móvil web está en la rama conjunta `codex/cde-staging-practicas-presentaciones`: controles compactos por debajo de 1024 px, índice superpuesto y contenido desplazable en pantalla completa. Validar también ficha, catálogo y escritorio al modificar el visor; las apps nativas y la aprobación visual móvil siguen pendientes.
- Actualizar la referencia vigente al cambiar el comportamiento; conservar las evidencias anteriores como historial. La validación local no equivale a despliegue ni aprobación móvil.

### Familias de series CDE

- [Backend y administración de familias](docs/context/cde-familias.md)
- [Contrato con NocoDB y automatizaciones](docs/context/cde-familias-automatizaciones.md)
- Cargar si la tarea afecta a la clasificación familiar de series, su herencia en lecciones, reconciliación de inventario o publicación automatizada.

### Navegación y layout

- `docs/context/navegacion-y-layout.md`
- Cargar si la tarea afecta a menús ES/CDE, Barba, color de sección o layout global.

### Suscripción, PMPro y emails

- `docs/context/suscripcion-pmpro-y-emails.md`
- Cargar si la tarea afecta a suscripción, checkout, trial, Stripe o emails.

### Email transaccional Mailgun

- `docs/context/email-transaccional-mailgun.md`
- Cargar si la tarea afecta a SMTP, remitente transaccional, Trellis mail, Mailgun o validación de entrega.

### Estructura legal y cookies

- `docs/context/legal-y-cookies.md`
- Cargar si la tarea afecta a páginas legales, consentimiento, banner o integración legal en formularios/checkout.

### Matomo

- `docs/context/matomo.md`
- Cargar si la tarea afecta a analítica, DNS, aprovisionamiento o variables de entorno de Matomo.

### Páginas editoriales CDE

- `docs/context/paginas-cde.md`
- Cargar si la tarea afecta a hub CDE, programa, suscripción o plantillas editoriales del curso.
