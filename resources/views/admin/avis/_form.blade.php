<div class="field">
    <label for="note">Note</label>
    <select name="note" id="note" required>
        <option value="">— Choisir une note —</option>
        @for ($i = 5; $i >= 1; $i--)
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
