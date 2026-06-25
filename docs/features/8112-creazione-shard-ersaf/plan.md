> Ticket: oc:8112

# Creazione shard ersaf — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Configurare l'ambiente dev ersaf da zero partendo dal boilerplate Webmapp, aggiungere il submodule `wm-osmfeatures`, copiare gli asset dal vecchio repo `ersaf-osm` e portare l'app in stato funzionante con Nova accessibile.

**Architecture:** Setup sequenziale — prima la configurazione infrastrutturale (`.env`, submodule, Composer), poi l'avvio Docker e le operazioni artisan, infine la copia degli asset. Nessuna dipendenza circolare tra i task.

**Tech Stack:** Laravel 11, Laravel Nova 5, Docker Compose, PostgreSQL/PostGIS, wm-package (submodule path), wm-osmfeatures (submodule path)

## Global Constraints

- `APP_NAME=ersaf` e `DOCKER_PROJECT_DIR_NAME=ersaf` in tutti i contesti
- Commit convention: `feat(oc:8112): ...`
- ⚠️ I commit sono istruzioni testuali — non eseguire `git commit` autonomamente
- Il vecchio repo `ersaf-osm` non va toccato, serve solo come sorgente degli asset
- Porte Docker: default (8000, 9100, 5500, 5173) — verificare conflitti prima di `up`

---

### Task 1: Configurare `.env`

**Files:**
- Create: `.env` (da `.env-example`)

**Interfaces:**
- Produces: `.env` con `APP_NAME`, `DOCKER_PROJECT_DIR_NAME`, `DB_*`, `APP_KEY` compilati — usato da tutti i task successivi

- [ ] **Step 1: Copiare `.env-example` in `.env`**

```bash
cp .env-example .env
```

- [ ] **Step 2: Modificare i valori ersaf-specifici in `.env`**

Aprire `.env` e impostare:

```
APP_NAME=ersaf
DOCKER_PROJECT_DIR_NAME=ersaf
DB_DATABASE=ersaf
DB_USERNAME=ersaf
DB_PASSWORD=ersaf
APP_URL=http://localhost:8000
```

- [ ] **Step 3: Verificare che il file sia corretto**

```bash
grep -E "^(APP_NAME|DOCKER_PROJECT_DIR_NAME|DB_DATABASE|DB_USERNAME)" .env
```

Output atteso:
```
APP_NAME=ersaf
DOCKER_PROJECT_DIR_NAME=ersaf
DB_DATABASE=ersaf
DB_USERNAME=ersaf
```

- [ ] **Step 4: Commit**

```bash
git add .env
git commit -m "feat(oc:8112): configure .env for ersaf shard"
```

---

### Task 2: Aggiungere submodule `wm-osmfeatures`

**Files:**
- Modify: `.gitmodules`
- Modify: `composer.json`
- Create: `wm-osmfeatures/` (directory submodule)

**Interfaces:**
- Produces: `wm-osmfeatures/` disponibile come path locale per Composer

- [ ] **Step 1: Aggiungere il submodule Git**

```bash
git submodule add https://github.com/webmappsrl/wm-osmfeatures.git wm-osmfeatures
```

Output atteso: clona il repo in `wm-osmfeatures/` e aggiorna `.gitmodules`

- [ ] **Step 2: Verificare `.gitmodules` aggiornato**

```bash
cat .gitmodules
```

Output atteso:
```
[submodule "wm-package"]
	path = wm-package
	url = https://github.com/webmappsrl/wm-package.git

[submodule "wm-osmfeatures"]
	path = wm-osmfeatures
	url = https://github.com/webmappsrl/wm-osmfeatures.git
```

- [ ] **Step 3: Aggiungere la dipendenza Composer path in `composer.json`**

Aprire `composer.json` e modificare la sezione `repositories` aggiungendo il path di `wm-osmfeatures`:

```json
"repositories": [
    {
        "type": "composer",
        "url": "https://nova.laravel.com"
    },
    {
        "type": "path",
        "url": "./wm-package"
    },
    {
        "type": "path",
        "url": "./wm-osmfeatures"
    }
]
```

