# Contexto del Proyecto: Espacio Sutil

Este archivo proporciona el contexto necesario para que Gemini pueda asistir eficazmente en el desarrollo de este proyecto.

## 1. Resumen del Proyecto

- **Nombre:** Espacio Sutil
- **Tipo:** Sitio web profesional con WordPress.
- **Funcionalidad Principal:** Portal de membresías y cursos online (Curso de Desarrollo Espiritual).
- **Stack Tecnológico:**
  - **Trellis:** Provisión de servidores y despliegue.
  - **Bedrock:** Estructura moderna de WordPress.
  - **Sage 11:** Tema personalizado con Blade, Tailwind CSS y Vite.
  - **Plugins Clave:** Paid Memberships Pro (PMP).

## 2. Entorno de Desarrollo

- **Host:** macOS
- **Máquina Virtual:** Gestionada por **Trellis** con el proveedor **Lima**.
- **Directorio Raíz del Proyecto (Host):** `/Users/aitor/Documents/Sites/espaciosutil.org`
- **Directorio del Sitio WordPress (Host):** `/Users/aitor/Documents/Sites/espaciosutil.org/site`

## 3. Instrucciones para Gemini

### Idioma de Respuesta

Siempre debes responder en español.

### Sintaxis Blade

Debido a que el símbolo `@` tiene un significado especial en la interfaz de `gemini-cli`, al proporcionar fragmentos de código Blade que contengan directivas (como `@if`, `@foreach`, `@include`), es recomendable omitir el símbolo `@` para evitar conflictos. Por ejemplo, en lugar de `@if`, se puede usar `if`. Por tanto, al leer fragmentos de Blade que omiten la arroba, debo interpretarlos como si tuvieran la arroba.

### Ejecución de Comandos `wp-cli`

Para ejecutar comandos `wp-cli`, se deben seguir las siguientes reglas:

1.  **NO intentar ejecutar `wp-cli` dentro de la VM** usando `trellis exec` o `limactl`. La comunicación debe hacerse desde el sistema anfitrión (macOS).
2.  El comando `run_shell_command` debe usar una **ruta relativa** desde el CWD de Gemini (`/Users/aitor`) hasta el directorio del sitio de WordPress.
3.  La ruta relativa correcta para el parámetro `directory` es: `Documents/Sites/espaciosutil.org/site`.

**Ejemplo de uso correcto:**
```python
default_api.run_shell_command(
    command="wp post list --post_type=page",
    directory="Documents/Sites/espaciosutil.org/site"
)
```

### Nota sobre la Base de Datos

El archivo `.env` en el directorio `site/` ha sido configurado con `DB_HOST='127.0.0.1'` para permitir la conexión a la base de datos desde el anfitrión. No se debe cambiar a `localhost`.

### Gestión de Versiones (Git)

Se ha verificado que Gemini puede ejecutar el flujo completo de Git (add, commit, push) en este repositorio. Todos los mensajes de commit deben seguir las convenciones de [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/).