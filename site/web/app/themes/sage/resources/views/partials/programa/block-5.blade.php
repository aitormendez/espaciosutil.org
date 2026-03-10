@php
  $study_tools = [
      [
          'title' => 'Vídeo y audio',
          'description' =>
              'Las lecciones pueden seguirse en vídeo o en audio, según el momento y la forma de estudio de cada persona.',
          'icon' => 'tabler-brand-youtube-filled',
          'icon_secondary' => 'tabler-headphones-filled',
      ],
      [
          'title' => 'Capítulos temáticos',
          'description' =>
              'Cada contenido se divide en bloques reconocibles para que sea posible volver a un tema concreto sin perder tiempo buscando.',
          'icon' => 'tabler-sitemap-filled',
      ],
      [
          'title' => 'Continuidad de reproducción',
          'description' =>
              'El reproductor recuerda dónde te quedaste, facilitando un estudio más fluido en contenidos de mayor extensión.',
          'icon' => 'tabler-bookmark-filled',
      ],
      [
          'title' => 'Subtítulos',
          'description' =>
              'Los subtítulos amplían la comodidad de estudio y permiten acompañar mejor la comprensión del contenido.',
          'icon' => 'tabler-badge-cc-filled',
      ],
      [
          'title' => 'Resumen en texto',
          'description' =>
              'Cada lección puede apoyarse en un resumen que ayuda a repasar, fijar ideas centrales y retomar el estudio con rapidez.',
          'icon' => 'tabler-file-description-filled',
      ],
      [
          'title' => 'Cuestionario de repaso',
          'description' =>
              'Las preguntas de repaso convierten la lección en una experiencia más activa y permiten comprobar qué ideas han quedado integradas.',
          'icon' => 'tabler-school-filled',
      ],
      [
          'title' => 'Seguimiento del recorrido',
          'description' =>
              'La marca de lección completada y el índice estructurado ayudan a orientarse dentro del conjunto y a sostener la continuidad del estudio.',
          'icon' => 'tabler-binary-tree-filled',
      ],
      [
          'title' => 'Acceso online',
          'description' =>
              'Todo el contenido está disponible online, de manera que puede seguirse desde cualquier lugar.',
          'icon' => 'tabler-key-filled',
      ],
  ];
@endphp

<div class="prose-cde mx-auto max-w-3xl px-6 lg:px-0">
  <h2>Herramientas y formato de estudio</h2>
  <p>
    Cada lección del CDE está pensada como una unidad de estudio, no solo como una pieza audiovisual. Por eso el curso
    incorpora herramientas que ayudan a seguir el hilo, volver a puntos concretos y fijar mejor lo aprendido.
  </p>
</div>

<div class="mx-auto mt-12 grid max-w-6xl gap-4 px-6 lg:px-0 md:grid-cols-2 xl:grid-cols-4">
  @foreach ($study_tools as $tool)
    <x-icon-card :title="$tool['title']" :description="$tool['description']" :icon="$tool['icon']"
      :icon-secondary="$tool['icon_secondary'] ?? null" />
  @endforeach
</div>
