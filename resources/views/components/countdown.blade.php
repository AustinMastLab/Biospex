{{--
    Countdown to a date: days, hours, minutes and seconds, counted in the browser by the Alpine "countdown" component
    (resources/js/alpine-components.js). Works wherever it is rendered, including HTML loaded into a modal.

    Usage: <x-countdown :deadline="$event->present()->scoreboard_date" class="mx-auto" />
--}}
@props(['deadline'])

<div {{ $attributes->merge(['class' => 'clockdiv']) }} x-data="countdown(@js((string) $deadline))">
    <div>
        <span class="days" x-text="days"></span>
        <div class="smalltext">{{ t('Days') }}</div>
    </div>
    <div>
        <span class="hours" x-text="hours"></span>
        <div class="smalltext">{{ t('Hours') }}</div>
    </div>
    <div>
        <span class="minutes" x-text="minutes"></span>
        <div class="smalltext">{{ t('Minutes') }}</div>
    </div>
    <div>
        <span class="seconds" x-text="seconds"></span>
        <div class="smalltext">{{ t('Seconds') }}</div>
    </div>
</div>
