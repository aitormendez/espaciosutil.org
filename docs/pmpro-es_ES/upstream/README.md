# ESP-1932 — Procedencia y cobertura es_ES de PMPro 3.8.6

Fecha: 2026-09-29. Dictamen: catálogo oficial localizado, legalmente reutilizable y compilable, pero insuficiente para cerrar B2. Este paquete es evidencia y base de trabajo; NO es un paquete aprobado para instalar. No se ha activado PMPro ni modificado WordPress, BD, staging o producción.

## Procedencia y versión

- Proyecto oficial: https://translate.strangerstudios.com/projects/paid-memberships-pro/es/default/
- Exportación descargada: https://translate.strangerstudios.com/projects/paid-memberships-pro/es/default/export-translations/?format=po
- Documento del proveedor que remite al servidor: https://www.paidmembershipspro.com/paid-memberships-pro-in-your-language/
- La cabecera declara la misma licencia del paquete PMPro; `readme.txt` de 3.8.6 y la cabecera PHP declaran GPLv2. Se incluye `LICENSE-PMPro.txt` de esa entrega. Procedencia oficial comunitaria, no traducción editorial propia.
- GlotPress identifica el locale como `es`, correspondiente a Spanish (Spain). El archivo para WordPress debe llamarse `paid-memberships-pro-es_ES.mo`. Cabecera: PO-Revision-Date 2026-01-28 13:26:26+0000, GlotPress/4.1.0, nplurals=2; plural=n != 1. No declara compatibilidad contractual con 3.8.6: es una exportación mutable sin número de release. Los hashes fijan la copia examinada.
- Código comparado: entrega local de PMPro 3.8.6, commit `e32b6afb0359df30c78950c008739e27c99046f8`, preparada en ESP-1896 / PR #2. No se cambia ese commit ni el PR del core.
- Extracción nueva con WP-CLI desde el código: 2.997 entradas. Se usa este POT, NO sólo el POT empaquetado, porque tres claves/contenidos difieren en cobertura exacta. La extracción no carga WordPress ni conecta a la BD. WP-CLI consultó metadatos públicos de bloques; mostró un aviso deprecado de su librería Gettext y terminó correctamente.

## Verificación reproducible

Desde `site`, extraer contra el árbol candidato:

```sh
wp i18n make-pot /ruta/paid-memberships-pro /ruta/evidencia/pmpro-3.8.6-extracted.pot --domain=paid-memberships-pro --skip-audit
```

Desde este paquete:

```sh
msgfmt --check --statistics official-es_ES.po -o official-es_ES.mo
php audit.php /ruta/site/web/wp pmpro-3.8.6-extracted.pot
```

Resultado msgfmt: 2.346 mensajes traducidos, 701 sin traducir, sin errores de formato; avisos por Last-Translator y Language-Team ausentes. Importación PO/MO con POMO de WordPress correcta. El auditor compara msgid + contexto, rechaza fuzzy y exige ambas formas plurales. Sobre las 2.997 claves reales de 3.8.6, 2.343 tienen traducción exacta (78,18%); 654 no. La compilación y lectura MO NO prueban carga de dominio en un WordPress arrancado.

| Grupo de referencias del código | Con traducción / total | Sin traducción |
| --- | ---: | ---: |
| Páginas | 114 / 122 | 8 |
| Preheaders | 30 / 30 | 0 |
| Funciones/localización/checkout/email/login comunes | 134 / 155 | 21 |
| Stripe | 145 / 160 | 15 |
| Campos | 12 / 21 | 9 |
| Plantillas/clase de emails | 59 / 168 | 109 |

Son conjuntos por referencias y pueden solaparse; incluyen mensajes administrativos, técnicos y metadatos del editor. No representan por sí solos cadenas visibles por el cliente ni pantallas aprobadas. `coverage.csv` conserva cada clave, contexto, traducción y referencia; `summary.json` guarda las cifras y ejemplos MO. Es el inventario para delimitar la implementación, no una orden de traducir todo el administrador.

Lectura MO comprobada: Membership Account → Cuenta de membresía; Membership Level → Nivel de membresía; Account Information → Información de la cuenta; Username → Nombre de usuario; Password → Contraseña; Submit and Check Out → Enviar y pagar.

Fallback inglés comprobado: `You have already used the discount code provided.`, `This payment does not match the amount due for this checkout.`, `Your membership confirmation for {{ sitename }}`. También faltan claves con contexto de cancelación, actualizar facturación y mensajes de accesibilidad. El viejo catálogo empaquetado 3.6.5 tampoco basta: en el primer contraste con el POT empaquetado 3.8.6 cubrió 227/265 claves comunes/páginas/preheaders frente a 238/265 de GlotPress; estas cifras preliminares NO sustituyen la extracción final anterior.

## Pantallas y fuentes del sitio

