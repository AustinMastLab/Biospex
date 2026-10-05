# Commit Message Conventions

This document explains how to use commit message conventions to control automated versioning and deployment in our CI/CD
pipeline.

## Overview

Our CI/CD system uses **Semantic Versioning (SemVer)** to automatically create releases and deploy to production.
Version numbers follow the format `MAJOR.MINOR.PATCH` (e.g., `1.2.3`).

## How It Works

1. **Development Branch**: Pushing to `development` does not deploy. Run the workflow manually on `development` (Actions → Build and Deploy → Run workflow, or `gh workflow run "Build and Deploy" --ref development`) to deploy to the development environment (Host: `3.138.217.206`).
   - Operates using `dev-` prefixed SQS queues.
2. **Main Branch**: Push to `main` → Creates GitHub release with auto-incremented version → Automatically deploys to
   production (Host: `3.142.169.134`)
   - Operates using `prod-` prefixed SQS queues.
3. **Version Control**: Commit messages determine which part of the version number gets incremented

## Version Bump Rules

### Patch Version (Default)

**Format**: Regular commit message without special tags  
**Example**: `1.2.3` → `1.2.4`

```bash
git commit -m "Fix user authentication bug"
git commit -m "Update documentation typos"
git commit -m "Improve error handling"
```

### Minor Version

**Format**: Include `[minor]` or `[feature]` in commit message  
**Example**: `1.2.3` → `1.3.0`

```bash
git commit -m "Add new dashboard widgets [minor]"
git commit -m "Implement user profile feature [feature]"
git commit -m "Add export functionality [minor]"
```

### Major Version

**Format**: Include `[major]` or `[breaking]` in commit message  
**Example**: `1.2.3` → `2.0.0`

```bash
git commit -m "Redesign entire user interface [major]"
git commit -m "Remove deprecated API endpoints [breaking]"
git commit -m "Change database schema structure [major]"
```

## Special Controls

### Skip Deployment

**Format**: Include `[skip deploy]` or `[no deploy]` in commit message  
**Effect**: Code is pushed but no deployment occurs

```bash
git commit -m "Update README documentation [skip deploy]"
git commit -m "Work in progress - not ready [no deploy]"
git commit -m "Add development notes [skip deploy]"
```

### Multiple Changes

When you have multiple commits since the last release, the **highest version bump** takes precedence:

```bash
# If you have these commits:
git commit -m "Fix login bug"              # patch
git commit -m "Add new feature [minor]"    # minor  
git commit -m "Update docs"                # patch

# Result: Minor version bump (1.2.3 → 1.3.0)
```

## Workflow Examples

### Development Workflow

```bash
# 1. Work on development branch
git checkout development
git commit -m "Add user settings page [feature]"
git push origin development
# → No deploy. Run the workflow manually on development to deploy:
gh workflow run "Build and Deploy" --ref development

# 2. Test and iterate
git commit -m "Fix settings validation"
git push origin development
gh workflow run "Build and Deploy" --ref development
```

### Production Release Workflow

```bash
# 3. When ready for production
git checkout main
git merge development
git push origin main
# → Creates release (e.g., 1.3.0) and deploys to production automatically
```

### Hotfix Workflow

```bash
# For urgent production fixes
git checkout main
git commit -m "Fix critical security vulnerability"
git push origin main
# → Creates patch release (e.g., 1.3.1) and deploys immediately
```

## Version Examples

| Current Version | Commit Message                       | New Version        |
|-----------------|--------------------------------------|--------------------|
| `1.0.0`         | `Fix login issue`                    | `1.0.1`            |
| `1.0.1`         | `Add search feature [minor]`         | `1.1.0`            |
| `1.1.0`         | `Redesign dashboard [major]`         | `2.0.0`            |
| `2.0.0`         | `Update documentation [skip deploy]` | No release created |
| `2.0.0`         | `Fix bug + Add feature [minor]`      | `2.1.0`            |

## Best Practices

### ✅ Good Commit Messages

```bash
git commit -m "Fix user profile image upload"
git commit -m "Add real-time notifications [feature]"
git commit -m "Refactor authentication system [major]"
git commit -m "Update development setup guide [skip deploy]"
```

### ❌ Avoid These

```bash
git commit -m "stuff"
git commit -m "fixes"
git commit -m "update"
git commit -m "wip [major]"  # Don't use major for work in progress
```

### Commit Message Guidelines

1. **Be Descriptive**: Clearly explain what changed
2. **Use Present Tense**: "Add feature" not "Added feature"
3. **Keep It Concise**: Aim for 50-72 characters
4. **Use Tags Wisely**: Only use `[major]` for truly breaking changes
5. **Test First**: Ensure your changes work before committing to main

## Troubleshooting

### "No version bump occurred"

- Check if your commit message contains `[skip deploy]` or `[no deploy]`
- Verify you're pushing to the `main` branch for production releases

### "Wrong version increment"

- Review commit messages since the last tag
- The highest version bump in any commit message determines the final increment
- Use `git log --oneline <last-tag>..HEAD` to see commits that will be included

### "Deployment failed"

- Check the GitHub Actions logs in the repository
- Verify all required secrets and variables are configured
- Ensure the commit doesn't contain deployment skip tags

## Manual Release Creation

If you need to create a release manually or override the automatic versioning:

```bash
# Create and push a tag manually
git tag -a 1.5.0 -m "Manual release v1.5.0"
git push origin 1.5.0

# Or use GitHub CLI
gh release create 1.5.0 --title "Release 1.5.0" --notes "Manual release"
```

## Summary

- **Development**: `git push origin development`, then run the workflow manually on `development` to deploy
- **Production**: `git push origin main` → Auto-create release → Auto-deploy to production
- **Version Control**: Use `[minor]`, `[major]`, or `[breaking]` in commit messages
- **Skip Deployment**: Use `[skip deploy]` or `[no deploy]` when needed
- **Default**: No tags = patch version increment

## Frequently Asked Questions

For questions or issues with the deployment process, check the GitHub Actions logs or contact the development team.