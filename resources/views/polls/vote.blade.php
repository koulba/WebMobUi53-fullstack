<x-vue-app-layout>
    <x-slot:scripts>@vite(['resources/js/poll-vote.js'])</x-slot>
    <x-slot:title>Voter</x-slot>
    <div id="app" data-props='@json([
        "token" => $token,
        "isAuthenticated" => $isAuthenticated,
        "loginUrl" => $loginUrl,
    ])'></div>
</x-vue-app-layout>
