<?php

/*
 * Copyright (C) 2014 - 2026, Biospex
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

namespace App\Providers;

use App\Services\DarwinCore\MetaFileProcessor;
use Aws\Lambda\LambdaClient;
use Aws\S3\S3Client;
use Aws\Sfn\SfnClient;
use Aws\Sqs\SqsClient;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Laracasts\Utilities\JavaScript\LaravelViewBinder;
use Laracasts\Utilities\JavaScript\Transformers\Transformer;

/**
 * Infrastructure Service Provider
 *
 * Registers infrastructure-related services including AWS clients,
 * JavaScript transformer, image manager, and Darwin Core processors.
 */
class InfrastructureServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     *
     * Binds infrastructure services to the container including AWS clients,
     * JavaScript variable transformer, Imagick-based image manager, and
     * Darwin Core meta file processor.
     */
    public function register(): void
    {
        $this->registerAwsClients();

        $this->app->bind(Transformer::class, function ($app) {
            return new Transformer(
                new LaravelViewBinder(
                    $app['events'],
                    config('javascript.bind_js_vars_to_this_view')
                ),
                config('javascript.js_namespace')
            );
        });

        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new ImagickDriver);
        });

        $this->app->bind(MetaFileProcessor::class, function ($app) {
            return new MetaFileProcessor(
                $app->make(\App\Services\DarwinCore\DarwinCoreXmlLoader::class),
                $app->make(\App\Models\Meta::class),
                $app['config']->get('config.dwcRequiredRowTypes'),
                $app['config']->get('config.dwcRequiredFields')
            );
        });
    }

    /**
     * Register AWS client services.
     *
     * Configures and registers AWS SDK clients (SQS, S3, Step Functions, Lambda)
     * as singletons. Uses credentials from configuration if available, otherwise
     * falls back to IAM role for EC2/Production environments.
     */
    protected function registerAwsClients(): void
    {
        $awsConfig = [
            'version' => 'latest',
            'region' => config('services.aws.region', 'us-east-2'),
        ];

        $key = config('services.aws.credentials.key');
        $secret = config('services.aws.credentials.secret');

        // If keys are in .env, use them. If not, the SDK automatically
        // uses the IAM Role (for EC2/Production).
        if (! empty($key) && ! empty($secret)) {
            $awsConfig['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        // Register AWS Clients as singletons
        $this->app->singleton(SqsClient::class, fn () => new SqsClient($awsConfig));
        $this->app->singleton(S3Client::class, fn () => new S3Client($awsConfig));
        $this->app->singleton(SfnClient::class, fn () => new SfnClient($awsConfig));
        $this->app->singleton(LambdaClient::class, fn () => new LambdaClient($awsConfig));
    }
}
