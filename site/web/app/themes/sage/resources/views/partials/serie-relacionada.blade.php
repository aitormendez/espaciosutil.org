<li class="serie border-blanco m-0 flex flex-col items-start border-t !px-6 py-10 first:border-t md:flex-row lg:px-0">
  <img src="{{ get_the_post_thumbnail_url($serie) }}" alt="{{ $thumbnail_meta($serie)['alt'] }}"
    class="aspect-square w-40 rounded-full object-cover md:my-0 md:w-1/5">

  <div class="col-der m-0 md:w-4/5 md:pl-12">
    <a href="{{ get_permalink($serie) }}" class="font-sans text-2xl font-light md:text-3xl">{{ $serie->post_title }}</a>

    <div class="excerpt mt-6">
      {!! wpautop(get_the_excerpt($serie)) !!}
    </div>

    <ul class="metadatos !my-12 table pl-0 font-sans text-base md:text-lg">
      @php $taxs = $taxonomias($serie) @endphp
      @if ($taxs['reveladores'])
        <li class="table-row">
          <span class="table-cell w-28 md:w-40">{!! $taxs['reveladores_epigrafe'] !!}</span>
          <span class="cell table">{!! $taxs['reveladores'] !!}</span>
        </li>
      @endif
      @if ($taxs['canales'])
        <li class="table-row">
          <span class="table-cell w-28 md:w-40">{!! $taxs['canales_epigrafe'] !!}</span>
          <span class="cell table">{!! $taxs['canales'] !!}</span>
        </li>
      @endif
      @if ($taxs['facilitadores'])
        <li class="table-row">
          <span class="table-cell w-28 md:w-40">{!! $taxs['facilitadores_epigrafe'] !!}</span>
          <span class="cell table">{!! $taxs['facilitadores'] !!}</span>
        </li>
      @endif
      @if ($taxs['autores'])
        <li class="table-row">
          <span class="table-cell w-28 md:w-40">{!! $taxs['autores_epigrafe'] !!}</span>
          <span class="cell table">{!! $taxs['autores'] !!}</span>
        </li>
      @endif
    </ul>
    <div class="not-prose">
      <ul class="enlaces pl-0">
        @php $links = $enlaces($serie) @endphp

        @if ($links)
          <ul class="enlaces list-none pl-0 text-base md:text-lg">
            @foreach ($links as $link)
              @if ($link['serie_formato'] === 'audio')
                <li class="border-gris4 m-0 border-t py-2 last:border-b">
                  <a class="text-morado2 font-sans font-light" href="{{ $link['serie_enlace'] }}"
                    target="_blank">Escuchar
                    <i class="text-blanco">{{ $serie->post_title }}</i>
                    en Ivoox </a>
                </li>
              @elseif($link['serie_formato'] === 'video')
                <li class="border-gris4 m-0 border-t py-2 last:border-b">
                  <a class="text-morado2 font-sans font-light" href="{{ $link['serie_enlace'] }}" target="_blank">Ver
                    <i class="text-blanco">{{ $serie->post_title }}</i>
                    en
                    YouTube </a>
                </li>
              @endif
            @endforeach
          </ul>
        @endif
      </ul>
    </div>
  </div>
</li>
