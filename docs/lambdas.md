# AWS Lambda functions

BIOSPEX hands image fetching, OCR, zipping, and reconciliation to AWS Lambda in `us-east-2`. The function code is in separate repositories under the `AustinMastLab` GitHub organization. This page records how each function is triggered, how it reports back, and how it's deployed.

This information was taken from AWS and the repositories on 2026-10-05. Check AWS again before relying on the details.

## Functions

| Function | Repository | Runtime | Memory / timeout | Reserved concurrency |
| --- | --- | --- | --- | --- |
| `BiospexImageFetcher` | [BiospexImageFetcher](https://github.com/AustinMastLab/BiospexImageFetcher) | Node.js 22 | 1024 MB / 180 s | 100 |
| `InternetArchiveImageFetcher` | [InternetArchiveImageFetcher](https://github.com/AustinMastLab/InternetArchiveImageFetcher) | Python 3.12 | 1024 MB / 180 s | 5 |
| `BiospexOcrProcessor` | [BiospexOcrProcessor](https://github.com/AustinMastLab/BiospexOcrProcessor) | Node.js 22 | 2048 MB / 120 s | 100 |
| `BiospexZipCreator` | [BiospexZipCreator](https://github.com/AustinMastLab/BiospexZipCreator) | Node.js 22 | 3072 MB / 900 s | 10 |
| `BiospexZipMerger` | [BiospexZipMerger](https://github.com/AustinMastLab/BiospexZipMerger) | Node.js 22 | 3072 MB / 900 s | 1 |
| `BiospexBatchCreator` | [BiospexBatchCreator](https://github.com/AustinMastLab/BiospexBatchCreator) | Node.js 22 | 5120 MB / 900 s | 1 |
| `BiospexReconcile312` | [BiospexReconcile312](https://github.com/AustinMastLab/BiospexReconcile312) | Python 3.12 | 1024 MB / 900 s | 8 |
| `BiospexLabelReconcile` | [BiospexLabelReconcile](https://github.com/AustinMastLab/BiospexLabelReconcile) | Python 3.10 | 1024 MB / 900 s | 8 |

`BiospexLabelReconcile` is the old reconciliation function. It is no longer used, having been replaced by `BiospexReconcile312`, but it is still deployed.

The **ZipBatchOrchestrator** repository ([ZipBatchOrchestrator](https://github.com/AustinMastLab/ZipBatchOrchestrator)) holds an AWS Step Functions state machine, not a Lambda. It runs `BiospexZipCreator` and `BiospexZipMerger` for large exports.

## Environments: aliases, queues, and buckets

Each function (except `BiospexZipMerger`) has three **aliases**: `prod`, `dev`, and `loc`. Each alias is connected to its own environment's SQS queue or S3 bucket:

| Environment | S3 bucket | SQS prefix |
| --- | --- | --- |
| Production | `biospex-app` | `prod-` |
| Development | `biospex-dev` | `dev-` |
| Local | `biospex-loc` | `loc-` |

Reserved concurrency is set on the **function**, not on the alias. All three environments share it. Stopping a function with `app:lambda-control` (concurrency 0) stops it for production, development, and local at the same time.

The SQS triggers for `dev` and `loc` are normally **disabled**. To test a Lambda pipeline on development or locally, enable that alias's event source mapping first. Disable it again afterwards.

## How each function is triggered

| Function | Trigger | Input | Reports to |
| --- | --- | --- | --- |
| `BiospexImageFetcher` | SQS `{prefix}-image-trigger` | One message per image: `taskType` (`export` or `ocr`), `accessURI`, `s3Bucket`, `s3Path`, `updatesQueueUrl`, and size limits. | Exports: `{prefix}-export-update`. OCR: no message on success, because the S3 upload starts OCR; failures are reported. Messages that keep failing go to `{prefix}-image-trigger-dlq`. |
| `InternetArchiveImageFetcher` | SQS `{prefix}-ia-image-trigger` | The same as `BiospexImageFetcher`, for images hosted on archive.org. | The same as `BiospexImageFetcher`. |
| `BiospexOcrProcessor` | S3 `ObjectCreated` under `zooniverse/lambda-ocr-wip/` | The image uploaded by the fetcher. The queue, file, and subject IDs travel in the S3 object metadata. | `{prefix}-ocr-update`, with the OCR text. Deletes the image afterwards. |
| `BiospexZipCreator` | SQS `{prefix}-export-zip-trigger` (small exports), or the Step Function (large exports) | `processDir`, `s3Bucket`, `queueId`, `updatesQueueUrl`, and an optional `startIndex`/`endIndex`. | `{prefix}-export-update` with `zip-ready` or `partial-zip-ready`. Writes `export/{processDir}.zip`. |
| `BiospexZipMerger` | The Step Function | The keys of the partial zips, `finalKey`, and `queueId`. | `{prefix}-export-update` with `zip-ready`. Deletes the partial zips. |
| `BiospexBatchCreator` | SQS `{prefix}-batch-trigger` | `downloadId`, `exportPath`, `totalSize`, and `updatesQueueUrl`. | `{prefix}-batch-update`, with the list of batch files. Writes `batch/{file}-part{n}.zip`. |
| `BiospexReconcile312` | S3 `ObjectCreated` for `zooniverse/lambda-reconciliation/*.csv`, or SQS `{prefix}-reconcile-trigger` | `bucket`, `expeditionId`, and `explanations`. | `{prefix}-reconcile-update`. Writes to `zooniverse/reconciled/`, `transcript/`, `summary/`, and `explained/`. |

On the BIOSPEX side, each `*-update` queue is read by its own listener: `export:listen`, `ocr:listen`, `batch:listen`, `reconcile:listen`, and `image:listen-dlq` for the dead-letter queue. These listeners start on demand. See [Architecture](architecture.md#long-running-processes).

## Large exports: the ZipBatchOrchestrator Step Function

`ZooniverseZipTriggerService::sendZipTrigger()` decides how an export is zipped:

- **Up to `services.aws.zip_threshold` files (8,000):** a single message on `{prefix}-export-zip-trigger`, and `BiospexZipCreator` builds one zip.
- **Above that:** it starts the `ZipBatchOrchestrator` state machine. The ARN is hard-coded in the service.
  1. **SplitFiles** splits the files into four fixed ranges: 0–4,999, 5,000–9,999, 10,000–14,999, and 15,000–19,999.
  2. **MapState** runs `BiospexZipCreator` for each range, up to four at once, producing partial zips.
  3. **MergeZips** runs `BiospexZipMerger`, which combines the parts into `export/{processDir}.zip` and reports `zip-ready`.

The four ranges cover at most 20,000 files, which matches the maximum expedition size (`config/config.php` → `expedition_size`). If that limit is ever raised, the state machine has to change too.

There is only one state machine for all environments, and it calls the functions without an alias, so it always runs their unpublished (`$LATEST`) version.

## Deploying a function

Each repository except ZipBatchOrchestrator has a `deploy.sh` that you run from the repository directory. `InternetArchiveImageFetcher` builds its zip inside the `public.ecr.aws/lambda/python:3.12` Docker image, because Pillow needs Lambda-compatible native libraries; start Docker before running its script.

1. It builds `function.zip`, using `npm install --production` for Node.js, or a Python 3.12 build for `BiospexReconcile312`.
2. It asks before uploading to AWS. The upload uses `us-east-2`, with `biospex-loc` as a temporary bucket.

`BiospexReconcile312`'s script targets the function name in the `FUNCTION_NAME` environment variable, defaulting to `BiospexReconcile312`.

A deploy updates `$LATEST`. Check in AWS how each alias (`prod`, `dev`, `loc`) points at a version before you assume a deploy is live in production.

> TODO: record the release procedure for publishing a version and moving the `prod` alias, if one is used.

The Step Function definition is in `ZipBatchOrchestrator/step-function.json`. It is updated in the AWS console or with `aws stepfunctions update-state-machine`.

## Known gaps

- **`config/services.php` → `aws.lambdas` still lists `BiospexLabelReconcile`** and not `BiospexReconcile312`. As a result, `app:lambda-control` controls the old function, not the active one.
- **The pause checks look up function names that no longer exist.** `TesseractOcrQueueService` checks `BiospexTesseractOcr` and `ZooniverseExportQueueService` checks `BiospexImageProcess`. AWS returns "not found", and the code treats that as "ready", so pausing the real functions doesn't hold back new queues.
- **The automatic stop passes the wrong value.** `TesseractOcrProcessJob` and `ZooniverseExportProcessImagesJob` pass the concurrency value (`100`) instead of the function name to `app:lambda-control`, so it never stops anything.
- **Stale event source mapping:** a mapping from `ImageProcessQueue` to a function named `ImageProcessor` remains in AWS, which looks like a leftover from an earlier design.
- **The repository READMEs are partly out of date.**
  - The ZipBatchOrchestrator README says the state machine runs `BiospexBatchCreator`; the deployed definition does not.
  - The BiospexBatchCreator README mentions a `ZooniverseBatchTriggerService`; the trigger is `ZooniverseExportDownloadBatchJob`.
