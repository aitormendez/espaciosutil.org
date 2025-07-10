# Contexto del Proyecto: Espacio Sutil (Restauración)

Este archivo proporciona el contexto necesario para que Gemini pueda asistir eficazmente en la restauración de este proyecto.

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
- **Directorio del Sitio WordPress (Host)::** `/Users/aitor/Documents/Sites/espaciosutil.org/site`

## 3. Instrucciones para Gemini

### Idioma de Respuesta

Siempre debes responder en español.

### Sintaxis Blade

Debido a que el símbolo `@` tiene un significado especial en la interfaz de `gemini-cli`, al proporcionar fragmentos de código Blade que contengan directivas (como `@if`, `@foreach`, `@include`), es recomendable omitir el símbolo `@` para evitar conflictos. Por ejemplo, en lugar de `@if`, se puede usar `if`. Por tanto, al leer fragmentos de Blade que omiten la arroba, debo interpretarlos como si tuvieran la arroba.

El mismo problema ocurra con las directivas con `@`en CSS.

### Ejecución de Comandos `wp-cli`

Para ejecutar comandos `wp-cli`, se deben seguir las siguientes reglas:

1.  **NO intentar ejecutar `wp-cli` dentro de la VM** usando `trellis exec` o `limactl`. La comunicación debe hacerse desde el sistema anfitrión (macOS).
2.  El comando `run_shell_command` debe usar una **ruta relativa** desde el directorio raíz del proyecto (donde se inicia `gemini-cli`) hasta el directorio del sitio de WordPress.
3.  La ruta relativa correcta para el parámetro `directory` es: `site`.

**Ejemplo de uso correcto:**

```python
default_api.run_shell_command(
    command="wp post list --post_type=page",
    directory="site"
)
```

### Nota sobre la Base de Datos

En el proyecto original el archivo `.env` en el directorio `site/` debía tener `DB_HOST='127.0.0.1'` para permitir la conexión a la base de datos desde el anfitrión, pero sospecho que este cambio podía estar en el origen del problema. Veremos ahora si podemos mantener la configuración por defecto de la instalación.

### Gestión de Versiones (Git)

Se ha verificado que Gemini puede ejecutar el flujo completo de Git (status, diff, add, commit, push) en este repositorio. Todos los mensajes de commit deben seguir las convenciones de [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/).

- **Mensajes de Commit:** Debido a limitaciones técnicas, los mensajes de commit deben ser de una sola línea.

### Gestión de Archivos Vault

Antes de añadir archivos `vault.yml` al área de preparación (`git add`) o de realizar un commit que los incluya, Gemini debe verificar su estado. Si los archivos `vault.yml` tienen modificaciones y están desencriptados, Gemini debe encriptarlos utilizando el comando `trellis vault encrypt <entorno>` (por ejemplo, `trellis vault encrypt development`, `trellis vault encrypt staging`, `trellis vault encrypt production`) antes de proceder. Esto asegura que nunca se suban al repositorio desencriptados.

## 4. Estado Actual de la Situación

### Problemas Persistentes (Estado Anterior)

- **Login de WordPress no funcionaba:** El usuario no podía iniciar sesión en el panel de administración. Se producía un bucle de redirección.
- **Editor de WordPress no accesible:** Incluso si el login funcionara, el editor del panel de administración no se cargaba correctamente (solo se veía la `wpadminbar`).
- **Comandos `wp-cli` fallaban:** Los comandos `wp-cli` ejecutados desde el anfitrión fallaban consistentemente con errores de conexión a la base de datos (`No such file or directory`).

### Estado Actual de los Problemas

- **Login de WordPress y Editor:** El login de WordPress y el acceso al editor funcionan correctamente en esta nueva instalación.
- **Comandos `wp-cli`:** Los comandos `wp-cli` se ejecutan correctamente desde el anfitrión.

### Diagnóstico Realizado

- Se ha verificado y configurado `DB_HOST='127.0.0.1'` en `site/.env`.
- Se ha configurado `db_user_host: '127.0.0.1'` en `trellis/group_vars/development/wordpress_sites.yml`.
- Se ha ejecutado `trellis provision development` varias veces sin errores.
- Se han vaciado las cachés del navegador y la caché de objetos del servidor (Redis/Memcached).
- Se han desactivado todos los plugins y se ha cambiado al tema por defecto (aunque esto no pudo probarse completamente debido al problema de login).
- Se ha intentado forzar la configuración de la base de datos en `site/wp-cli.yml` sin éxito.
- El problema parece ser una desconexión fundamental entre el host (donde se ejecuta el navegador y `wp-cli`) y la base de datos dentro de la VM de Lima, a pesar de que la configuración parece ser correcta.

## 5. Plan de Restauración (Fuerza Bruta) - Completado

Dada la persistencia de los problemas y la dificultad para diagnosticarlos en el entorno anterior, se decidió proceder con una **instalación limpia** de Trellis, Bedrock y Sage.

### Objetivo Cumplido

Se ha establecido un entorno de desarrollo completamente funcional y por defecto, donde el login de WordPress y los comandos `wp-cli` operan sin problemas.

### Pasos Realizados

1.  **Creación de un nuevo proyecto Trellis:** Se inició un nuevo proyecto Trellis desde cero.
2.  **Configuración del nuevo sitio de WordPress:** Se definió la configuración básica del sitio, incluyendo la correcta configuración de `DB_HOST` a través de Ansible.
3.  **Aprovisionamiento de la nueva VM:** Se ejecutó el aprovisionamiento para configurar el servidor y la base de datos, resolviendo los problemas de conexión.
4.  **Instalación y configuración del tema Sage:** Se añadió y configuró el tema Sage, incluyendo la migración de assets, dependencias y configuraciones personalizadas.
5.  **Traslado de Funcionalidad y Contenido:** Se han trasladado los plugins no gestionados por Composer, los scripts de sincronización de base de datos, los view composers, las rutas de API personalizadas, los campos ACF y los nuevos proveedores de servicios. La base de datos ha sido sincronizada con éxito.

## 6. Despliegue con GitHub Actions

Los flujos de trabajo de despliegue (`deploy-staging.yml` y `deploy-production.yml`) ahora utilizan entornos de GitHub Actions (`staging` y `production` respectivamente).

Para el entorno de `production`, es necesario configurar el secreto `TRELLIS_DEPLOY_SSH_KNOWN_HOSTS` en el repositorio de GitHub. Este secreto debe contener las claves SSH conocidas del servidor de producción para permitir la conexión segura durante el despliegue.

## 7. Notas de Arquitectura y Frontend

### Reproductor de Vídeo (Bunny.net y React)

- La funcionalidad del reproductor de vídeo no se gestiona con JavaScript simple o directamente en Blade, sino a través de **componentes de React (JSX)**.
- El componente principal que controla el reproductor de vídeo de Bunny.net es `resources/js/components/FeaturedVideo.jsx`.
- Este componente utiliza la librería `@vidstack/react` para renderizar el reproductor.
- **URL del Póster:** La URL correcta para la imagen del póster (miniatura) de un vídeo en Bunny.net no es `poster.jpg`, sino `thumbnail.jpg`. La estructura es: `https://{PULL_ZONE}.b-cdn.net/{VIDEO_ID}/thumbnail.jpg`.