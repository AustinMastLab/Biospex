@if(! $pass)
    <div class="col-md-8 mx-auto">
        <h3 class="text-center">{{ t('You do not have sufficient permissions.') }}</h3>
    </div>
@else
    <div class="col-sm-8 mx-auto">
        <h3 class="text-center">{{ t('Invite users to %s group.', $group->title) }}</h3>
        <form action="{{ route('admin.invites.store', [$group]) }}" method="post" role="form">
            @csrf
            <input type="hidden" name="entries" value="{{ old('entries', $inviteCount) }}">
            @livewire('group-invite-manager', ['invites' => $group->invites, 'group' => $group, 'errors' => $errors->toArray()])
            <div class="form-group col-md-8 d-flex align-items-start justify-content-between mx-auto">
                <button type="button" class="btn btn-primary mr-4 text-uppercase"
                        data-dismiss="modal">{{ t('Cancel') }}
                </button>
                <button type="submit" class="btn btn-primary text-uppercase">{{ t('Submit') }}</button>
            </div>
        </form>
    </div>
@endif