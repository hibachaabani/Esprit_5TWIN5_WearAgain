<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Double authentification (2FA)
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Ajoutez une couche de sécurité supplémentaire à votre compte. Une fois activée,
            vous devrez saisir un code depuis votre application d'authentification à chaque connexion.
        </p>
    </header>

    <div class="mt-6">
        @if(auth()->user()->two_factor_enabled)
            {{-- 2FA activée --}}
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                    </svg>
                    Activée
                </span>
            </div>

            @if(session('status') === '2fa-enabled')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="mb-4 text-sm text-green-600">
                    La double authentification a été activée.
                </p>
            @endif

            <form method="POST" action="{{ route('2fa.disable') }}">
                @csrf
                <x-primary-button
                    onclick="return confirm('Voulez-vous vraiment désactiver la double authentification ?')"
                    class="bg-red-600 hover:bg-red-500 focus:bg-red-700 active:bg-red-900 focus:ring-red-500">
                    Désactiver la 2FA
                </x-primary-button>
            </form>

        @else
            {{-- 2FA désactivée --}}
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                    </svg>
                    Désactivée
                </span>
            </div>

            @if(session('status') === '2fa-disabled')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="mb-4 text-sm text-gray-600">
                    La double authentification a été désactivée.
                </p>
            @endif

            <form method="POST" action="{{ route('2fa.enable') }}">
                @csrf
                <x-primary-button>
                    Activer la 2FA
                </x-primary-button>
            </form>
        @endif
    </div>
</section>
