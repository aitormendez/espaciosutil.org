# Propuesta de estructura legal y gestion de cookies

Fecha: 2026-03-14  
Estado: recomendacion operativa  
Alcance: Espacio Sutil + Curso de Desarrollo Espiritual

Nota:
- Este documento deja una propuesta lista para implementar y publicar.
- No sustituye una revision juridica final.
- Los textos estan adaptados al stack y al modelo comercial reales del proyecto, pero hay 3 datos que conviene confirmar antes de publicarlos: email de contacto legal, datos de Registro Mercantil y listado definitivo de proveedores.

## 1. Hallazgos del proyecto

### Hallazgos tecnicos

- El plugin `gdpr-cookie-compliance` esta instalado por Composer, pero hoy figura como `inactive`.
- El sitio no tiene una estructura legal publicada adaptada al curso; solo existe un borrador generico de `Privacy Policy` de WordPress.
- El sitio usa WordPress + PMPro + Stripe para vender una suscripcion digital recurrente.
- La infraestructura ya contempla Matomo autohospedado en `matomo.espaciosutil.org`.
- El formulario de contacto actual no incluye casilla de aceptacion de privacidad.
- PMPro no tiene configurada una pagina de terminos para el checkout.

### Hallazgos de negocio y producto

- La suscripcion se comercializa como una sola membresia con tres frecuencias tecnicas:
  - mensual: 5 EUR,
  - semestral: 25 EUR,
  - anual: 45 EUR.
- El plan operativo del proyecto ya da por validado:
  - IVA incluido,
  - prueba gratuita de 7 dias,
  - cancelacion al final del periodo ya pagado.
- El curso da acceso inmediato a contenido digital y area privada.

### Datos juridicos encontrados en el proyecto

Titular identificado en las plantillas de email:

- Libranzai, SL
- CIF: B84372218
- Cuesta de San Vicente, 12
- 28008 Madrid (Madrid)
- Espana

Pendientes de confirmar antes de publicar:

- email legal o de privacidad,
- datos de inscripcion registral,
- proveedores externos definitivos que deban nombrarse expresamente.

## 2. Recomendacion

## Decision sobre cookies

Recomiendo una implementacion propia y ligera dentro del tema, y no volver al plugin.

Motivo:

- el sitio ya tiene un punto de entrada global en `resources/js/app.js`,
- el layout permite insertar un banner ligero sin acoplarse a un plugin,
- solo hay una categoria opcional clara ahora mismo: analitica,
- el stack actual no justifica el peso, la interfaz ni la dependencia continua de un plugin de consentimiento.

### Mi recomendacion concreta

Implementar un sistema propio con este alcance:

- categoria `tecnicas` siempre activa,
- categoria `analitica` desactivada por defecto,
- banner inicial con `Aceptar`, `Rechazar` y `Configurar`,
- panel de preferencias accesible en todo momento desde footer,
- Matomo bloqueado hasta consentimiento,
- almacenamiento de la eleccion en un identificador first-party de consentimiento,
- renovacion de la eleccion antes de 24 meses; operativamente recomiendo 12 meses.

### Alternativa posible, pero no recomendada como primera opcion

Seria posible intentar encajar Matomo en un esquema de medicion exenta de consentimiento, con configuracion muy estricta y sin cookies de analitica. No la recomiendo como via principal por tres razones:

- vuestro plan de tracking quiere medir embudo, checkout y conversion,
- el proyecto probablemente evolucionara y es facil salir del marco exento sin darse cuenta,
- juridicamente es mas robusto y facil de defender un opt-in explicito para analitica.

Conclusion:

- si, recomiendo implementacion propia;
- pero no una implementacion "minimalista sin control", sino un consentimiento ligero y conservador.

## Estructura legal recomendada

Para este sitio no me quedaria en tres paginas. Recomiendo esta estructura:

1. `Aviso legal`
2. `Politica de privacidad`
3. `Politica de cookies`
4. `Condiciones de contratacion y suscripcion`

Y ademas, fuera de esas paginas:

- casilla de privacidad en el formulario de contacto,
- casilla de aceptacion de condiciones en checkout,
- casilla expresa para inicio inmediato del contenido digital y perdida del desistimiento cuando proceda,
- enlace persistente a `Configurar cookies` en footer.

