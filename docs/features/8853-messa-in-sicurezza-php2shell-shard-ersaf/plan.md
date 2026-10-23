> Ticket: oc:8853

# Piano — Messa in sicurezza PHP2Shell (CVE-2026-109618/109619) sullo shard ersaf

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans per eseguire
> questo piano task per task. Gli step usano checkbox (`- [ ]`).

**Goal:** portare l'UAT di ersaf (la sua produzione) su `php:8.4.27-fpm`, con il pool FPM ripulito
e le porte dei servizi su `127.0.0.1`; verificare l'assenza di compromissione su UAT e DEV e
spegnere il DEV.

**Architettura:** replica di oc:8842 (maphub) e oc:8836 (camminiditalia) secondo la procedura
condivisa; modifiche solo nel repo ersaf (`docker/configs/phpfpm/`, `compose.yml`,
`develop.compose.yml`), applicazione manuale sull'UAT via SSH.

**Tech stack:** Docker Compose, PHP-FPM 8.4 (immagine ufficiale), Laravel 11 + Horizon, Apache
sull'host come reverse proxy.

**Spec:** `docs/features/8853-messa-in-sicurezza-php2shell-shard-ersaf/overview.md` +
`wm-package/docs/howto/messa-in-sicurezza-php-shard.md` v1.6.0 (leggibile con
`git -C wm-package show origin/develop:docs/howto/messa-in-sicurezza-php-shard.md`).

## Vincoli globali

- **Nessuna operazione git senza approvazione esplicita del dev**: i commit indicati sono
  istruzioni testuali, si eseguono solo dopo il review-gate.
- Server: UAT-ersaf `root@138.201.94.203` (repo in `/var/www/html/ersaf`, compose
  `develop.compose.yml`, `APP_NAME=ersafuat`); DEV-ersaf `root@49.13.142.17` (alias SSH
  `dev.ersaf`).
- Ordine sull'UAT: **snapshot (dev)** → evidenze → **Horizon giù** → check disco → `git pull` →
  tag `wm-phpfpm:8.4-pre-8853` → build `--pull` → recreate → verifiche. Mai invertire.
- Rebuild **sempre con `--pull`** (senza, la base in cache produce un'immagine identica alla
  vulnerabile con «build riuscita»).
- La verifica è sempre `php -v` **dentro** il container e `php-fpm -tt` per il pool, mai l'esito
  della build.
- Valori: `FROM php:8.4.27-fpm`; `upload_max_filesize = 32M`; `post_max_size = 128M`;
  `php_admin_flag[session.upload_progress.enabled] = off`; `memory_limit` e `php.ini` invariati.
- Rollback per un flusso rotto dai limiti: correggere `www.conf` e rifare build + recreate,
  **mai** tornare a `wm-phpfpm:8.4-pre-8853` (riporta la falla). `wm-phpfpm:8.4-pre-8853` solo se l'immagine nuova non parte.
- Le evidenze si salvano **fuori dai repo**, in `~/evidenze-8853/` sulla macchina locale.
- Il DEV non si patcha e non si toccano le sue policy di riavvio (decisione dev, `notes.md`).

## Review Focus

Modi di fallimento che i check del piano non esercitano, dal più probabile:

1. Porta lasciata su `0.0.0.0` in uno dei due compose, o conflitto fra le due liste `ports`
   sommate dall'`extends` → coperto dal controllo `docker compose config` di Task 1 e da
   `docker ps` di Task 4.
2. Kibana o MinIO raggiunti da Apache con un indirizzo diverso da `localhost` (vhost) → coperto
   dal controllo del vhost in Task 3 e dalle prove HTTP di Task 4.
3. Import via Nova con file sopra 32M → nessun flusso reale oggi (tabella `media` vuota);
   follow-up in `notes.md`.
4. `horizon` ricreato ma ancora sull'immagine vecchia → coperto dal `php -v` per container di
   Task 4.
5. Accesso remoto di qualcuno a Postgres/Kibana dell'UAT senza tunnel → dopo il bind serve
   `ssh -L`; nessun consumatore noto, segnalato nella chiusura.

---

### Task 1 — Repo: pin, pool FPM e porte

