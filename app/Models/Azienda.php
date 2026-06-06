<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Azienda extends Model
{
    use HasFactory;
    protected $table = 'aziende';


    protected $fillable = [
        'nome', 'slug', 'codice_fiscale',
        'partita_iva', 'indirizzo', 'attiva',
    ];

    protected $casts = ['attiva' => 'boolean'];

    public function dipendenti()
    {
        return $this->hasMany(User::class)->where('role', 'dipendente');
    }

    public function hrUsers()
    {
        return $this->belongsToMany(User::class, 'hr_azienda');
    }

    public function cartelleMese()
    {
        return $this->hasMany(CartellaMese::class);
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
    }

    public function importLogs()
    {
        return $this->hasMany(ImportLog::class);
    }

    public function scopeAttive($query)
    {
        return $query->where('attiva', true);
    }

    public function getStoragePathAttribute(): string
    {
        return $this->slug;
    }
}
