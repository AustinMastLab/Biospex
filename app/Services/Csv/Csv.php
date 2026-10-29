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

namespace App\Services\Csv;

use League\Csv\CannotInsertRecord;
use League\Csv\CharsetConverter;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\SyntaxError;
use League\Csv\Writer;

/**
 * Class Csv
 *
 * Readers and writers use RFC 4180 escaping (quotes doubled, no escape character).
 * League's default backslash escape writes `\"` without doubling the quote, which
 * Excel, Google Sheets and Python read as the end of the field.
 */
class Csv
{
    public Reader $reader;

    public Writer $writer;

    /**
     * Return Reader.
     */
    public function getReader(): Reader
    {
        return $this->reader;
    }

    /**
     * Return writer.
     */
    public function getWriter(): Writer
    {
        return $this->writer;
    }

    /**
     * Create reader using file path.
     */
    public function readerCreateFromPath($file)
    {
        $this->reader = Reader::createFromPath($file)->setEscape('');
    }

    /**
     * Release the reader, closing its file.
     *
     * On EFS (NFS) a file that is still open can't really be deleted, so its directory can't be removed either.
     */
    public function closeReader(): void
    {
        unset($this->reader);
    }

    /**
     * Create reader from stream.
     *
     * @return void
     */
    public function readerCreateFromStream($stream)
    {
        $this->reader = Reader::createFromStream($stream)->setEscape('');
    }

    /**
     * Create writer from file path.
     */
    public function writerCreateFromPath($filePath)
    {
        $this->writer = Writer::createFromPath($filePath, 'w+')->setEscape('');
    }

    /**
     * Create writer from stream.
     *
     * @return void
     */
    public function writerCreateFromStream($stream)
    {
        $this->writer = Writer::createFromStream($stream)->setEscape('');
    }

    /**
     * Set delimiter.
     *
     * @throws Exception
     */
    public function setDelimiter(string $delimiter = ',')
    {
        $this->reader->setDelimiter($delimiter);
    }

    /**
     * Set enclosure.
     *
     * @throws Exception
     */
    public function setEnclosure(string $enclosure = '"')
    {
        $this->reader->setEnclosure($enclosure);
    }

    /**
     * Set escape.
     *
     * @throws Exception
     */
    public function setEscape(string $escape = '')
    {
        $this->reader->setEscape($escape);
    }

    /**
     * Set header offset.
     *
     *
     * @throws Exception
     */
    public function setHeaderOffset(int $offset = 0): void
    {
        $this->reader->setHeaderOffset($offset);
    }

    /**
     * Return header row.
     *
     * @return string[]
     *
     * @throws SyntaxError
     */
    public function getHeader(): array
    {
        return $this->reader->getHeader();
    }

    /**
     * Fetch all rows.
     *
     * @throws Exception
     */
    public function getRecords(array $header = []): \Iterator
    {
        return $this->reader->getRecords($header);
    }

    /**
     * Insert one row.
     *
     * @throws CannotInsertRecord|Exception
     */
    /**
     * Align rows that may have different keys to one header.
     *
     * The header is every key found in any row, in first-seen order, and each row is
     * returned in that column order with '' for fields it lacks, so no row can add
     * unlabeled values or shift into the wrong column.
     *
     * @return array{0: array<int, string>, 1: array<int, array<string, mixed>>}
     */
    public function alignRows(iterable $rows): array
    {
        $rows = collect($rows)->map(fn ($row) => (array) $row)->values();
        $blank = $rows->reduce(fn (array $keys, array $row) => $keys + array_fill_keys(array_keys($row), ''), []);

        return [
            array_map('strval', array_keys($blank)),
            $rows->map(fn (array $row) => array_replace($blank, $row))->all(),
        ];
    }

    public function insertOne($row): void
    {
        $this->writer->insertOne($row);
    }

    /**
     * Insert all rows.
     *
     * @throws \TypeError
     */
    public function insertAll($rows): void
    {
        $this->writer->insertAll($rows);
    }

    /**
     * Return the count. If header is offset, then header not counted.
     */
    public function getReaderCount(): int
    {
        return count($this->reader);
    }

    /**
     * Set encoding.
     */
    public function setEncoding(): CharsetConverter
    {
        return (new CharsetConverter)
            ->inputEncoding('utf-8')
            ->outputEncoding('utf-8');
    }

    /**
     * Add formatter.
     *
     * @return void
     */
    public function addEncodingFormatter()
    {
        $this->writer->addFormatter($this->setEncoding());
    }
}
