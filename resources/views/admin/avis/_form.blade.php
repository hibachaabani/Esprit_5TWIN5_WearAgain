<div class="form narrow">
    <div class="field">
        <label for="user_id">Membre auteur</label>
        <select name="user_id" id="user_id" required>
            <option value="">— Choisir un membre —</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id', $avi->user_id ?? '') === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('user_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    @if (class_exists(\App\Models\Atelier::class))
        <div class="field">
            <label for="atelier_id">Atelier</label>
            <select name="atelier_id" id="atelier_id" required>
                <option value="">— Choisir un atelier —</option>
                @foreach (\App\Models\Atelier::orderBy('nom')->get() as $atelier)
                    <option value="{{ $atelier->id }}" @selected((string) old('atelier_id', $avi->atelier_id ?? '') === (string) $atelier->id)>
                        {{ $atelier->nom }}
                    </option>
                @endforeach
            </select>
            @error('atelier_id')<p class="error">{{ $message }}</p>@enderror
        </div>
    @endif

    <div class="field">
        <label for="note">Note</label>
        <select name="note" id="note" required>
            @for ($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" @selected((string) old('note', $avi->note ?? '') === (string) $i)>
                    {{ $i }} / 5
                </option>
            @endfor
        </select>
        @error('note')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="commentaire">Commentaire</label>
        <textarea name="commentaire" id="commentaire" rows="4"
                  placeholder="Partagez votre expérience…">{{ old('commentaire', $avi->commentaire ?? '') }}</textarea>
        @error('commentaire')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>