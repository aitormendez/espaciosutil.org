@php
  $phases = [
      [
          'title' => 'Fase 1. Comentarios temáticos por texto revelado',
          'status' => 'available',
          'status_label' => 'Disponible ahora',
          'paragraphs' => [
              'La primera fase reúne grabaciones en vídeo y audio que comentan en profundidad las enseñanzas organizadas de cada uno de los textos espirituales trabajados en Espacio Sutil.',
              'Estos comentarios, elaborados por Iván Prospector, no nacen de una lectura improvisada del material, sino de una organización sistemática previa que permite seleccionar, ordenar y desarrollar los contenidos con una intención claramente pedagógica.',
              'No se trata, por tanto, de una simple lectura comentada, sino de una exposición estructurada de enseñanzas, presentada de forma accesible sin perder profundidad.',
              'En el estado actual del curso, esta fase se expresa sobre todo a través del trabajo visible con Urantia y Seth.',
          ],
      ],
      [
          'title' => 'Fase 2. Integración de enseñanzas entre textos',
          'status' => 'in-progress',
          'status_label' => 'En desarrollo',
          'paragraphs' => [
              'La segunda fase da un paso más: toma los contenidos trabajados por separado y los pone en relación. Su función es construir síntesis, mostrar convergencias, resolver aparentes contradicciones y ofrecer una visión más amplia de la espiritualidad revelada.',
              'Aquí la armonización ocupa un lugar central. No se trata solo de comparar textos, sino de comprender cómo distintas revelaciones pueden iluminarse mutuamente cuando se las estudia con orden y criterio.',
          ],
      ],
      [
          'title' => 'Fase 3. Curso completo estructurado en niveles',
          'status' => 'in-progress',
          'status_label' => 'En desarrollo',
          'paragraphs' => [
              'La tercera fase proyecta el CDE como un programa completo de recorrido progresivo, organizado en cuatro niveles: Iniciación, Desarrollo, Avanzado y Maestría.',
              'Los dos primeros niveles estarán planteados como un tronco común. A partir de Avanzado, el programa podrá abrir itinerarios de especialización según las áreas de trabajo y resonancia del alumno.',
              'Esta fase debe entenderse como la arquitectura global hacia la que evoluciona el curso, no como una oferta completa ya disponible en este momento.',
          ],
      ],
  ];
@endphp

<div class="mx-auto max-w-3xl px-6 lg:px-0">
  <div class="prose-cde mx-auto max-w-4xl">
    <h2>Una propuesta en tres fases</h2>
    <p>
      El CDE está concebido como un programa que se despliega por etapas. Cada fase amplía la anterior y añade un
      nivel mayor de integración, profundidad y estructura.
    </p>
  </div>

  <div class="mt-12">
    @foreach ($phases as $phase)
      <x-timeline-card :title="$phase['title']" :status="$phase['status']" :status-label="$phase['status_label']"
        :paragraphs="$phase['paragraphs']" :first="$loop->first" :last="$loop->last" />
    @endforeach
  </div>
</div>
