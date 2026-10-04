<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $hidden = ['pix_key'];

    protected $fillable = [
        'name',
        'sport',
        'event_date',
        'location',
        'registration_fee',
        'pix_key',
        'pix_receiver_name',
        'pix_receiver_city',
    ];

    protected function casts(): array
    {
        return [
            'pix_key' => 'encrypted',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Registration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
