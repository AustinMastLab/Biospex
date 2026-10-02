<?php

use App\Http\Middleware\FlashHelperMessage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('prevents flash responses from being cached', function () {
    session()->put('success', 'Record saved.');

    $request = Request::create('/', 'GET');
    $response = app(FlashHelperMessage::class)->handle(
        $request,
        fn (): Response => response()->make()
    );

    $flashCookie = collect($response->headers->getCookies())
        ->first(fn ($cookie): bool => $cookie->getName() === 'app_flash');

    expect($request->attributes->get('responsecache.doNotCache'))->toBeTrue();
    expect($flashCookie)->not->toBeNull();
    expect($flashCookie->getValue())->toContain('Record saved.');
    expect(session()->has('success'))->toBeFalse();
});
