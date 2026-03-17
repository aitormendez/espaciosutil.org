# Páginas CDE

## Alcance

Panorámica de las páginas editoriales principales del Curso de Desarrollo Espiritual y de los componentes Blade reutilizables que comparten.

## Páginas principales

- `template-cde-hub.blade.php`
  - Hub general del CDE.
  - Puerta de entrada, orientación y acciones principales.

- `template-suscripcion.blade.php`
  - Landing editorial de membresía.
  - Integra tabla de PMPro, beneficios y acceso a series/bloques.

- `template-curso.blade.php`
  - Índice del curso.
  - Mantiene contenido principal y navegador lateral de series/bloques.

- `template-programa.blade.php`
  - Página “Programa” / “El curso en profundidad”.
  - Construida por bloques parciales editoriales.

## Componentes Blade reutilizables

- `x-cta`
- `x-list-card`
- `x-icon-card`
- `x-timeline-card`
- `x-source-group-card`
- `x-series-blocks-list`
- `x-status-card`

## Datos compartidos

- `App\View\Composers\TemplateCurso` sirve `series_cde_lessons` a:
  - `template-curso`
  - `template-suscripcion`
  - `template-programa`

Esto evita duplicar lógica de taxonomías y conteos entre páginas editoriales del curso.

## Archivos clave

- `site/web/app/themes/sage/resources/views/template-cde-hub.blade.php`
- `site/web/app/themes/sage/resources/views/template-suscripcion.blade.php`
- `site/web/app/themes/sage/resources/views/template-curso.blade.php`
- `site/web/app/themes/sage/resources/views/template-programa.blade.php`
- `site/web/app/themes/sage/app/View/Composers/TemplateCurso.php`
