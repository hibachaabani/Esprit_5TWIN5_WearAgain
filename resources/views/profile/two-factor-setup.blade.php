<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configurer la double authentification
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6 sm:p-8 space-y-6">

                {{-- Étape 1 --}}
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Étape 1 — Scannez le QR code</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Ouvrez <strong>Google Authenticator</strong>, <strong>Authy</strong> ou toute autre
                        application compatible TOTP, puis scannez ce code.
                    </p>

                    <div class="mt-4 flex justify-center">
                        {!! $qrCodeSvg !!}
                    </div>
                </div>

                {{-- Secret manuel --}}
                <div>
                    <p class="text-sm text-gray-600">
                        Si vous ne pouvez pas scanner le code, saisissez cette clé manuellement dans votre application :
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                        <code class="block flex-1 bg-gray-100 text-gray-800 text-sm font-mono px-3 py-2 rounded select-all">
                            {{ $secret }}
                        </code>
                    </div>
                </div>

                <hr class="border-gray-200">

                {{-- Étape 2 --}}
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Étape 2 — Confirmez avec un code</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Saisissez le code à 6 chiffres affiché dans votre application pour confirmer la configuration.
                    </p>

                    <form method="POST" action="{{ route('2fa.confirm') }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="code" value="Code de vérification" />
                            <x-text-input
                                id="code"
                                name="code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                placeholder="000000"
                                class="mt-1 block w-full tracking-widest text-center text-lg"
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        @if(session('erreur'))
                            <p class="text-sm text-red-600">{{ session('erreur') }}</p>
                        @endif

                        <div class="flex items-center gap-4">
                            <x-primary-button>Confirmer et activer</x-primary-button>
                            <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
