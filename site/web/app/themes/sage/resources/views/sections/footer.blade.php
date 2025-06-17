@php use Illuminate\Support\Facades\Vite; @endphp

<footer id="footer" class="text-blanco bg-negro/90 relative border-t font-sans">
    <ul id="social" class="bg-negro mb-6 flex w-full flex-col justify-center justify-center border-b p-4 md:flex-row">
        <li class="mb-2 flex justify-center md:mb-0 md:mr-8">
            <a href="https://www.youtube.com/@Espaciosutil" class="flex flex-col items-center" target="_blank">
                <img src="{{ Vite::asset('resources/images/youtube.svg') }}" alt="YouTube" class="h-auto w-10">
                Espacio Sutil en YouTube
            </a>
        </li>
        <li class="mb-2 flex justify-center md:mb-0 md:mr-8">
            <a href="https://www.youtube.com/@EspaciosutilTV" class="flex flex-col items-center" target="_blank">
                <img src="{{ Vite::asset('resources/images/youtube.svg') }}" alt="YouTube TV" class="h-auto w-10">
                <span>Espacio Sutil TV en YouTube</span>
            </a>
        </li>
        <li class="flex justify-center">
            <a href="https://www.ivoox.com/escuchar-audios-espacio-sutil_al_789810_1.html?show=programs"
                class="flex flex-col items-center" target="_blank">
                <img src="{{ Vite::asset('resources/images/podcast.svg') }}" alt="Podcast" class="h-auto w-8">
                <span>Espacio Sutil en Ivoox</span>
            </a>
        </li>
    </ul>

    @php(dynamic_sidebar('sidebar-footer'))

    <x-navigation-footer name="footer_navigation" />
</footer>