## 3. Estructura final propuesta

## 3.1. Aviso legal

Objetivo:
- cumplir art. 10 LSSI,
- identificar al titular del sitio,
- regular uso general de la web.

Contenido minimo:
- titular del sitio,
- CIF,
- domicilio,
- email o canal de contacto,
- datos registrales si existen,
- condiciones de uso,
- propiedad intelectual,
- enlaces externos,
- limitacion de responsabilidad,
- ley aplicable y jurisdiccion.

## 3.2. Politica de privacidad

Objetivo:
- informar del tratamiento de datos personales en contacto, cuenta, membresia y uso del curso.

Contenido minimo:
- responsable,
- finalidades,
- bases juridicas,
- categorias de datos,
- destinatarios,
- transferencias,
- plazos de conservacion,
- derechos,
- medidas basicas de seguridad,
- informacion especifica sobre progreso de lecciones, quiz y datos de cuenta.

## 3.3. Politica de cookies

Objetivo:
- explicar que tecnologias usa el sitio,
- distinguir tecnicas y analiticas,
- documentar el panel de preferencias,
- listar o agrupar cookies reales.

Contenido minimo:
- definicion de cookies y tecnologias similares,
- tipos usados,
- base juridica,
- como aceptar/rechazar/configurar,
- tabla de cookies o familias de cookies,
- vigencia del consentimiento,
- forma de retirarlo.

## 3.4. Condiciones de contratacion y suscripcion

Objetivo:
- cubrir la venta del acceso digital recurrente al curso.

Contenido minimo:
- descripcion del servicio,
- precios y fiscalidad,
- prueba gratuita,
- renovacion automatica,
- cancelacion,
- acceso y cuenta,
- medios de pago,
- no cesion de credenciales,
- disponibilidad y cambios del servicio,
- propiedad intelectual,
- desistimiento de contenido digital,
- reembolsos,
- soporte e incidencias.

## 4. Textos propuestos

## 4.1. Aviso legal

Titulo sugerido:

`Aviso legal`

Texto propuesto:

> En cumplimiento de lo dispuesto en la Ley 34/2002, de 11 de julio, de servicios de la sociedad de la informacion y de comercio electronico, se informa a los usuarios de este sitio web de los siguientes datos de identificacion:
>
> Titular: Libranzai, SL  
> CIF: B84372218  
> Domicilio social: Cuesta de San Vicente, 12, 28008 Madrid (Madrid), Espana  
> Sitio web: https://espaciosutil.org  
> Email de contacto: [CONFIRMAR EMAIL LEGAL]
>
> En caso de estar inscrita en el Registro Mercantil, se hara constar aqui la informacion registral completa: [CONFIRMAR DATOS REGISTRALES].
>
> La aplicacion web `Espacio Sutil Publisher`, accesible actualmente en https://tiktok.espaciosutil.org/tiktok, forma parte de los servicios digitales operados por Libranzai, SL para la gestion y publicacion asistida de contenidos editoriales en plataformas de terceros, incluyendo TikTok. Salvo que se indique expresamente otra cosa, las presentes condiciones de uso y el resto de textos legales del sitio resultan tambien aplicables a dicha aplicacion en lo que corresponda por su naturaleza y funcionalidad.
>
> El acceso y uso de este sitio web atribuye la condicion de usuario e implica la aceptacion de las presentes condiciones de uso. El usuario se compromete a utilizar la web, sus contenidos y sus servicios de forma licita, diligente y conforme a la buena fe, al orden publico y a la normativa aplicable.
>
> Queda prohibido utilizar el sitio web con fines ilicitos, lesivos de derechos o intereses de terceros, o que puedan danar, inutilizar, sobrecargar o deteriorar el normal funcionamiento del sitio, sus servicios o sus contenidos.
>
> Los contenidos de esta web, incluyendo textos, imagenes, diseno, estructura, logotipos, marcas, codigo y demas elementos, son titularidad de Libranzai, SL o se utilizan con la debida autorizacion, y se encuentran protegidos por la normativa sobre propiedad intelectual e industrial. Queda prohibida su reproduccion, distribucion, comunicacion publica, transformacion o cualquier otro uso no autorizado expresamente por escrito.
>
> El titular podra enlazar a paginas o servicios de terceros unicamente con finalidad informativa o funcional. Libranzai, SL no se responsabiliza del contenido, disponibilidad, politicas o practicas de sitios externos ajenos a su control.
>
> Libranzai, SL se reserva el derecho a modificar, actualizar o retirar, en cualquier momento y sin necesidad de previo aviso, contenidos, servicios, diseno, estructura o condiciones de uso de la web.
>
> La informacion y contenidos ofrecidos en el area divulgativa y formativa del sitio tienen caracter informativo y educativo. Salvo indicacion expresa en contrario, no constituyen asesoramiento medico, psicologico, psiquiatrico o sanitario, ni sustituyen la atencion profesional individualizada que pudiera resultar necesaria en cada caso.
>
> Para cualquier controversia relacionada con este sitio web sera de aplicacion la legislacion espanola. Siempre que la normativa de consumo no disponga otra cosa, las partes se someteran a los juzgados y tribunales del domicilio del consumidor o usuario, y en su defecto a los de Madrid.

