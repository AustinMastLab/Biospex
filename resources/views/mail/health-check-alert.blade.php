The {{ config('app.name') }} ({{ config('app.env') }}) health check failed twice in a row at {{ now('UTC')->toDateTimeString() }} UTC:

@foreach ($problems as $problem)
- {{ $problem }}
@endforeach

Check with: sudo supervisorctl status
This email repeats at most once an hour while the same problems last.
