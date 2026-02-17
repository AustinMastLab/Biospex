<?php

/*
 * BIOSPEX CUSTOM DEPLOYMENT TASKS - Option 1 Implementation
 *
 * This file contains custom deployment tasks for the Biospex project.
 *
 * KEY FEATURES:
 * - CI/CD artifact deployment (no server-side building)
 * - Environment-specific configuration uploads
 * - Queue-safe deployments with active job checking
 * - Supervisor management for background processes
 * - Custom Laravel Artisan commands integration
 *
 * Copyright (C) 2014 - 2025, Biospex
 * biospex@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Deployer;

/*
 * =============================================================================
 * COMPOSER DEPENDENCY MANAGEMENT - SAFE INSTALLATION
 * =============================================================================
 */

desc('Install Composer dependencies safely (with --no-scripts to prevent database connection issues)');
task('deploy:vendors', function () {
    if (! test('[ -f {{release_path}}/composer.json ]')) {
        return;
    }

    // Check if this is a development deployment
    $isDevelopment = get('environment') === 'development';

    if ($isDevelopment) {
        // Install with dev dependencies for development environment
        run('cd {{release_path}} && {{bin/composer}} install --prefer-dist --no-progress --no-suggest --optimize-autoloader --no-scripts');
        writeln('✅ Composer dependencies installed with dev packages (development environment)');
    } else {
        // Install without dev dependencies for production
        run('cd {{release_path}} && {{bin/composer}} install --prefer-dist --no-progress --no-suggest --no-dev --optimize-autoloader --no-scripts');
        writeln('✅ Composer dependencies installed safely (production environment - without dev packages)');
    }
});

desc('Run Laravel package discovery after environment is ready');
task('artisan:package:discover', function () {
    cd('{{release_or_current_path}}');
    run('php artisan package:discover --ansi');
    writeln('✅ Laravel package discovery completed');
});

/*
 * =============================================================================
 * CUSTOM LARAVEL ARTISAN TASKS
 * =============================================================================
 */
desc('Running custom database update queries');
task('artisan:app:update-queries', function () {
    cd('{{release_or_current_path}}');
    run('php artisan app:update-queries');  // Custom command for database schema updates
});

desc('Deploying application-specific files and configurations');
task('artisan:app:deploy-files', function () {
    cd('{{release_or_current_path}}');
    run('php artisan app:deploy-files');    // Custom command for file deployments
});

/**
 * Publish Filament assets
 */
desc('Publish Filament assets');
task('artisan:filament:assets', function () {
    cd('{{release_or_current_path}}');
    run('php artisan filament:assets');
    writeln('✅ Filament assets published');
});

desc('Optimize Filament resources and assets');
task('artisan:filament:optimize', function () {
    cd('{{release_or_current_path}}');
    run('php artisan filament:optimize --ansi');
    writeln('✅ Filament optimization completed');
});

/*
 * =============================================================================
 * FILE SYSTEM & PERMISSIONS MANAGEMENT
 * =============================================================================
 */

desc('Setting proper file permissions and clearing logs');
task('set:permissions', function () {
    // Set ownership: ubuntu user, www-data group for web server access
    run('sudo chown -R ubuntu.www-data {{deploy_path}}');

    // Clear all log files to prevent disk space issues
    run('sudo truncate -s 0 {{release_or_current_path}}/storage/logs/*.log');
});

desc('Ensure Supervisor log directory exists');
task('supervisor:ensure-log-dir', function () {
    $logDir = '/var/log/supervisor';
    $appTag = get('app_tag', 'app');        // fallback if not set

    // Create main log dir if missing
    run("sudo mkdir -p {$logDir}");

    // Create app-specific log dir (e.g. /var/log/supervisor/digacad)
    $appLogDir = "{$logDir}/{$appTag}";
    run("sudo mkdir -p {$appLogDir}");

    // Optional: set sane permissions
    run("sudo chown root:root {$logDir}");
    run("sudo chmod 755 {$logDir}");
    run("sudo chmod 755 {$appLogDir}");

    writeln("Supervisor log directory ready: {$appLogDir}");
});

/*
 * =============================================================================
 * SUPERVISOR PROCESS MANAGEMENT
 * =============================================================================
 */

desc('Reload Supervisor configuration (config-only update)');
task('supervisor:reload', function () {
    run('sudo supervisorctl reread');
    run('sudo supervisorctl update');
});

/*
 * =============================================================================
 * CI/CD ARTIFACT DEPLOYMENT - CORE OF OPTION 1 IMPLEMENTATION
 * =============================================================================
 */

