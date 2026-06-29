<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InvitoDipendente;
use Carbon\Carbon;
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

            $cognome = trim((string) $this->campo($riga, ['cognome']));
            $nomeProprio = trim((string) $this->campo($riga, ['nome']));

            // $nome = trim((string) $this->campo($riga, ['nome', 'cognomeenome', 'cognomenome']));
            $nome = trim("$cognome $nomeProprio");
            $cf = strtoupper(trim((string) $this->campo($riga, ['codfiscale', 'codicefiscale', 'cf'])));
            $email = strtolower(trim((string) $this->campo($riga, ['email', 'mail'])));

            $matricola = trim((string) $this->campo($riga, ['n', 'matricola']));
            $sede = trim((string) $this->campo($riga, ['sede']));
            $sesso = strtoupper(trim((string) $this->campo($riga, ['sesso'])));
            $luogoNascita = trim((string) $this->campo($riga, ['luogodinascita', 'luogonascita']));
            $dataNascita = $this->parseData($this->campo($riga, ['datadinascita', 'datanascita']));
            $dataAssunzione = $this->parseData($this->campo($riga, ['dataassunzione']));
            $dataLicenziamento = $this->parseData($this->campo($riga, ['datalicenziamento']));
            $scadenzaContratto = $this->parseData($this->campo($riga, ['scadenzacontratto']));

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
                    'cognome' => $cognome ?: null,
                    'nome' => $nomeProprio ?: null,
                    'matricola' => $matricola ?: null,
                    'sede' => $sede ?: null,
                    'sesso' => in_array($sesso, ['F', 'M'], true) ? $sesso : null,
                    'luogo_nascita' => $luogoNascita ?: null,
                    'data_nascita' => $dataNascita,
                    'data_assunzione' => $dataAssunzione,
                    'data_licenziamento' => $dataLicenziamento,
                    'scadenza_contratto' => $scadenzaContratto,

                    'email' => $email,
                    'codice_fiscale' => $cf,
                    'role' => 'dipendente',
                    'azienda_id' => $aziendaId,
                    'password' => Str::random(40),
                    'must_change_password' => false,
                ]);

                $token = Password::broker()->createToken($user);
                $user->notify(new InvitoDipendente($token));
                $user->notify(new InvitoDipendente($token));
                $user->update(['invito_inviato_il' => now()]);


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

    /**
     * Converte una data Excel "gg/mm/aa" (o "gg/mm/aaaa") in Y-m-d.
     * Anno a 2 cifre: pivot a 30 → 00-30 = 2000+, 31-99 = 1900+.
     */
    private function parseData(mixed $valore): ?string
    {
        $valore = trim((string) $valore);
        if ($valore === '') {
            return null;
        }

        if (! preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2}|\d{4})$#', $valore, $m)) {
            return null;
        }

        [$g, $mese, $anno] = [(int) $m[1], (int) $m[2], (int) $m[3]];

        if (strlen($m[3]) === 2) {
            $anno += $anno <= 30 ? 2000 : 1900;
        }

        try {
            return Carbon::createFromDate($anno, $mese, $g)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

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
