# Architecture

## Stack

| Layer | Technology |
| --- | --- |
| Language | PHP 8.5 |
| Framework | Laravel 13 |
| Public and project admin UI | Blade, Livewire 4, Alpine.js |
| Site admin panel | Filament 5, at `/admin/dashboard` (`app/Providers/Filament/AdminPanelProvider.php`) |
| Asset build | Laravel Mix (`webpack.mix.js`), run with `yarn`. Not Vite. |
| Relational database | MySQL |
| Document database | MongoDB, through `mongodb/laravel-mongodb` |
| Queue | Beanstalkd, with workers managed by Supervisor |
| Cache | Redis |
| Real-time to browsers | Laravel Reverb (WebSockets), with `laravel-echo` and `pusher-js` on the front end |
| Real-time from Zooniverse | The Zooniverse public Pusher channel, read by a long-running listener |
| Cloud | AWS: S3 for files, SQS for messages, Lambda for image fetching, OCR, zipping, and reconciliation, SSM for environment configuration |
| Tests | Pest, on SQLite. MongoDB is not available in tests. |

## Code layout

| Path | Contents |
| --- | --- |
| `app/Console/Commands` | Artisan commands: scheduled tasks, SQS listeners, the Pusher listener, and admin tools. |
| `app/Jobs` | Queued jobs. Each job chooses its queue with `onQueue(config('config.queue.*'))`. |
| `app/Services` | Most of the business logic, grouped by domain: `Actor`, `Reconcile`, `Transcriptions`, `Event`, `WeDigBio`, `DarwinCore`, `Csv`, `Api`, and others. |
| `app/Services/Actor` | One class per actor (`Zooniverse`, `GeoLocate`, `TesseractOcr`), created through `ActorFactory`. |
| `app/Models` | Eloquent models. MongoDB models extend `BaseMongoModel`. |
| `app/Http`, `routes/front`, `routes/admin`, `routes/api` | Controllers and routes for the public site, the project admin (`/admin`), and the API (`api.` subdomain, `v1`). |
| `app/Livewire` | Livewire components. |
| `app/Filament` | Filament resources and pages for the site admin panel. |
| `resources/supervisor` | Supervisor config templates, rendered on each deploy. |
| `deploy.php`, `deploy/custom.php` | Deployer recipe. |
| `.ai/` | Project guidelines and rules used to generate `CLAUDE.md` and `AGENTS.md`. |

## Where data lives

### MySQL

Users, groups, projects, expeditions, actors, workflow managers, imports, export and OCR queues (`ExportQueue`, `OcrQueue` and their files), events, WeDigBio, bingo, and downloads. In short, everything relational and administrative.

### MongoDB

The high-volume specimen and transcription data. These models extend `BaseMongoModel`:

| Model | Collection contents |
| --- | --- |
| `Subject` | Specimens, including OCR text. |
| `Occurrence` | Darwin Core occurrence records. |
| `PanoptesTranscription` | Classifications imported from the Zooniverse CSV. |
| `PusherTranscription` | Live classifications from Pusher, used by dashboards. Unique on `classification_id`. |
| `Reconcile` | Reconciled results. |
| `GeoLocateExport` | GeoLocate export data. |

Relationships across the two databases (for example, an `Expedition` in MySQL and its `Subject` documents in MongoDB) are resolved in code. They can't be joined in a query.

### S3

Imports, exports, Zooniverse CSVs, reconciliation output, OCR work files, reports, and uploads. The Zooniverse directory layout is in `config/zooniverse.php` under `directory`.

## Queues

Jobs run on Beanstalkd. Queue names are in `config/config.php` under `queue`. Each queue has its own Supervisor program, rendered from `resources/supervisor/queues.conf`:

| Queue | Workers | Used for |
| --- | --- | --- |
| `default` | 2 | General work, deletes, CSV exports, and starting SQS listeners. |
| `chart` | 1 | AmChart statistics. |
| `classification` | 1 | Zooniverse CSV requests and classification counts. |
| `event` | 1 | Scoreboards and WeDigBio progress. |
| `export` | 2 | Zooniverse export stages. |
| `geolocate` | 1 | GeoLocate exports and downloads. |
| `import` | 1 | Darwin Core imports. |
| `ocr` | 3 | Tesseract OCR stages. |
| `biospex-event` | 1 | Event transcriptions from Pusher. |
| `wedigbio-event` | 1 | WeDigBio transcriptions from Pusher. |
| `pusher-process` | 2 | First step for each Pusher message. |
| `pusher-handler` | 2 | Enriching Pusher classifications and saving them to MongoDB. |
| `reconcile` | 1 | Reconciliation results, transcriptions, and expert review. |
| `workflow` | 1 | Workflow manager tasks. |

## Long-running processes

These also run under Supervisor, from `resources/supervisor/command.conf` and `reverb.conf`:

| Program | Command | Starts |
| --- | --- | --- |
| `panoptes-pusher` | `panoptes:listen` | When `PANOPTES_LISTENER_ENABLED=1`. |
| `{env}-batch-update` | `batch:listen` | On demand. |
| `{env}-export-update` | `export:listen` | On demand. |
| `{env}-reconcile-update` | `reconcile:listen` | On demand. |
| `{env}-ocr-update` | `ocr:listen` | On demand. |
| `{env}-image-trigger-dlq` | `image:listen-dlq` | On demand. |
| `biospex-reverb` | Reverb server | Always. |

`{env}` is `prod`, `dev`, or `loc`. The SQS listeners have `autostart=0`. They show as STOPPED until a job starts them with `sqs:control <queue> --action=start`, and they stop themselves after an idle grace period (`services.aws.*_idle_grace`).

## Scheduled tasks

These are defined in `routes/console.php`:

| Command | When | What it does |
| --- | --- | --- |
| `export:queue` | Every minute | Advances the Zooniverse export queue. |
| `tesseract:ocr-process` | Every minute | Advances the OCR queue. |
| `cache:prune-stale-tags` | Hourly | Cache maintenance. |
| `bingo:clean` | Daily at 10:05 | Removes expired bingo users. |
| `app:check-lambda-reconcile` | Daily at 6:00 | Checks the Lambda reconciliation directory. |
| `workflow:manage` | Daily, production only | Flushes the cache, then runs the actors for every active expedition. |

## External services

| Service | Used for | Code |
| --- | --- | --- |
| Zooniverse Panoptes API | Projects, workflows, subjects, users, and classification CSV exports (OAuth2). | `app/Services/Api/PanoptesApiService.php`, `app/Services/Csv/ZooniverseCsvService.php` |
| Zooniverse Pusher | Live classification feed for all of Zooniverse. BIOSPEX keeps only its own projects. | `app/Console/Commands/ListenerPanoptesPusherCommand.php` |
| Zooniverse Talk | Comments. | `app/Services/Api/ZooniverseTalkApiService.php` |
| GeoLocate | Georeferencing exports and stats. | `app/Services/Actor/GeoLocate` |
| AWS S3, SQS, Lambda, Step Functions | Files, messages, and heavy processing. See [Lambda functions](lambdas.md). | `app/Services/Api/AwsS3ApiService.php`, `app/Services/SqsListenerService.php`, `config/services.php` (`aws`) |
| AWS SSM | Environment variables for each server. | `generate-env`, `push-env-params` |
| Mail | Notifications and error emails. | `config/mail.php` |
| Translation.io | Translations. | `tio/laravel` |
