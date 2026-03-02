# Matriz de Contexto e Inventario de Navegación

Fecha de captura: 2026-03-01  
Origen de datos: `wp-cli` sobre entorno local (`site/`).

## 1) Inventario actual de menús

### 1.1 Locations registradas

| Location | Descripción |
| --- | --- |
| `pmpro-login-widget` | Widget de acceso PMPro |
| `primary_navigation` | Navegación principal ES |
| `membresia_navigation` | Navegación de cuenta/membresía |
| `cde_navigation` | Navegación CDE |

### 1.2 Menús asignados

| Menú | Term ID | Location | Nº ítems |
| --- | ---: | --- | ---: |
| `principal` | 3 | `primary_navigation` | 23 |
| `membresia` | 59 | `membresia_navigation` | 5 |
| `cde` | 60 | `cde_navigation` | 3 |

### 1.3 Árbol actual de `primary_navigation` (ES)

- Espacio Sutil (`#`)
  - Centro de Espiritualidad Profunda (`/centro-de-espiritualidad-profunda/`)
  - Equipo (`/equipo/`)
  - Contacto (`/contacto/`)
  - Reseña histórica de Espacio Sutil (`/resena-historica-de-espacio-sutil/`)
- Series (`#`)
  - Textos canalizados (`/textos-canalizados/`)
  - Otros textos (`/otros-textos/`)
  - Otras series (`/otras-series/`)
  - Reveladores (`/reveladores/`)
  - Canales (`/canales/`)
- Actividades (`#`)
  - Divulgación (`/divulgacion/`)
  - Acompañamiento terapéutico (`/orientacion-terapeutica/`)
  - Formación (`/formacion/`)
  - Aclaración de dudas (`/aclaracion-de-dudas/`)
  - Comunidad (`/comunidad/`)
- Iniciación espiritual (`#`)
  - ¿Te quieres iniciar? (`/te-quieres-iniciar/`)
  - Asesoramiento espiritual (`/asesoramiento-espiritual/`)
  - Sesiones de asesoramiento espiritual (`/sesiones-de-asesoramiento-espiritual/`)
  - Preguntas frecuentes (`/category/pregunta-frecuente/`)
- Noticias (`/noticias/`)

### 1.4 Menú `cde_navigation` actual

- Contenido (`/curso-de-desarrollo-espiritual/`)
- Suscripción (`/suscripciones/`)
- Programa (`/el-curso-en-profundidad/`)

### 1.5 Menú `membresia_navigation` actual

- Cuenta (`/cuenta-de-membresia/`)
- Tu perfil (`/cuenta-de-membresia/tu-perfil/`)
- Pedidos (`/cuenta-de-membresia/pedidos-de-membresia/`)
- Cancelar (`/cuenta-de-membresia/cancelacion-de-membresia/`)
- Facturación (`/cuenta-de-membresia/facturacion-de-membresia/`)

## 2) Matriz de contexto extendida (URL -> contexto)

Regla base:
- Contexto CDE: URLs CDE explícitas + `post_type=cde` + taxonomías CDE.
- Contexto ES: resto de URLs, incluyendo páginas ES, series, áreas y noticias.

### 2.1 URLs CDE explícitas

| URL | Contexto | Menú principal | Switch destino |
| --- | --- | --- | --- |
| `/curso-de-desarrollo-espiritual/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/indice-de-lecciones/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/suscripciones/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/el-curso-en-profundidad/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/login-2/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/login/` | CDE (transición) | `cde_navigation` | Home ES (`/`) |
| `/cuenta-de-membresia/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/cuenta-de-membresia/tu-perfil/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/cuenta-de-membresia/pedidos-de-membresia/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/cuenta-de-membresia/facturacion-de-membresia/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/cuenta-de-membresia/cancelacion-de-membresia/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/pago-de-membresia/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/confirmacion-de-membresia/` | CDE | `cde_navigation` | Home ES (`/`) |
| `/bases-de-colaboracion/` | CDE | `cde_navigation` | Home ES (`/`) |

Notas:
- Estado actual: el índice se monta en `/curso-de-desarrollo-espiritual/` con `template-curso`.
- Objetivo recomendado: separar landing (`/curso-de-desarrollo-espiritual/`) e índice (`/indice-de-lecciones/`).
- `login-2` se considera objetivo canónico para acceso de miembros.
- `login` queda en transición mientras se racionaliza el flujo de acceso en un solo paso.

### 2.2 Patrones CDE

