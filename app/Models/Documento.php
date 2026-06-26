<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documenti';

    protected $fillable = [
        'nome_file', 'path_storage', 'codice_fiscale',
        'azienda_id', 'user_id', 'cartella_mese_id',
        'import_log_id', 'utente_non_trovato', 'scaricato_il', 'tipo',
        'descrizione', 'data_documento',
    ];

    protected $casts = [
        'scaricato_il' => 'datetime',
        'utente_non_trovato' => 'boolean',
        'data_documento' => 'date',
        'tipo' => TipoDocumento::class,

    ];

    public function azienda()
    {
        return $this->belongsTo(Azienda::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cartellaMese()
    {
        return $this->belongsTo(CartellaMese::class);
    }

    public function importLog()
    {
        return $this->belongsTo(ImportLog::class);
    }

    public function scopeVisibiliPer($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }
        $aziendaIds = $user->aziendeGestite()->pluck('aziende.id');

        return $query->whereIn('azienda_id', $aziendaIds);
    }

    public function scopePerCodiceFiscale($query, string $cf)
    {
        return $query->where('codice_fiscale', strtoupper($cf));
    }

    public function scopeCedolini($query)
    {
        return $query->where('tipo', 'cedolino');
    }

    public function scopeAziendali($query)
    {
        return $query->where('tipo', 'aziendale');
    }
}
