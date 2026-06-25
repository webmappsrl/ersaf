> Ticket: oc:8112

# Creazione shard ersaf

## Cosa cambia
Il progetto ersaf viene ricreato da zero partendo dal boilerplate Webmapp, abbandonando il fork osm2cai2 (ora rinominato `ersaf-osm`). Il nuovo repo è pulito, conforme alle convenzioni Webmapp, e integra `wm-osmfeatures` come submodule per l'accesso ai dati OSM tramite osmfeatures.

## Perché
Il vecchio repo ersaf era un fork di osm2cai2 con customizzazioni accumulate difficili da rimuovere. La scelta di ripartire da zero con il boilerplate garantisce un ambiente dev pulito, manutenibile e allineato al metodo Webmapp, con i dati provenienti da osmfeatures invece che da OSM direttamente.

## Requisiti
- [ ] Configurare `.env` con `APP_NAME=ersaf` e `DOCKER_PROJECT_DIR_NAME=ersaf` (porte Docker di default)
- [ ] Aggiungere `wm-osmfeatures` come submodule Git (`./wm-osmfeatures`) e dipendenza Composer path
- [ ] Copiare gli asset dal vecchio repo `ersaf-osm`: `public/assets/images/` (4 file), `public/css/custom-nova.css`, `public/css/nova-logo.css`, `favicon.ico`
- [ ] Avviare i container Docker (`docker compose up -d`)
- [ ] Pubblicare le migration stub di wm-package (`php artisan vendor:publish --tag="wm-package-migrations"`)
- [ ] Eseguire `php artisan migrate` e seed base
- [ ] Verificare che l'app risponda, Nova sia accessibile e il login funzioni
- [ ] Verificare che le tabelle wm-package siano presenti nel DB dopo `migrate`

## Rischi
- **Porte Docker in conflitto** con altri shard attivi sulla stessa macchina — da verificare prima dell'avvio. Mitigazione: controllare `docker ps` prima di `up`.
- **Versione wm-osmfeatures** non allineata — il submodule potrebbe puntare a un commit vecchio. Mitigazione: aggiornare al branch main dopo l'aggiunta.

## Out of scope
- Migrazione dati dal DB di `ersaf-osm` — i dati operativi vengono recuperati da osmfeatures tramite il submodule `wm-osmfeatures` (non da dump)
- Customizzazioni applicative (modelli, action, policy) — verranno affrontate in ticket successivi
- Gestione file registro porte condiviso tra shard (ticket separato da aprire su wm-package)

## Moduli toccati
| File/Modulo | Repo | Operazione |
|---|---|---|
| `.env` | ersaf (principale) | Crea da `.env-example` |
| `.gitmodules` | ersaf (principale) | Aggiunge `wm-osmfeatures` |
| `composer.json` | ersaf (principale) | Aggiunge dipendenza path `wm-osmfeatures` |
| `wm-osmfeatures/` | ersaf (principale) | Nuovo submodule |
| `public/assets/images/` | ersaf (principale) | Copia da ersaf-osm |
| `public/css/custom-nova.css` | ersaf (principale) | Copia da ersaf-osm |
| `public/css/nova-logo.css` | ersaf (principale) | Copia da ersaf-osm |
| `public/favicon.ico` | ersaf (principale) | Copia da ersaf-osm |
