<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barber extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'working_days',
        'start_working_time',
        'end_working_time',
    ];

    protected $casts = [
        'working_days' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
