# Trellis – Espacio Sutil

Trellis es un conjunto de playbooks de Ansible que facilita la configuración y gestión de entornos de desarrollo y producción para WordPress, utilizando una arquitectura moderna basada en el stack Roots: Trellis + Bedrock + Sage.

## Estructura del proyecto

Este repositorio forma parte del proyecto Espacio Sutil y se organiza de la siguiente manera:

- `trellis/` → contiene la infraestructura y automatización con Ansible.
- `site/` → contiene el sitio WordPress estructurado con Bedrock.
- `site/web/app/themes/sage/` → contiene el tema personalizado basado en Sage 11.

## Requisitos

- [Trellis CLI](https://github.com/roots/trellis-cli)
- [Ansible](https://docs.ansible.com/)
- Acceso SSH al servidor de staging/producción
- Acceso a GitHub (claves configuradas)

## Flujo de trabajo

### 1. Aprovisionar el servidor

Antes del primer despliegue, asegúrate de haber aprovisionado el servidor. Esto instalará y configurará automáticamente todas las dependencias necesarias (PHP, Nginx, MariaDB, etc.).

```bash
cd trellis
trellis provision staging
```

### 2. Despliegue automático con GitHub Actions

El proyecto está configurado para realizar despliegues automáticos a través de GitHub Actions, usando un Makefile para facilitar su ejecución.

#### Variables de entorno necesarias

Asegúrate de que los siguientes secretos estén definidos en `Settings → Secrets and variables → Actions` en GitHub:

- `TRELLIS_DEPLOY_SSH_PRIVATE_KEY`
- `TRELLIS_DEPLOY_SSH_KNOWN_HOSTS`
- `ANSIBLE_VAULT_PASSWORD`

#### Desplegar a staging

```bash
cd trellis
make deploy-staging
```

#### Desplegar a producción

```bash
cd trellis
make deploy-production
```

El sistema compila los assets en GitHub Actions antes de hacer `rsync` al servidor.

### 3. Sincronización de base de datos

Pendiente de implementar (por ahora, la sincronización se realiza manualmente mediante WP-CLI o herramientas gráficas).

## Contacto

Este repositorio es parte del desarrollo del sitio [espaciosutil.org](https://espaciosutil.org/).
