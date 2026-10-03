# Deployment

- This project deploys with GitHub Actions and `deployphp`. Pushes to `main` deploy to production; `development` deploys only when the workflow is run manually. Manual deploys from a local machine use `dep deploy production` or `dep deploy development`.
- Assets are built in CI, so do not expect server-side frontend builds during deployment.
- Commit message tags control versioning and deployment; see `COMMIT_CONVENTIONS.md`.
