<div class="col-md-10 offset-2 mx-auto">
    <h3 class="text-center pt-4">{{ t('Teams Summary') }}</h3>
    <hr>
    @if($event->teams->isEmpty())
        <p class="text-center">{{ t('No teams exist at this time.') }}</p>
    @else
        <div class="color-action text-center">{{ t('Use shift + click to multi-sort') }}</div>
        <table id="teams-tbl" class="table table-striped table-bordered dt-responsive nowrap"
               style="width:100%; font-size: .8rem">
            <thead>
            <tr>
                <th scope="col">{{ t('Teams') }}</th>
                <th scope="col">{{ t('Users') }}</th>
                <th scope="col">{{ t('Digitizations') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($event->teams as $team)
                @foreach ($team->users as $user)
                    <tr>
                        <td>{{ $team->title }}</td>
                        <td>{{ $user->nfn_user }}</td>
                        <td>{{ $user->transcriptions_count }}</td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    @endif
</div>
