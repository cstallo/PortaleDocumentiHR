<?php

namespace App\Models;

use App\Enums\EsitoElaborazione;
use Illuminate\Database\Eloquent\Model;

class ImportFile extends Model
{
    protected $table = 'import_file';

    protected $fillable = [
        'import_log_id', 'nome_file', 'codice_fiscale', 'esito_elaborazione',
        'documento_id', 'user_id', 'email_destinatario', 'notifica_id', 'nota_errore',
    ];

    protected $casts = [
        'esito_elaborazione' => EsitoElaborazione::class,
    ];

    public function importLog()
    {
        return $this->belongsTo(ImportLog::class);
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // hasOne su notifica_id (chiave non standard): una notifica = un destinatario = una riga email
    public function emailLog()
    {
        return $this->hasOne(EmailLog::class, 'notifica_id', 'notifica_id');
    }
}
