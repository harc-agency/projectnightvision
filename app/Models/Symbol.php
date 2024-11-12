<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Symbol extends Model
{
    protected $fillable = [
        'symbol_key',
        'title',
        'description',
        'symbol',
        'interpretation',
        'featured_image',
    ];

    public function getRouteKeyName()
    {
        return 'symbol_key';
    }
}
