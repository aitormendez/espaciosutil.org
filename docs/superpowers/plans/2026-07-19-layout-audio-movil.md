# Layout móvil del reproductor de audio — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sustituir en pantallas menores de 768 px la fila horizontal de `DefaultAudioLayout` por un reproductor vertical legible y táctil, conservando el layout predeterminado en escritorio y el comportamiento actual del vídeo.

**Architecture:** `FeaturedAudio` seguirá siendo propietario del `MediaPlayer`, las pistas y la persistencia. Un nuevo componente `MobileAudioLayout` compondrá primitivas de Vidstack en cuatro zonas —título, controles, progreso y utilidades— y un pequeño módulo puro resolverá qué layout montar desde la media query `md`.

**Tech Stack:** React 19, `@vidstack/react` 1.12.13, Tailwind CSS 4, CSS de Sage/Vite y `node:test`.

## Global Constraints

- Conservar `DefaultAudioLayout` para anchuras desde 768 px.
- Conservar `DefaultVideoLayout` sin cambios.
- Soportar correctamente un ancho mínimo de 320 px y ampliación de texto.
- Mantener capítulos, velocidad de reproducción, progreso y persistencia existentes.
- Mantener objetivos táctiles de al menos 44 × 44 px.

---

### Task 1: Selección responsive del layout de audio

**Files:**
- Create: `site/web/app/themes/sage/resources/js/components/featuredAudioLayout.js`
- Create: `site/web/app/themes/sage/resources/js/components/featuredAudioLayout.test.js`

**Interfaces:**
- Produces: `AUDIO_DESKTOP_MEDIA_QUERY` y `resolveFeaturedAudioLayout(isDesktop: boolean): 'mobile' | 'desktop'`.

- [ ] **Step 1: Write the failing test**

```js
import assert from 'node:assert/strict';
import test from 'node:test';

import {
  AUDIO_DESKTOP_MEDIA_QUERY,
  resolveFeaturedAudioLayout,
} from './featuredAudioLayout.js';

test('usa el layout móvil por debajo de md', () => {
  assert.equal(AUDIO_DESKTOP_MEDIA_QUERY, '(min-width: 768px)');
  assert.equal(resolveFeaturedAudioLayout(false), 'mobile');
});

test('conserva el layout de escritorio desde md', () => {
  assert.equal(resolveFeaturedAudioLayout(true), 'desktop');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test resources/js/components/featuredAudioLayout.test.js`

Expected: FAIL porque `featuredAudioLayout.js` todavía no existe.

- [ ] **Step 3: Write minimal implementation**

```js
export const AUDIO_DESKTOP_MEDIA_QUERY = '(min-width: 768px)';

export const resolveFeaturedAudioLayout = (isDesktop) =>
  isDesktop ? 'desktop' : 'mobile';
```

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test resources/js/components/featuredAudioLayout.test.js`

Expected: 2 tests aprobados.

### Task 2: Construir e integrar los controles móviles

**Files:**
- Create: `site/web/app/themes/sage/resources/js/components/MobileAudioLayout.jsx`
- Create: `site/web/app/themes/sage/resources/css/components/featured-audio.css`
- Modify: `site/web/app/themes/sage/resources/js/components/FeaturedAudio.jsx`
- Modify: `site/web/app/themes/sage/resources/css/app.css`

**Interfaces:**
- Consumes: `AUDIO_DESKTOP_MEDIA_QUERY` y `resolveFeaturedAudioLayout`.
- Produces: `MobileAudioLayout`, montado dentro del mismo `MediaPlayer` cuando la media query `md` no coincide.

- [ ] **Step 1: Implement the four mobile sections**

Crear `MobileAudioLayout.jsx` con primitivas de Vidstack:

```jsx
<section className="featured-audio-mobile-layout dark">
  <div data-audio-section="title"><Title /><ChapterTitle /></div>
  <div data-audio-section="controls"><SeekButton /><PlayButton /><SeekButton /></div>
  <div data-audio-section="progress"><TimeSlider.Root /></div>
  <div data-audio-section="utilities"><Time /><MobileChaptersMenu /><MobileSettingsMenu /></div>
</section>
```

El slider usará `TimeSlider.Chapters`, `Track`, `TrackFill`, `Progress`, `Thumb` y `Preview`. Los menús usarán `useChapterOptions()` y `usePlaybackRateOptions()` para conservar acciones reales de Vidstack.

- [ ] **Step 2: Add component-scoped CSS**

Crear `featured-audio.css` con cuatro filas, título con `overflow-wrap: anywhere`, botones de 44 px, play de 52 px, slider siempre visible y menús limitados a `calc(100vw - 2rem)`. Importarlo desde `resources/css/app.css`.

- [ ] **Step 3: Switch layouts at md**

En `FeaturedAudio.jsx`, escuchar `AUDIO_DESKTOP_MEDIA_QUERY`, resolver el modo con `resolveFeaturedAudioLayout` y montar exactamente uno de estos layouts:

```jsx
{audioLayout === 'desktop' ? (
  <DefaultAudioLayout icons={defaultLayoutIcons} />
) : (
  <MobileAudioLayout />
)}
```

- [ ] **Step 4: Run automated verification**

Run: `node --test resources/js/components/featuredAudioLayout.test.js resources/js/lessons/subindex.test.js`

Expected: todos los tests aprobados.

Run: `npm run build`

Expected: Vite termina con código 0 y sin errores.

- [ ] **Step 5: Verify the rendered UI**

Comprobar en el navegador 320, 360, 390, 767 y 768 px, en pausa y reproducción. Confirmar que no existe overflow horizontal, el título es legible, la barra conserva capítulos, los menús funcionan y a 768 px reaparece el layout de escritorio.
