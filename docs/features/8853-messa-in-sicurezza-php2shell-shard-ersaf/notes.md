> Ticket: oc:8853

# Notes — Messa in sicurezza PHP2Shell sullo shard ersaf

## Verifica di compromissione (23/10/2026, su evidenze raccolte prima di ogni rebuild)

**Esito: tentativi di exploit arrivati fino a PHP, nessuna evidenza di successo.** Vale per UAT
e DEV. Evidenze conservate fuori dal repo (`~/evidenze-8853/` sul Mac del dev), più snapshot
Hetzner di entrambi i server fatti prima.

- **Tentativi PHP2Shell arrivati al motore.** Nella `/tmp` dei container FPM ci sono file di
  sessione vuoti (`sess_*`, 6 sull'UAT, 9 sul DEV) creati fra il 21 e il 23/10. Laravel non usa
  le sessioni native di PHP: li crea `session.upload_progress` quando una POST porta il campo di
  upload progress, cioè il vettore di una delle due CVE. Ogni file corrisponde al secondo a una
  `POST /` nei log Apache (es. UAT, 21/10 12:03:56 e 12:49:54, stesso IP), tutte con risposta
  405; due, del 22/10 sera da un altro IP, hanno l'errore `proxy_fcgi` «Partial results» /
  «reading input brigade», la forma dell'exploit già vista su forestas (oc:8833). Un nome è
  identico sui due server (`sess_a1b2c3d4…`): stesso scanner.
- **Nessun segno di successo:** nessun file modificato dal 20/10 nella webroot (compresi
  `vendor/`, `storage/`, `public/`, esclusi log e cache); nessun PHP fuori posto in `public/` e
  `storage/` (solo viste compilate di giugno); nei container solo processi attesi, `docker diff`
  limitato ai file di sessione e a cache/config di composer e psysh datati giugno 2026; crontab
  di host e container invariati; nessun utente nuovo; `authorized_keys` non toccati dopo il
  25/06 (UAT) e il 04/12/2025 (DEV); nessuna connessione in uscita anomala; nessuna risposta 2xx
  ai pattern di exploit (`allow_url_include`, `auto_prepend_file`: 16 risposte 404 e 24 405 per
  server).
- **Porte:** da fuori rispondono solo 22, 80 e 443 su entrambi i server (verificato con `nc`);
  le porte dei container pubblicate su `0.0.0.0` (FPM 9108, Postgres, MinIO, Redis, ES, Kibana)
  non sono raggiungibili da internet. La via d'ingresso di maphub (FPM pubblicato) qui è chiusa
  dal firewall.
- **Lacune:** i log sono alterabili da chi avesse avuto RCE, e il log FPM interno non c'è.
  L'esito pulito non sostituisce la rotazione dei segreti, che resta a Giuseppe per tutti i server.

## Deviazioni dal piano

### Task 5 — ordine sul DEV

Il piano metteva la raccolta delle evidenze del DEV prima dello snapshot. La procedura vuole lo
snapshot per primo: si è seguito l'ordine della procedura (snapshot confermato dal dev, poi
evidenze).

### Task 2 — build locale

