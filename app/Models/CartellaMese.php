<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartellaMese extends Model
{
    protected $table = 'cartelle_mese';

    protected $fillable = [
        'azienda_id', 'anno', 'mese',
        'label', 'path_relativo', 'created_by',
    ];

    private const MESI_LABEL = [
        1 => 'Gennaio',   2 => 'Febbraio',  3 => 'Marzo',
        4 => 'Aprile',    5 => 'Maggio',    6 => 'Giugno',
        7 => 'Luglio',    8 => 'Agosto',    9 => 'Settembre',
        10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
    ];

    public function azienda()
    {
        return $this->belongsTo(Azienda::class);
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
    }

    public function importLogs()
    {
        return $this->hasMany(ImportLog::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMeseLabelAttribute(): string
    {
        return self::MESI_LABEL[$this->mese] ?? '';
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
