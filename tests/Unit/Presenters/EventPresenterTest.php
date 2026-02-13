<?php

namespace Tests\Unit\Presenters;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, PresenterTestHelpers::class);

it('generates unique aria labels for different events', function (string $method) {
    $event1 = Event::factory()->create(['title' => 'Event One']);
    $event2 = Event::factory()->create(['title' => 'Event Two']);

    if ($method === 'contactEmailIcon') {
        $event1->update(['contact_email' => 'test@example.com']);
        $event2->update(['contact_email' => 'test2@example.com']);
    }

    $output1 = $event1->present()->$method();
    $output2 = $event2->present()->$method();

    $this->assertLinkEmptyOrSafe($output1, [$event1->uuid]);
    $this->assertLinkEmptyOrSafe($output2, [$event2->uuid]);

    if ($output1 === '') {
        return;
    }

    // Extract aria-label
    $anchor1 = $this->extractFirstAnchor($output1);
    $anchor2 = $this->extractFirstAnchor($output2);

    $label1 = $this->getAttr($anchor1, 'aria-label');
    $label2 = $this->getAttr($anchor2, 'aria-label');

    expect($label1)->not->toBeEmpty();
    expect($label1)->not->toBe($label2);

    // Check if unique identifier (uuid) is present in the label
    expect($label1)->toContain($event1->uuid);
    expect($label2)->toContain($event2->uuid);
})->with(CoveredPresenterAnchorMethods::forPresenter('EventPresenter'));

it('safely escapes danger strings in event presenter', function (string $dangerString, string $method) {
    $event = Event::factory()->create([
        'title' => $dangerString,
        'contact_email' => 'test@example.com',
    ]);

    $output = $event->present()->$method();
    $this->assertLinkEmptyOrSafe($output);

    if ($output === '') {
        return;
    }

    $escaped = e($dangerString);
    expect($output)->toContain($escaped);

    if ($dangerString !== $escaped && $method !== 'twitterIcon') {
        expect($output)->not->toContain($dangerString);
    }
})->with('dangerStrings')->with(CoveredPresenterAnchorMethods::forPresenter('EventPresenter'));
