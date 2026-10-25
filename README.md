# Patchling

A small game about software maintenance, served at https://patchling.enricodelazzari.com.

The game itself is plain HTML, CSS and JavaScript (`resources/views/game.blade.php`, `resources/css/app.css`, `resources/js/game.js`). A Laravel 13 app (PHP 8.5) serves it and keeps a leaderboard.

## Leaderboard and sharing

- When a game starts, the client calls `POST /api/games`; the server records the start time.
- When it ends, the client sends the final stats to `POST /api/games/{game}/result`. The server checks they are plausible and that the game didn't finish faster than real time allows (5 seconds per month), then computes the verdict and the score itself.
- Results are anonymous until the player names them with `PATCH /api/results/{result}`, using the `claim_token` returned on creation. Only named results appear in `GET /api/leaderboard`.
- Every result has a public page at `/r/{result}` with Open Graph tags and a generated preview image (`/r/{result}/card.png`), so links shared on social networks show the verdict and score.

Score: `revenue × 10 + months × 10`, plus `(health + happy users) × 5` for games that last all 36 months, plus 500 for a *Healthy site*, minus 150 per successful attack.

The anti-cheat checks stop hand-crafted requests, not someone determined to read the JavaScript.

## Local development

```sh
composer setup   # installs dependencies, creates .env and the SQLite database, builds assets
composer dev     # app server, queue, logs and Vite
php artisan test
```

## Deploying to Laravel Cloud

1. Create an application on [Laravel Cloud](https://cloud.laravel.com) from this repository, on the `main` branch, with PHP 8.5.
2. Attach a database (Serverless Postgres or MySQL); Cloud injects the `DB_*` variables.
3. Keep the default build commands (`composer install`, `npm ci`, `npm run build`) and add `php artisan migrate --force` to the deploy commands.
4. Enable the scheduler on the app cluster: it runs `model:prune` daily to delete abandoned games.
5. Set `APP_URL=https://patchling.enricodelazzari.com`, add the custom domain in Cloud and point its DNS record to Cloud.
6. Turn off GitHub Pages in the repository settings.
