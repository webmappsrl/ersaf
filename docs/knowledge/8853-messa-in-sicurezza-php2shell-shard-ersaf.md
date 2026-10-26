# Immagine PHP dello shard: pin, pool FPM, porte e aggiornamento

## Come funziona oggi

La procedura generale (ordine snapshot → evidenze → patch, bump pinnato con `--pull`, verifica
`php -v` in ogni container, limiti di upload) vive in wm-package,
`docs/howto/messa-in-sicurezza-php-shard.md`, dal rilascio v1.6.0: il submodule di ersaf punta a
un commit precedente, quindi va letta da `origin/develop` di wm-package. Qui c'è solo ciò che è di
ersaf.

- L'immagine `wm-phpfpm:8.4` è prodotta da `docker/configs/phpfpm/Dockerfile` (base pinnata
  `php:8.4.27-fpm`, Debian trixie, oc:8853). La buildano i servizi `php` e `horizon`, più
  `scout-init` e `laravel` in `local.compose.yml`; `develop.compose.yml` eredita da `compose.yml`.
  Il Dockerfile di `wm-package/docker/configs/phpfpm/` non lo usa nessun compose di ersaf.
- Pool FPM (`www.conf`): `session.upload_progress.enabled=off`, `upload_max_filesize=32M`,
  `post_max_size=128M`, `memory_limit=1024M`. `php.ini` porta numeri diversi (50M/2048M), ma per
  il web vincono i `php_admin_*` del pool; CLI e Horizon usano `php.ini`. I 32M/128M sono quelli
  di camminiditalia (oc:8836): sull'UAT, il 2026-10-23, la tabella `media` era vuota e il bucket
  `wmfe` conteneva 1,3MB.
- **Porte su `127.0.0.1`** in `compose.yml` e `develop.compose.yml` (oc:8853, come maphub
  oc:8842, dove la porta FPM pubblicata era stata la via d'ingresso): prima erano su `0.0.0.0` e
  le proteggeva solo il firewall Hetzner. Apache sull'host raggiunge FPM e MinIO via `localhost`;
  Postgres, Kibana, MinIO e Mailpit da un'altra macchina si raggiungono solo con un tunnel SSH.
  `local.compose.yml` ha ancora Kibana e `artisan serve` su `0.0.0.0` (solo macchina del dev).
- **Ambienti:** la produzione di fatto è l'UAT (`ersaf.uat.maphub.it`, avviato con
  `develop.compose.yml`). Il vhost Apache dell'UAT manda `/kibana/` a `localhost:5608`
  (`DOCKER_KIBANA_PORT` del `.env` del server): la configurazione è solo sull'host, non nel repo.
  Il server `ersaf.dev.maphub.it` è spento dal 23/10/2026, senza essere stato cancellato
  (oc:8853): non gira lo shard ma un prototipo derivato da osm2cai2, mai committato su GitHub.
- **Il deploy non aggiorna l'immagine:** `prod-deploy.yml` non ha mai girato con successo (nessuna
  variabile `HOSTPROD`, esiti del 22/06 e 25/06/2026) e comunque fa solo `git pull` + script. Una
  modifica a `docker/configs/phpfpm/` o ai `ports` arriva sull'UAT solo con `git pull`, build
  `--pull` e `up -d` manuali via SSH.
- **Macchina locale:** il tag `wm-phpfpm:8.4` è condiviso con gli altri shard; l'ultimo che builda
  vince.

## Perché così

- **Verifiche e hardening secondo l'howto di wm-package** (oc:8853, nato con oc:8833): le
  motivazioni di pin, `upload_progress` off e ordine forense stanno lì.

## Come ci siamo arrivati

- **`php:8.4.3-fpm` pinnato** e mai più aggiornato fino a oc:8853: lo shard è rimasto esposto a
  PHP2Shell (CVE-2026-109618/109619). Dal 21 al 23/10/2026 i tentativi di exploit sono arrivati
  fino a PHP (file di sessione da `upload_progress` in `/tmp` del container FPM), senza evidenza di
  successo.
- **Limiti upload a 1024M/1024M** prima di oc:8853.
- **Patch del DEV** (proposta in oc:8853): scartata, perché il server girava un codice diverso
  dallo shard.
- **Rotazione dei segreti dopo la patch:** fuori da oc:8853, la fa Giuseppe quando avrà messo in
  sicurezza tutti i server.
