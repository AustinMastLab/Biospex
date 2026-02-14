@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Edit Account') }}
@stop

{{-- Content --}}
@section('content')
    <div class="row">
        <div class="col-sm-8 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <div class="col-12">
                    <h1 class="page-title text-center content-header mb-4 text-uppercase">{{ t('Edit Account') }}</h1>
                    <form method="post"
                          action="{{ route('admin.users.update', [$user]) }}"
                          role="form" enctype="multipart/form-data">
                        {!! method_field('put') !!}
                        @csrf
                        <div class="form-group">
                            <label for="first_name" class="col-form-label required">{{ t('First Name') }}
                                :</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('first_name')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="first_name" name="first_name"
                                   autocomplete="given-name"
                                   value="{{ old('first_name', $user->profile->first_name) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('first_name') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="col-form-label required">{{ t('Last Name') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('last_name')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="last_name" name="last_name"
                                   autocomplete="family-name"
                                   value="{{ old('last_name', $user->profile->last_name) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('last_name') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="email" class="col-form-label required">{{ t('Email') }}:</label>
                            <input type="email" class="form-control {{ ($errors->has('email')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="email" name="email"
                                   autocomplete="email"
                                   value="{{ old('email', $user->email) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('email') }}</span>
                        </div>
                        <div class="form-group">
                            <div class="col-12 p-0">
                                <label for="timezone" class="col-form-label required">{{ t('Timezone') }}
                                    :</label>
                            </div>
                            <div class="col-6 p-0">
                                <select name="timezone" id="timezone"
                                        class="form-control custom-select {{ ($errors->has('timezone')) ? 'is-invalid' : '' }} a11y-form-control a11y-form-control"
                                        required>
                                    @foreach($timezones as $key => $value)
                                        <option {{ $key == old('timezone', $user->profile->timezone) ?
                                        ' selected=selected' : '' }} value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notification" class="form-check-inline col-form-label">
                                <input class="form-check-input" type="checkbox" name="notification"
                                       value="{{ old('notification', $user->notification) }}" {{ $user->notification === 1 ? "checked" : "" }}>
                                Notification (Receive notifications for projects you belong to)
                            </label>
                        </div>
                        <div class="form-row mt-4">
                            <div class="form-group col-sm-6 mt-4">
                                @livewire('image-upload', ['modelType' => 'Profile', 'fieldName' => 'avatar', 'maxSize' => 2048], key('avatar-upload-'.$user->id))
                                <input type="hidden" name="avatar_path" id="avatar_path" value="{{ $user->profile->avatar_path }}">
                            </div>
                            <input type="hidden" name="current_avatar" value="{{ $user->profile->avatar_file_name }}">
                            <div class="form-group col-sm-6">
                                <img alt="profile avatar" class="img-fluid"
                                     style="display: inline; width: 100px; height: 100px;"
                                     src="{{ $user->profile->present()->showAvatar() }}"/>
                            </div>
                        </div>
                        @include('common.cancel-submit-buttons')
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <div class="col-12">
                    <h2 class="text-center content-header mb-4 text-uppercase">{{ t('Change Password') }}</h2>
                    <form method="post"
                          action="{{ route('admin.users.password', [$user]) }}" role="form">
                        {!! method_field('put') !!}
                        @csrf
                        <div class="form-group">
                            <label for="current_password" class="col-form-label required">{{ t('Old Password') }}
                                :</label>
                            <input type="password"
                                   class="form-control {{ ($errors->has('current_password')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="current_password" name="current_password"
                                   autocomplete="current-password"
                                   required>
                            <span class="invalid-feedback">{{ $errors->first('current_password') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="password" class="col-form-label required">{{ t('New Password') }}
                                :</label>
                            <input type="password"
                                   class="form-control {{ ($errors->has('password')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="password" name="password"
                                   autocomplete="new-password"
                                   required>
                            <span class="invalid-feedback">{{ $errors->first('password') }}</span>
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation"
                                   class="col-form-label required">{{ t('Confirm Password') }}:</label>
                            <input type="password"
                                   class="form-control {{ ($errors->has('password_confirmation')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="password_confirmation" name="password_confirmation"
                                   autocomplete="new-password"
                                   required>
                            <span class="invalid-feedback">{{ $errors->first('password_confirmation') }}</span>
                        </div>
                        @include('common.cancel-submit-buttons')
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Listen for Livewire file upload events
        document.addEventListener('livewire:init', function () {
            console.log('Livewire init event fired - avatar upload listener registered');
            
            Livewire.on('fileUploaded', (eventData) => {
                console.log('fileUploaded event received:', eventData);
                
                // The event data comes as an array, get the first element
                const uploadData = Array.isArray(eventData) ? eventData[0] : eventData;
                console.log('Processed upload data:', uploadData);
                
                // Update the hidden field with the uploaded file path
                if (uploadData.fieldName === 'avatar' && uploadData.modelType === 'Profile') {
                    console.log('Updating avatar_path field from:', document.getElementById('avatar_path').value);
                    console.log('Updating avatar_path field to:', uploadData.filePath);
                    document.getElementById('avatar_path').value = uploadData.filePath;
                    
                    // Optionally update the displayed image immediately
                    console.log('Avatar uploaded successfully:', uploadData.filePath);
                } else {
                    console.log('fileUploaded event ignored - wrong field or model type');
                    console.log('Expected: fieldName=avatar, modelType=Profile');
                    console.log('Received: fieldName=' + uploadData.fieldName + ', modelType=' + uploadData.modelType);
                }
            });
        });
    </script>
    @endpush
@stop