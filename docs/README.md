# BIOSPEX Documentation

These pages describe how BIOSPEX works and how it is run, for anyone new to the project. Read them in order:

1. [Overview](overview.md): what BIOSPEX is, who uses it, and the main concepts.
2. [Architecture](architecture.md): the stack, data stores, queues, and external services.
3. [Pipelines](pipelines.md): how data moves through Zooniverse, Pusher, exports, OCR, and reconciliation.
4. [Lambda functions](lambdas.md): the AWS Lambdas, their repositories, triggers, and deployment.
5. [Operations](operations.md): servers, deployment, environment configuration, logs, and day-to-day tasks.

Other documents in the repository root:

| File | What it covers |
| --- | --- |
| `COMMIT_CONVENTIONS.md` | Commit message tags that control versioning and deployment. |
| `DEPLOYMENT_SETUP.md` | GitHub Actions and deployphp setup in detail. |
| `LAMBDA_FIX.md` | Plan for splitting image fetching across two Lambdas. |
| `WEDIGBIO_MIGRATION_RUNBOOK.md` | Deployment order for the WeDigBio migration phases. |

## Open questions

These items aren't in the code, so they need to come from the maintainer:

- **Accounts and ownership.** Who owns the AWS account, the Zooniverse account, the domains, the GitHub organization (`AustinMastLab`), and the mail provider. Who pays for them, and who to contact.
- **Credentials.** Where they are kept, such as a password manager or AWS SSM. Record the location only, never the values.
- **Lambda releases.** How a new Lambda version is published and moved to the `prod` alias. See [Lambda functions](lambdas.md).
- **Shared server.** The production server also hosts other sites. Who is responsible for each one?
- **History.** Design decisions and known problems that a new developer should know about.
