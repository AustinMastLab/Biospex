# Biospex

## Overview
Biospex is a Laravel-based web application for managing biodiversity specimen data and citizen science projects. The platform integrates with Zooniverse for crowdsourced transcription, supports automated OCR processing, and provides comprehensive data export capabilities.

## Features
- **Citizen Science Integration**: Seamlessly connects with Zooniverse platform for specimen transcription
- **Automated OCR Processing**: AWS Lambda-based OCR for specimen label extraction  
- **Data Management**: Import, export, and reconciliation of biodiversity specimen data
- **Queue Processing**: Background job processing for large-scale operations
- **Multi-Environment Support**: Separate development and production configurations

## Deployment

This project deploys with GitHub Actions and Deployer 8.

### Quick Deployment Commands

**Production** deploys automatically when `main` changes (normally by merging a `development` → `main` PR). The workflow calculates the next version, deploys, and creates a GitHub release:
```bash
git push origin main
```

**Development** does **not** deploy on push. Run the workflow manually on the `development` branch, either from the Actions tab (Build and Deploy → Run workflow → `development`) or with the GitHub CLI:
```bash
gh workflow run "Build and Deploy" --ref development
```

**Skip deployment** for a push to `main` by adding `[skip deploy]` or `[no deploy]` to the commit message:
```bash
git commit -m "Update documentation [skip deploy]"
```

**From your machine** (needs the deploy key), deploy either environment directly:
```bash
dep deploy production
dep deploy development
```

For the full deployment process, environment configuration (`.env` comes from AWS SSM through the shared [deployer-recipes](https://github.com/AustinMastLab/deployer-recipes) `env:ssm` task), and operations, see [docs/operations.md](docs/operations.md) and the rest of [docs/](docs/README.md).

### Key Features
- ✅ **CI/CD Artifacts**: Assets built in GitHub Actions, no server-side building
- ✅ **Queue Safety**: Automatic job checking prevents interruption of active tasks
- ✅ **Environment Isolation**: Separate queue names for development vs production
- ✅ **Skip Deployment**: Use `[skip deploy]` in commit messages to push without deploying

### Detailed Setup & Configuration

**For complete deployment setup, troubleshooting, and advanced configuration:**  
👉 **[See DEPLOYMENT_SETUP.md](DEPLOYMENT_SETUP.md)**

The detailed guide covers:
- GitHub token configuration
- CI/CD workflow explanation  
- Queue configuration and environment variables
- Troubleshooting common deployment issues
- Testing procedures and best practices

## License
Biospex is open-sourced software licensed under GNU General Public License v3.0.

## Translation
Translation by https://translation.io