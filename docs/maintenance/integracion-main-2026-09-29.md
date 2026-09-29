# Integración de ramas en main — 2026-09-29

## Alcance

Se integran el backend CDE móvil hasta membresías de tiendas, cuenta nativa, progreso, cuestionarios, estudio y capítulos; el amarillo sol; las dependencias ordinarias de septiembre; WordPress 7.1.2; y la configuración de staging compatible con estos cambios. PMPro permanece en 3.8.6. La integración no ejecuta despliegues, migraciones remotas ni transacciones de pago.

## Ramas y resolución

La publicación usa un commit consolidado sobre `main`: incluye el árbol funcional revisado y excluye la ascendencia local que contiene recibos operativos privados. Las ramas originales y la integración completa quedan conservadas localmente. El README de facturación publica el contrato técnico y las limitaciones, sin los nuevos recibos privados de despliegue. Integración de contenido no implica que todas las referencias locales sean antecesoras de `main`.

- `codex/cde-mobile-membresias` contiene la cadena de backend y versiones 0.2, 0.3.1, 0.4, 0.5 y cuenta nativa. Se incorpora su estado final una sola vez y se conserva su API más reciente.
- `codex/espacio-sutil-staging` incluye una copia anterior de la API: no sustituye los módulos recientes ni las excepciones restringidas de aislamiento. Se incorpora la configuración cifrada pertinente mediante un commit nuevo. Su commit privado de copia de producción no se incluye en la ascendencia pública; se conserva localmente.
- `chore/deps-routine-2026-09`, `chore/esp-1892-wordpress-7-1-2` y `codex/color-sol-amarillo` se integran conservando sus cambios finales.
- `chore/deps-routine-2026-07` y `esp-30-clave-leccion` tienen parches equivalentes ya incorporados, confirmado con `git cherry`. Se conserva el contenido vigente sin retroceder archivos.
- Las ramas de mantenimiento de junio/agosto, WordPress 7.0.3, PMPro, localización, prueba gratuita y `esp-34-clave-leccion-main` ya estaban contenidas en la base o en la cadena integrada.
- La referencia de respaldo y el informe operativo privado de producción se conservan localmente. El resumen publicable de PMPro ya está en `main`.

Se comprueba la igualdad semántica de los cuatro vaults modificados del checkout principal con sus versiones confirmadas y se restaura su cifrado original. La configuración histórica de staging incorporada desde su rama es un cambio distinto y permanece cifrada; no se publican valores en texto claro.

## Ajustes de integración

Staging apunta a `main` y deja de forzar el origen local de bundles. El alias WP-CLI usa el dominio del entorno. `ContentSerie.php` coincide con el nombre de su clase y pasa la generación del autoload optimizado; se elimina el aviso PSR-4 correspondiente.

## Validación del conjunto

- Instalación Composer y validación de ambos proyectos; generación del autoload optimizado del tema.
- Instalación reproducible de paquetes npm y compilación de Sage y de los bloques. Permanecen avisos de tamaño de bundles.
- Lint de los 37 PHP modificados y parseo de los 12 YAML modificados no cifrados.
- Aislamiento de staging/producción, directivas Blade y seguridad del hook de credenciales Composer.
- Pruebas PHP: estudio (6), capítulos (5), cuenta (9), dominio de membresías (27), prueba compartida (24), trial PMPro (16 casos), cinco resultados de recuperación de pedidos y tres imágenes de correo.
- 17 pruebas Python del verificador de tiendas, con dobles de los proveedores.
- Localización sobre los fuentes reales WordPress 7.1.2 y PMPro 3.8.6: 365 claves críticas, placeholders, plurales, hashes y 16 plantillas predeterminadas de correo.
- Instalación WordPress aislada con MySQL 8 y datos exclusivamente sintéticos: 24 comprobaciones correctas de arranque, tema/ACF, membresía, acceso REST premium, cancelación, trial compartido, bloqueo de red/correo y proveedores nativos desactivados. Sin pedidos ni suscripciones de pago creados.

El PHP local utilizado informa 8.5.1, incluso bajo la ruta local denominada `php@8.4`. Aparecen avisos de obsolescencia de las constantes PDO de la configuración del tema, sin fallo de las comprobaciones. No se atribuye esta ejecución a PHP 8.4 ni a los servidores.

## Límites y siguientes despliegues

Los módulos móviles requieren staging o activación explícita. Los proveedores de tienda requieren además configuración privada, esquema instalado y verificador operativo; fusionar no los activa. Las migraciones móviles mantienen sus guardas de staging. Las pruebas locales no acreditan compras reales Apple/Google, renovación remota ni funcionamiento del conjunto en producción. Un futuro despliegue debe validar primero staging, con respaldo y comprobaciones funcionales acordes a los módulos que se activen. Los workflows de despliegue siguen siendo manuales.
