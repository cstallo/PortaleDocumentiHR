<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BachecaDestinatario extends Model
{
    protected $table = 'bacheca_destinatari';

    protected $fillable = [
        'messaggio_id', 'user_id', 'letto_il', 'notifica_inviata',
    ];

    protected $casts = [
        'letto_il' => 'datetime',
        'notifica_inviata' => 'boolean',
    ];

    public function messaggio()
    {
        return $this->belongsTo(BachecaMessaggio::class, 'messaggio_id');
    }

    public function utente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
