> Ticket: oc:8853

# Messa in sicurezza PHP2Shell (CVE-2026-109618/109619) sullo shard ersaf

## Cosa cambia

Replica su ersaf della messa in sicurezza PHP2Shell già fatta su forestas (oc:8833),
camminiditalia (oc:8836) e maphub (oc:8842), seguendo
`wm-package/docs/howto/messa-in-sicurezza-php-shard.md` (v1.6.0, su `origin/develop` di
wm-package: il submodule di ersaf punta a un commit precedente che non la contiene). Le scelte
seguono quelle dei ticket precedenti del tag `php2shell`. Lavoro a step: **locale → UAT**;
il DEV si spegne.

Ambienti (rilevati il 23/10 via SSH, sola lettura):

| Server | Ruolo | PHP oggi | Azione |
|---|---|---|---|
| UAT-ersaf (138.201.94.203, `ersaf.uat.maphub.it`, Hetzner GEOBOX2) | **produzione** di fatto | 8.4.3 (FPM e Horizon) | bump a 8.4.27 + pulizia pool + porte su `127.0.0.1` |
| DEV-ersaf (49.13.142.17, `ersaf.dev.maphub.it`, Hetzner GEOBOX2) | sviluppo | 8.4.7 (FPM) / 8.4.15 (Horizon) | verifica di compromissione, snapshot, **power off** (non cancellato) |

1. **Pin dell'immagine PHP:** `docker/configs/phpfpm/Dockerfile` passa da `php:8.4.3-fpm` a
   `php:8.4.27-fpm`. Il tag `wm-phpfpm:8.4` è buildato da quel Dockerfile e usato da `php` e
   `horizon` (`compose.yml`, `develop.compose.yml` che li estende, `local.compose.yml` che
   aggiunge `scout-init` e `laravel`). Sull'UAT i container girano da `develop.compose.yml`
   nella cartella del repo (`/root/html` è un link a `/var/www/html`), con il Dockerfile del
   repo e un profilo di estensioni che gli corrisponde.
2. **Pulizia permanente del pool FPM** (`docker/configs/phpfpm/www.conf`), con i valori di
   camminiditalia: `php_admin_flag[session.upload_progress.enabled] = off` (oggi non impostato,
   quindi attivo) e `upload_max_filesize` / `post_max_size` da 1024M a **32M / 128M**.
   `memory_limit` (1024M nel pool, 2048M in `php.ini` per la CLI) e `php.ini` non si toccano. Misura fatta sull'UAT: tabella `media`
   vuota, bucket MinIO `wmfe` 1,3MB in 20 file (massimo 1,1MB, `icons.json`).
3. **Porte su `127.0.0.1`**, come su maphub (oc:8842): ogni porta pubblicata nei compose che
   girano sui server (`compose.yml`, `develop.compose.yml`) passa da `${PORTA}:...` a
   `127.0.0.1:${PORTA}:...`. Sull'UAT oggi sono su `0.0.0.0` Postgres, FPM (9108), serve/vite,
   MinIO, Kibana e Mailpit, protette solo dal firewall Hetzner. Apache le raggiunge tutte via
   `localhost` (FPM su `localhost:9108`, MinIO su `localhost:9002`).
