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

    $operation = trim((string) get('update_queries_operation', ''));

    // Keep task available for ad-hoc runs; only execute when operation is provided.
    if ($operation === '') {
        writeln('⚠️  Skipping app:update-queries: update_queries_operation is not configured.');
        writeln('   Set it in deploy.php or pass it at runtime for manual task execution.');

        return;
    }

    run("php artisan app:update-queries {$operation}");
    writeln("✅ app:update-queries completed for operation: {$operation}");
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
    $token = $_ENV['API_TOKEN'] ?? getenv('API_TOKEN') ?? '';

    if (empty($token)) {
        writeln('⚠️  Skipping OpCache reset: API_TOKEN not set');

        return;
    }

    $environment = get('environment', 'production');
    $domain = ($environment === 'production') ? 'api.biospex.org' : 'devapi.biospex.org';
    $url = "https://{$domain}/v1/opcache/reset";

    try {
        writeln("Triggering OpCache reset via API: {$url}");

        $response = run(
            'curl -sS -k -X POST '.
            '--connect-timeout 10 --max-time 30 '.
            "-H 'Authorization: Bearer {$token}' ".
            "-H 'Accept: application/json' ".
            "-w '\nHTTP_STATUS=%{http_code}\n' ".
            "'{$url}'"
        );

        if (str_contains($response, 'successful')) {
            writeln('✅ OpCache reset successful');

            return;
        }

        throw new \Exception("Response: {$response}");
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

/**
 * Task: generate .env from AWS SSM Parameter Store during deployment
 *
 * WHERE IT'S USED:
 * - Executes during every deployment via Deployer (after code is deployed)
 * - Runs once per deployment on the remote server
 *
 * WHY IT'S USED:
 * - Generates the .env file dynamically from AWS Systems Manager Parameter Store
 * - Keeps sensitive environment variables out of the repository (security best practice)
 * - Ensures each environment (production/development) gets the correct .env configuration
 * - Separated script in home directory prevents accidental commits of secrets
 */
desc('Generate .env from AWS SSM Parameter Store');
task('env:ssm', function () {
    $appName = 'biospex';

    // Target environment determined by the deployment host configuration
    $environment = currentHost()->get('environment') ?? 'development';

    // Locate and execute the generate-env script from remote user's home directory
    $remoteUser = get('remote_user');
    $homeDir = "/home/{$remoteUser}";

    $cmd = "cd {$homeDir} && ./generate-env {$appName} {$environment}";

    writeln("Running: {$cmd}");
    run($cmd);
})->once(); // only once per deploy
