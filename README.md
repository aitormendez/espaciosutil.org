# Espacio Sutil – Proyecto completo con Roots Stack

Este repositorio contiene la implementación completa del sitio web de **Espacio Sutil**, utilizando el stack [Roots](https://roots.io):

- **Trellis** para provisión de servidores y despliegue automatizado con Ansible
- **Bedrock** como estructura moderna para WordPress
- **Sage 11** como tema personalizado con Blade, Tailwind CSS y Vite

---

## 📁 Estructura del proyecto

```text
espaciosutil.org/
├── trellis/         ← configuración de infraestructura y despliegue
├── site/            ← instalación WordPress con estructura Bedrock
│   └── web/app/themes/sage/ ← tema personalizado Sage 11
```

---

## 🛠 Tecnologías utilizadas

- WordPress (vía Bedrock)
- Sage 11 (Blade, Vite, Tailwind 4.1, Acorn)
- WooCommerce (para la tienda)
- Plugin Members (gestión de acceso)
- Bunny.net + Vidstack (para academia de videos)
- Trellis (Ansible + Nginx + MariaDB + Let's Encrypt)

---

## 📚 Documentación por componentes

| Componente  | Descripción                                                | Enlace                                                                       |
| ----------- | ---------------------------------------------------------- | ---------------------------------------------------------------------------- |
| **Trellis** | Provisión de servidores, entornos y despliegue con Ansible | [`trellis/README.md`](./trellis/README.md)                                   |
| **Sage 11** | Tema personalizado basado en Blade/Vite/Tailwind           | [`site/web/app/themes/sage/README.md`](./site/web/app/themes/sage/README.md) |
| **Bedrock** | Estructura moderna para WordPress                          | [`site/README.md`](./site/README.md) (opcional)                              |

---

## ▶️ Primeros pasos (desarrollo local)

1. Clonar el repositorio
2. Proveer el entorno local con Trellis (Lima, Vagrant o Docker)
3. Instalar dependencias PHP y JS en el tema Sage
4. Compilar los assets

```bash
cd site/web/app/themes/sage
npm install
npm run dev
```

---

## 🔒 Variables sensibles y despliegue

Todas las variables sensibles se gestionan con `ansible-vault` y están documentadas en los archivos `vault.yml`. El despliegue se realizará mediante GitHub Actions (en preparación).

---

## ℹ️ Notas adicionales

- Este proyecto ha descartado Yarn y Plug'n'Play (PnP) en favor de `npm` por compatibilidad con scripts de WordPress.
- Todos los componentes están organizados para que puedan actualizarse o reemplazarse de forma modular.

---

Para más detalles, consulta los `README.md` específicos de cada componente.
