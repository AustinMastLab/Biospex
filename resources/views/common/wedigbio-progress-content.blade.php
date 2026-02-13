@if($weDigBioEvent === null)
    <div class="text-center bg-brand-red">
        <span class="scoreboard-title">{{ t('No current WeDigBio Event') }}</span>
    </div>
@else
    <div class="text-center bg-brand-red">
        <span class="scoreboard-title">{{ $weDigBioEvent->present()->progress_title }}</span>
        <h2 class="text-white text modal-number mt-3">{{ $weDigBioEvent->transcriptions_count }}</h2>
        <span class="scoreboard-title">{{ t('Transcriptions') }}</span>
    </div>

    <table class="table table-striped">
        <thead>
        <tr>
            <td colspan="3" class="color-action">Stats update every 5 minutes.</td>
        </tr>
        <tr>
            <th scope="col">#</th>
            <th scope="col">{{ t('Project') }}</th>
            <th scope="col">{{ t('Transcriptions') }}</th>
        </tr>
        </thead>
        <tbody id="table-rows">
        @php($i = 1)
        @foreach($weDigBioEvent->transcriptions as $transcription)
            <tr>
                <td>{{ $i }}</td>
                <td>{{ $transcription->project->title }}</td>
                <td>{{ $transcription->total }}</td>
            </tr>
            @php($i++)
        @endforeach
        </tbody>
    </table>
@endif