desc('Download and extract pre-built assets from GitHub Actions (OPTION 1 CORE FEATURE)');
task('deploy:ci-artifacts', function () {
    // Environment variables automatically provided by GitHub Actions workflow
    $githubActions = ($_ENV['GITHUB_ACTIONS'] ?? getenv('GITHUB_ACTIONS') ?? '') === 'true';

    $githubToken = $_ENV['GITHUB_TOKEN'] ?? getenv('GITHUB_TOKEN') ?? '';
    $githubSha = $_ENV['GITHUB_SHA'] ?? getenv('GITHUB_SHA') ?? '';
    $githubRepo = $_ENV['GITHUB_REPO'] ?? getenv('GITHUB_REPO') ?? 'AustinMastLab/Biospex';

    // If not running in GitHub Actions, skip this task (manual deploy path)
    if (! $githubActions) {
        writeln('⚠️  Skipping deploy:ci-artifacts (not running in GitHub Actions).');
        writeln('    Tip: Use GitHub Actions for artifact-based deploys, or deploy from repository instead.');

        return;
    }

    // Validate required environment variables
    if (empty($githubToken) || empty($githubSha)) {
        // Provide more helpful error message with debugging information
        $envVars = array_keys($_ENV);
        $relevantEnvVars = array_filter($envVars, function ($key) {
            return strpos(strtoupper($key), 'GITHUB') !== false;
        });

        $errorMsg = "GITHUB_TOKEN and GITHUB_SHA environment variables are required.\n";
        $errorMsg .= 'Available GitHub-related env vars: '.implode(', ', $relevantEnvVars)."\n";
        $errorMsg .= 'All env vars count: '.count($envVars);

        throw new \Exception($errorMsg);
    }

    // Artifact naming convention: biospex-{git-sha}
    $artifactName = "biospex-{$githubSha}";
    writeln("Downloading CI artifact: {$artifactName}");

    // Step 1: Get artifact download URL from GitHub API
    $apiUrl = "https://api.github.com/repos/{$githubRepo}/actions/artifacts";
    $response = runLocally("curl -H 'Authorization: Bearer {$githubToken}' -H 'Accept: application/vnd.github.v3+json' '{$apiUrl}?name={$artifactName}&per_page=1'");
    $artifacts = json_decode($response, true);

    // Validate artifact exists
    if (empty($artifacts['artifacts'])) {
        throw new \Exception("No CI artifact found with name: {$artifactName}");
    }

    $downloadUrl = $artifacts['artifacts'][0]['archive_download_url'];
    cd('{{release_or_current_path}}');

    // Step 2: Download, extract, and deploy CI-built assets
    run("curl -L -H 'Authorization: Bearer {$githubToken}' -H 'Accept: application/vnd.github.v3+json' '{$downloadUrl}' -o artifact.zip");
    run('unzip -o -q artifact.zip');       // Extract artifact quietly, overwrite existing files

    // Find the deepest 'deployment-package' and rsync its contents to flatten
    $nestLevelCmd = run('find . -type d -name "deployment-package" -printf "%p\\n" | wc -l');
    $nests = (int) trim($nestLevelCmd);

    if ($nests > 0) {
        // Find innermost (or only) and rsync up (silent flatten)
        $innermost = run('find . -type d -name "deployment-package" | sort -r | head -1');
        $innermost = trim($innermost);
        if (! empty($innermost)) {
            run("rsync -av '{$innermost}/' ./");  // Copy contents to root
            // Clean all deployment-package dirs
            run('find . -type d -name "deployment-package" -exec rm -rf {} +');
        }
    }

    run('rm -f artifact.zip'); // Cleanup artifact file

    writeln('✅ CI artifacts deployed successfully - No server-side building required!');
});

/*
 * =============================================================================
 * OPCACHE MANAGEMENT
 * =============================================================================
 */

desc('Reset OpCache after deployment');
task('opcache:reset', function () {
    $token = $_ENV['OPCACHE_WEBHOOK_TOKEN'] ?? getenv('OPCACHE_WEBHOOK_TOKEN') ?? '';

    if (empty($token)) {
        writeln('⚠️  Skipping OpCache reset: OPCACHE_WEBHOOK_TOKEN not set');

        return;
    }

    // Determine the base URL. If deployer is local, use your local test domain.
    // In production, currentHost()->get('hostname') or a configured app_url is best.
    $environment = get('environment', 'production');
    $domain = ($environment === 'production') ? 'biospex.org' : 'dev.biospex.org';

    // For local testing, you can change this to 'localhost' or 'opcache.test'
    $url = "https://api.{$domain}/opcache/reset";

    try {
        writeln("Triggering OpCache reset via API: {$url}");
        // Use -X POST and -d to send the token in the body
        $response = run("curl -sL -k -X POST -d 'token={$token}' '{$url}'");

        if (str_contains($response, 'successful')) {
            writeln('✅ OpCache reset successful');
        } else {
            throw new \Exception("Response: {$response}");
        }
    } catch (\Exception $e) {
        writeln('❌ OpCache reset failed: '.$e->getMessage());
    }
});

/*
 * =============================================================================
 * DEPLOYMENT VERIFICATION
 * =============================================================================
 */

desc('Verify flat deployment structure');
task('deploy:verify-structure', function () {
    $nestCheck = run('find {{release_path}} -type d -name "deployment-package" | wc -l');
    if ((int) trim($nestCheck) > 0) {
        throw new \Exception("Nesting detected post-deploy: {$nestCheck} dirs. Check CI artifact.");
    }
    writeln('✅ Deployment structure verified: flat and clean');
});

// Task: generate .env from SSM on the remote server
desc('Generate .env from AWS SSM Parameter Store');
task('env:ssm', function () {
    // app name for the SSM path and deploy_path
    $appName = 'biospex';

    // environment is already set on the host ('production' or 'development')
    $environment = currentHost()->get('environment') ?? 'development';

    // we assume generate-env-params is in the home directory of the remote_user
    $remoteUser = get('remote_user');
    $homeDir = "/home/{$remoteUser}";

    $cmd = "cd {$homeDir} && ./generate-env {$appName} {$environment}";

    writeln("Running: {$cmd}");
    run($cmd);
})->once(); // only once per deploy