4. **Applicazione sull'UAT, manuale:** il workflow `prod-deploy.yml` non ha mai girato con
   successo (nessuna variabile `HOSTPROD`) e comunque non ricostruisce l'immagine. In ordine:
   snapshot Hetzner (lo fa il dev), raccolta evidenze, `horizon:terminate` e attesa, check del
   disco, `git pull` del branch con la patch, tag dell'immagine corrente come `wm-phpfpm:8.4-pre-8853`
   (sull'ID da `docker inspect`), rebuild con `--pull`, ricreazione dei servizi, verifica
   `php -v` dentro ogni container PHP.
5. **Verifica di compromissione** su UAT e DEV sulle evidenze lato host (log Apache dal 20/10,
   webroot montata compresi i file ignorati da git — `vendor/`, `storage/`, `public/` —, cron,
   utenti, chiavi SSH), come negli shard precedenti; sull'UAT, prima del recreate, anche
   `docker diff`, `ps aux`, crontab e `docker logs` di `php-ersafuat` e `horizon-ersafuat`,
   perché il recreate azzera il loro filesystem.

## Perché

Il 21/10/2026 sono state pubblicate due vulnerabilità critiche del motore PHP («PHP2Shell»):
esecuzione di comandi senza autenticazione, exploit pubblico, attacchi già in corso. La falla è
nel motore e non nel codice: ersaf gira su 8.4.3 (UAT) e 8.4.7/8.4.15 (DEV), tutte vulnerabili,
con `session.upload_progress` attivo e limiti di upload a 1024M, la combinazione che l'exploit
sfrutta. Allo scrum del 23/10 il lavoro è stato dichiarato a priorità altissima, con ersaf
citato fra gli shard ancora da fare. L'UAT è l'ambiente che fa da produzione per il cliente.

## Requisiti

- [ ] `docker/configs/phpfpm/Dockerfile` fissato a `php:8.4.27-fpm`
- [ ] `www.conf`: `php_admin_flag[session.upload_progress.enabled] = off`
- [ ] `www.conf`: `upload_max_filesize = 32M`, `post_max_size = 128M`
- [ ] `compose.yml` e `develop.compose.yml`: tutte le porte pubblicate legate a `127.0.0.1`
- [ ] Locale: build con `--pull`, `php -v` = 8.4.27 e `php-fpm -tt` coi valori nuovi dentro i
      container PHP, sito su
- [ ] UAT, prima di qualsiasi operazione: snapshot Hetzner confermato dal dev, evidenze raccolte
      e salvate fuori dai repo
- [ ] UAT: `horizon:terminate` e attesa; check del disco libero prima della build
- [ ] UAT: `docker tag` dell'immagine corrente come `wm-phpfpm:8.4-pre-8853` prima del rebuild
- [ ] UAT: rebuild sempre con `--pull`; verifica con `php -v` **dentro** `php-ersafuat` e
      `horizon-ersafuat` (mai l'esito della build)
- [ ] Criterio di done sull'UAT (come oc:8836/oc:8842): `php -v` = 8.4.27 in tutti i container
      PHP, pool coi valori nuovi (`php-fpm -tt`), POST oltre il limite respinto con il worker
      vivo, Horizon attivo, sito su, `docker ps` senza porte su `0.0.0.0` per i servizi ersaf
- [ ] DEV: verifica di compromissione lato host, snapshot Hetzner, power off (il server resta,
      non si cancella)
- [ ] Esito della verifica di compromissione di UAT e DEV registrato nel ticket

## Rischi

Esiti della Fase: challenge. I punti verificati come ipotetici (salto di Debian con 8.4.27,
modifiche locali dell'UAT come backdoor, job interrotti da Horizon) sono in `notes.md`.

- **Il rollback `wm-phpfpm:8.4-pre-8853` riporta in vita la falla** (8.4.3 + upload_progress attivo +
  1024M): per un flusso legittimo rotto dai nuovi limiti non si torna mai all'immagine vecchia,
  si correggono i valori in `www.conf` e si rifà build + recreate. `wm-phpfpm:8.4-pre-8853` serve solo se
  l'immagine nuova non parte.
- **Modifiche locali sull'UAT:** il repo sul server ha `NovaServiceProvider.php` e
  `footer.blade.php` modificati e non committati (menu Nova, 25/06/2026, prima della CVE: non
  sono una backdoor). Non toccano i file della patch, quindi il `git pull` non va in conflitto;
  restano come sono.
- **Porte dichiarate in due file:** `develop.compose.yml` estende `compose.yml` e le liste
  `ports` si sommano; una riga lasciata su `0.0.0.0` in uno dei due lascia la porta pubblica o
  fa fallire l'avvio. Si cambiano entrambi i file insieme e si verifica con `docker ps`.
- **Il deploy non ricostruisce l'immagine:** la patch committata non arriva ai server da sola.
  Applicazione manuale documentata nel piano.

## Out of scope

- Rotazione dei segreti (password DB, chiavi MinIO): la fa Giuseppe per tutti i server a fine
  giro (scrum del 23/10). `JWT_SECRET` non si ruota mai.
- Patch del DEV: non gira lo shard ersaf ma un prototipo derivato da osm2cai2 (DB `osm2cai2`,
  modelli `CaiHut`, `HikingRoute`, `Club`…; storia ripartita da un «Initial commit» del
  04/12/2025, nessun commit in comune con `webmappsrl/ersaf`; HEAD locale `f13975b` del 17/06 e
  la feature «Rel datapack» in stage, mai committata). Si spegne dopo lo snapshot, senza toccare
  le policy di riavvio: chi lo riaccenderà lo aggiornerà.
- Il Dockerfile di wm-package (`wm-package/docker/configs/phpfpm/Dockerfile`, base mobile
  `php:8.4-fpm`): nessun compose di ersaf lo usa.
- `php.ini` e `memory_limit`: non si toccano, come negli shard precedenti.
- Configurazione di Apache sull'host (`LimitRequestBody 0`): non si tocca, come negli shard
  precedenti.
- Aggiornamento del sistema operativo dei server (Ubuntu 20.04): oc:8835.
- Build dell'immagine in CI + registry: follow-up già registrato in oc:8842.

## Moduli toccati

| File / operazione | Repo / dove |
|---|---|
| `docker/configs/phpfpm/Dockerfile` (pin 8.4.27) | ersaf |
| `docker/configs/phpfpm/www.conf` (upload_progress off, 32M/128M) | ersaf |
| `compose.yml`, `develop.compose.yml` (porte su `127.0.0.1`) | ersaf |
| `docs/features/8853-messa-in-sicurezza-php2shell-shard-ersaf/` (overview, plan, notes) | ersaf |
| Snapshot, evidenze, build `--pull`, recreate, tag `wm-phpfpm:8.4-pre-8853`, verifica compromissione | server UAT-ersaf (fuori repo) |
| Evidenze, verifica compromissione, snapshot, power off | server DEV-ersaf (fuori repo) |
| wm-package, wm-osmfeatures | **non toccati** |
