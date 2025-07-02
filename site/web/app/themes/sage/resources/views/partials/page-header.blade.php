<div class="page-header flex w-full flex-col items-center px-6 text-center">
    <div class="prose prose-sutil mb-24 w-full max-w-5xl md:pt-24">
        @if (is_tax('revelador'))
            <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Revelador
            </div>
        @elseif (is_tax('canal'))
            <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Canal
            </div>
        @elseif (is_tax('facilitador'))
            <div class="text-gris3 border-gris3 bg-negro/80 mb-6 inline-block border-y px-4 py-2 text-2xl italic">
                Facilitador
            </div>
        @endif
        <h1 class="text-center text-5xl font-thin md:text-7xl">{!! $title !!}</h1>
    </div>

    @if (in_array(get_the_ID(), [
            pmpro_getOption('account_page_id'),
            pmpro_getOption('member_profile_edit_page_id'),
            pmpro_getOption('billing_page_id'),
            pmpro_getOption('invoice_page_id'),
            pmpro_getOption('cancel_page_id'),
        ]))
        @include('partials.membresia-tabs')
    @endif
</div>
