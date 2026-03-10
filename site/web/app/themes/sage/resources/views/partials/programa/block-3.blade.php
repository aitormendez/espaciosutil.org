@php
  $areas = [
      [
          'title' => 'Descriptiva',
          'description' =>
              'Comprensiones fundamentales sobre la realidad espiritual, su estructura, sus principios y sus niveles de manifestación.',
          'icon' => 'tabler-directions-filled',
      ],
      [
          'title' => 'Mentalidad',
          'description' =>
              'Transformación de la forma de pensar del buscador, revisión de marcos mentales y desarrollo de una comprensión más amplia del sentido de la experiencia.',
          'icon' => 'tabler-sunset-2-filled',
      ],
      [
          'title' => 'Energía',
          'description' =>
              'Reconocimiento y trabajo consciente con energías sutiles, sus dinámicas y su papel dentro del proceso evolutivo.',
          'icon' => 'tabler-sparkles-filled',
      ],
      [
          'title' => 'Conciencia',
          'description' =>
              'Expansión de la percepción y estudio de fenómenos ligados a la conciencia ampliada y a realidades multidimensionales.',
          'icon' => 'tabler-leaf-filled',
      ],
      [
          'title' => 'Sistemas',
          'description' =>
              'Exploración de herramientas de evolución y lectura simbólica, como la astrología, la numerología, la cábala, el tarot o la geometría sagrada.',
          'icon' => 'tabler-accessible-filled',
      ],
  ];
@endphp

<div class="prose-cde mx-auto max-w-3xl px-6 lg:px-0">
  <h2>Las cinco grandes áreas de profundización</h2>
  <p>
    A medida que el programa avance hacia su desarrollo completo, el CDE se organizará en torno a cinco grandes áreas
    de profundización. Estas áreas no deben entenderse como módulos aislados, sino como dimensiones complementarias de
    un mismo proceso formativo.
  </p>
</div>

<div class="mx-auto mt-12 grid max-w-5xl gap-4 px-6 lg:px-0 md:grid-cols-2 xl:grid-cols-3">
  @foreach ($areas as $area)
    <x-icon-card :title="$area['title']" :description="$area['description']" :icon="$area['icon']" />
  @endforeach
</div>