## 4.2. Politica de privacidad

Titulo sugerido:

`Politica de privacidad`

Texto propuesto:

> En esta politica de privacidad te explicamos como tratamos tus datos personales cuando navegas por este sitio web, contactas con Espacio Sutil, creas una cuenta, contratas una suscripcion, utilizas el area privada del Curso de Desarrollo Espiritual o interactuas con la aplicacion web `Espacio Sutil Publisher` para conectar una cuenta de TikTok y gestionar publicaciones asistidas.
>
> ### 1. Responsable del tratamiento
>
> Responsable: Libranzai, SL  
> CIF: B84372218  
> Domicilio: Cuesta de San Vicente, 12, 28008 Madrid (Madrid), Espana  
> Email de contacto para privacidad: [CONFIRMAR EMAIL LEGAL]
>
> ### 2. Que datos tratamos
>
> Podemos tratar las siguientes categorias de datos:
>
> - datos identificativos y de contacto, como nombre y correo electronico;
> - datos de cuenta, acceso y membresia;
> - datos de facturacion y estado de la suscripcion;
> - informacion aportada por el usuario en formularios o comunicaciones;
> - datos de uso del area privada, como progreso de lecciones, resultados de quiz y acciones necesarias para prestar el servicio;
> - datos tecnicos y operativos asociados al uso de `Espacio Sutil Publisher`, como identificadores de cuenta de TikTok, datos basicos de perfil autorizados por el usuario, tokens de acceso o renovacion, configuracion de privacidad de la publicacion y estados tecnicos de envio o publicacion;
> - datos tecnicos y de seguridad derivados de la navegacion;
> - datos de analitica web, solo cuando el usuario lo autorice.
>
> ### 3. Finalidades del tratamiento
>
> Tratamos tus datos para:
>
> - atender solicitudes enviadas a traves del formulario de contacto o por correo;
> - crear y gestionar tu cuenta de usuario;
> - gestionar la contratacion, pago, renovacion, cancelacion y soporte de la suscripcion;
> - permitir el acceso al contenido del curso y a sus funciones asociadas;
> - mantener un registro funcional del progreso del alumno y de determinadas interacciones necesarias para la prestacion del servicio;
> - permitir la conexion autorizada de una cuenta de TikTok con `Espacio Sutil Publisher`, mostrar la cuenta conectada, guardar de forma segura los tokens necesarios para mantener la autorizacion y ejecutar publicaciones asistidas en nombre del usuario cuando este las confirme expresamente;
> - enviar comunicaciones de servicio relacionadas con la cuenta, los pagos, la membresia o incidencias;
> - cumplir obligaciones legales, contables, fiscales y de seguridad;
> - obtener estadisticas de uso y mejorar el sitio, unicamente si has consentido la analitica.
>
> ### 4. Base juridica
>
> Las bases juridicas del tratamiento son:
>
> - el consentimiento del usuario, para consultas, formularios y analitica opcional;
> - la ejecucion de un contrato o la aplicacion de medidas precontractuales, para la gestion de la suscripcion y del acceso al curso;
> - el cumplimiento de obligaciones legales, para facturacion, contabilidad, fiscalidad, prevencion del fraude y atencion de derechos;
> - el interes legitimo del responsable, en lo estrictamente necesario para seguridad, continuidad tecnica y defensa ante reclamaciones.
>
> ### 5. Destinatarios
>
> Tus datos podran comunicarse o ponerse a disposicion de proveedores que intervienen en la prestacion del servicio, por ejemplo:
>
> - proveedores de alojamiento, mantenimiento tecnico, correo y seguridad;
> - Stripe, como proveedor de pagos y gestion de cobros recurrentes;
> - proveedores estrictamente necesarios para la entrega tecnica de contenidos digitales, como servicios de streaming o CDN;
> - TikTok y sus servicios para desarrolladores, cuando el usuario utilice `Espacio Sutil Publisher` para autorizar una cuenta o publicar contenido mediante dicha plataforma;
> - autoridades u organismos publicos cuando exista obligacion legal.
>
> Cuando la analitica este activada, los datos se trataran conforme a la configuracion de Matomo y a lo indicado en la politica de cookies.
>
> ### 6. Conservacion
>
> Conservaremos los datos durante el tiempo necesario para cada finalidad:
>
> - datos de contacto: durante el tiempo necesario para atender la solicitud y, despues, mientras puedan derivarse responsabilidades;
> - datos de cuenta y membresia: mientras la cuenta permanezca activa y durante los plazos legales posteriores;
> - datos de facturacion y pagos: durante los plazos exigidos por la normativa fiscal y contable;
> - datos funcionales del curso, como progreso y quiz: mientras exista la cuenta o hasta que el usuario solicite su supresion, salvo que deban mantenerse por obligacion legal o para defensa ante reclamaciones;
> - datos de analitica: segun los plazos indicados en la politica de cookies.
>
> ### 7. Derechos
>
> Puedes ejercer tus derechos de acceso, rectificacion, supresion, oposicion, limitacion y portabilidad, asi como retirar en cualquier momento los consentimientos otorgados, enviando una solicitud a [CONFIRMAR EMAIL LEGAL] e identificandote adecuadamente.
>
> Si consideras que tus derechos no han sido atendidos correctamente, puedes presentar una reclamacion ante la Agencia Espanola de Proteccion de Datos: https://www.aepd.es
>
> ### 8. Menores
>
> Los servicios de suscripcion y el area privada del curso no estan dirigidos a menores de edad sin la intervencion de sus padres, madres o representantes legales. Si detectamos datos de un menor obtenidos sin autorizacion valida, podremos eliminarlos.
>
> ### 9. Seguridad
>
> Libranzai, SL aplica medidas tecnicas y organizativas razonables para proteger los datos personales y reducir el riesgo de acceso no autorizado, perdida, alteracion o tratamiento indebido.
>
> ### 10. Cambios en esta politica
>
> Esta politica de privacidad puede actualizarse para reflejar cambios legales, tecnicos o funcionales del servicio. La version publicada en cada momento sera la vigente.

