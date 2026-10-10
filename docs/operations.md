# Operations

## Servers

| Environment | Host | Branch | App path |
| --- | --- | --- | --- |
| Production | `3.142.169.134` | `main` | `/data/web/biospex` (current release at `/data/web/biospex/current`) |
| Development | `3.138.217.206` | `development` | `/data/web/biospex` |

- **SSH:** as `ubuntu`, using the `biospexaws.pem` key.
- **Databases:** production and development share one MySQL and MongoDB server, `172.31.20.104`, which is private to the VPC. They use separate databases: `biospex` and `biospex_dev`. Check which database you're connected to before running anything destructive.
- **Other sites on the production server:** it also runs other applications under Supervisor and nginx:
  - Digitization Knowledge (CKAN) at `/data/web/digitizationknowledge`
  - Digitization Academy (`da` Horizon)
  - `rapid.biospex.org`
  - `wedigbio-ingest`

  > TODO: who is responsible for each of these.

## Deployment

BIOSPEX deploys with GitHub Actions (`.github/workflows/deploy.yml`) and Deployer (`deploy.php`, `deploy/custom.php`).

| Trigger | Result |
| --- | --- |
| Push or merge to `main` | Calculates the next version, deploys to production, and creates a GitHub release. |
| Run the workflow manually on `development` | Deploys to development. Pushing to `development` does **not** deploy, despite what the root `README.md` says. |
| `[skip deploy]` or `[no deploy]` in the commit message | Pushes without deploying. |
| `[minor]`/`[feature]` or `[major]`/`[breaking]` in the commit message | Bumps the version. See `COMMIT_CONVENTIONS.md`. |
| `dep deploy production` or `dep deploy development` | Deploys manually from a machine that has the key. |

Assets are built in CI and downloaded as an artifact; nothing is built on the server.

### Usual workflow

1. Create an issue, then a branch for it: `gh issue develop N --base development --name Biospex-N --checkout`.
2. Open a PR into `development`, merge it, and run the development deploy.
3. Check the change on development.
4. Open a PR from `development` into `main`. Merging it deploys to production.

### What a deploy does

The task order is in `deploy.php`:

1. **Prepare the release.** Generate `.env` from SSM (`env:ssm`), install Composer dependencies, and download the CI assets.
2. **Set up Laravel.** Run `storage:link`, `package:discover`, and `filament:assets`. Then run `app:deploy-files --current-path=…`, which renders the Supervisor configs into `storage/app/supervisor`.
3. **Update the database.** Run `migrate`, then `app:update-queries` (see below).
4. **Rebuild caches.** Clear and rebuild the config, route, view, and event caches, plus `filament:optimize`.
5. **Publish.** Switch the `current` symlink. No OPcache reset is needed: nginx passes PHP the real release path (`$realpath_root`), so each release is new to OPcache.
6. **Restart processes.** Run `supervisorctl reread` and `supervisorctl update`, then `queue:restart`, `reverb:restart`, and restart the Panoptes listener if it's running. Then verify the structure.

### Shared deploy tooling (deployer-recipes)

