<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InvitoDipendente;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportaDipendentiService
{
    private const CF_REGEX = '/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/i';

    /**
     * @param  array<int, array<string, mixed>>  $record  righe del parser (keyed per intestazione)
     * @return array{importati: int, saltati: array<int, array{riga: int, valore: string, motivo: string}>}
     */
    public function importa(array $record, int $aziendaId): array
    {
        $importati = 0;
        $saltati = [];

        foreach ($record as $i => $riga) {
            $numeroRiga = $i + 2; // riga 1 = intestazioni nel file

            $nome = trim((string) $this->campo($riga, ['nome', 'cognomeenome', 'cognomenome']));
            $cf = strtoupper(trim((string) $this->campo($riga, ['codfiscale', 'codicefiscale', 'cf'])));
            $email = strtolower(trim((string) $this->campo($riga, ['email', 'mail'])));

            // riga completamente vuota → ignora senza segnalare
            if ($nome === '' && $cf === '' && $email === '') {
                continue;
            }

            $validator = Validator::make(
                ['nome' => $nome, 'cf' => $cf, 'email' => $email],
                [
                    'nome' => ['required', 'string', 'max:255'],
                    'cf' => ['required', 'regex:'.self::CF_REGEX],
                    'email' => ['required', 'email'],
                ]
            );

            if ($validator->fails()) {
                $saltati[] = [
                    'riga' => $numeroRiga,
                    'valore' => $nome !== '' ? $nome : $email,
                    'motivo' => $validator->errors()->first(),
                ];

                continue;
            }

            if (User::where('email', $email)->exists()) {
                $saltati[] = ['riga' => $numeroRiga, 'valore' => $email, 'motivo' => 'email già presente'];

                continue;
            }

            if (User::where('azienda_id', $aziendaId)->where('codice_fiscale', $cf)->exists()) {
                $saltati[] = ['riga' => $numeroRiga, 'valore' => $cf, 'motivo' => 'CF già presente in questa azienda'];

                continue;
            }

            try {
                $user = User::create([
                    'name' => $nome,
                    'email' => $email,
                    'codice_fiscale' => $cf,
                    'role' => 'dipendente',
                    'azienda_id' => $aziendaId,
                    'password' => Str::random(40),
                    'must_change_password' => false,
                ]);

                $token = Password::broker()->createToken($user);
                $user->notify(new InvitoDipendente($token));

                $importati++;
            } catch (\Throwable $e) {
                $saltati[] = ['riga' => $numeroRiga, 'valore' => $email, 'motivo' => 'errore: '.$e->getMessage()];
            }
        }

        return ['importati' => $importati, 'saltati' => $saltati];
    }

    /**
     * Legge un campo dal record provando più alias di intestazione (normalizzati).
     *
     * @param  array<string, mixed>  $riga
     * @param  array<int, string>  $alias
     */
    private function campo(array $riga, array $alias): mixed
    {
        foreach ($riga as $chiave => $valore) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower((string) $chiave));
            if (in_array($norm, $alias, true)) {
                return $valore;
            }
        }

        return null;
    }
}
