<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'status'];

    protected $casts = [
        'status' => 'string',
    ];

    public function contactLists(): BelongsToMany
    {
        return $this->belongsToMany(ContactList::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed';
    }
}