`.env` generation lives in the shared [AustinMastLab/deployer-recipes](https://github.com/AustinMastLab/deployer-recipes) Composer package, which every AustinMastLab Deployer site uses:

| Site | Repository | SSM path |
| --- | --- | --- |
| BIOSPEX | AustinMastLab/Biospex | `/biospex/<environment>` |
| Digitization Academy | AustinMastLab/DigitizationAcademy | `/digitizationacademy/<environment>` |
| WeDigBio reports | AustinMastLab/wedigbio-reports | `/wedigbio-reports/<environment>` |

- **Deployer 8 required.** The package's `env:ssm` task sends its script to the server in a shell heredoc, which Deployer 7 breaks. From v1.0.1 the package refuses to install alongside Deployer 7.
- **Nothing to install on the servers.** The script is sent with each deploy; there is no longer a `~/generate-env` in the server's home directory (removed 2026-10-05). Each server only needs the AWS CLI and `jq`.
- **Changing it.** Edit the package, tag a new version (`v1.0.2`, …), then run `composer update austinmastlab/deployer-recipes` in each site and deploy. Test on BIOSPEX or Digitization Academy development first; WeDigBio reports has no development deployment.
- **First deploy after a change to the output format** rewrites `.env` once (values unchanged) and makes one backup; later deploys report `No changes`.

### One-off data updates

`app:update-queries {operation}` is an empty dispatcher for one-time data fixes:

1. Add the operation to `AppUpdateQueriesCommand`.
2. Set `set('update_queries_operation', '<name>')` in `deploy.php`.
3. Deploy. The operation runs after the migrations.
4. In a follow-up PR, remove the operation and reset the setting to `''`.

## Environment configuration

- **Where `.env` comes from.** Each server's `.env` is generated from AWS SSM Parameter Store, at the path `/biospex/production` or `/biospex/development`, by the `env:ssm` deploy task (see [Shared deploy tooling](#shared-deploy-tooling-deployer-recipes); `set('ssm_app', 'biospex')` in `deploy.php`). The task runs the package's `generate-env` script on the server with the server's IAM role, so secrets never pass through GitHub. The script refuses to write an empty file, writes only after every value has been read, keeps the file's permissions (`600` on BIOSPEX), and keeps the last 5 `.env.backup.*` files in `shared/`.
- **Server IAM roles.** `ProdEC2Role` and `DevEC2Role` (policies `ProdEC2DeployPolicy` and `DevEC2DeployPolicy`) allow only what the servers use: SSM read and KMS decrypt for the three sites' parameter paths, the site's S3 bucket and SQS queues, Lambda concurrency (read and set), and starting the zip Step Function. Lambda code, alias, and trigger management are done from a developer machine as `LocalDeployUser`, not from the servers.
- **Rolling back.** `dep rollback <environment>` only switches the `current` symlink; it doesn't regenerate `.env`. If a rollback needs an older `.env`, copy the matching `shared/.env.backup.*` back over `shared/.env`.
- **Pushing a local file to SSM:** `vendor/bin/push-env-params biospex <environment>` pushes each line of `.env.aws.<environment>` as a `SecureString` parameter. Those files hold real values, and git ignores them through `/.env.*`. `vendor/bin/remove-env-params biospex <environment>` deletes every parameter under `/biospex/<environment>`, after you type the environment name to confirm; it can't be undone.
- **Supervisor values.** Some variables (`APP_SERVER_USER`, `APP_TAG`, `PANOPTES_LISTENER_ENABLED`, the queue names, and others) are written into the Supervisor configs by `app:deploy-files`. After changing one of them, run `app:deploy-files`, then `supervisorctl reread` and `supervisorctl update`.
- **`env.example`** lists the settings SSM sets, with local placeholder values, plus a few local-only keys (AWS keys, cache and session drivers). When you add a setting to SSM, add it there too.

## Processes and logs

| Task | Command |
| --- | --- |
| Status of all programs | `sudo supervisorctl status` |
| Restart the Pusher listener | `sudo supervisorctl restart biospex:panoptes-pusher` |
| Restart queue workers (after code changes) | `php artisan queue:restart` |
| Start or stop an SQS listener | `php artisan sqs:control <queue-key> --action=start` (or `stop`) |
| Count jobs in a Beanstalkd queue | `php artisan queue:count` |
| Stop or start a Lambda in every environment | `php artisan app:lambda-control <function> stop` (or `start`) |
| Stop or start one environment's Lambda triggers | `php artisan app:lambda-control <function> stop --alias=dev` (run from your machine) |
| Purge the AWS queues for an environment | `php artisan app:awsqueue-purge` |

- **Laravel logs:** `/data/web/biospex/shared/storage/logs`. These are truncated on each deploy.
- **Supervisor program logs:** `/var/log/supervisor/biospex/`. Supervisor rotates them itself, at 50 MB with 3 backups. Logrotate doesn't manage them.
- **Stopped programs:** the `prod-*` SQS listener programs normally show as STOPPED. They start when needed and stop after an idle period.

## Local development

- **Environment:** WSL Ubuntu 22, with the app at `/data/web/biospex` and the local domain `biospex.test`. The API is at `api.biospex.test`.
- **Services needed:** PHP 8.5, MySQL, MongoDB, Beanstalkd, Redis, and Supervisor. The local Supervisor configs come from the same templates, with the `loc-` prefix.
- **Assets:** `yarn install`, then `yarn run dev` (or `yarn run production`, or `yarn run watch`).
- **Tests:** `php artisan test --compact`. Tests use Pest on SQLite, with the sync queue and the array cache. MongoDB isn't available, so code that depends on MongoDB must be faked or kept out of tests.
- **Formatting:** `vendor/bin/pint --dirty`.
- **Project guidelines:** these are in `.ai/guidelines` and `.ai/rules`. Laravel Boost compiles them into `CLAUDE.md` and `AGENTS.md`.

## Known pitfalls

- **Shared database server.** Development work can affect production performance, and a mistaken connection string can point at production data.
- **Duplicate transcriptions.** The live Pusher feed, job retries, and the nightly backfill can all write the same classification. Unique indexes on `pusher_transcriptions.classification_id` and `event_transcriptions` prevent duplicates, so keep them in place.
- **MongoDB indexes.** MongoDB can't hold two indexes on the same key with different options. To change one, drop the old index first. Restoring a database dump can bring old indexes back.
- **Large `distinct` queries** hit MongoDB's 16 MB limit. Use an aggregation (`$group` with `$count`) instead.
- **Pusher quota.** If Pusher reports error 4004 (over quota), the listener stops reconnecting for an hour.
- **Upgrade packages that extend framework commands together with Laravel.** On 2026-10-05, Digitization Academy's Horizon workers crashed after a Laravel update because `horizon:work` extends `queue:work`, Laravel added a new option to `queue:work`, and Horizon was pinned to an old version (`5.35.*`) that didn't declare it. After a `composer update`, check `composer outdated --direct` for packages held back by tight constraints, and after deploying check that queue workers stay up, not just that Supervisor shows RUNNING.
- **Disable the `dev` and `loc` Lambda SQS triggers after testing.** Enabled triggers poll SQS constantly and use up the monthly AWS free tier quickly. See [Lambda functions](lambdas.md#environments-aliases-queues-and-buckets).
- **SQS listeners are started by jobs.** If an export, OCR, or reconcile run stalls, check whether its listener is running and whether its Lambda concurrency is set to 0.
