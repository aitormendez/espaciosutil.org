# Piloto web de presentaciones CDE

> Estado vigente del editor: [edición privada con ACF](../../docs/cde-presentaciones-editor.md). Este documento conserva el procedimiento del piloto inicial; las limitaciones de edición y las posiciones del visor descritas abajo son históricas.

Estado: instalado y validado en WordPress local real (`espaciosutil.test`), también desplegado y validado en staging (release `20261008105351`, commit `8044d9f`). Lección elegida: `cde:01:00:0.1`, «Qué es El libro de Urantia», post 2389 confirmado por HTTPS el 2026-10-08. No ejecutar en producción.

## Conversión local

Requiere Poppler (`pdftoppm`, `pdffonts`, `pdfimages`), Python, Pillow y pypdf. Para pruebas Python también se necesita reportlab. El runtime Python de Codex usado en este piloto dispone de las tres bibliotecas. No instalar herramientas de conversión en el servidor.

```sh
python3 scripts/cde-presentations/convert.py --pdf '/ruta/final/en/Series CDE/Presentación/original.pdf' --lesson-key cde:01:00:0.1 --output /ruta/privada/paquete --evidence /ruta/privada/evidencia
```

La carpeta de salida debe ser nueva. El PDF final no se modifica ni se copia al paquete. La fuente se verifica por SHA-256 antes y después de convertir. El manifiesto y sus variantes WebP son deterministas para el mismo entorno de conversión. La hoja de contacto queda fuera del paquete remoto. Los tamaños son 640, 1280, 1920, 2560 y 3840 para las fuentes con texto; una fuente rasterizada sin fuentes se limita conservadoramente a sus píxeles internos. No se extrae ni publica texto.

Paquete real preparado: `tmp/presentation-pilot/package`, 15 páginas, 75 WebP y manifiesto, 16.401.212 bytes en total. No se versionan medios ni se publican en uploads. Su ruta local completa consta en el informe del piloto.

## Almacenamiento e importación

El mu-plugin solo se carga en `WP_ENV=staging` o `development`. En staging requiere `CDE_PRESENTATIONS_DIR` apuntando a un directorio existente fuera del document root; también permite una constante del mismo nombre para tests. En staging, la configuración candidata de Trellis define `/srv/www/espaciosutil.org/shared/cde-presentations` y el hook de despliegue crea el directorio persistente con permisos 0750. Aplicado al piloto de staging el 2026-10-08. En `development`, si no se configura la variable, usa `site/storage/cde-presentations`, fuera de `site/web`; hay que crear esa carpeta antes de importar. La ruta se resuelve desde `WP_CONTENT_DIR`, por lo que funciona tanto desde el host macOS como desde el montaje en Lima sin rutas absolutas del otro sistema.

Una vez desplegada la rama mediante el procedimiento del proyecto y transferido exclusivamente el contenido del paquete a una carpeta privada de entrada, ejecutar WP-CLI desde el host en `site`:

```sh
wp @staging cde presentation import /ruta/privada/de/entrada --lesson-key=cde:01:00:0.1 --expected-revision=none
wp @staging cde presentation import /ruta/privada/de/entrada --lesson-key=cde:01:00:0.1 --expected-revision=none --apply --activate
```

No asumir que el puntero está vacío: leer antes `_cde_presentation_revision` y `lesson_presentation_enabled`, guardar sus valores y sustituir `none` por la revisión realmente observada cuando corresponda. La importación valida clave única, todos los hashes, MIME, dimensiones, esquema, lista exacta de archivos y ausencia de enlaces. Bloquea por lección y relee la revisión antes de instalar. Una importación fallida no activa el paquete; un directorio `.incoming-*` interrumpido puede requerir limpieza posterior supervisada. Repetir con el SHA activo como revisión esperada no duplica archivos.

Para una sustitución, conservar la revisión previa. El rollback vuelve a importar su directorio inmutable con `--expected-revision=SHA_ACTUAL --apply --activate`. Si antes no había presentación, desactivar `lesson_presentation_enabled` y restaurar el metadato previo desde el snapshot. No borrar revisiones como parte del rollback.

## Entrega y visor

El endpoint web es `wp-admin/admin-post.php?action=cde_presentation&lesson=ID&kind=manifest|image`. Autoriza cada GET/HEAD con la política existente `Access::lesson(..., false)` y devuelve `private, no-store`. Una URL copiada no concede acceso. `preview=1` exige capacidad editorial `edit_post`. Una revisión sustituida devuelve 409; un paquete roto, 503. La biblioteca multimedia pública no interviene.

Para este piloto se transmite el WebP verificado mediante PHP. Esto evita modificar Nginx/las reglas Tailscale en paralelo y mantiene el almacén fuera del directorio público. X-Accel-Redirect queda como optimización posterior, pendiente de prueba de carga; este piloto no afirma su implementación. La API Bearer para apps y la automatización de Contenidos también quedan fuera de esta etapa.

Sage añade el visor detrás de la autorización existente de la lección, después del reproductor. Incluye flechas, contador, controles de teclado, diálogo nativo, zoom y pan de Swiper, variantes responsive, carga de la diapositiva actual y vecinas, error/reintento y destrucción antes de salir con Barba. El grupo ACF privado permite mostrar/ocultar y consultar miniaturas y revisión. No permite editar orden ni subir PDF.

## Pruebas

```sh
python3 scripts/cde-presentations/test_package.py
php scripts/cde-presentations/test_import.php tmp/presentation-pilot/package
```

`test_http.py` requiere el servidor local `qa-router.php` en `127.0.0.1:8877`, con `CDE_QA_PACKAGE` y `CDE_QA_STORE` definidos; el almacén debe contener `2389/<revision>/manifest.json` y sus imágenes. Para comprobar visualmente el mismo Blade y JS, arrancar además Vite en `127.0.0.1:5177`. El harness usa cookies `qa_mode=member|denied|editor`, exclusivamente simuladas. Nunca se despliega este harness: usa primitivas WordPress simuladas para ejecutar el controlador y Access reales. Las pruebas no sustituyen la validación de la sesión y la membresía reales de staging.

Validación local real completada: importación idempotente, membresía permitida y denegada, archivos privados, ACF con 15 miniaturas, navegación de salida y retorno, zoom y diseño responsive. Evidencias en `docs/evidencias/presentaciones-2026-10-08/wordpress-local.md`. Pendientes: comprobación integral de vídeo/quiz (la copia local tiene un fallo previo de metadata Bunny) y gesto táctil en dispositivo físico. No modificar permisos de membresía para facilitar el piloto.

## Ejecutar el piloto en Lima

Desde el host, con directorio de trabajo `site`, omitir `@staging` en los comandos de importación. La lección local 2389 ya está activada y su revisión es `d831299f008ae8d911eceec756eb8399c570f6b878e52d1efa887712f70159f3`; usar ese valor como `--expected-revision` en una repetición. No volver a usar `none`. Abrir http://espaciosutil.test/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/ con una cuenta que tenga acceso a la lección.
