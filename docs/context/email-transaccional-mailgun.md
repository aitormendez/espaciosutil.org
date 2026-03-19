# Email transaccional con Mailgun

Documento operativo para la configuración y mantenimiento del correo transaccional del sitio.

## Alcance

- Envío SMTP saliente desde WordPress/PMPro en `production`.
- Remitente funcional unificado para emails transaccionales.
- Configuración en Trellis + override en WordPress.

## Estado actual

Implementación cerrada el **19 de marzo de 2026**.

- Proveedor: **Mailgun EU**
- Dominio de envío: `espaciosutil.org`
- Host SMTP: `smtp.eu.mailgun.org`
- Puerto operativo desde producción: `2525`
- Usuario SMTP dedicado: `wordpress@espaciosutil.org`
- Remitente visible: `Espacio Sutil <admin@espaciosutil.org>`

## Motivo de la implementación

El correo transaccional fallaba en producción después de salir a vivo. La auditoría mostró dos problemas:

1. Trellis seguía aprovisionando `ssmtp` con placeholders (`smtp.example.com`, `smtp_user`, `smtp_password`).
2. PMPro estaba resolviendo el remitente como `wordpress@espaciosutil.test`.

Además, desde el droplet de producción los puertos `587` y `465` hacia Mailgun no respondían en la prueba directa, mientras que `2525` sí funcionó. Por eso la configuración final usa `2525`.

## Configuración en Trellis

### SMTP del servidor

Archivo: `trellis/group_vars/all/mail.yml`

- `mail_smtp_server: smtp.eu.mailgun.org:2525`
- `mail_admin: admin@espaciosutil.org`
- `mail_hostname: espaciosutil.org`
- `mail_user: wordpress@espaciosutil.org`
- `mail_password: "{{ vault_mail_password }}"`

### Secreto SMTP

Archivo: `trellis/group_vars/all/vault.yml`

- `vault_mail_password` contiene la contraseña SMTP de Mailgun.
- El secreto debe permanecer siempre cifrado con Ansible Vault.
- No documentar nunca la contraseña en texto plano.

### Variables de entorno WordPress

Archivos:

- `trellis/group_vars/production/wordpress_sites.yml`
- `trellis/group_vars/staging/wordpress_sites.yml`

Variables añadidas:

- `transactional_email_from: admin@espaciosutil.org`
- `transactional_email_name: Espacio Sutil`

Esto hace que el `.env` desplegado exponga:

- `TRANSACTIONAL_EMAIL_FROM`
- `TRANSACTIONAL_EMAIL_NAME`

## Configuración en WordPress

Archivo: `site/web/app/mu-plugins/espaciosutil-transactional-email.php`

Responsabilidades:

- Forzar `wp_mail_from` con `TRANSACTIONAL_EMAIL_FROM`
- Forzar `wp_mail_from_name` con `TRANSACTIONAL_EMAIL_NAME`
- Forzar `pre_option_pmpro_from_email`
- Forzar `pre_option_pmpro_from_name`

Con esto:

- los correos enviados por `wp_mail()` salen con el remitente correcto;
- PMPro deja de depender del valor antiguo guardado en base de datos;
- el contenido que usa `get_option('pmpro_from_email')` también queda alineado.

## Validación realizada

Validación ejecutada el **19 de marzo de 2026** en producción:

- `wp_mail()` devolvió `true`.
- Mailgun registró los eventos **Accepted** y **Delivered** para `admin@espaciosutil.org`.
- El remitente visto por Mailgun quedó como `Espacio Sutil <admin@espaciosutil.org>`.

## Procedimiento de aplicación

### Si solo cambia la credencial o el puerto SMTP

1. Actualizar `trellis/group_vars/all/mail.yml`.
2. Actualizar `vault_mail_password` en `trellis/group_vars/all/vault.yml`.
3. Ejecutar:

```bash
cd trellis
ansible-playbook server.yml -e env=production --tags mail
```

### Si cambia el remitente visible

1. Actualizar:
   - `trellis/group_vars/production/wordpress_sites.yml`
   - `trellis/group_vars/staging/wordpress_sites.yml`
2. Verificar el mu-plugin `site/web/app/mu-plugins/espaciosutil-transactional-email.php`.
3. Desplegar:

```bash
cd trellis
ansible-playbook deploy.yml -e env=production -e site=espaciosutil.org
```

## Comprobaciones útiles

### Ver remitente resuelto por WordPress

```bash
cd /srv/www/espaciosutil.org/current
sudo -u web wp eval --path=web/wp "echo apply_filters('wp_mail_from', 'fallback@example.com') . PHP_EOL; echo apply_filters('wp_mail_from_name', 'Fallback') . PHP_EOL;"
```

### Enviar prueba desde WordPress

```bash
cd /srv/www/espaciosutil.org/current
sudo -u web wp eval --path=web/wp "var_export(wp_mail('admin@espaciosutil.org', 'Prueba transaccional Mailgun', 'Correo de prueba.'));"
```

### Revisar eventos en Mailgun

- Abrir `Reporting > Logs`
- Filtrar por destinatario `admin@espaciosutil.org`
- Verificar al menos `Accepted` y `Delivered`

## Riesgos y notas operativas

- **No volver a `587` ni `465` en producción** sin revalidar conectividad desde el droplet.
- Si el mu-plugin no está en `origin/main`, un despliegue futuro puede sobrescribir el hotfix del release actual.
- La referencia correcta para tareas futuras sobre este tema es este documento, no el contexto general de suscripción.
