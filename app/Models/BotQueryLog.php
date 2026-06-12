<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotQueryLog extends Model
{
    protected $fillable = [
        'user_id', 'dipendente_user_id', 'azienda_id',
        'domanda', 'esito', 'campo',
        'periodo_anno', 'periodo_mese',
        'risposta', 'motivo', 'gestita',
    ];

    protected $casts = [
        'periodo_anno' => 'integer',
        'periodo_mese' => 'integer',
        'gestita' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dipendente()
    {
        return $this->belongsTo(User::class, 'dipendente_user_id');
    }

    public function azienda()
    {
        return $this->belongsTo(Azienda::class);
    }
}
