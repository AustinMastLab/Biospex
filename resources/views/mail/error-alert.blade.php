{{ config('app.name') }} ({{ config('app.env') }}) reported an error at {{ now('UTC')->toDateTimeString() }} UTC.

@if ($suppressedCount > 0)
It also happened {{ $suppressedCount }} more {{ Str::plural('time', $suppressedCount) }} since the last email about it.

@endif
Error:    {{ $exceptionClass }}
Message:  {{ $exceptionMessage }}
Location: {{ $location }}
Context:  {{ $context }}

Trace (first 30 lines):
{{ $trace }}

The full error is in the application log. Emails for the same error are limited to one per {{ config('config.error_alerts.group_window_minutes') }} minutes.
