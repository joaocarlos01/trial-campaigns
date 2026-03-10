<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = ['subject', 'body', 'contact_list_id', 'status', 'scheduled_at'];

    
    protected $casts = [
        'status' => 'string',
        'scheduled_at' => 'datetime',
    ];

    public function contactList(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    public function sends(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function getStatsAttribute(): array
    {
        //$sends = $this->sends;

        //return [
        //    'pending' => $sends->where('status', 'pending')->count(),
        //    'sent'    => $sends->where('status', 'sent')->count(),
        //    'failed'  => $sends->where('status', 'failed')->count(),
        //    'total'   => $sends->count(),
        //];

        // Como estava feito originalmente, era enviado o sends completo para o memoria do PHP. 
        //Campanhas grandes iram causar problemas de desempenho.  

        $counts = $this->sends()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'sent'    => (int) ($counts['sent'] ?? 0),
            'failed'  => (int) ($counts['failed'] ?? 0),
            'total'   => (int) $counts->sum(),
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
