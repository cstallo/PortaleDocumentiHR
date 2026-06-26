<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class DocumentoController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $user = auth()->user();

        $cedolini = Documento::where('user_id', $user->id)
            ->where('azienda_id', $user->azienda_id)
            ->where('tipo', 'cedolino')
            ->with('cartellaMese')
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'cedolini');

        $documenti = Documento::where('user_id', $user->id)
            ->where('azienda_id', $user->azienda_id)
            ->where('tipo', '!=', 'cedolino')
            ->orderByDesc('data_documento')
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'documenti');

        $messaggiBacheca = $user->messaggiBacheca()
            ->with('autore')
            ->pubblicati()
            ->orderByDesc('pinned')
            ->orderByDesc('pubblicato_il')
            ->get();

        $nonLettiCount = $user->messaggiBacheca()
            ->pubblicati()
            ->wherePivotNull('letto_il')
            ->count();

        // marca come letti i messaggi non ancora aperti (dopo aver contato i non letti per il badge)
        $user->messaggiBacheca()
            ->wherePivotNull('letto_il')
            ->each(function ($messaggio) use ($user) {
                $user->messaggiBacheca()->updateExistingPivot($messaggio->id, [
                    'letto_il' => now(),
                ]);
            });

        // conteggi "nuovi" (mai scaricati) per i badge delle tab
        $cedoliniNuovi = Documento::where('user_id', $user->id)
            ->where('azienda_id', $user->azienda_id)
            ->where('tipo', 'cedolino')
            ->whereNull('scaricato_il')
            ->count();

        $documentiNuovi = Documento::where('user_id', $user->id)
            ->where('azienda_id', $user->azienda_id)
            ->where('tipo', '!=', 'cedolino')
            ->whereNull('scaricato_il')
            ->count();

        return view('documenti.index', compact(
            'cedolini', 'documenti', 'messaggiBacheca',
            'nonLettiCount', 'cedoliniNuovi', 'documentiNuovi',
        ));
    }

    public function download(Documento $documento)
    {
        $this->authorize('download', $documento);

        $disk = Storage::disk('cedolini');

        if (! $disk->exists($documento->path_storage)) {
            abort(404, 'File non trovato.');
        }

        if (is_null($documento->scaricato_il)) {
            $documento->update(['scaricato_il' => now()]);
        }

        return $disk->download($documento->path_storage, $documento->nome_file);
    }

    public function inline(Documento $documento)
    {
        $this->authorize('download', $documento);

        $disk = Storage::disk('cedolini');

        if (! $disk->exists($documento->path_storage)) {
            abort(404, 'File non trovato.');
        }

        // response() usa Content-Disposition: inline → il PDF si apre nel browser/iframe.
        return $disk->response($documento->path_storage, $documento->nome_file, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
