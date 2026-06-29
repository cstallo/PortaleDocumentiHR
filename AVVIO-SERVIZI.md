# Avvio servizi — sviluppo locale

Procedura per avviare tutti i servizi necessari (import dipendenti, cedolini, notifiche).
Servono **3 terminali**, tutti nella cartella del progetto:

```bash
cd /Users/cstallo/Desktop/workArea/Zanzar/PortaleHR/portale-documenti
```

---

## Terminale 1 — Microservizio Python (parser Excel)

Legge i file Excel/`.xls` per conto di Laravel. Sta nella cartella sorella del progetto.

```bash
cd /Users/cstallo/Desktop/workArea/Zanzar/PortaleHR/parser-cedolini
.venv/bin/python -m uvicorn app.main:app --host 127.0.0.1 --port 8001
```

- ✅ Pronto quando vedi `Application startup complete`.
- ⚠️ Se esce `address already in use` → è **già attivo**, va bene così: non avviarne un secondo.

---

## Terminale 2 — Queue worker

Esegue i job in coda (`ImportaDipendentiJob`, `ElaboraZipMensile`) e gli invii.
Il `restart` è **obbligatorio** dopo ogni modifica al codice usato dai job (carica la versione aggiornata).

```bash
php artisan queue:restart
php artisan queue:work --queue=import,default,notifications
```

- ✅ Pronto quando vedi `Processing jobs from the [import,default,notifications] queue(s)`.
- Lascia il terminale aperto: stampa ogni job processato.

---

## Terminale 3 — Web server

Con i limiti di upload alzati (il `php artisan serve` normale **non** basta per i file grandi).

```bash
php -d upload_max_filesize=256M -d post_max_size=256M -S localhost:8000 -t public
```

- ✅ Pronto quando vedi `Development Server (http://localhost:8000) started`.

---

## Nel browser

1. `http://localhost:8000/admin` → login (super_admin / hr)
2. **Importazione → Importa dipendenti** → seleziona azienda → carica il file Excel → avvia
3. Verifica: campanella (riepilogo import), tabella **Utenti** (colonna "Invito"),
   inbox mail (Mailtrap in sviluppo), e `/privacy` dall'area dipendente.

---

## Note

- **Mail in sviluppo**: configurate su Mailtrap (sandbox) — le email vengono catturate, non
  recapitate ai destinatari reali. Nessun servizio da avviare, è solo configurazione `.env`.
- **Dopo modifiche a service/job/notification** usati dalla coda → sempre `php artisan queue:restart`.
- **Conflitto porte**: Laravel su `8000`, parser Python su `8001`.
