<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Codes de récupération
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 sm:p-8 space-y-6">

                <div class="rounded-md bg-yellow-50 border border-yellow-300 p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-yellow-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-yellow-800">Sauvegardez ces codes maintenant</h3>
                            <p class="mt-1 text-sm text-yellow-700">
                                Ces codes ne seront affichés <strong>qu'une seule fois</strong>. Conservez-les dans
                                un endroit sûr. Chaque code ne peut être utilisé qu'une fois pour accéder à votre
                                compte si vous perdez l'accès à votre application d'authentification.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    @foreach($plainCodes as $code)
                        <code class="block bg-gray-100 text-gray-800 font-mono text-sm px-3 py-2 rounded text-center tracking-widest select-all">
                            {{ $code }}
                        </code>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        J'ai sauvegardé mes codes
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
