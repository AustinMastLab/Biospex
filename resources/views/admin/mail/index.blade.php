@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Mail') }}
@stop

{{-- Content --}}
@section('content')
    <div class="row">
        <div class="col-sm-10 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <div class="col-12">
                    <h2 class="text-center content-header mb-4 text-uppercase">{{ t('Mail') }}</h2>
                    <form method="post" action="{{ route('admin.mail.create') }}" role="form"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-sm-6">
                                <label for="recipients"
                                       class="col-form-label required">{{ t('Recipients') }}
                                    :</label>
                                <select name="recipients" id="recipients"
                                        class="form-control custom-select {{ ($errors->has('recipients')) ? 'is-invalid' : '' }} a11y-form-control a11y-form-control"
                                        required>
                                    <option {{ old('recipients') === 'owners' ? ' selected=selected' : '' }} value="owners">
                                        Project Owners
                                    </option>
                                    <option {{ old('recipients') === 'all' ? ' selected=selected' : '' }} value="all">
                                        All Users
                                    </option>
                                </select>
                                <span class="invalid-feedback">{{ $errors->first('recipients') }}</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject" class="col-form-label required">{{ t('Subject') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('subject')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="subject" name="subject"
                                   value="{{ old('subject') }}" required>
                            <span class="invalid-feedback">{{ $errors->first('subject') }}</span>
                        </div>

                        <div>{{ t('** Only type message. Greeting and signature added automatically.') }}</div>
                        <div class="form-group">
                            <label for="message" class="col-form-label required">{{ t('Message') }}
                                :</label>
                            <textarea id="message" name="message"
                                      class="form-control textarea {{ ($errors->has('message')) ? 'is-invalid' : '' }} a11y-form-control"
                                      required>{{ old('message') }}</textarea>
                            <span class="invalid-feedback">{{ $errors->first('message') }}</span>
                        </div>

                        @include('common.cancel-submit-buttons')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

