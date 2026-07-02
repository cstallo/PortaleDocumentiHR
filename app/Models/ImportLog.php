<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $table = 'import_log';

    protected $fillable = [
        'azienda_id', 'zip_originale', 'descrizione', 'zip_path_temp',
        'cartella_mese_id', 'admin_id', 'stato',
        'tot_file_zip', 'file_elaborati', 'file_errore_cf',
        'file_nome_non_valido', 'file_duplicati',
        'dettaglio_errori', 'completato_il',
    ];

    protected $casts = [
        'dettaglio_errori' => 'array',
        'completato_il' => 'datetime',
    ];

    public function azienda()
    {
        return $this->belongsTo(Azienda::class);
    }

    public function cartellaMese()
    {
        return $this->belongsTo(CartellaMese::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
    }

    public function files()
    {
        return $this->hasMany(ImportFile::class);
    }
}
