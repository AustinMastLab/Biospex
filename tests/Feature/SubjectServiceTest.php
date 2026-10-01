<?php

use App\Models\Subject;
use App\Services\Subject\SubjectService;
use MongoDB\BSON\ObjectId;
use MongoDB\Laravel\Eloquent\Builder;

use function Pest\Laravel\mock;

it('uses a sequential BSON array when attaching sparse subject selections', function () {
    $mongoCollection = new class
    {
        public array $filter;

        public array $update;

        public function updateMany(array $filter, array $update): void
        {
            $this->filter = $filter;
            $this->update = $update;
        }
    };

    $query = mock(Builder::class);
    $subject = mock(Subject::class);

    $subject->shouldReceive('newQuery')
        ->once()
        ->andReturn($query);

    $query->shouldReceive('raw')
        ->once()
        ->withArgs(function (Closure $operation) use ($mongoCollection): bool {
            $operation($mongoCollection);

            return true;
        });

    (new SubjectService($subject))->attachSubjects(collect([
        4 => '507f1f77bcf86cd799439011',
        9 => '507f1f77bcf86cd799439012',
    ]), 123);

    $subjectIds = $mongoCollection->filter['_id']['$in'];

    expect(array_is_list($subjectIds))->toBeTrue();
    expect(array_map(fn (ObjectId $subjectId): string => (string) $subjectId, $subjectIds))
        ->toBe([
            '507f1f77bcf86cd799439011',
            '507f1f77bcf86cd799439012',
        ]);
    expect($mongoCollection->update)->toBe([
        '$addToSet' => ['expedition_ids' => ['$each' => [123]]],
    ]);
});

it('resolves legacy image source IDs to MongoDB document IDs', function () {
    $firstDocumentId = new ObjectId('507f1f77bcf86cd799439011');
    $secondDocumentId = new ObjectId('507f1f77bcf86cd799439012');
    $firstSubject = new Subject(['id' => $firstDocumentId]);
    $secondSubject = new Subject(['id' => $secondDocumentId]);
    $query = mock(Builder::class);
    $subject = mock(Subject::class);

    $subject->shouldReceive('whereIn')
        ->once()
        ->with('imageId', [
            '3e3a7635-43a7-4267-8757-8b99631f2ee5',
            '5c298ace-6ebb-448d-8b8d-cc03e31607ca',
        ])
        ->andReturn($query);

    $query->shouldReceive('get')
        ->once()
        ->with(['_id'])
        ->andReturn(collect([$firstSubject, $secondSubject]));

    $documentIds = (new SubjectService($subject))->getDocumentIdsByImageIds(collect([
        '3e3a7635-43a7-4267-8757-8b99631f2ee5',
        '5c298ace-6ebb-448d-8b8d-cc03e31607ca',
    ]));

    expect($documentIds->all())
        ->toBe([
            '507f1f77bcf86cd799439011',
            '507f1f77bcf86cd799439012',
        ]);
});

it('fails when a selected image source ID no longer has a Subject document', function () {
    $query = mock(Builder::class);
    $subject = mock(Subject::class);

    $subject->shouldReceive('whereIn')
        ->once()
        ->with('imageId', ['3e3a7635-43a7-4267-8757-8b99631f2ee5'])
        ->andReturn($query);

    $query->shouldReceive('get')
        ->once()
        ->with(['_id'])
        ->andReturn(collect());

    expect(fn () => (new SubjectService($subject))->getDocumentIdsByImageIds(collect([
        '3e3a7635-43a7-4267-8757-8b99631f2ee5',
    ])))->toThrow(RuntimeException::class, 'Unable to resolve every selected image ID to a Subject document.');
});
