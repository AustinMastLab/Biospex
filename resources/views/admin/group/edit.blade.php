@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('edit') }} {{ t('group') }}
@stop

{{-- Content --}}
@section('content')
    <div class="row">
        <div class="col-sm-8 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <form method="post"
                      action="{{ route('admin.groups.update', [$group]) }}"
                      role="form" enctype="multipart/form-data">
                    {!! method_field('put') !!}
                    @csrf
                    <div class="col-12">
                        <h1 class="page-title text-center content-header mb-4 text-uppercase">{{ t('Edit Group') }}</h1>
                        <div class="form-group">
                            <label for="title" class="col-form-label required">{{ t('Title') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('title')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="title" name="title"
                                   value="{{ old('title', $group->title) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('title') }}</span>
                        </div>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="user_id" class="col-form-label required">{{ t('Owner') }}:</label>
                        <select name="user_id" id="user_id"
                                class="form-control custom-select {{ ($errors->has('user_id')) ? 'is-invalid' : '' }} a11y-form-control a11y-form-control"
                                required>
                            @foreach($users as $key => $name)
                                <option {{ $key == old('user_id', $group->owner->id) ?
                                        ' selected=selected' : '' }} value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </select>

                        <span class="invalid-feedback">{{ $errors->first('user_id') }}</span>
                    </div>
                    @include('common.cancel-submit-buttons')
                </form>
            </div>
        </div>
    </div>
@stop