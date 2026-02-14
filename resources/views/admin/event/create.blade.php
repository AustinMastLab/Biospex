@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Create Event') }}
@stop

{{-- Content --}}
@section('content')
    <div class="row">
        <div class="col-sm-10 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <div class="col-12">
                    <h2 class="text-center content-header text-uppercase mb-4">{{ t('Create Event') }}</h2>
                    <form method="post"
                          action="{{ route('admin.events.store') }}"
                          role="form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="entries" value="{{ old('entries', 1) }}">
                        <input type="hidden" name="owner_id" value="{{ Auth::id() }}">
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
                                   value="{{ old('title') }}" required>
                            <span class="invalid-feedback">{{ $errors->first('title') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="description" class="col-form-label required">{{ t('Description') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('description')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="description" name="description"
                                   value="{{ old('description') }}"
                                   placeholder="Between 6 and 140 characters" required>
                            <span class="invalid-feedback">{{ $errors->first('description') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="contact" class="col-form-label required">{{ t('Contact') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('contact')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="contact" name="contact"
                                   value="{{ old('contact') }}" required>
                            <span class="invalid-feedback">{{ $errors->first('contact') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="contact_email" class="col-form-label required">{{ t('Contact Email') }}
                                :</label>
                            <input type="email"
                                   class="form-control {{ ($errors->has('contact_email')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="contact_email" name="contact_email"
                                   value="{{ old('contact_email') }}" required>
                            <span class="invalid-feedback">{{ $errors->first('contact_email') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="hashtag" class="col-form-label">{{ t('Hash Tags') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('hashtag')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="hashtag" name="hashtag" placeholder="{{ t('Separated by commas') }}"
                                   value="{{ old('hashtag') }}">
                            <span class="invalid-feedback">{{ $errors->first('hashtag') }}</span>
                        </div>
                        <div class="form-row">
                            <div class="col-sm-4 form-group">
                                <label for="start_date"
                                       class="col-form-label required">{{ t('Start Date') }}
                                    :</label>
                                <input type="text"
                                       class="form-control date-time-picker {{ ($errors->has('start_date')) ? 'is-invalid' : '' }} a11y-form-control"
                                       id="start_date" name="start_date"
                                       value="{{ old('start_date') }}"
                                       required>
                                <span class="invalid-feedback">{{ $errors->first('start_date') }}</span>
                            </div>
                            <div class="col-sm-4 form-group">
                                <label for="end_date"
                                       class="col-form-label required">{{ t('End Date') }}:</label>
                                <input type="text"
                                       class="form-control date-time-picker {{ ($errors->has('end_date')) ? 'is-invalid' : '' }} a11y-form-control"
                                       id="end_date" name="end_date"
                                       value="{{ old('end_date') }}"
                                       required>
                                <span class="invalid-feedback">{{ $errors->first('end_date') }}</span>
                            </div>
                            <div class="col-sm-4 form-group">
                                <label for="timezone"
                                       class="col-form-label required">{{ t('Timezone') }}:</label>
                                <select name="timezone" id="timezone"
                                        class="form-control custom-select {{ ($errors->has('timezone')) ? 'is-invalid' : '' }} a11y-form-control a11y-form-control"
                                        required>
                                    @foreach(time_zone_select() as $key => $value)
                                        <option {{ $key == old('timezone', 'America/New_York') ?
                                        ' selected=selected' : '' }} value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="teams" class="col-form-label">{{ t('Teams') }}:</label>
                            <div class="controls col-sm-12">
                                @livewire('event-team-manager', ['teams' => null, 'event' => null, 'errors' => $errors->toArray()])
                            </div>
                        </div>
                        @include('common.cancel-submit-buttons')
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop