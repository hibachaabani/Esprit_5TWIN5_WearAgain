<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VetementPhoto extends Model
{
    protected $table = 'vetement_photos';

    protected $fillable = ['vetement_id', 'chemin', 'ordre'];

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    /** URL publique de cette photo */
    public function url(): string
    {
        return asset('storage/' . $this->chemin);
    }
}
