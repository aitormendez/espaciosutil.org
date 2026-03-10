@php
  $method_items = [
      [
          'title' => 'Organización sistemática previa',
          'text' =>
              'El contenido no se presenta como una lectura lineal sin filtro, sino como un material previamente ordenado para que sus núcleos de sentido puedan entenderse con mayor claridad.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
      [
          'title' => 'Explicación clara de contenidos complejos',
          'text' =>
              'El curso busca hacer accesibles enseñanzas densas sin simplificarlas en exceso ni diluir sus matices.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
      [
          'title' => 'Selección pedagógica',
          'text' =>
              'Las ideas se trabajan con criterio de relevancia, continuidad y progresión, de modo que cada lección tenga un lugar reconocible dentro del conjunto.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
      [
          'title' => 'Armonización entre textos',
          'text' =>
              'Cuando diferentes materiales parecen entrar en contradicción, el trabajo del curso consiste en examinarlos, contextualizarlos y encontrar una lectura más amplia que permita integrarlos.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
      [
          'title' => 'Continuidad de estudio',
          'text' =>
              'El programa está pensado para que el alumno no consuma piezas aisladas, sino que avance dentro de un recorrido con sentido.',
          'icon' => 'tabler-arrow-badge-right-filled',
      ],
  ];
@endphp

<div class="mx-auto max-w-3xl px-6 lg:px-0">
  <div class="prose-cde">
    <h2>Cómo se trabaja el contenido dentro del curso</h2>
    <p>
      El valor del CDE no está solo en los textos que estudia, sino en la forma en que esos materiales se organizan, se
      aclaran y se relacionan entre sí.
    </p>
  </div>

  <x-list-card :items="$method_items" class="mt-12 gap-8" item-class="flex items-start gap-4"
    icon-class="text-cde-light mt-1 h-10 w-10 shrink-0 relative -top-2"
    title-class="text-gris1 block text-2xl font-light font-sans" text-class="text-gris2 text-lg font-light font-sans" />
</div>