| Patrón | Contexto | Menú principal | Switch destino |
| --- | --- | --- | --- |
| Cualquier `single` de `post_type=cde` | CDE | `cde_navigation` | Home ES (`/`) |
| Cualquier archivo/término de taxonomías `serie_cde` y `nivel_cde` | CDE | `cde_navigation` | Home ES (`/`) |

### 2.3 URLs ES explícitas del menú principal

| URL | Contexto | Menú principal | Switch destino |
| --- | --- | --- | --- |
| `/centro-de-espiritualidad-profunda/` | ES | `primary_navigation` | Home CDE |
| `/equipo/` | ES | `primary_navigation` | Home CDE |
| `/contacto/` | ES | `primary_navigation` | Home CDE |
| `/resena-historica-de-espacio-sutil/` | ES | `primary_navigation` | Home CDE |
| `/textos-canalizados/` | ES | `primary_navigation` | Home CDE |
| `/otros-textos/` | ES | `primary_navigation` | Home CDE |
| `/otras-series/` | ES | `primary_navigation` | Home CDE |
| `/reveladores/` | ES | `primary_navigation` | Home CDE |
| `/canales/` | ES | `primary_navigation` | Home CDE |
| `/divulgacion/` | ES | `primary_navigation` | Home CDE |
| `/orientacion-terapeutica/` | ES | `primary_navigation` | Home CDE |
| `/formacion/` | ES | `primary_navigation` | Home CDE |
| `/aclaracion-de-dudas/` | ES | `primary_navigation` | Home CDE |
| `/comunidad/` | ES | `primary_navigation` | Home CDE |
| `/te-quieres-iniciar/` | ES | `primary_navigation` | Home CDE |
| `/asesoramiento-espiritual/` | ES | `primary_navigation` | Home CDE |
| `/sesiones-de-asesoramiento-espiritual/` | ES | `primary_navigation` | Home CDE |
| `/category/pregunta-frecuente/` | ES | `primary_navigation` | Home CDE |
| `/noticias/` | ES | `primary_navigation` | Home CDE |

### 2.4 Patrones ES (series, áreas, noticias y páginas)

| Patrón | Contexto | Menú principal | Switch destino |
| --- | --- | --- | --- |
| Home (`/`) | ES | `primary_navigation` | Home CDE |
| Cualquier `single/archive` de `post_type=serie` (`/series/...`) | ES | `primary_navigation` | Home CDE |
| Cualquier `single/archive` de `post_type=area` (`/areas/...`) | ES | `primary_navigation` | Home CDE |
| Cualquier `single/archive` de `post_type=noticia` (`/noticias/...`) | ES | `primary_navigation` | Home CDE |
| Cualquier página publicada no clasificada como CDE | ES | `primary_navigation` | Home CDE |

### 2.5 Flujos especiales de membresía (no navegación principal)

| URL/Patrón | Contexto | Consideración de navegación |
| --- | --- | --- |
| `/cuenta-de-membresia/cancelacion-de-membresia/?levelstocancel=...` | CDE | Flujo interno de cuenta. No se muestra como ítem principal del menú CDE objetivo. |

## 3) Listados completos para auditoría

### 3.1 Páginas publicadas ES (59 slugs)

- `test`
- `hipnosis-cuantica-curativa`
- `sesiones-de-scanner-cuantico-del-alma`
- `mensaje-enviado`
- `c-soy-estudiante-y-practicante-de-espiritualidad`
- `b-he-oido-hablar-de-estos-temas-y-me-siento-atraido-por-ellos`
- `a-nunca-he-estado-en-contacto-con-temas-espirituales-profundos`
- `que-no-es-espiritualidad-profunda`
- `canales`
- `comentarios-de-lee-carroll-kryon-sobre-la-canalizacion`
- `autocanalizacion-transcripcion-de-la-sesion-de-presentacion`
- `que-es-la-canalizacion-de-informacion-espiritual`
- `diferencia-entre-conciencia-y-consciencia`
- `asesoramiento-espiritual-2`
- `orientacion-terapeutica-individual`
- `como-se-sabe-que-los-textos-canalizados-son-verdadera-legitima-y-fidedigna-informacion-espiritual-revelada`
- `en-que-se-diferencia-un-revelador-almico-de-uno-arquetipico`
- `en-que-consiste-la-canalizacion-de-informacion-espiritual`
- `resena-historica-de-espacio-sutil`
- `sesiones-de-asesoramiento-espiritual`
- `transcripciones-a-audio`
- `curso-on-line-de-numerologia-avanzada`
- `curso-basico-de-iniciacion-al-eneagrama`
- `curso-basico-de-limpieza-y-proteccion-energetica`
- `lectura-de-aura`
- `por-que-es-necesaria-la-sanacion-energetica-ii-sintomas-de-afecciones-energeticas`
- `por-que-es-necesaria-la-sanacion-energetica-i-experiencia-de-ivan-prospector`
- `gestacion-y-crianza-consciente`
- `coaching-de-manifestacion`
- `entradas`
- `home`
- `otros-textos`
- `reveladores`
- `sesiones-de-sanacion-energetica`
- `autoterapia-individual`
- `autoterapia-en-grupo`
- `prospeccion-vital`
- `por-que-es-conveniente-pagar-desde-un-punto-de-vista-terapeutico-por-servicios-espirituales`
- `fases-de-un-proceso-autoterapeutico`
- `otras-series`
- `textos-canalizados`
- `por-que-la-informacion-espiritual-debe-ser-facilitada-a-los-iniciados`
- `que-es-espiricentrismo`
- `en-que-consistira-el-gran-cambio-de-consciencia`
- `cuales-son-las-vias-de-evolucion-en-la-espiritualidad-profunda`
- `que-es-espiritualidad-profunda`
- `calendario`
- `equipo`
- `centro-de-espiritualidad-profunda`
- `aclaracion-de-dudas`
- `asesoramiento-espiritual`
- `te-quieres-iniciar`
- `cocreacion-grupal`
- `comunidad`
- `formacion`
- `orientacion-terapeutica`
- `divulgacion`
- `espacio-sutil`
- `contacto`

