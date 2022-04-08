@component('mail::layout')
    {{-- Header --}}
    @slot('header')
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
        This message was sent from an unmonitored account. You can get in touch with the Wikibase team through our [https://wikiba.se/contact/](Contact) page.
        @endcomponent
    @endslot
@endcomponent
