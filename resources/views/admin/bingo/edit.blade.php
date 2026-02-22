@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Edit') }} {{ $bingo->title }}
@stop

{{-- Content --}}
@section('content')
    @include('admin.bingo.partials.bingo-panel')
    <div class="row">
        <div class="col-sm-10 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <div class="col-12">
                    <h2 class="text-center content-header text-uppercase mb-4">{{ t('Edit Bingo') }}</h2>

                    <form method="post"
                          action="{{ route('admin.bingos.update', [$bingo]) }}"
                          role="form" enctype="multipart/form-data">
                        {!! method_field('put') !!}
                        @csrf
                        <input type="hidden" name="user_id" value="{{ old('user_id', $bingo->user_id)  }}">
                        <div class="form-group">
                            <div class="col-12 p-0">
                                <label for="project_id" class="col-form-label required">{{ t('Project') }}:</label>
                            </div>
                            <div class="col-6 p-0">
                                <select name="project_id" id="project_id"
                                        class="form-control custom-select {{ ($errors->has('project_id')) ? 'is-invalid' : '' }} a11y-form-control a11y-form-control"
                                        required>
                                    @foreach($projects as $key => $title)
                                        <option {{ $key == old('project_id', $bingo->project_id) ?
                                        ' selected=selected' : '' }} value="{{ $key }}">{{ $title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="title" class="col-form-label required">{{ t('Title') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('title')) ? 'is-invalid' : '' }} a11y-form-control"
                                   pattern=".{5,20}" title="5 to 20 characters"
                                   id="title" name="title"
                                   value="{{ old('title', $bingo->title) }}"
                                   placeholder="Between 5 and 20 characters" required>
                            <span class="invalid-feedback">{{ $errors->first('title') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="directions" class="col-form-label required">{{ t('Directions') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('directions')) ? 'is-invalid' : '' }} a11y-form-control"
                                   pattern=".{10,200}" title="10 to 200 characters"
                                   id="directions" name="directions"
                                   value="{{ old('directions', $bingo->directions) }}"
                                   placeholder="Between 10 and 200 characters" required>
                            <span class="invalid-feedback">{{ $errors->first('directions') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="contact" class="col-form-label required">{{ t('Contact') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('contact')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="contact" name="contact" title="Email" placeholder="Email"
                                   value="{{ old('contact', $bingo->contact) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('contact') }}</span>
                        </div>

                            <div class="form-group">
                                <label class="col-form-label required">{{ t('Words') }}:</label>
                                @include('admin.bingo.partials.words', ['words' => null])
                            </div>
                            @include('common.cancel-submit-buttons')

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection