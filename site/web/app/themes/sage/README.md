# Tema Sage 11 para Espacio Sutil

Este es el tema personalizado para el sitio web de **Espacio Sutil**, basado en [Sage 11](https://roots.io/sage/), con las siguientes tecnologías integradas:

- 🎨 [Tailwind CSS 4.1](https://tailwindcss.com) para estilos modernos y utilitarios
- ⚡️ [Vite](https://vitejs.dev) para desarrollo front-end con recarga instantánea
- 🧠 [Laravel Blade](https://laravel.com/docs/10.x/blade) como sistema de plantillas
- 🌱 [Acorn](https://github.com/roots/acorn) para acceso a herramientas de Laravel en WordPress
- 🧩 [Acorn Post Types](https://github.com/roots/acorn-post-types) para registrar post types y taxonomías desde configuración

## Requisitos

- Node.js >= 18
- PHP >= 8.1
- Composer
- npm (⚠️ no se utiliza Yarn en este proyecto)

> **Importante**: Este proyecto **no utiliza Yarn** debido a problemas de compatibilidad con scripts del ecosistema WordPress. Se ha eliminado `.yarn/`, `.pnp.*` y `yarn.lock`.

## Instalación

1. Clona el repositorio del proyecto (o navega al directorio del tema dentro del stack Bedrock).

2. Ejecuta la instalación de dependencias:

   ```bash
   npm install
   composer install
   ```

3. Compila los assets para producción:

   ```bash
   npm run build
   ```

   O para desarrollo:

   ```bash
   npm run dev
   ```

## Estructura del tema

- `resources/` – Archivos Blade, CSS, JS, fuentes y plantillas
- `public/` – Salida compilada de Vite
- `app/` – Configuración Acorn, Service Providers y lógica del tema
- `composer.json` – Dependencias PHP
- `package.json` – Scripts y dependencias JS
- `vite.config.js` – Configuración de Vite
- `theme.json` – Configuración de estilos y bloques para el editor

## Funcionalidades Clave

### Contenido del Curso (CPT CDE)

El tema implementa un Custom Post Type (CPT) `cde` para gestionar el contenido del "Curso de Desarrollo Espiritual".

- **Índice Jerárquico Automatizado:** Las páginas del curso utilizan un índice jerárquico que se genera automáticamente, facilitando la navegación y estructuración del contenido.
- **Extracto Enriquecido:** Cada entrada del curso puede tener un extracto enriquecido (WYSIWYG) gestionado a través de un campo de [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/). Este extracto es visible para todos los usuarios.
- **Contenido Restringido:** El contenido principal de las entradas del curso está dividido en dos partes: un extracto público y el contenido completo, que es accesible únicamente para usuarios con una membresía activa.

### Membresías y Navegación (Paid Memberships Pro)

El tema integra el sistema de membresías [Paid Memberships Pro (PMP)](https://www.paidmembershipspro.com/) para gestionar el acceso a contenido restringido.

- **Navegación Condicional:** La navegación específica de PMP (tanto para escritorio como para móvil) se muestra de forma condicional, apareciendo únicamente en las páginas relevantes de membresía (ej. Cuenta, Facturación, Cancelación, Pedidos, Perfil, Pago).
- La navegación de las secciones privadas se gestiona mediante la librería [Log1x/Navi](https://github.com/Log1x/Navi), que permite administrar los menús desde el editor de WordPress.
- Los menús de membresía se renderizan con condicionales según el estado de la cuenta (sesión iniciada, membresía activa) mediante un único componente de Blade, compartido por las versiones de escritorio y móvil, con clases unificadas de Tailwind CSS.

## Desarrollo y Configuración

- Este tema es parte del stack Roots (Trellis + Bedrock + Sage).
- El despliegue se realizará mediante GitHub Actions (configuración pendiente).
- Se recomienda mantener el uso de `npm` para garantizar compatibilidad con el editor de bloques y herramientas nativas de WordPress.
- **Gestión de Campos ACF con ACF Composer:** Las definiciones de los campos de Advanced Custom Fields (ACF) se gestionan directamente en el código del tema, ubicadas en `app/Fields/`, utilizando la librería [Log1x/AcfComposer](https://github.com/Log1x/AcfComposer). Esto permite un control de versiones y una gestión más robusta de los campos personalizados.
- Se ha implementado desde cero una clase `prose` en `resources/css/common/typography.css`, basada en unidades relativas (`em`) para permitir un escalado proporcional de la tipografía mediante clases como `prose-xl` y `prose-2xl`. Este enfoque replica el comportamiento del plugin `@tailwindcss/typography`, pero sin depender de él, ya que dicho plugin requiere `tailwind.config.js` o `.cjs`, archivos no utilizados en Tailwind 4.1. También se ha definido la clase `not-prose` para eliminar todos los estilos enriquecidos mediante `all: unset` y `display: revert`.

## Créditos

## Bloque de video con Bunny.net

El tema utiliza un plugin personalizado llamado `espacio-sutil-blocks` para añadir bloques reutilizables, entre ellos un bloque de video integrado con Bunny.net.

- El bloque permite insertar un video de una Video Library de Bunny.net mediante su `libraryId` y `videoId`.
- Se utiliza la API de Bunny.net para obtener dinámicamente las resoluciones disponibles y la miniatura (`thumbnailUrl`) mediante un endpoint personalizado de la REST API de WordPress.
- Los datos de autenticación (clave API y pull zone) se configuran en el archivo `.env`, gestionado desde los `vault.yml` de Trellis:
  - `BUNNY_KEY`
  - `BUNNY_PULL_ZONE`
- El plugin se compila automáticamente durante el despliegue, gracias a un hook definido en `trellis/deploy-hooks/build-before.yml`.

> Este bloque ha sido migrado y adaptado desde un proyecto previo, y validado tanto en el editor como en el frontend.
