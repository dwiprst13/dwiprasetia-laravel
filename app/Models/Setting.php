<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'site_name',
        'site_logo',
        'about',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