## 4.3. Politica de cookies

Titulo sugerido:

`Politica de cookies`

Texto propuesto:

> Este sitio web utiliza cookies y tecnologias similares propias y, en su caso, de terceros estrictamente necesarios para su funcionamiento. Ademas, puede utilizar cookies o identificadores de analitica, pero solo si el usuario los acepta expresamente.
>
> ### 1. Que son las cookies
>
> Las cookies son pequenos archivos o identificadores que se almacenan en tu dispositivo al visitar una pagina web y que permiten recordar informacion sobre tu visita, tu sesion o tus preferencias. Tambien pueden emplearse tecnologias similares, como almacenamiento local del navegador.
>
> ### 2. Que categorias usamos
>
> #### Cookies tecnicas o necesarias
>
> Son imprescindibles para que la web funcione correctamente y para prestar servicios solicitados por el usuario. Incluyen, entre otras:
>
> - cookies tecnicas de WordPress para inicio de sesion, sesion, seguridad y preferencias basicas;
> - cookies funcionales necesarias para el area privada y la membresia;
> - identificadores tecnicos asociados a la gestion del checkout y de la cuenta;
> - el identificador necesario para recordar tu eleccion sobre cookies.
>
> Estas cookies no requieren consentimiento previo en la medida en que sean estrictamente necesarias para prestar el servicio solicitado o permitir la comunicacion entre el dispositivo y la red.
>
> #### Cookies de analitica
>
> Si las aceptas, utilizaremos Matomo para obtener estadisticas agregadas de uso del sitio, como paginas visitadas, procedencia del trafico, acciones realizadas y rendimiento general de la web. Estas cookies o identificadores no se activaran hasta que el usuario preste su consentimiento.
>
> En una implementacion tipica de Matomo pueden aparecer identificadores como `_pk_id*`, `_pk_ses*` u otros equivalentes segun la configuracion tecnica vigente en cada momento.
>
> ### 3. Base juridica
>
> La base juridica para las cookies tecnicas es su necesidad para el funcionamiento del sitio y para prestar servicios expresamente solicitados por el usuario. La base juridica para las cookies de analitica es el consentimiento.
>
> ### 4. Como puedes gestionar tus preferencias
>
> Al acceder al sitio puedes:
>
> - aceptar la analitica,
> - rechazar la analitica,
> - configurar tus preferencias.
>
> Puedes cambiar tu decision en cualquier momento desde el enlace `Configurar cookies` disponible en el pie de pagina.
>
> Tambien puedes bloquear o eliminar cookies desde la configuracion de tu navegador, aunque hacerlo puede afectar al funcionamiento del area privada, del acceso a la cuenta o del proceso de contratacion.
>
> ### 5. Vigencia de la eleccion
>
> La eleccion sobre cookies se conservara durante el plazo configurado en el sistema de consentimiento y se volvera a solicitar transcurrido dicho plazo o cuando se produzcan cambios relevantes en el uso de cookies.
>
> ### 6. Tabla orientativa de cookies
>
> La siguiente relacion es orientativa y debera ajustarse a la implementacion final:
>
> - `wordpress_test_cookie`: comprobacion tecnica de compatibilidad del navegador.
> - `wordpress_logged_in_*`, `wordpress_sec_*`: autenticacion y seguridad de usuarios registrados.
> - `wp-settings-*`, `wp-settings-time-*`: preferencias tecnicas del usuario autenticado.
> - `pmpro_visit` y otras tecnicas equivalentes de PMPro: soporte funcional del area privada y de determinados registros tecnicos de uso cuando el usuario esta autenticado.
> - identificador propio de consentimiento: almacenamiento de la eleccion sobre cookies.
> - `_pk_id*`, `_pk_ses*` u otras de Matomo: analitica, solo con consentimiento.
>
> ### 7. Actualizaciones
>
> Esta politica podra actualizarse cuando cambien las cookies utilizadas, la configuracion tecnica del sitio o las exigencias legales aplicables.

