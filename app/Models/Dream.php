<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dream extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dream_content',
        'is_public',
        'dream_date',
        'mood_before_sleep',
        'mood_after_waking',
        'intensity',
        'sleep_quality',
        'title',
        'overall_theme',
        'analysis',
        'sentiment',
        'sleep_duration',
        'location',
        'weather',
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
