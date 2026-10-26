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

function withUmask(string $command): string
{
    return "umask 002 && $command";
}

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

    run(withUmask('cd {{release_path}} && {{bin/composer}} clear-cache'));

    // Check if this is a development deployment
    $isDevelopment = get('environment') === 'development';

    if ($isDevelopment) {
        // Install with dev dependencies for development environment
        run(withUmask('cd {{release_path}} && {{bin/composer}} install --prefer-dist --no-cache --no-progress --no-interaction --optimize-autoloader --no-scripts'));
        writeln('✅ Composer dependencies installed with dev packages (development environment)');
    } else {
        // Install without dev dependencies for production
        run(withUmask('cd {{release_path}} && {{bin/composer}} install --prefer-dist --no-cache --no-progress --no-interaction --no-dev --optimize-autoloader --no-scripts'));
        writeln('✅ Composer dependencies installed safely (production environment - without dev packages)');
    }
});

desc('Run Laravel package discovery after environment is ready');
task('artisan:package:discover', function () {
    cd('{{release_or_current_path}}');
    run(withUmask('php artisan package:discover --ansi'));
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

    run(withUmask("php artisan app:update-queries {$operation} --force"));
    writeln("✅ app:update-queries completed for operation: {$operation}");
});

desc('Deploying application-specific files and configurations');
task('artisan:app:deploy-files', function () {
    cd('{{release_or_current_path}}');
    run(withUmask('php artisan app:deploy-files --current-path={{current_path}}'));    // Render Supervisor configs for the current symlink
});

/**
 * Publish Filament assets
 */
desc('Publish Filament assets');
task('artisan:filament:assets', function () {
    cd('{{release_or_current_path}}');
    run(withUmask('php artisan filament:assets'));
    writeln('✅ Filament assets published');
});

desc('Optimize Filament resources and assets');
task('artisan:filament:optimize', function () {
    cd('{{release_or_current_path}}');
    run(withUmask('php artisan filament:optimize --ansi'));
    writeln('✅ Filament optimization completed');
});

/*
 * Cached pages name the Vite build's hashed files, which only exist in the release that built them. Pages cached
 * by the old release until the symlink switch would point at files the new release doesn't have, so the response
 * cache is cleared again right after the switch.
 */
desc('Clear the response cache after the symlink switch');
task('artisan:responsecache:clear', function () {
    cd('{{current_path}}');
    run(withUmask('php artisan responsecache:clear'));
    writeln('✅ Response cache cleared');
});

/*
 * =============================================================================
 * SUPERVISOR PROCESS MANAGEMENT
 * =============================================================================
 */

desc('Restart the Panoptes listener so it runs the new release');
task('supervisor:restart-panoptes-listener', function () {
    cd('{{release_or_current_path}}');

    // Supervisor names the program from config, e.g. "biospex:prod-panoptes-pusher".
    $program = trim(run("php artisan tinker --execute 'echo config(\"app.tag\").\":\".config(\"config.panoptes_listener\");'"));
    $status = run("sudo supervisorctl status {$program} || true");

    // Leave it alone when it isn't running (disabled with PANOPTES_LISTENER_ENABLED, or stopped by hand).
    if (! str_contains($status, 'RUNNING')) {
        writeln("⚠️  Skipping Panoptes listener restart: {$program} is not running.");

        return;
    }

    run("sudo supervisorctl restart {$program}");
    writeln("✅ Restarted {$program}");
});

// deploy:ci-artifacts, deploy:verify-structure and supervisor:reload come from
// deployer-recipes/recipe/ci-artifacts.php (required in deploy.php).
