<li class="!px-6 lg:px-0 serie first:border-t border-t border-blanco py-10 flex items-start flex-col md:flex-row m-0">
    <img src="{{ get_the_post_thumbnail_url($serie) }}" alt="{{ $thumbnail_meta($serie)['alt'] }}"
        class="w-40 md:w-1/5 rounded-full aspect-square md:my-0 object-cover">

    <div class="col-der md:pl-12 md:w-4/5 m-0">
        <a href="{{ get_permalink($serie) }}"
            class="font-sans font-light text-2xl md:text-3xl">{{ $serie->post_title }}</a>

        <div class="excerpt mt-6">
            {!! wpautop(get_the_excerpt($serie)) !!}
        </div>

        <ul class="metadatos table font-sans text-base md:text-lg !my-12 pl-0">
            @php $taxs = $taxonomias($serie) @endphp
            @if ($taxs['reveladores'])
                <li class="table-row">
                    <span class="table-cell w-28 md:w-40">{!! $taxs['reveladores_epigrafe'] !!}</span>
                    <span class="table cell">{!! $taxs['reveladores'] !!}</span>
                </li>
            @endif
            @if ($taxs['canales'])
                <li class="table-row">
                    <span class="table-cell w-28 md:w-40">{!! $taxs['canales_epigrafe'] !!}</span>
                    <span class="table cell">{!! $taxs['canales'] !!}</span>
                </li>
            @endif
            @if ($taxs['facilitadores'])
                <li class="table-row">
                    <span class="table-cell w-28 md:w-40">{!! $taxs['facilitadores_epigrafe'] !!}</span>
                    <span class="table cell">{!! $taxs['facilitadores'] !!}</span>
                </li>
            @endif
            @if ($taxs['autores'])
                <li class="table-row">
                    <span class="table-cell w-28 md:w-40">{!! $taxs['autores_epigrafe'] !!}</span>
                    <span class="table cell">{!! $taxs['autores'] !!}</span>
                </li>
            @endif
        </ul>
        <div class="not-prose">
            <ul class="enlaces pl-0">
                @php $links = $enlaces($serie) @endphp

                @if ($links)
                    <ul class="enlaces text-base md:text-lg pl-0 list-none">
                        @foreach ($links as $link)
                            @if ($link['serie_formato'] === 'audio')
                                <li class="border-t border-gris4 last:border-b py-2 m-0">
                                    <a class="font-sans font-light text-morado2" href="{{ $link['serie_enlace'] }}"
                                        target="_blank">Escuchar
                                        <i class="text-blanco">{{ $serie->post_title }}</i>
                                        en Ivoox </a>
                                </li>
                            @elseif($link['serie_formato'] === 'video')
                                <li class="border-t border-gris4 last:border-b py-2 m-0">
                                    <a class="font-sans font-light text-morado2" href="{{ $link['serie_enlace'] }}"
                                        target="_blank">Ver
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