- [ ] **Step 4: Aggiungere il require in `composer.json`**

Nella sezione `require`, aggiungere dopo `"wm/wm-package": "*"`:

```json
"webmapp/wm-osmfeatures": "*"
```

Il nome esatto del package va verificato nel file `composer.json` dentro `wm-osmfeatures/`:

```bash
cat wm-osmfeatures/composer.json | grep '"name"'
```

Usare il valore trovato come chiave nel require.

- [ ] **Step 5: Commit**

```bash
git add .gitmodules wm-osmfeatures composer.json
git commit -m "feat(oc:8112): add wm-osmfeatures submodule and composer path dependency"
```

---

### Task 3: Avviare i container Docker

**Files:**
- Nessun file modificato — operazione di runtime

**Interfaces:**
- Produces: container `php_ersaf`, `db_ersaf`, `redis_ersaf` in stato running — richiesti dai task 4 e 5

- [ ] **Step 1: Verificare assenza conflitti di porta**

```bash
docker ps --format "table {{.Names}}\t{{.Ports}}" | grep -E "8000|9100|5500|5173"
```

Se escono container in conflitto, fermarli prima di procedere:

```bash
docker stop <nome-container-in-conflitto>
```

- [ ] **Step 2: Avviare i container**

```bash
docker compose -f develop.compose.yml up -d
```

Output atteso: tutti i servizi in stato `Started` o `Running`

- [ ] **Step 3: Verificare che i container siano up**

```bash
docker compose -f develop.compose.yml ps
```

Output atteso: `php_ersaf`, `db_ersaf`, `redis_ersaf`, `elasticsearch_ersaf` in stato `running`

- [ ] **Step 4: Generare APP_KEY**

```bash
docker compose -f develop.compose.yml exec php php artisan key:generate
```

Output atteso: `Application key set successfully.`

---

### Task 4: Installare dipendenze e pubblicare migration

**Files:**
- Modify: `database/migrations/` (aggiunti i file stub da wm-package)

**Interfaces:**
- Consumes: container Docker up (Task 3)
- Produces: migration stub di wm-package in `database/migrations/` — richiesto da Task 5

- [ ] **Step 1: Installare le dipendenze Composer**

```bash
docker compose -f develop.compose.yml exec php composer install
```

Output atteso: `Generating optimized autoload files` senza errori. Se `wm-osmfeatures` non ha un package name corretto, Composer mostrerà un warning — correggere il nome nel Task 2 Step 4 se necessario.

- [ ] **Step 2: Pubblicare le migration stub di wm-package**

```bash
docker compose -f develop.compose.yml exec php php artisan vendor:publish --tag="wm-package-migrations"
```

Output atteso: lista di file copiati in `database/migrations/`

- [ ] **Step 3: Verificare che le migration siano state pubblicate**

```bash
ls database/migrations/ | wc -l
```

Output atteso: numero maggiore di quello precedente (le stub di wm-package sono ~62)

- [ ] **Step 4: Commit delle migration pubblicate**

```bash
git add database/migrations/
git commit -m "feat(oc:8112): publish wm-package migration stubs"
```

---

### Task 5: Eseguire migrate e seed

**Files:**
- Nessun file modificato — operazione di runtime sul DB

**Interfaces:**
- Consumes: migration pubblicate (Task 4), container DB up (Task 3)
- Produces: DB ersaf con tutte le tabelle wm-package create

- [ ] **Step 1: Eseguire le migration**

```bash
docker compose -f develop.compose.yml exec php php artisan migrate
```

Output atteso: lista di migration eseguite, nessun errore

- [ ] **Step 2: Eseguire il seed base**

```bash
docker compose -f develop.compose.yml exec php php artisan db:seed
```

Output atteso: `Database seeding completed successfully.`

- [ ] **Step 3: Verificare le tabelle wm-package nel DB**

```bash
docker compose -f develop.compose.yml exec php php artisan tinker --execute="echo implode(', ', DB::select(\"SELECT tablename FROM pg_tables WHERE schemaname='public'\" ) |> array_map(fn(\$t) => \$t->tablename, \$__));"
```

Oppure più semplicemente:

```bash
docker compose -f develop.compose.yml exec db psql -U ersaf -d ersaf -c "\dt" | grep -E "ec_|ugc_|apps|layers|taxonomy"
```

Output atteso: tabelle come `ec_pois`, `ec_tracks`, `ugc_pois`, `apps`, `layers`, `taxonomy_*` presenti

---

### Task 6: Copiare gli asset da ersaf-osm

**Files:**
- Create: `public/assets/images/logo-ersaf.jpg`
- Create: `public/assets/images/android-icon.png`
- Create: `public/assets/images/ios-icon.png`
- Create: `public/assets/images/Maps-Center-Direction-icon.png`
- Create: `public/css/custom-nova.css`
- Create: `public/css/nova-logo.css`
- Modify: `public/favicon.ico`

**Interfaces:**
- Produces: asset ersaf disponibili nell'app — richiesti dalla UI Nova

- [ ] **Step 1: Creare le directory di destinazione**

```bash
mkdir -p public/assets/images
mkdir -p public/css
```

- [ ] **Step 2: Scaricare gli asset da ersaf-osm tramite GitHub raw**

```bash
# Logo principale
curl -L "https://raw.githubusercontent.com/webmappsrl/ersaf-osm/main/public/assets/images/logo-ersaf.jpg" \
  -o public/assets/images/logo-ersaf.jpg

# Icona Android
curl -L "https://raw.githubusercontent.com/webmappsrl/ersaf-osm/main/public/assets/images/android-icon.png" \
  -o public/assets/images/android-icon.png

# Icona iOS
curl -L "https://raw.githubusercontent.com/webmappsrl/ersaf-osm/main/public/assets/images/ios-icon.png" \
  -o public/assets/images/ios-icon.png

# Icona mappa
curl -L "https://raw.githubusercontent.com/webmappsrl/ersaf-osm/main/public/assets/images/Maps-Center-Direction-icon.png" \
  -o "public/assets/images/Maps-Center-Direction-icon.png"

# CSS Nova custom
curl -L "https://raw.githubusercontent.com/webmappsrl/ersaf-osm/main/public/css/custom-nova.css" \
  -o public/css/custom-nova.css

# CSS Nova logo
curl -L "https://raw.githubusercontent.com/webmappsrl/ersaf-osm/main/public/css/nova-logo.css" \
  -o public/css/nova-logo.css

# Favicon
curl -L "https://raw.githubusercontent.com/webmappsrl/ersaf-osm/main/public/favicon.ico" \
  -o public/favicon.ico
```

- [ ] **Step 3: Verificare che i file siano stati scaricati correttamente**

```bash
ls -lh public/assets/images/
ls -lh public/css/custom-nova.css public/css/nova-logo.css
ls -lh public/favicon.ico
```

Output atteso: tutti i file presenti con dimensione > 0 bytes

- [ ] **Step 4: Commit degli asset**

```bash
git add public/assets/images/ public/css/custom-nova.css public/css/nova-logo.css public/favicon.ico
git commit -m "feat(oc:8112): copy ersaf brand assets from ersaf-osm"
```

---

### Task 7: Verifica finale

**Files:**
- Nessun file modificato — verifica di runtime

- [ ] **Step 1: Aprire l'app nel browser**

Navigare su `http://localhost:8000` — deve rispondere con la pagina di benvenuto Laravel o il redirect a Nova.

- [ ] **Step 2: Verificare Nova accessibile**

Navigare su `http://localhost:8000/nova` — deve mostrare la pagina di login Nova senza errori 500.

- [ ] **Step 3: Creare un utente admin e verificare il login**

```bash
docker compose -f develop.compose.yml exec php php artisan tinker
```

Nel tinker:

```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@ersaf.it',
    'password' => bcrypt('password'),
]);
```

Poi fare login su `http://localhost:8000/nova` con `admin@ersaf.it` / `password`.

- [ ] **Step 4: Verificare che i CSS custom di Nova siano caricati**

Dopo il login, aprire i devtools del browser e verificare che `custom-nova.css` e `nova-logo.css` vengano caricati (tab Network, filtrare per `.css`).
