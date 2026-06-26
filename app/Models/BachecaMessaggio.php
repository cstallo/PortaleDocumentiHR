<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BachecaMessaggio extends Model
{
    protected $table = 'bacheca_messaggi';

    protected $fillable = [
        'autore_id', 'titolo', 'corpo', 'pinned', 'pubblicato_il',
    ];

    protected $casts = [
        'pubblicato_il' => 'datetime',
        'pinned' => 'boolean',
    ];

    public function autore()
    {
        return $this->belongsTo(User::class, 'autore_id');
    }

    public function destinatari()
    {
        return $this->belongsToMany(User::class, 'bacheca_destinatari', 'messaggio_id', 'user_id')
            ->withPivot('letto_il', 'notifica_inviata')
            ->withTimestamps();
    }

    public function scopePubblicati($query)
    {
        return $query->whereNotNull('pubblicato_il');
    }

    public function scopeBozze($query)
    {
        return $query->whereNull('pubblicato_il');
    }

    public function isPubblicato(): bool
    {
        return ! is_null($this->pubblicato_il);
    }
}
