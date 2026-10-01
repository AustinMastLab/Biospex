<?php

use App\Http\ViewComposers\NavComposer;
use App\Models\WeDigBioEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    $this->travelBack();
});

it('does not supply an event for the navigation before an active event starts', function () {
    $this->travelTo('2026-10-01 12:00:00 UTC');

    WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2026-10-02 12:00:00',
        'end_date' => '2026-10-03 12:00:00',
    ]);

    $view = Mockery::mock(View::class);
    $view->shouldReceive('with')
        ->once()
        ->with('event', null);

    app(NavComposer::class)->compose($view);
});

it('renders a countdown instead of happening now before an active event starts', function () {
    $this->travelTo('2026-10-01 12:00:00 UTC');

    $event = WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2026-10-02 12:00:00',
        'end_date' => '2026-10-03 12:00:00',
    ]);

    $content = view('front.wedigbio.partials.event', ['event' => $event])->render();

    expect($content)
        ->not->toContain('Happening Now!')
        ->not->toContain('Progress')
        ->not->toContain('Rates')
        ->toContain('class="clockdiv"')
        ->toContain($event->start_date->toIso8601ZuluString());
});

it('supplies an event and renders progress controls while it is happening', function () {
    $this->travelTo('2026-10-02 12:00:00 UTC');

    $event = WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2026-10-01 12:00:00',
        'end_date' => '2026-10-03 12:00:00',
    ]);

    $view = Mockery::mock(View::class);
    $view->shouldReceive('with')
        ->once()
        ->withArgs(fn (string $key, WeDigBioEvent $boundEvent): bool => $key === 'event' && $boundEvent->is($event));

    app(NavComposer::class)->compose($view);

    $event->slug = 'wedigbio-2026';
    $event->channel_key = 'wedigbio-2026';

    $content = view('front.wedigbio.partials.event', ['event' => $event])->render();

    expect($content)
        ->toContain('Happening Now!')
        ->toContain('Progress')
        ->toContain('Rates');
});

it('renders progress controls for a completed event', function () {
    $this->travelTo('2026-10-04 12:00:00 UTC');

    $event = WeDigBioEvent::factory()->create([
        'active' => true,
        'start_date' => '2026-10-01 12:00:00',
        'end_date' => '2026-10-03 12:00:00',
    ]);
    $event->slug = 'wedigbio-2026';
    $event->channel_key = 'wedigbio-2026';

    $content = view('front.wedigbio.partials.event', ['event' => $event])->render();

    expect($content)
        ->toContain('Completed')
        ->toContain('Progress')
        ->toContain('Rates');
});