## 4.4. Condiciones de contratacion y suscripcion

Titulo sugerido:

`Condiciones de contratacion y suscripcion`

Texto propuesto:

> Las presentes condiciones regulan la contratacion de la suscripcion digital ofrecida en el sitio web `espaciosutil.org`, titularidad de Libranzai, SL, y el acceso al area privada del Curso de Desarrollo Espiritual.
>
> ### 1. Identificacion del prestador
>
> Titular: Libranzai, SL  
> CIF: B84372218  
> Domicilio: Cuesta de San Vicente, 12, 28008 Madrid (Madrid), Espana  
> Email de soporte y contratacion: [CONFIRMAR EMAIL LEGAL]
>
> ### 2. Objeto del servicio
>
> La suscripcion da acceso digital, personal y no transferible al area privada del Curso de Desarrollo Espiritual y a los contenidos, materiales, funcionalidades y recursos incluidos en la membresia vigente en cada momento.
>
> El contenido tiene caracter formativo e informativo. No constituye un servicio medico, psicologico, psiquiatrico o sanitario, ni sustituye asesoramiento profesional individualizado.
>
> ### 3. Planes, precios e impuestos
>
> En el momento de redactar estas condiciones, la suscripcion se ofrece con las siguientes frecuencias de pago:
>
> - plan mensual: 5 EUR;
> - plan semestral: 25 EUR;
> - plan anual: 45 EUR.
>
> Salvo indicacion expresa en contrario, los precios se muestran con IVA incluido.
>
> Libranzai, SL podra modificar los precios o las condiciones economicas para futuras contrataciones o renovaciones, informando de ello con antelacion suficiente cuando resulte exigible.
>
> ### 4. Prueba gratuita
>
> La suscripcion puede incluir una prueba gratuita de 7 dias, en los terminos indicados en la pagina de venta o en el checkout.
>
> Salvo que se indique otra cosa, la prueba gratuita esta pensada para un unico uso por usuario o cuenta. Si el usuario no cancela antes de que finalice la prueba, se realizara automaticamente el primer cobro correspondiente al plan elegido.
>
> ### 5. Forma de pago y renovacion
>
> Los pagos y renovaciones recurrentes se gestionan a traves de Stripe, segun el metodo de pago seleccionado por el usuario durante el checkout.
>
> La suscripcion se renueva automaticamente con la periodicidad elegida al contratar, salvo que el usuario la cancele antes de la fecha de renovacion correspondiente.
>
> ### 6. Cancelacion
>
> El usuario puede cancelar la renovacion automatica desde su area de cuenta o solicitandolo a traves del canal de soporte habilitado.
>
> La cancelacion impedira cobros futuros, pero no dara derecho a devolucion del periodo ya iniciado o ya abonado, sin perjuicio de los derechos legalmente irrenunciables del consumidor. El acceso se mantendra, en su caso, hasta el final del periodo ya pagado o del periodo de prueba aun vigente.
>
> ### 7. Acceso, cuenta y uso permitido
>
> El acceso a la suscripcion requiere crear una cuenta y mantener actualizados los datos esenciales de la misma.
>
> El usuario es responsable de custodiar sus credenciales y de no compartirlas con terceros. La suscripcion es personal e intransferible. Libranzai, SL podra suspender o cancelar accesos en caso de uso fraudulento, cesion de credenciales, abuso tecnico o incumplimiento grave de estas condiciones.
>
> ### 8. Disponibilidad del servicio
>
> Libranzai, SL realizara esfuerzos razonables para mantener la disponibilidad del servicio, pero no garantiza un funcionamiento ininterrumpido ni la ausencia absoluta de errores. Podran realizarse tareas de mantenimiento, mejoras, cambios tecnicos, reorganizacion de contenidos o actualizaciones de la plataforma.
>
> ### 9. Contenidos y propiedad intelectual
>
> Todos los contenidos accesibles mediante la suscripcion, incluyendo videos, audios, textos, materiales docentes, estructura del curso y recursos asociados, estan protegidos por derechos de propiedad intelectual.
>
> El usuario no puede copiar, distribuir, comunicar publicamente, revender, grabar, descargar de forma no autorizada, compartir con terceros o explotar comercialmente dichos contenidos sin autorizacion expresa y por escrito.
>
> ### 10. Derecho de desistimiento y acceso inmediato a contenido digital
>
> La suscripcion contratada consiste en el suministro de contenido digital no prestado en soporte material y en el acceso a un area privada cuyo disfrute puede comenzar de forma inmediata tras el alta o durante el periodo de prueba.
>
> Para poder acceder de forma inmediata al contenido, el usuario debera solicitar expresamente el inicio de la ejecucion del servicio y reconocer que, una vez comenzada dicha ejecucion, puede perder su derecho de desistimiento en los terminos previstos por la normativa de consumo aplicable.
>
> Esta aceptacion se recabara de forma expresa en el proceso de checkout.
>
> ### 11. Reembolsos
>
> Como regla general, no se realizaran devoluciones por periodos de suscripcion ya iniciados o ya disfrutados, ni por falta de uso del servicio por causas imputables al usuario, sin perjuicio de los derechos que la normativa de consumo reconozca como irrenunciables.
>
> Si el usuario detecta un cobro indebido o una incidencia en la contratacion, podra ponerse en contacto con el soporte del sitio para su revision.
>
> ### 12. Soporte
>
> El usuario puede contactar con Espacio Sutil para incidencias de acceso, cuenta, pagos o contratacion a traves de [CONFIRMAR EMAIL LEGAL] o del canal de contacto habilitado en la web.
>
> ### 13. Modificacion de las condiciones
>
> Libranzai, SL podra modificar estas condiciones cuando sea necesario por razones legales, tecnicas, organizativas o comerciales. La version publicada en cada momento sera la vigente, sin perjuicio de los derechos del usuario respecto de contrataciones ya realizadas.
>
> ### 14. Legislacion aplicable y resolucion de conflictos
>
> Estas condiciones se rigen por la legislacion espanola. En caso de conflicto, seran competentes los juzgados y tribunales que correspondan conforme a la normativa de consumidores y usuarios.

