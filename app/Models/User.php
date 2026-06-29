<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

protected $fillable = [
    'name', 'email', 'password',
    'role', 'azienda_id', 'codice_fiscale', 'must_change_password', 'bot_enabled',
    // anagrafica import dipendenti
    'cognome', 'nome', 'matricola', 'sede', 'sesso',
    'luogo_nascita', 'data_nascita',
    'data_assunzione', 'data_licenziamento', 'scadenza_contratto', 'invito_inviato_il',
];

    protected $hidden = ['password', 'remember_token'];

protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'must_change_password' => 'boolean',
    'bot_enabled' => 'boolean',
    // date anagrafica
    'data_nascita' => 'date',
    'data_assunzione' => 'date',
    'data_licenziamento' => 'date',
    'scadenza_contratto' => 'date',
];

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['super_admin', 'hr']);
    }

    /**
     * Forza il codice fiscale in UPPERCASE a ogni scrittura (invariante di progetto).
     * Garantisce l'invariante da qualunque punto si crei/aggiorni l'utente.
     */
    protected function codiceFiscale(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper($value) : null,
        );
    }

    public function azienda()
    {
        return $this->belongsTo(Azienda::class);
    }

    public function aziendeGestite()
    {
        return $this->belongsToMany(Azienda::class, 'hr_azienda');
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
    }

    public function messaggiBacheca()
    {
        return $this->belongsToMany(BachecaMessaggio::class, 'bacheca_destinatari', 'user_id', 'messaggio_id')
            ->withPivot('letto_il', 'notifica_inviata')
            ->withTimestamps();
    }

    public function aziendeVisibili()
    {
        if ($this->isSuperAdmin()) {
            return Azienda::query();
        }

        return $this->aziendeGestite();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isHr(): bool
    {
        return $this->role === 'hr';
    }

    public function isDipendente(): bool
    {
        return $this->role === 'dipendente';
    }

    public function scopeByCodiceFiscale($query, string $cf)
    {
        return $query->where('codice_fiscale', strtoupper($cf));
    }

    public function scopeDipendenti($query)
    {
        return $query->where('role', 'dipendente');
    }

    public function scopeHr($query)
    {
        return $query->where('role', 'hr');
    }
}
