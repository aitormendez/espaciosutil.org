# Validación de prácticas — 2026-10-01

Implementación candidata en el worktree de la web, rama `codex/cde-practicas`, basada en `325fb19c3b60bd0ddf171566dd56dbd677aa03d8`. No desplegada ni conectada a NocoDB. Los datos de las capturas son ficticios.

## Resultados

- Prueba de integración con WordPress real: 25 comprobaciones y 6 rechazos de conflictos. Comprueba acceso anónimo/sin membresía/con membresía/cancelado, importación como borrador, conservación del texto, repetición sin duplicados, cambios posteriores al plan, identidades duplicadas, entorno incorrecto, contenido activo rechazado, relación muchos a muchos, exclusión de borradores y lecciones inactivas o con contraseña, REST real, búsquedas y oEmbed.
- Compilación Vite correcta. Aviso de tamaño de chunks ya aplicable a las dependencias del tema, sin error de compilación.
- Verificación de directivas Blade y sintaxis PHP de los archivos nuevos.
- Navegador: anónimo ve la puerta de membresía; un miembro de prueba entra en el catálogo, abre la ficha, sigue la lección relacionada y despliega las instrucciones sin abandonar la lección.
- Vista móvil probada a 390 × 844: ancho del documento y de la sección 375 px dentro del viewport de 390 px, sin desbordamiento horizontal en la muestra.

## Entorno y límites

WordPress 7.0.3, ACF PRO 6.8.5, PMPro 3.8.6, PHP 8.5.1 y SQLite Database Integration 3.0.2. Se copiaron los archivos locales instalados a `/private/tmp/cde-practicas-qa/wp`; no se copió `.env` ni se conectó ninguna base compartida. El `composer.lock` de la rama declara una versión de WordPress distinta; la validación en la versión exacta del despliegue y con MySQL queda para staging coordinado.

El fixture `site/tests/fixtures/practicas/sqlite-compat.php` bloquea correo/red y adapta únicamente `CONVERT_TZ`, `STRCMP` y la resolución del alias `GROUP BY ID` para las consultas de PMPro en SQLite. No se modifica PMPro. Esta prueba valida las decisiones de acceso, no cobros, suscripciones de pago ni equivalencia general de SQLite con MySQL. La activación inicial de PMPro encontró incompatibilidades SQL en su migración vacía; la ejecución final de las pruebas pasó sin errores de base de datos una vez aplicado el fixture de compatibilidad.

La copia visual usa Sage y sus vistas reales, assets compilados y proveedores Composer registrados explícitamente en su arranque temporal porque el fixture no usa el bootstrap Bedrock. Sin menús, widgets ni datos reales del sitio. No se modifica por ello el arranque del tema de la rama.

## Repetir la prueba

Preparar exclusivamente un WordPress desechable en `/private/tmp/cde-practicas-qa/wp`, con URL `http://127.0.0.1:8897`, `DB_ENGINE=sqlite`, `WP_ENVIRONMENT_TYPE=local`, cron desactivado y usuario administrador `qa`. Instalar ACF y PMPro copiados del entorno de pruebas, colocar el fixture de compatibilidad en `wp-content/mu-plugins` antes de activarlos, y copiar allí `espaciosutil-cde-practicas.php` y la carpeta `cde-practicas`. La prueba se niega a ejecutarse fuera de ese entorno y no usa el WordPress del proyecto.

Desde el worktree web:

```bash
wp eval-file site/tests/cde-practicas-integration.php --path=/private/tmp/cde-practicas-qa/wp --user=qa
php site/tests/blade-php-directives.php
```

Desde `site/web/app/themes/sage`, con dependencias propias del worktree:

```bash
npm run build
```

## Evidencia visual

- [Ficha en escritorio](practice-desktop.jpg).
- [Ficha en móvil](practice-mobile.jpg).
- [Práctica dentro de una lección, escritorio](lesson-desktop.jpg).
- [Práctica dentro de una lección, móvil](lesson-mobile.jpg).