## 4.5. Textos cortos para puntos de captura

## Formulario de contacto

Checkbox obligatorio:

`He leido y acepto la Politica de privacidad.`

Capa informativa corta, junto al boton:

`Tus datos seran tratados por Libranzai, SL para atender tu solicitud de contacto. Puedes ejercer tus derechos y ampliar informacion en la Politica de privacidad.`

## Checkout PMPro

Checkbox 1:

`He leido y acepto las Condiciones de contratacion y suscripcion.`

Checkbox 2:

`Solicito el acceso inmediato al contenido digital y soy consciente de que, una vez iniciada la ejecucion del servicio, puedo perder mi derecho de desistimiento en los terminos legalmente aplicables.`

## Footer

Enlaces persistentes recomendados:

- Aviso legal
- Politica de privacidad
- Politica de cookies
- Condiciones de contratacion
- Configurar cookies

## 5. Implementacion tecnica recomendada

## 5.1. Cookies

Implementacion recomendada:

- crear un partial Blade especifico para consentimiento;
- montar el estado de consentimiento en JS ligero propio;
- no cargar Matomo por defecto;
- cargar Matomo solo tras `accept analytics`;
- exponer una funcion o evento global para reabrir preferencias desde footer;
- almacenar la eleccion en cookie propia o `localStorage` con versionado.

