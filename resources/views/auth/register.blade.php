<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" x-data="{ type: '{{ old('type', 'particulier') }}' }">
        @csrf

        <!-- Type de compte -->
        <fieldset class="mb-4">
            <legend class="block font-medium text-sm text-gray-700">{{ __('I am') }}</legend>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 cursor-pointer" :class="type === 'particulier' && 'border-indigo-500 ring-1 ring-indigo-500'">
                    <input type="radio" name="type" value="particulier" x-model="type" class="text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">{{ __('An individual') }}</span>
                </label>
                <label class="flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 cursor-pointer" :class="type === 'association' && 'border-indigo-500 ring-1 ring-indigo-500'">
                    <input type="radio" name="type" value="association" x-model="type" class="text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">{{ __('An association') }}</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
        </fieldset>

        <!-- Champs association -->
        <div x-show="type === 'association'" x-cloak class="mb-4 space-y-4">
            <div>
                <x-input-label for="association_nom" :value="__('Association name')" />
                <x-text-input id="association_nom" class="block mt-1 w-full" type="text" name="association_nom" :value="old('association_nom')" x-bind:required="type === 'association'" />
                <x-input-error :messages="$errors->get('association_nom')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="numero_agrement" :value="__('Approval number')" />
                <x-text-input id="numero_agrement" class="block mt-1 w-full" type="text" name="numero_agrement" :value="old('numero_agrement')" x-bind:required="type === 'association'" />
                <x-input-error :messages="$errors->get('numero_agrement')" class="mt-2" />
            </div>

            <p class="text-sm text-gray-600">{{ __('Your association will be visible once verified by our team.') }}</p>
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
