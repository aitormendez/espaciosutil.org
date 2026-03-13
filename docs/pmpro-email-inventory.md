# Inventario de emails PMPro

Este proyecto usa una estrategia mixta:

- Plantillas clave controladas en codigo, dentro del tema.
- Plantillas secundarias controladas desde el editor de PMPro.

## Plantillas controladas en codigo

Estos slugs deben mantenerse sin `body` guardado en PMPro para que el override del tema siga teniendo prioridad visual y funcional.

- `default`
- `header`
- `footer`
- `checkout_paid`
- `checkout_paid_admin`
- `invoice`
- `membership_recurring_trial`

Archivos:

- `site/web/app/themes/sage/paid-memberships-pro/email/default.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/header.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/footer.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/checkout_paid.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/checkout_paid_admin.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/invoice.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/membership_recurring_trial.html`

## Plantillas controladas en el editor de PMPro

Estos slugs ya no tienen override en el tema. Su contenido debe gestionarse desde:

`WP Admin > Memberships > Settings > Email Templates`

- `membership_recurring`
- `billing`
- `billing_admin`
- `billing_failure`
- `billing_failure_admin`
- `payment_action`
- `payment_action_admin`
- `membership_expiring`
- `membership_expired`
- `cancel`
- `cancel_admin`
- `cancel_on_next_payment_date`
- `cancel_on_next_payment_date_admin`
- `refund`
- `refund_admin`
- `admin_change`
- `admin_change_admin`
- `credit_card_expiring`
- `checkout_free`
- `checkout_free_admin`
- `checkout_check`
- `checkout_check_admin`

## Regla operativa

- Si una plantilla se gestiona en codigo, no guardar su `body` en PMPro.
- Si una plantilla se gestiona en el editor, no crear override equivalente en el tema.
- `membership_recurring_trial` es una plantilla custom del proyecto y debe seguir en codigo porque depende de la logica del trial.