Comportamiento minimo:

- sin consentimiento: solo tecnicas;
- aceptar: tecnicas + analitica;
- rechazar: solo tecnicas;
- configurar: modal simple con descripcion de categorias.

## 5.2. WordPress y contenido

Orden recomendado de implantacion:

1. Crear las cuatro paginas legales como borrador en WordPress.
2. Activar la pagina de `Condiciones de contratacion y suscripcion` en PMPro.
3. Anadir checkbox de privacidad al formulario de contacto.
4. Implementar el consentimiento propio y el enlace `Configurar cookies`.
5. Desinstalar y eliminar la dependencia del plugin `gdpr-cookie-compliance`.
6. Revisar footer y menus para enlazar las paginas.

## 6. Datos a confirmar antes de publicar

- Email legal o de privacidad que aparecera en Aviso legal y Politica de privacidad.
- Datos de Registro Mercantil, si deben mostrarse.
- Listado definitivo de proveedores a citar expresamente:
  - hosting,
  - correo transaccional,
  - Stripe,
  - Bunny.net,
  - Matomo si se quiere mencionar por nombre,
  - cualquier otro tercero que finalmente cargue recursos o trate datos.
- Si la prueba gratuita es realmente para todos los planes o solo para algunos en la version final publicada.
- Si se mantendra exactamente la regla de cancelacion al final del periodo ya pagado.

## 7. Resumen ejecutivo

La recomendacion para este proyecto es:

- si, implementar vuestro propio banner de cookies;
- hacerlo minimalista, pero juridicamente conservador;
- usar cuatro textos legales y no tres;
- tratar la suscripcion como contrato digital recurrente con prueba gratuita, renovacion automatica y acceso inmediato;
- recabar consentimiento especifico para el desistimiento del contenido digital;
- anadir clausulas cortas en formulario y checkout, no solo paginas largas en footer.

## 8. Fuentes oficiales utilizadas

- AEPD, `Guia sobre el uso de las cookies`:
  - https://www.aepd.es/guias/guia-cookies.pdf
- AEPD, `Orientaciones sobre cookies de medicion de audiencia`:
  - https://www.aepd.es/guias/orientaciones-cookies-medicion-audiencia.pdf
- BOE, Ley 34/2002, arts. 10 y 22.2:
  - https://www.boe.es/buscar/act.php?id=BOE-A-2002-13758
- BOE, Real Decreto Legislativo 1/2007, arts. 97, 98.7 y 103:
  - https://www.boe.es/buscar/act.php?id=BOE-A-2007-20555
