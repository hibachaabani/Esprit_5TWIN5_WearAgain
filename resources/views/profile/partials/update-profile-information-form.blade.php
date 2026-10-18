<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        {{-- Photo de profil : aperçu immédiat de l'image choisie, avant l'enregistrement --}}
        <div class="flex items-center gap-4" x-data="{ apercu: null, retirer: false }">
            <span class="shrink-0 overflow-hidden bg-gray-200 text-gray-500 flex items-center"
                  style="width: 4.5rem; height: 4.5rem; border-radius: 9999px; justify-content: center; font-weight: 600; font-size: 1.25rem;">
                <img x-show="apercu" :src="apercu" alt="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                @if ($user->photoUrl())
                    <img x-show="! apercu && ! retirer" src="{{ $user->photoUrl() }}" alt="Votre photo de profil" style="width: 100%; height: 100%; object-fit: cover;">
                    <span x-show="! apercu && retirer" style="display: none;">{{ $user->initiales() }}</span>
                @else
                    <span x-show="! apercu">{{ $user->initiales() }}</span>
                @endif
            </span>

            <div>
                <x-input-label for="photo" value="Photo de profil" />
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block text-sm text-gray-600"
                       @change="apercu = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null; retirer = false">
                <p class="mt-1 text-xs text-gray-500">JPG, PNG ou WebP · 2 Mo maximum · au moins 100 × 100 pixels.</p>
                @if ($user->photo)
                    <label class="mt-1 inline-flex items-center gap-4 text-sm text-gray-600">
                        <input type="checkbox" name="supprimer_photo" value="1" x-model="retirer" :disabled="apercu !== null">
                        Supprimer ma photo
                    </label>
                @endif
                <x-input-error class="mt-2" :messages="$errors->get('photo')" />
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
