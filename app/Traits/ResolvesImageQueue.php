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

namespace App\Traits;

use Aws\Sqs\SqsClient;

trait ResolvesImageQueue
{
    /**
     * Resolve the SQS queue URL for a given image URI.
     *
     * Routes archive.org URLs to the Internet Archive image trigger queue.
     * All other URLs are routed to the standard image trigger queue.
     *
     * @param  SqsClient  $sqs      AWS SQS client
     * @param  string     $accessUri  The image access URI to inspect
     * @return string               The resolved SQS queue URL
     */
    protected function resolveImageQueueUrl(SqsClient $sqs, string $accessUri): string
    {
        $host = parse_url($accessUri, PHP_URL_HOST) ?? '';

        $queueKey = str_contains($host, 'archive.org')
            ? 'ia_image_trigger'
            : 'image_trigger';

        return $sqs->getQueueUrl([
            'QueueName' => config("services.aws.sqs.{$queueKey}"),
        ])['QueueUrl'];
    }
}

