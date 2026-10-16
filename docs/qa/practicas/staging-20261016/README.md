# QA de staging — 2026-10-16

Validación de la entrega conjunta de prácticas y presentaciones sobre el release `20261016080930`, código `f1a816f2e0e5366acc82df9793d5bb8e4f61f6ff`. [Informe del despliegue](../../../infraestructura/staging-practicas-presentaciones-2026-10-16.md).

- [Runtime y campos ACF](runtime-results.json).
- [Matriz HTTP de acceso](http-results.json) e [imagen privada](image-results.json).
- [Huellas de código y manifest servido](source-hashes.txt).
- [Índice, alineación y pantalla completa](cde-practicas-lesson-browser.json).
- [Presentación, audio y fronteras de sección](combined-browser.json).
- [Búsqueda, serie heredada y ficha individual](catalogue-browser.json).
- [Combinaciones de bloques opcionales](lesson-separators-browser.json).
- [Práctica desplegada](staging-practica.png) y [presentación desplegada](staging-presentacion.png).

El recorrido de visores y las capturas se obtuvieron en la primera release conjunta `20261016075345`; sus archivos de frontend no cambiaron en la corrección posterior del filtro. Runtime, huellas y filtro del catálogo se verificaron de nuevo en la release final.

La captura y el recorrido usan sesiones temporales de cuentas existentes; no guardan la lección ni cambian relaciones o membresías. La advertencia inicial de progreso se resolvió instalando el esquema auxiliar ya existente, descrito en el informe. La recarga posterior mostró cero errores de consola. Las pruebas del editor verificaron 15 imágenes privadas y la selección de práctica dentro de las pestañas de Lección CDE, sin guardar cambios.