**Files:**
- Modify: `docker/configs/phpfpm/Dockerfile:1`
- Modify: `docker/configs/phpfpm/www.conf:541-543` (più una riga nuova)
- Modify: `compose.yml:11,27,103`
- Modify: `develop.compose.yml:8-9,45,69-70,93-94`

- [ ] **Step 1.1:** `Dockerfile:1`: `FROM php:8.4.3-fpm` → `FROM php:8.4.27-fpm`.
- [ ] **Step 1.2:** `www.conf`: `upload_max_filesize = 1024M` → `32M`, `post_max_size = 1024M` →
  `128M`, aggiungere `php_admin_flag[session.upload_progress.enabled] = off` subito sotto;
  `memory_limit = 1024M` invariato.
- [ ] **Step 1.3:** in `compose.yml` e `develop.compose.yml` ogni voce di `ports` da
  `${VAR}:porta` (o `porta:porta`) a `127.0.0.1:${VAR}:porta`, come oc:8842 (es.
  `- 127.0.0.1:${DOCKER_KIBANA_PORT:-5601}:5601`). `local.compose.yml` non si tocca.
- [ ] **Step 1.4:** verifica della composizione, con un `.env` provvisorio copiato da
  `.env-example` se manca:
  `docker compose -f develop.compose.yml config | grep -E 'host_ip|published'`
  Atteso: ogni porta con `host_ip: 127.0.0.1`, nessuna senza.
- [ ] **Step 1.5 (commit, solo dopo review-gate):**
  `fix(oc:8853): pin php:8.4.27-fpm, hardening del pool FPM e porte su 127.0.0.1 (PHP2Shell)` —
  include anche `docs/features/8853-*/` e quanto prodotto in Task 6; PR verso `develop`, poi
  `develop` → `main` per l'UAT.

### Task 2 — Verifica in locale

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-2--build-locale)

**Files:** nessuno (operazioni Docker locali in `/Users/peco/Documents/BackEnd/ersaf`).

- [ ] **Step 2.1:** `.env` locale da `.env-example` con `APP_NAME=ersaf` e
  `DOCKER_PROJECT_DIR_NAME=ersaf` (non committato).
- [ ] **Step 2.2:** `docker compose -f local.compose.yml build --pull php`
  Atteso: il log di build scarica `php:8.4.27-fpm`.
- [ ] **Step 2.3:** `docker compose -f local.compose.yml up -d`
- [ ] **Step 2.4:** `for c in $(docker compose -f local.compose.yml ps -q php horizon laravel); do docker exec $c php -v | head -1; done`
  (in `local.compose.yml` horizon non ha `container_name`, quindi si passa dagli ID).
  Atteso: `PHP 8.4.27` per tutti.
- [ ] **Step 2.5:** `docker exec php-ersaf php-fpm -tt 2>&1 | grep -E "upload_max_filesize|post_max_size|upload_progress"`
  Atteso: `32M`, `128M`, `session.upload_progress.enabled = off`.
- [ ] **Step 2.6:** app viva: `docker exec php-ersaf php artisan about` senza errori; se lo stack
  locale non si avvia per mancanza di dati, annotarlo come divergenza e fermarsi ai controlli
  2.4–2.5.

### Task 3 — UAT: prerequisiti ed evidenze (sola lettura)

**Files:** evidenze in `~/evidenze-8853/uat/`.

- [ ] **Step 3.1:** conferma dal dev che lo snapshot Hetzner dell'UAT è completato.
- [ ] **Step 3.2:** stato di partenza: `docker ps`, `docker inspect` dell'immagine di
  `php-ersafuat` e `horizon-ersafuat` (ID), `git status`, `git log -1`.
- [ ] **Step 3.3:** per `php-ersafuat` e `horizon-ersafuat`: `docker diff`, `ps aux`,
  `crontab -l`, `docker logs --since 2026-10-20`, file recenti in `/tmp`.
- [ ] **Step 3.4:** lato host: log Apache di `ersaf.uat.maphub.it` dal 20/10 (con le rotazioni),
  file modificati dal 20/10 nella webroot **compresi** `vendor/`, `storage/`, `public/`,
  crontab di root e www-data, utenti, `authorized_keys` (mtime), connessioni in ascolto.
- [ ] **Step 3.5:** vhost Apache: indirizzi verso cui fa proxy (`grep -n ProxyPass\|proxy:fcgi`),
  atteso solo `localhost`.
