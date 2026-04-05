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
- **Directorio raíz del proyecto:** `/Users/aitor/Documents/Sites/espaciosutil.org`
- **Directorio del sitio WordPress:** `/Users/aitor/Documents/Sites/espaciosutil.org/site`

## 3. Instrucciones de Trabajo

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

`CONTEXT.md` debe ser solo el punto de entrada general. Para trabajo específico, cargar después únicamente los documentos necesarios en `docs/context/`.

Índice principal:

- `docs/context/README.md`

### Arquitectura CDE y frontend

- `docs/context/arquitectura-cde-y-frontend.md`
- Cargar si la tarea afecta a reproductor, medios, subíndice, quiz o índice del curso.

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
