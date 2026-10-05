# Overview

BIOSPEX helps natural history collections get their specimen records transcribed by volunteers. Collections import images and metadata for their specimens. BIOSPEX groups the specimens into batches, sends each batch to [Zooniverse](https://www.zooniverse.org) for volunteers to transcribe, and then collects, reconciles, and exports the results.

- Production: https://biospex.org
- Repository: https://github.com/AustinMastLab/Biospex
- License: GPL v3 (`COPYING.txt`)

## Who uses it

| User | What they do |
| --- | --- |
| Project managers (collection staff) | Create projects and expeditions, import specimen data, start OCR and exports, download results, run events. |
| Volunteers | Transcribe on Zooniverse; visit BIOSPEX for project pages, events, scoreboards, and bingo games. |
| Site administrators | Manage everything through the Filament admin panel at `/admin/dashboard`. |

## Main concepts

| Concept | Model | Store | Meaning |
| --- | --- | --- | --- |
| Group | `Group` | MySQL | A team of users who share projects. |
| Project | `Project` | MySQL | A collection's transcription effort, with a public page, resources, and expeditions. |
| Subject | `Subject` | MongoDB | One specimen: its image URL plus Darwin Core data from the import. |
| Occurrence | `Occurrence` | MongoDB | Darwin Core occurrence data that belongs to subjects. |
| Import | `Import` | MySQL | A Darwin Core Archive (DwC-A) upload, or a recordset from a URL, that creates subjects. |
| Expedition | `Expedition` | MySQL | A batch of subjects sent to one Zooniverse workflow. |
| Actor | `Actor`, `ActorExpedition` | MySQL | A processing step attached to an expedition: Zooniverse, GeoLocate, or Tesseract OCR. Actor classes are in `app/Services/Actor`. |
| Workflow manager | `WorkflowManager` | MySQL | Marks an expedition as active, so the nightly `workflow:manage` command runs its actors. |
| Panoptes project | `PanoptesProject` | MySQL | Links an expedition to its Zooniverse project ID and workflow ID. |
| Panoptes transcription | `PanoptesTranscription` | MongoDB | One volunteer classification, imported from the Zooniverse CSV export. |
| Reconcile | `Reconcile` | MongoDB | The reconciled (consensus) result for a subject across volunteers. Supports expert review. |
| Pusher transcription | `PusherTranscription` | MongoDB | A live classification from the Zooniverse Pusher feed, enriched for dashboards. |
| Event | `Event`, `EventTeam`, `EventUser`, `EventTranscription` | MySQL | A timed transcription competition between teams, with a live scoreboard. |
| WeDigBio event | `WeDigBioEvent`, `WeDigBioProject`, `WeDigBioEventTranscription` | MySQL | The global WeDigBio transcription event, its participating projects, and progress counts. |
| Bingo | `Bingo`, `BingoUser`, `BingoWord` | MySQL | A transcription bingo game played during events. |
| GeoLocate | `GeoLocateForm`, `GeoLocateExport`, and related models | MySQL and MongoDB | Exports records to the GeoLocate georeferencing service and downloads the results. |

## The lifecycle of an expedition

1. **Import.** A project manager uploads a Darwin Core Archive. `DwcBatchImportJob` creates `Subject` and `Occurrence` documents in MongoDB.
2. **OCR (optional).** Tesseract OCR runs on the subject images through AWS Lambda and stores the label text on each subject.
3. **Create the expedition.** The manager selects subjects and links the expedition to a Zooniverse project and workflow.
4. **Export.** BIOSPEX fetches the images, builds a Zooniverse manifest CSV, and zips it for download. The project manager uploads it to Zooniverse.
5. **Transcription.** Volunteers transcribe on Zooniverse. Each classification arrives live through Pusher and updates dashboards, event scoreboards, and WeDigBio counts.
6. **Reconcile.** Each night, BIOSPEX requests the Zooniverse classification CSV. A Lambda reconciles it, and BIOSPEX imports transcriptions, reconciled results, and counts.
7. **Expert review and download.** Managers can review problem records, then download reconciled CSVs and reports.

[Pipelines](pipelines.md) describes steps 2 and 4 to 6 in detail.