### 3.2 Series publicadas (47 slugs)

- `conversaciones-con-dios-libro-2`
- `libro-iii-la-alquimia-del-espiritu-humano`
- `el-kybalion`
- `objeciones-y-sujeciones`
- `autoterapia-espiritual`
- `entrevistas-a-personas-conscientes`
- `entrevistas-a-personas-conscientes-para-estado-universal-tv`
- `cocreacion-grupal`
- `autocanalizacion`
- `nuestras-voces`
- `nuevas-voces`
- `habilidades-gerenciales`
- `organizaciones-conscientes-5-0`
- `conversaciones-sobre-organizaciones-conscientes`
- `tertulias-ligeras`
- `aclaracion-de-dudas`
- `espacio-sutil-en-clubhouse`
- `espiricentrismo`
- `articulos`
- `temas-de-un-curso-de-milagros`
- `sesion-ucdm-sevilla`
- `comentarios-a-grandes-citas`
- `eventos`
- `la-noche-sutil`
- `conversaciones-con-dios-libro-i`
- `el-libro-de-urantia-sesiones-especiales-entrevista-a-ivan-prospector`
- `espiritualidad-y-evolucion-conciencial`
- `iniciacion-a-la-espiritualidad-teorica`
- `un-curso-de-milagros-libro-de-texto`
- `libro-ii-los-tratados`
- `libro-i-un-curso-de-amor`
- `el-material-de-seth`
- `habla-seth-i`
- `habla-seth-ii`
- `habla-seth-iii`
- `los-creadores`
- `el-creador`
- `la-nueva-tierra`
- `libro-i-los-tiempos-finales`
- `libro-ii-no-piense-como-un-humano`
- `libro-v-el-viaje-a-casa`
- `parte-iv-la-vida-y-las-ensenanzas-de-jesus`
- `parte-i-el-universo-central-y-los-superuniversos`
- `el-libro-blanco`
- `el-libro-azul`
- `el-misterio-del-amor`
- `un-manual-para-la-ascension`

### 3.3 Áreas publicadas (9 slugs)

- `el-libro-de-urantia`
- `seth`
- `un-curso-de-milagros`
- `ramtha`
- `conversaciones-con-dios`
- `kryon`
- `un-curso-de-amor`
- `circulo-carmesi-tobias`
- `circulo-carmesi-adamus`

### 3.4 Noticias publicadas (1 slug)

- `quieres-colaborar-con-el-curso-de-desarrollo-espiritual`

## 4) Datos técnicos útiles para implementación

- `post_type=serie` usa slug/archive: `/series/`
- `post_type=area` usa slug/archive: `/areas/`
- `post_type=noticia` usa slug/archive: `/noticias/`
- `post_type=cde` existe como tipo público (la navegación CDE se apoya en páginas + lecciones CDE).
- La página `login-2` (`post_id=2243`) usa shortcode `[pmpro_login]`.
- PMPro tiene `pmpro_login_page_id=2243` (apunta a `/login-2/`).
- El enlace `Acceso` de la top bar apunta a `login-2` con fallback a `login` y `wp_login_url()` si faltan páginas.
