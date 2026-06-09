<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatoCedolino extends Model
{
    protected $table = 'dati_cedolino';

    protected $fillable = [
        'documento_id', 'azienda_id', 'user_id',
        'codice_fiscale', 'periodo_anno', 'periodo_mese',
        'netto',
        'imponibile_contributi_mese', 'imponibile_contributi_anno',
        'irpef_pagata_mese', 'irpef_pagata_anno',
        'ferie_residue', 'permessi_residui', 'retribuzione_di_fatto',
        'parsing_riuscito', 'parsing_note',
    ];

    protected $casts = [
        'periodo_anno'                => 'integer',
        'periodo_mese'                => 'integer',
        'netto'                       => 'decimal:2',
        'imponibile_contributi_mese'  => 'decimal:2',
        'imponibile_contributi_anno'  => 'decimal:2',
        'irpef_pagata_mese'           => 'decimal:2',
        'irpef_pagata_anno'           => 'decimal:2',
        'ferie_residue'               => 'decimal:2',
        'permessi_residui'            => 'decimal:2',
        'retribuzione_di_fatto'       => 'decimal:2',
        'parsing_riuscito'            => 'boolean',
        'parsing_note'                => 'array',
    ];

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function azienda()
    {
        return $this->belongsTo(Azienda::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibiliPer($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $aziendaIds = $user->aziendeGestite()->pluck('aziende.id');
        return $query->whereIn('azienda_id', $aziendaIds);
    }
}