Il primo build locale è fallito per disco pieno della VM di Docker Desktop (58,4G su 58,4G,
`apt-get`: «You don't have enough free space»); il pull di `php:8.4.27-fpm` era riuscito. Dopo
la pulizia di container fermi, cache di build e immagini senza tag (approvata dal dev) il build
è riuscito: `php -v` = 8.4.27 nell'immagine e in `php-ersaf`, `php-fpm -tt` con
`upload_max_filesize = 32M`, `post_max_size = 128M`, `session.upload_progress.enabled = 0`.
Strumenti dell'immagine nuova (base Debian trixie): node 20.19.2, GDAL 3.10.3, osm2pgsql 2.1.1,
ImageMagick 7.1.1.

Il clone era nuovo, senza `vendor/`: `composer install` falliva su `laravel/nova` (HTTP 403,
nessuna credenziale Nova in `~/.composer/auth.json`). Su indicazione del dev è stato copiato
`auth.json` da camminiditalia (ignorato da git in ersaf); poi `composer install`, `key:generate`
e `migrate` sul DB locale vuoto. Esito: `php-ersaf`, `horizon-ersaf`, `laravel-ersaf` tutti su
PHP 8.4.27, Laravel 11.41.3, home 302, `/nova/login` 200, «Horizon is running».

## Bug trovati

Nessuno, per ora.

## Decisioni

- **Si replicano le scelte dei ticket precedenti del tag `php2shell`** (oc:8833, oc:8836,
  oc:8842) senza ridiscuterle: indicazione esplicita del dev.
- **L'UAT è la produzione di ersaf.** Il workflow `prod-deploy.yml` non ha mai girato con
  successo (22/06 e 25/06) e il repo non ha la variabile `HOSTPROD`: l'aggiornamento è manuale.
- **Il DEV si spegne, non si patcha.** Non gira lo shard ersaf ma un prototipo derivato da
  osm2cai2 (DB `osm2cai2`, storia ripartita il 04/12/2025, nessun commit in comune con
  `webmappsrl/ersaf`, feature «Rel datapack» in stage dal 17/06). Snapshot Hetzner e power off,
  senza cancellare il server e senza toccare le policy di riavvio (`restart=always`, Apache
  `enabled`): chi lo riaccenderà lo aggiornerà (decisione del dev).
- **Limiti di upload 32M / 128M**, come su camminiditalia: sull'UAT la tabella `media` è vuota
  e il bucket `wmfe` contiene 1,3MB (massimo 1,1MB).
- **Porte su `127.0.0.1`** in `compose.yml` e `develop.compose.yml`, come su maphub (oc:8842).
- **Rotazione dei segreti esclusa:** la fa Giuseppe per tutti i server (scrum del 23/10).
- **Tag Orchestrator di ambiente e di contenuto saltati** su richiesta del dev.
- **Bypass del blocco PHPStan** (2026-10-23T13:05:28Z), confermato esplicitamente dal dev, che
  se ne assume la responsabilità. Motivazione: PHPStan non è installato in ersaf
  (`vendor/bin/phpstan` assente: non è fra le dipendenze di `composer.json`); problema
  preesistente, registrato nei follow-up; il diff di oc:8853 non contiene file PHP.
- **Stima saltata** su richiesta del dev, come negli shard precedenti: la proposta di
  `wm-estimate` (misurato 0,87h + stimato 2,9h = 3,8h) non è stata scritta su Orchestrator.
- **Challenge, punti verificati e scartati come ipotetici:**
  - salto di distribuzione Debian con `php:8.4.27-fpm`: il Dockerfile di ersaf è identico a
    quelli di maphub, camminiditalia e forestas salvo il `FROM`, e lì la build a 8.4.27 è
    riuscita;
  - modifiche locali sull'UAT (`NovaServiceProvider.php`, `footer.blade.php`) come possibile
    backdoor: sono la configurazione del menu Nova, datate 25/06/2026, prima della CVE;
  - `horizon:terminate` con `restart: always` che riavvia Horizon sull'immagine vecchia: le code
    Redis dell'UAT sono vuote, nessun job da interrompere.

## Follow-up

- **PHPStan non è installato in ersaf:** `composer.json` dell'app non richiede
  `phpstan/phpstan` né `larastan` (stanno solo nel `require-dev` di wm-package, che non si
  installa da dipendenza), quindi `vendor/bin/phpstan` non esiste. Il workflow
  `.github/workflows/phpstan.yml` fallirebbe allo step «Run PHPStan», e parte solo su PR che
  toccano file `.php`: non è mai stato eseguito (`gh run list` vuoto).

- Se la feature «Rel datapack» del prototipo DEV arriva sullo shard, rivedere i limiti di upload
  (32M/128M) sulla dimensione reale dei datapack.
