@php
  $sourceGroups = [
      [
          'title' => 'Base amplia del programa',
          'highlighted' => true,
          'items' => [
              'El Kybalion',
              'Ramtha',
              'Un Curso de Milagros',
              'Un Curso de Amor',
              'Conversaciones con Jeshua',
              'Conversaciones con Dios',
              'Kryon',
              'Tobías',
              'Adamus',
          ],
      ],
      [
          'title' => 'Otras fuentes que nutren el enfoque',
          'highlighted' => true,
          'items' => [
              'Tradiciones esotéricas y ocultistas',
              'Espiritualidad oriental',
              'Enseñanzas de grandes maestros',
              'Información canalizada',
              'Comunicaciones de origen no humano en misión de ayuda a la Tierra',
          ],
      ],
  ];
@endphp

<div class="prose-cde mx-auto max-w-3xl px-6 lg:px-0">
  <h2>Fuentes del CDE</h2>
  <p>
    El Curso de Desarrollo Espiritual se apoya en una base amplia de revelaciones modernas y contemporáneas, junto
    con otras tradiciones y materiales que contribuyen a ampliar la comprensión del camino espiritual.
  </p>
  <h class="">Nucleo visible actual</h>
</div>
<x-series-blocks-list :series="$series_cde_lessons" class="mt-12 font-sans" />

<div class="mx-auto mt-12 grid max-w-3xl gap-4 px-6 lg:px-0 xl:grid-cols-2">
  @foreach ($sourceGroups as $group)
    <x-source-group-card :title="$group['title']" :items="$group['items']" :highlighted="$group['highlighted'] ?? false" />
  @endforeach
</div>

<div class="prose-cde mx-auto mt-12 max-w-3xl px-6 lg:px-0">
  <p>
    El espíritu del CDE es inclusivo, evolutivo y abierto a seguir incorporando materiales que aporten mayor luz,
    comprensión y profundidad al estudio espiritual.
  </p>
</div>