| Superficie | Evidencia actual | Criterio pendiente |
| --- | --- | --- |
| Cuenta, membresía, cancelación y facturación | Cobertura exacta listada; hay huecos | Pantalla completa y estados sin membresía/activa/cancelación en español |
| Planes 11/12/13 | Tema Sage personalizado; textos de nivel también en BD | Confirmar nombres, periodicidad, importes y CTA efectivos |
| Trial elegible/consumido | `espaciosutil-pmpro-trials.php` contiene copy español propio, dominio distinto | Probar ambos estados y concordancia de fecha/importe; catálogo PMPro no lo sustituye |
| Checkout y errores Stripe | Preheaders cubiertos; hay mensajes Stripe sin traducción | Preenvío con y sin trial, código repetido/inválido y errores seguros, sin transacciones reales |
| Emails | Tema mantiene header/footer/default/checkout_paid/checkout_paid_admin/invoice/membership_recurring_trial; otros cuerpos son del editor | Render de asunto Y cuerpo, comprobando prioridad real de BD/tema/catálogo; no enviar correos |

El ejemplo `checkout_paid.html` del tema ya contiene cuerpo español y marcadores `!!...!!`, pero no demuestra traducción del asunto Liquid actual ni que no exista un override en BD. No se ha leído la BD. No resetear plantillas para forzar el catálogo: se perderían personalizaciones. Referencias locales: `docs/context/suscripcion-pmpro-y-emails.md`, `docs/pmpro-email-inventory.md`.

## Entrega separada y acotada que se debe implementar

Propietario CTO: conservar la responsabilidad de implementación web asignada en ESP-1894; no hay especialista WordPress en el roster. QA Automation conserva la validación independiente de ESP-1933.

1. Rama/commit propios desde la entrega PMPro, con diff exclusivo de localización; no modificar plugin upstream ni core. Catálogo oficial congelado con hashes + suplemento propio revisable y licencia/atribución. No declarar el paquete 100% traducido.
2. Seleccionar del CSV las cadenas realmente alcanzables en cuenta/facturación/cancelación/planes/trial/checkout y errores Stripe, incluidos contextos y accesibilidad. Excluir paneles administrativos, diagnósticos internos y pasarelas no usadas con justificación. Completar esas claves sin fuzzy; conservar printf, HTML, plural y marcadores Liquid.
3. Asuntos y cuerpos de emails de alta, factura, trial/renovación, fallo de pago y cancelación: preservar prioridades tema/BD. Preparar fallback de asunto y cuerpo cuando haga falta; no reemplazar a ciegas las personalizaciones editoriales. Evidenciar cuáles requieren lectura del entorno aislado.
4. Carga reproducible limitada a dominio `paid-memberships-pro`, locale `es_ES`. PMPro 3.8.6 carga en `init` prioridad 1, primero `WP_LANG_DIR/plugins/paid-memberships-pro-es_ES.mo`, luego directorio del plugin. En Bedrock WP_LANG_DIR suele ser `site/web/app/languages`, pero confirmar constante efectiva. Una copia global es técnicamente posible; el suplemento propio debe sobrevivir a actualizaciones de traducción. Preferir ruta versionada propia con carga acotada, verificando prioridad y cualquier `.l10n.php` preexistente. No introducir cambios globales de gettext ni suprimir actualizaciones globalmente.
5. Prueba pequeña que falle ante claves críticas ausentes, marcadores alterados o formas plurales incompletas; verificar MO y carga aislada. No usar esta prueba como sustituto de pantallas/emails renderizados con WordPress/PHP-FPM.
6. Entregar commit/patch, lista crítica cerrada, evidencia y rollback al chat ejecutor. No crear más tareas de análisis recursivas para la misma implementación.

Impacto: salida textual exclusivamente, sin cambios de pagos, membresías, esquema, core o datos. Riesgos: catálogo mutable, sobreescritura por actualizaciones, contexto/placeholder incorrecto, preferencias de locale del usuario y overrides de email. Reversión del cambio de idioma: retirar cargador y catálogo propios, restaurar archivos previos (incluido `.l10n.php` si existe), invalidar caché pertinente y verificar de nuevo. La reversión del plugin PMPro y su BD es otra operación, regulada por ESP-1931/ESP-1894.

## Puertas y continuidad

B2 sigue abierto. Antes de activar, el chat autorizado debe disponer de la entrega de localización y de la interfaz crítica acreditada en entorno aislado. La ejecución del despliegue permanece exclusivamente en ese chat, con restauración/aislamiento de ESP-1931. ESP-1933 conserva el smoke funcional independiente; no crear una dependencia circular exigiendo que finalice todo ESP-1933 para terminar la preparación de B2. La acreditación estática no autoriza activación ni sustituye el smoke. Si no hay entorno aislado para el último tramo, registrar su bloqueo concreto y devolver al CTO/CEO, sin declarar pantallas verificadas.
