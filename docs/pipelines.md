# Pipelines

Most of BIOSPEX's work happens in background pipelines. Several of them hand work to AWS Lambda through SQS and get the results back through an SQS listener. The pattern is the same each time:

```
job → SQS trigger queue → Lambda → SQS update queue → {env}-*-update listener → result job
```

SQS queue names are built from the environment: `{prefix}-image-trigger`, `{prefix}-export-update`, and so on. The prefix is `prod`, `dev`, or `loc`. The full list is in `config/services.php` under `aws.sqs`. Lambda concurrency limits are under `aws.lambdas`, and `app:lambda-control` can stop or restore them. [Lambda functions](lambdas.md) describes each function, its trigger, and its repository.

## 1. Live classifications (Zooniverse Pusher)

The live feed powers the WeDigBio dashboard, event scoreboards, and WeDigBio progress.

```
Zooniverse Pusher channel
  → panoptes:listen (Supervisor: panoptes-pusher)
  → ProcessPanoptesPusherDataJob          [pusher-process]
      looks up the project ID and workflow ID in PanoptesProject and WeDigBioProject;
      stops if neither matches
      ├─ PusherClassificationJob          [pusher-handler]  fetches the subject and user from Panoptes (cached for an hour)
      │    → PusherTranscriptionJob       [pusher-handler]  saves a PusherTranscription to MongoDB
      ├─ EventTranscriptionJob            [biospex-event]   counts the classification for events → ScoreboardJob → ScoreboardEvent (Reverb)
      └─ WeDigBioEventTranscriptionJob    [wedigbio-event]  counts the classification for the WeDigBio event
```

Things to know:

- **The feed covers all of Zooniverse,** many classifications per second. Each message contains `classification_id`, `project_id`, `workflow_id`, `user_id`, `subject_ids`, and `subject_urls`, with the IDs as strings. Most messages are discarded in the first job.
- **The listener reconnects itself.** On repeated errors it backs off and goes dormant for an hour. It emails `MAIL_FROM_ADDRESS` at most once an hour, using a lock file in `storage/framework`.
- **Duplicates are blocked at the database level.** `pusher_transcriptions` is unique on `classification_id`, and `event_transcriptions` is unique on (`classification_id`, `event_id`, `team_id`, `user_id`). Jobs can retry and the nightly backfill can overlap with the live feed, so these constraints are what prevent double counting.
- **The nightly reconcile chain backfills the feed.** It runs `ZooniversePusherJob` (section 4), which fills in anything the live feed missed.

## 2. Zooniverse export

An export turns an expedition's subjects into a Zooniverse-ready zip of images plus a manifest CSV. State is tracked in `ExportQueue` (`stage`) and `ExportQueueFile` (`processed`).

The numbers below are enum values: `ActorExpedition.state` is `App\Enums\ActorExpeditionState` (1 `Exported`, 2 `Processing`, 3 `Complete`), and `ExportQueue.stage` is `App\Enums\ExportQueueStage` (1 `ProcessingImages`, 2 `BuildingCsv`, 3 `CreatingArchive`, 4 `CreatingReport`, 5 `DeletingFiles`). The process monitor shows each stage's label.

```
workflow:manage → Zooniverse actor (ActorExpedition state 1) → ZooniverseExportBuildQueueJob   creates ExportQueue and its files
export:queue (every minute) → ZooniverseExportQueueService
  stage 1  ZooniverseExportProcessImagesJob   [export]  one message per image → {prefix}-image-trigger → BiospexImageFetcher Lambda
           export:listen ← {prefix}-export-update      → ZooniverseExportImageUpdateJob marks each file
  stage 2  ZooniverseExportBuildCsvJob        [export]  builds the manifest, then ZooniverseZipTriggerService:
             up to 8,000 files → {prefix}-export-zip-trigger → BiospexZipCreator
             more files        → ZipBatchOrchestrator Step Function → BiospexZipCreator ×4 → BiospexZipMerger
           export:listen ← zip-ready                    → ZooniverseExportZipResultJob
  stage 4  creates the Download record → ZooniverseExportCreateReportJob → ZooniverseExportDeleteFilesJob
```

