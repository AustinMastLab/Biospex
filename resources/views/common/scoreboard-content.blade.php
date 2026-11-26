@if(is_null($event))
    <div class="text-center bg-brand-red">
        <span class="scoreboard-title">{{ t('Error: Event not found.') }}</span>
    </div>
@else
    <div class="text-center bg-brand-red">
        <span class="scoreboard-title">{{ $event->title }}</span>
        <h2 class="text-white text modal-number mt-3">{{ $event->transcriptions_count }}</h2>
        <span class="scoreboard-title">Transcriptions</span>
    </div>

    <table class="table table-striped">
        <thead>
        <tr>
            <td colspan="3" class="color-action">{{ t('Scoreboard updates every 5 minutes.') }}</td>
        </tr>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Team</th>
            <th scope="col">Transcriptions</th>
        </tr>
        </thead>
        <tbody id="table-rows">
        @php($i = 1)
        @foreach($event->teams as $team)
            <tr>
                <td>{{ $i }}</td>
                <td>{{ $team->title }}</td>
                <td>{{ $team->transcriptions_count }}</td>
            </tr>
            @php($i++)
        @endforeach
        </tbody>
    </table>

    <!-- countdown clock -->
    @if(event_active($event))
        <h2 class="text-center color-action pt-4">{{ t('Time Remaining') }}</h2>
        <x-countdown :deadline="$event->present()->scoreboard_date" class="mx-auto" />
    @else
        <h2 class="text-center pt-4">{{ t('Completed') }}</h2>
    @endif
@endif