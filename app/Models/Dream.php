<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dream extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'content', 
        'is_public', 
        'dream_date', 
        'dream_location', 
        'mood_before_sleep', 
        'mood_after_waking', 
        'intensity', 
        'sleep_quality',
        'possible_meaning',
        'sentiment',
        'sleep_duration'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'dream_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
