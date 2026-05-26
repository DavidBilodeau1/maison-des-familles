<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $fillable = [
        'name',
        'pin',
        'directory_name',
        'login_enabled',
        'selection_completed',
        'session_started_at',
        'session_expires_at',
    ];

    protected $casts = [
        'login_enabled' => 'boolean',
        'selection_completed' => 'boolean',
        'session_started_at' => 'datetime',
        'session_expires_at' => 'datetime',
    ];

    public function photoSelections()
    {
        return $this->hasMany(PhotoSelection::class);
    }

    public function selectedPhotos()
    {
        return $this->hasMany(PhotoSelection::class)->where('is_selected', true);
    }

    public function isSessionActive()
    {
        if (! $this->session_started_at || ! $this->session_expires_at) {
            return false;
        }

        return now()->between($this->session_started_at, $this->session_expires_at);
    }

    public function startSession()
    {
        $this->session_started_at = now();
        $this->session_expires_at = now()->addMinutes((int) config('photoshoot.session.duration_minutes', 30));
        $this->save();
    }
}
