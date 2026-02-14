@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Create Bingo') }}
@stop

{{-- Content --}}
@section('content')
    <div class="row">
        <div class="col-sm-10 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <div class="col-12">
                    <h2 class="text-center content-header text-uppercase mb-4">{{ t('Create Bingo') }}</h2>
                    <form method="post"
                          action="{{ route('admin.bingos.store') }}"
                          role="form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="entries" value="{{ old('entries', 1) }}">
                        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                        <div class="form-group">
                            <div class="col-12 p-0">
                                <label for="project_id" class="col-form-label required">{{ t('Project') }}:</label>
                            </div>
                            <div class="col-6 p-0">
                                <select name="project_id" id="project_id"
                                        class="form-control custom-select {{ ($errors->has('project_id')) ? 'is-invalid' : '' }} a11y-form-control a11y-form-control"
                                        required>
                                    @foreach($projects as $key => $title)
                                        <option {{ $key == old('project_id') ?
                                        ' selected=selected' : '' }} value="{{ $key }}">{{ $title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="title" class="col-form-label required">{{ t('Title') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('title')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="title" name="title"
                                   pattern=".{5,20}" title="5 to 20 characters"
                                   value="{{ old('title') }}"
                                   placeholder="Between 5 and 20 characters" required>
                            <span class="invalid-feedback">{{ $errors->first('title') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="directions" class="col-form-label required">{{ t('Directions') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('directions')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="directions" name="directions"
                                   pattern=".{10,200}" title="10 to 200 characters"
                                   value="{{ old('directions') }}"
                                   placeholder="Between 6 and 250 characters" required>
                            <span class="invalid-feedback">{{ $errors->first('directions') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="contact" class="col-form-label required">{{ t('Contact') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('contact')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="contact" name="contact" title="Email" placeholder="Email"
                                   value="{{ old('contact') }}" required>
                            <span class="invalid-feedback">{{ $errors->first('contact') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="words" class="col-form-label required">{{ t('Words') }}:</label>
                            @include('admin.bingo.partials.words', ['words' => null])
                        </div>
                        @include('common.cancel-submit-buttons')
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop