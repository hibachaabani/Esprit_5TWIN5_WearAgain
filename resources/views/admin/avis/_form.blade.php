<div class="form narrow">
    <div class="field">
        <label for="reparation_id">Réparation</label>
        <select name="reparation_id" id="reparation_id" required>
            <option value="">— Choisir une réparation —</option>
            @foreach ($reparations as $reparation)
                <option value="{{ $reparation->id }}" @selected(old('reparation_id', $avi->reparation_id ?? null) == $reparation->id)>
                    {{ $reparation->titre }} (#{{ $reparation->id }})
                </option>
            @endforeach
        </select>
        @error('reparation_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="user_id">Membre auteur</label>
        <select name="user_id" id="user_id" required>
            <option value="">— Choisir un membre —</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $avi->user_id ?? null) == $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('user_id')<p class="error">{{ $message }}</p>@enderror
    </div>

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
                  placeholder="Partagez votre expérience de la réparation…">{{ old('commentaire', $avi->commentaire ?? '') }}</textarea>
        @error('commentaire')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
