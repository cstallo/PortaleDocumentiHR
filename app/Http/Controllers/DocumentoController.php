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
        $documenti = Documento::where('user_id', $user->id)
            ->where('azienda_id', $user->azienda_id)
            ->with('cartellaMese', 'azienda')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('documenti.index', compact('documenti'));
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
