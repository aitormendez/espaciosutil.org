# API móvil 0.5: capítulos de audio verificados

La API mantiene el esquema Media. El audio recibe el subíndice temporal sólo si `_cde_mobile_audio_chapters_verified` coincide con la huella SHA-256 calculada a partir de las identidades/bibliotecas de ambos medios y del subíndice ACF actual. Cambiar el audio, vídeo o capítulos invalida la habilitación. Sin verificación vigente, la lista de capítulos de audio queda vacía.

Staging: desplegado únicamente `site/web/app/mu-plugins/cde-mobile/media.php` en 138.68.135.185, host comprobado `espacio-sutil-staging`. Respaldo en `/srv/www/espaciosutil.org/shared/backups/mobile-0.5.0/media.before.php`. Producción permanece fuera del alcance.

La lección 2875 se ha verificado comparando ocho segundos de PCM mono 4 kHz en cada uno de sus cinco inicios; correlaciones sin desfase entre 0.998226 y 0.999408. Se habilita exclusivamente ese par. El procedimiento reproducible y los resultados están en la rama `codex/app-0-5` del repositorio móvil, `scripts/verify-audio-chapters.py` y `docs/entregas/evidencias-0.5/audio-chapters.json`. No se extrapola a otros pares.

Validación local: `php site/tests/cde-mobile-audio-chapters.php` comprueba ausencia de herencia sin autorización, habilitación del par correcto e invalidación por cambios en audio, vídeo o capítulo (cinco comprobaciones). Verificación viva tras despliegue: cinco capítulos en vídeo y cinco en audio. El cliente trata independientemente los errores de medios y de estudio; los permisos de acceso no se debilitan.

Reversión: restaurar el archivo respaldado y eliminar el metadato `_cde_mobile_audio_chapters_verified` de la lección 2875. No hay cambios de tablas ni migraciones destructivas.
