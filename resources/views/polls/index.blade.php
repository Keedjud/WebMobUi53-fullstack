<x-default-layout>
    <x-slot:title>
        Sondages en cours
    </x-slot>

    <x-slot:scripts>
        @vite(['resources/js/poll-vote.js'])
    </x-slot>

    <div
        id="app"
        data-props='@json([
            "token" => "",
            "loginUrl" => $loginUrl,
        ])'
    ></div>
</x-default-layout>
