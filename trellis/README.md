# Trellis 

Trellis es un conjunto de playbooks de Ansible que facilita la configuración y gestión de entornos de desarrollo y producción para WordPress, utilizando una arquitectura moderna basada en el stack Roots: Trellis + Bedrock + Sage.

## Estructura del proyecto

Este repositorio forma parte del proyecto Espacio Sutil y se organiza de la siguiente manera:

- `trellis/` 
- `site/` 
- `site/web/app/themes/sage/` 

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

Asegúrate de que los siguientes secretos estén definidos en `Settings 

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

## Solución de problemas

### Error de acceso a MariaDB en Ubuntu 24.04 durante el aprovisionamiento

Al aprovisionar un servidor nuevo con Ubuntu 24.04 LTS, puede surgir el siguiente error durante la tarea `[mariadb : Set root user password]`:

```
failed: [server_ip] (item=localhost) => {"ansible_loop_var": "item", "changed": false, "item": "localhost", "msg": "unable to connect to database, check login_user and login_password are correct or /root/.my.cnf has the credentials. Exception message: (1698, \"Access denied for user 'root'@'localhost'\")"}
```

Este problema ocurre porque la versión de MariaDB incluida en Ubuntu 24.04 utiliza por defecto el plugin de autenticación `unix_socket` para el usuario `root`, en lugar de la autenticación por contraseña. Esto impide que Ansible pueda conectarse para establecer la contraseña.

#### Solución manual

Para solucionarlo, es necesario conectarse al servidor y cambiar el método de autenticación del usuario `root` de MariaDB manualmente.

1.  Conéctate al servidor como usuario `root`:
```bash
trellis ssh -u root <entorno>
```
(Reemplaza `<entorno>` por `staging` o `production`).

2.  Accede a la consola de MariaDB:
```bash
sudo mariadb
```

3.  Ejecuta los siguientes comandos SQL para cambiar el método de autenticación y establecer la contraseña. **Importante:** Reemplaza `vault_mysql_root_password` por la contraseña real almacenada en el vault de Ansible (`group_vars/all/vault.yml`).
```sql
ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('vault_mysql_root_password');
FLUSH PRIVILEGES;
EXIT;
```

4.  Una vez completado, puedes salir de la sesión SSH y volver a ejecutar el comando de aprovisionamiento.
```bash
trellis provision <entorno>
```

## Contacto

Este repositorio es parte del desarrollo del sitio [espaciosutil.org](https://espaciosutil.org/).
