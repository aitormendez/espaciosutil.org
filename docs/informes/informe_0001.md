# Mejora en el color de fondo de navegación

He hecho una mejora en la navegación para que el color de fondo del sitio sea más coherente y no genere confusión.

## ¿Qué pasaba antes?

Cuando se hacía clic en una opción principal del menú para abrir subopciones, el color de fondo cambiaba al instante. Si finalmente no se entraba en ninguna subpágina, ese color se quedaba igualmente, aunque el usuario siguiera en la página anterior.

## ¿Qué he cambiado?

Ahora el color de fondo se decide por la **sección real** en la que está el usuario, no por un clic puntual.

En la práctica:

- Al abrir una sección del menú, el color puede mostrarse como vista previa.
- Si se cierra el submenú sin entrar en otra página, el fondo vuelve automáticamente al color correcto de la página actual.
- Cuando sí se navega a una nueva página, el color se actualiza de forma definitiva según esa sección.

## Resultado

La experiencia ahora es más clara y estable:

- El color siempre representa dónde está realmente la persona.
- Se evita la sensación de “estar en una página con el color de otra”.
- El comportamiento es consistente durante toda la navegación del sitio.

Si quieres, en el siguiente informe te preparo también una pequeña guía visual con ejemplos de “antes / después”.
