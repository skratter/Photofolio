<?php

namespace App\Models;

use CyrildeWit\EloquentViewable\Contracts\Viewable;
use CyrildeWit\EloquentViewable\InteractsWithViews;
use Illuminate\Database\Eloquent\Model;

class Page extends Model implements Viewable
{
    use InteractsWithViews;

    protected $fillable = [
        'key',
    ];

    public static function forKey(string $key): self
    {
        return static::firstOrCreate(['key' => $key]);
    }
}
