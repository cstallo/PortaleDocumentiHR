<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use MassPrunable;

    protected $fillable = [
        'notifica_id', 'destinatario', 'user_id', 'azienda_id',
        'tipo', 'oggetto', 'stato', 'errore', 'tentativi', 'inviata_il',
    ];

    protected $casts = [
        'inviata_il' => 'datetime',
        'tentativi' => 'integer',
    ];

    /**
     * Righe da cancellare automaticamente: più vecchie di 6 mesi.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonths(6));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function azienda()
    {
        return $this->belongsTo(Azienda::class);
    }
}
