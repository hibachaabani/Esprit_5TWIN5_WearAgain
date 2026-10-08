<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favori extends Model
{
    protected $table = 'favoris';

    protected $fillable = ['user_id', 'vetement_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }
}
