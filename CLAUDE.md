# ersaf

Shard wm-package per ERSAF. La produzione di fatto è l'UAT (`ersaf.uat.maphub.it`).

## Regole del repo

- **Le modifiche a `docker/configs/phpfpm/` o ai `ports` dei compose non arrivano ai server col
  deploy**: sull'UAT servono `git pull`, build `--pull` e `up -d` manuali via SSH, e la verifica
  è `php -v` dentro ogni container PHP. Dettaglio in
  [docs/knowledge/8853-messa-in-sicurezza-php2shell-shard-ersaf.md](docs/knowledge/8853-messa-in-sicurezza-php2shell-shard-ersaf.md).

## Conoscenza

| Argomento | Cosa copre | Pagina |
|---|---|---|
| Immagine PHP dello shard | Pin dell'immagine, pool FPM, porte su localhost, aggiornamento manuale sull'UAT | [docs/knowledge/8853-messa-in-sicurezza-php2shell-shard-ersaf.md](docs/knowledge/8853-messa-in-sicurezza-php2shell-shard-ersaf.md) |