- **Failed image fetches go to `{prefix}-image-trigger-dlq`.** `image:listen-dlq` marks those files as failed.
- **Archive.org images use their own queue,** `{prefix}-ia-image-trigger`, handled by `InternetArchiveImageFetcher` with a lower concurrency.
- **Restarting a failed export:** `app:export-stage {queueId} --stage=N` reruns stage 1–5 manually (without `--stage`, the queue's current stage), and `export:queue {expeditionId}` resets an expedition.

## 3. Tesseract OCR

```
import or manual request → TesseractOcrCreateJob   creates OcrQueue and its files
tesseract:ocr-process (every minute) → TesseractOcrQueueService
  TesseractOcrProcessJob [ocr]   starts the ocr-update and image-trigger-dlq listeners;
                                 sends each image to {prefix}-image-trigger (taskType "ocr")
  BiospexImageFetcher            saves the image to S3 zooniverse/lambda-ocr-wip/{queueId}/{subjectId}.jpg
  S3 ObjectCreated → BiospexOcrProcessor   runs Tesseract, deletes the image
  ocr:listen ← {prefix}-ocr-update → TesseractOcrUpdateJob   saves OCR text to the Subject
  when every file is processed → TesseractOcrCompleteJob
```

OCR can be turned off with `OCR_ENABLED=false` (`config/config.php` → `ocr_enabled`). `app:clear-subjects` clears OCR data from subjects that are assigned to expeditions.

## 4. Zooniverse CSV, reconciliation, and nightly backfill

This pipeline brings Zooniverse's official classification data into BIOSPEX, reconciles it, and refreshes counts.

```
workflow:manage → Zooniverse actor (ActorExpedition state 2, zooniverse.enabled) → ZooniverseCsvJob [classification]
  asks Panoptes to generate a classification export, then waits 6 hours
  → ZooniverseProcessCsvJob     retries every 2 hours while Zooniverse is still building the export
  → ZooniverseCsvDownloadJob    streams the CSV to S3 zooniverse/lambda-reconciliation/{expeditionId}.csv;
                                starts reconcile:listen
  → S3 ObjectCreated → BiospexReconcile312 Lambda   writes reconciled/, transcript/, summary/ CSVs
  reconcile:listen ← {prefix}-reconcile-update → LabelReconciliationJob [reconcile]
     → ReconcileProcessAll: chain of
         ZooniverseTranscriptionJob        imports PanoptesTranscriptions from the transcript CSV
         ZooniversePusherJob               backfills PusherTranscriptions, event transcriptions, and WeDigBio counts (last 3 days)
         ZooniverseClassificationCountJob  updates counts → AmChartJob
```

- **Rerunning by hand:** `zooniverse:reconcile-chain {ids?}` sends expeditions straight to `{prefix}-reconcile-trigger`, which also runs `BiospexReconcile312`. `zooniverse:explained` does the same for "explained" reconciliation, which `ReconcileProcessExplained` handles.
- **Skipping expeditions:** an expedition's **Skip Panoptes API** (`skip_api`) and **Skip reconcile** (`skip_reconcile`) toggles, under Zooniverse Processing on its Filament edit page, exclude it from these steps. Only the admin panel can set them; the project owners' expedition form ignores them.
- **Expert review** (`expert:review`, `ExpertReview*Job`) builds review records from reconciled data, and `ExpertReconcileReviewPublishJob` publishes them.

## 5. Download batches

A project manager can download a large export as several smaller zips.

```
ZooniverseExportDownloadBatchJob → {prefix}-batch-trigger → BiospexBatchCreator   writes batch/{file}-part{n}.zip
batch:listen ← {prefix}-batch-update → ZooniverseExportBatchResultJob
```

## 6. Darwin Core import

```
upload or URL → DwcUriImportJob / RecordsetImportJob → DwcBatchImportJob [import]
  parses meta.xml and the core and extension files (app/Services/DarwinCore)
  → creates Subject and Occurrence documents in MongoDB
  → TesseractOcrCreateJob (when OCR is enabled)
```

Large files are processed in batches. The thresholds are under `config/config.php` → `dwc`. `dwc:import` requeues an import that failed.

## 7. GeoLocate

```
GeoLocateExportJob → GeoLocate service
app:geolocate-stats → GeoLocateStatsJob → GeoLocateDownloadJob → GeoLocateResultCsvJob
```

Everything runs on the `geolocate` queue. Configuration is in `config/geolocate.php`.