- [ ] **Step 3.6:** copia delle evidenze in locale (`scp`), fuori da ogni repo.

### Task 4 — UAT: applicazione e verifica

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-4--vhost-kibana)

**Files:** nessuno (SSH sull'UAT, dopo il merge su `main` approvato al review-gate).

- [ ] **Step 4.1:** **Horizon giù per primo**:
  `docker exec horizon-ersafuat php artisan horizon:terminate`, attesa di qualche secondo.
- [ ] **Step 4.2:** `df -h /` — atteso spazio libero sufficiente per la build (>5GB; oggi 268G).
- [ ] **Step 4.3:** `cd /var/www/html/ersaf && git pull` — atteso fast-forward con i 4 file della
  patch; i 2 file modificati in locale (`NovaServiceProvider.php`, `footer.blade.php`) restano.
- [ ] **Step 4.4:** `docker tag <ID immagine da Step 3.2> wm-phpfpm:8.4-pre-8853`
- [ ] **Step 4.5:** `docker compose -f develop.compose.yml build --pull php horizon`
- [ ] **Step 4.6:** `docker compose -f develop.compose.yml up -d` (ricrea i servizi con immagine
  o porte cambiate; niente `--force-recreate` su tutto).
- [ ] **Step 4.7:** `for c in php-ersafuat horizon-ersafuat; do docker exec $c php -v | head -1; done`
  Atteso: `PHP 8.4.27`.
- [ ] **Step 4.8:** `docker exec php-ersafuat php-fpm -tt 2>&1 | grep -E "upload_max_filesize|post_max_size|upload_progress"`
  Atteso: `32M`, `128M`, `off`.
- [ ] **Step 4.9:** `docker ps --format '{{.Names}}\t{{.Ports}}' | grep ersafuat`
  Atteso: nessuna porta su `0.0.0.0` o `:::`.
- [ ] **Step 4.10:** prove HTTP: home e `/nova/login` di `https://ersaf.uat.maphub.it` rispondono
  (200/302); un file dal bucket via `/wmfe/` risponde; Kibana via proxy, se configurato,
  risponde.
- [ ] **Step 4.11:** POST di 140MB su `https://ersaf.uat.maphub.it/` → rifiuto pulito; subito
  dopo la home risponde ancora (worker vivo). POST di 20MB → non respinto per dimensione.
- [ ] **Step 4.12:** `docker exec php-ersafuat php artisan horizon:status` → running;
  `php artisan queue:failed` vuoto.

### Task 5 — DEV: evidenze, snapshot, spegnimento

> ⚠️ L'implementazione ha deviato da questo task: [notes.md](notes.md#task-5--ordine-sul-dev)

**Files:** evidenze in `~/evidenze-8853/dev/`.

- [ ] **Step 5.1:** verifica di compromissione lato host come Step 3.3–3.4 (container
  `php81-ersafdev`, `horizon-ersafdev`; vhost `ersaf.dev.maphub.it`), evidenze copiate in locale.
- [ ] **Step 5.2:** il dev esegue lo snapshot Hetzner del DEV e conferma.
- [ ] **Step 5.3:** il dev esegue il power off dal pannello Hetzner (il server non si cancella).
- [ ] **Step 5.4:** `ssh -o ConnectTimeout=5 dev.ersaf true` e `curl -sI https://ersaf.dev.maphub.it`
  → atteso: nessuna risposta.

### Task 6 — Chiusura documentale

**Files:**
- Modify: `docs/features/8853-*/notes.md`
- Create: pagina in `docs/knowledge/` secondo la Fase: update-context di wm-plan
- `CLAUDE.md`: il repo non ne ha uno; proposta al dev se crearlo con la riga di regola

- [ ] **Step 6.1:** esito sintetico della verifica di compromissione di UAT e DEV (senza dati
  sensibili) in `notes.md` e nelle note del ticket.
- [ ] **Step 6.2:** proporre al dev la regola (come oc:8836/oc:8842): le modifiche a
  `docker/configs/phpfpm/` e ai compose non arrivano ai server col deploy, richiedono build
  `--pull` + recreate manuali sull'UAT.
- [ ] **Step 6.3:** aggiornare `notes.md` (deviazioni, esiti) e segnalare nel ticket che Postgres
  e Kibana dell'UAT ora si raggiungono solo via tunnel SSH.
