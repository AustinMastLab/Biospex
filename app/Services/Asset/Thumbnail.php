<?php

/*
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

namespace App\Services\Asset;

use App\Services\Requests\HttpRequest;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Filesystem\Factory as Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Class Thumbnail
 */
class Thumbnail
{
    private mixed $defaultThumbImg;

    private string $tnWidth;

    private string $tnHeight;

    private string $thumbDirectory;

    /**
     * Thumbnail constructor.
     */
    public function __construct(
        protected Storage $storage,
        protected File $file,
        protected ImageManager $imageManager,
        protected HttpRequest $httpRequest)
    {
        $this->setVariables();
    }

    /**
     * Set variables.
     */
    private function setVariables(): void
    {
        $this->setDefaultThumbnailImage();
        $this->setThumbnailWidthHeight();
        $this->setThumbnailDir();
    }

    /**
     * Set default thumbnail image.
     */
    private function setDefaultThumbnailImage(): void
    {
        $this->defaultThumbImg = $this->storage->disk('public')->path(config('config.thumb_default_img'));
    }

    /**
     * Set thumbnail width and height.
     */
    private function setThumbnailWidthHeight(): void
    {
        $this->tnWidth = config('config.thumb_width');
        $this->tnHeight = config('config.thumb_height');
    }

    /**
     * Set thumbnail directory.
     */
    private function setThumbnailDir(): void
    {

        $this->thumbDirectory = $this->storage->disk('public')
            ->path(config('config.thumb_output_dir').'/'.$this->tnWidth.'_'.$this->tnHeight);
    }

    /**
     * Return thumbnail or create if not exists.
     *
     * @throws FileNotFoundException
     */
    public function getThumbnail($url): string
    {
        $thumbName = md5($url).'.jpg';
        $thumbFilePath = $this->thumbDirectory.'/'.$thumbName;

        try {
            if (! $this->file->isFile($thumbFilePath)) {
                // Ensure thumbnail directory exists
                if (! $this->file->isDirectory($this->thumbDirectory)) {
                    $this->file->makeDirectory($this->thumbDirectory, 0755, true);
                }

                $this->processImage($url, $thumbFilePath);
            }
        } catch (Throwable $throwable) {
            Log::error('Thumbnail: Failed to generate thumbnail', [
                'url' => $url,
                'thumbnail_path' => $thumbFilePath,
                'error' => $throwable->getMessage(),
                'exception' => get_class($throwable),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);

            return $this->getFile($this->defaultThumbImg);
        }

        return $this->getFile($thumbFilePath);
    }

    /**
     * Get image and create thumbnail.
     *
     * @throws Exception
     */
    protected function processImage(string $url, string $filePath): void
    {
        try {
            // Use HttpRequest for reliable image downloads with retry logic
            $client = $this->httpRequest->createDirectHttpClient([
                'timeout' => 30,
                'connect_timeout' => 10,
                'headers' => [
                    'Accept' => 'image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ],
            ]);

            $response = $client->get($url);

            if ($response->getStatusCode() !== 200) {
                throw new Exception("Failed to download image, status code: {$response->getStatusCode()}");
            }

            $imageContent = $response->getBody()->getContents();

            if (empty($imageContent)) {
                throw new Exception("Downloaded image is empty from: {$url}");
            }

            $this->imageManager->decode($imageContent)
                ->resize($this->tnWidth, $this->tnHeight)
                ->save($filePath);

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            Log::warning('Thumbnail: Image download request failed', [
                'url' => $url,
                'status_code' => $statusCode,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to download image: {$e->getMessage()}", 0, $e);
        } catch (GuzzleException $e) {
            Log::warning('Thumbnail: Image download Guzzle error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("HTTP error downloading image: {$e->getMessage()}", 0, $e);
        } catch (Exception $e) {
            Log::warning('Thumbnail: Image processing error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get thumbnail file or default file.
     *
     * @throws FileNotFoundException
     */
    protected function getFile($thumbFile): string
    {
        if ($this->file->isFile($thumbFile)) {
            return $this->file->get($thumbFile);
        }

        return $this->file->get($this->defaultThumbImg);
    }
}
