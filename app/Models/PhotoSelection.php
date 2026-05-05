<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoSelection extends Model
{
    protected $fillable = [
        'family_id',
        'photo_filename',
        'is_selected',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }
}
