@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Create Group') }}
@stop

{{-- Content --}}
@section('content')
    <div class="row">
        <div class="col-sm-8 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <form method="post"
                      action="{{ route('admin.groups.store') }}"
                      role="form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" value="{{ Auth::id() }}" name="user_id" id="user_id">
                    <div class="col-12">
                        <h1 class="page-title text-center content-header mb-4 text-uppercase">{{ t('Create Group') }}</h1>
                        <div class="form-group">
                            <label for="title" class="col-form-label required">{{ t('Title') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('title')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="title" name="title"
                                   value="{{ old('title') }}" required>
                            <span class="invalid-feedback">{{ $errors->first('title') }}</span>
                        </div>
                        @include('common.cancel-submit-buttons')
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop