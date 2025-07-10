@php
    $title = sprintf(
        _n('Un comentario a “%s”', '%s comentarios a “%s”', get_comments_number(), 'sage'),
        number_format_i18n(get_comments_number()),
        get_the_title(),
    );
@endphp

<section id="comments"
    class="comments border-blanco text-blanco bg-morado5/90 flex flex-wrap justify-center border-t pb-20"">
    <div
        class="contenido prose prose-sutil prose-xl md:prose-2xl mx-auto mt-24 w-full max-w-4xl px-6 !leading-tight md:px-0">

        @if ($responses())
            <h2 class="bg-morado4 !mt-0 p-2 text-center font-sans !text-base">
                {!! $title !!}
            </h2>

            <ol class="comment-list">
                {!! $responses !!}
            </ol>

            @if ($paginated())
                <nav aria-label="Comment">
                    <ul class="pager">
                        @if ($previous())
                            <li class="previous">
                                {!! $previous !!}
                            </li>
                        @endif

                        @if ($next())
                            <li class="next">
                                {!! $next !!}
                            </li>
                        @endif
                    </ul>
                </nav>
            @endif
        @endif

        @if ($closed())
            <x-alert type="warning">
                {!! __('Comments are closed.', 'sage') !!}
            </x-alert>
        @endif

        @php(comment_form())
    </div>
</section>
