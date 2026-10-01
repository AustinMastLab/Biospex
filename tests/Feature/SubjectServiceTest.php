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
