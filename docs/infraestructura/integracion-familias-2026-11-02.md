# Integración segura de familias CDE — 2 de noviembre de 2026

## Base e integración

El checkout compartido partía de `4d0cc3e`, cuarenta commits por detrás de `origin/main` (`6ccf94d`). Antes de integrar se conservó el trabajo local en el commit `aa4d3c5`, rama `backup/trabajo-local-cde-20261102`.

La integración usa como base el main remoto, que ya contenía las prácticas y presentaciones desplegadas en producción. Conserva la configuración de almacenamiento privado compartido, los servicios de presentaciones, el campo ACF privado `cde_private_image`, los inicializadores de prácticas y el endurecimiento PHP de upstream.

## Trabajo local que no debe desplegarse

La variante experimental de presentaciones con adjuntos de Medios nativos permanece en la rama de respaldo. No es compatible directamente con las referencias privadas `sha:page` utilizadas por la versión productiva. No se integra el servicio experimental `Media`, sus importadores, su campo de imagen estándar ni la desactivación del módulo en producción.

Para recuperarla, comparar esa rama con el main integrado y diseñar una migración explícita. No restaurar toda la rama sobre producción ni sobre el checkout de trabajo sin revisar las diferencias.

## Cambios integrados

- Backend de familias, catálogo de cuatro slugs, selector administrativo, resolución heredada y endpoint público `/wp-json/cde/v1/familias`.
- Distintivo de familia y color de las lecciones, iconos personalizados con `currentColor` y fondo CDE si no hay familia válida.
- Mejoras del visor de presentaciones: pantalla completa nativa, controles, foco y accesibilidad, sin cambiar su formato de almacenamiento productivo.
- Aislamiento de integraciones limitado a `WP_ENV=development`, comprobaciones de sincronización y transporte SSH acotado.
- Configuración y documentación de staging y documentos históricos de mantenimiento. Un despliegue normal no ejecuta los playbooks de mantenimiento ni una provisión de staging.
- Exclusión de nuevos dumps SQL y artefactos temporales de Git. Los documentos entregados al usuario, capturas y copias locales se mantienen en disco; no se despliegan.

## Validación y límites

Pruebas aisladas: 58 verificaciones del backend familiar, 33 del frontend, 14 JavaScript, aislamiento inerte en producción, comprobaciones de directivas Blade y redirecciones, sintaxis de scripts y build Vite correctos. Avisos de tamaño de chunks y `use client` de Vidstack no impiden el build.

Esta nota documenta la integración; no acredita por sí sola el despliegue. El endpoint y la revisión desplegada deben comprobarse después de Trellis antes de reactivar ESP-3182. El endpoint devuelve las cuatro familias incluso si aún no se ha ejecutado la semilla del catálogo; `term_id` puede ser `null`. El despliegue no clasifica series ni crea contenidos automáticamente.

La clasificación productiva sigue limitada a series ya existentes y corresponde a ESP-3182. Sigue pendiente comprobar la licencia de los símbolos y completar el crédito Side Project / Noun Project en el Aviso legal vigente antes de hacerlos públicos.
