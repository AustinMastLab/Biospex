@extends('admin.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('Clone Project') }}
@stop

@push('styles')
    <style>
        .ui-jqgrid.ui-jqgrid-bootstrap > .ui-jqgrid-view {
            font-size: 1rem;
        }

        #searchmodfbox_jqGridExpedition {
            top: auto;
        }
    </style>
@endpush

{{-- Content --}}
@section('content')

    <div class="row">
        <div class="col-sm-10 mx-auto">
            <div class="card white box-shadow pt-2 pb-5 my-5 p-sm-5">
                <div class="col-12">
                    <h2 class="text-center content-header mb-4 text-uppercase">{{ t('Clone Project') }}</h2>
                    <form method="post" id="projectFrm" action="{{ route('admin.projects.store') }}"
                          role="form"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="entries" name="entries" value="{{ old('entries', 1) }}">
                        <input type="hidden" name="id" value="">
                        <div class="form-row">
                            <div class="form-group col-sm-6">
                                <label for="group_id"
                                       class="col-form-label required">{{ t('Group') }}
                                    :</label>
                                <select name="group_id" id="group_id"
                                        class="form-control custom-select {{ ($errors->has('group_id')) ? 'is-invalid' : '' }} a11y-form-control a11y-form-control"
                                        required>
                                    <option value="">{{ t('Select Group') }}</option>
                                    @foreach($groupOptions as $key => $name)
                                        {{ $key }}
                                        <option {{ $key == old('group_id', $project->group_id) ?
                                        ' selected=selected' : '' }} value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <span class="invalid-feedback">{{ $errors->first('group_id') }}</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="title" class="col-form-label required">{{ t('Title') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('title')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="title" name="title"
                                   value="{{ old('title', $project->title) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('title') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="contact" class="col-form-label required">{{ t('Contact') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('contact')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="contact" name="contact"
                                   value="{{ old('contact', $project->contact) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('contact') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="contact_email" class="col-form-label required">{{ t('Contact Email') }}
                                :</label>
                            <input type="email"
                                   class="form-control {{ ($errors->has('contact_email')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="contact_email" name="contact_email"
                                   value="{{ old('contact_email', $project->contact_email) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('contact_email') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="contact_title"
                                   class="col-form-label {{ ($errors->has('contact_title')) ? 'is-invalid' : '' }} required">
                                {{ t('Contact Title') }}
                                :</label>
                            <input type="text" class="form-control a11y-form-control" id="contact_title" name="contact_title"
                                   value="{{ old('contact_title', $project->contact_title) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('contact_title') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="organization" class="col-form-label">{{ t('Organization') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('organization')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="organization" name="organization"
                                   value="{{ old('organization', $project->organization) }}">
                            <span class="invalid-feedback">{{ $errors->first('organization') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="organization_website"
                                   class="col-form-label pl-0 {{ ($errors->has('organization_website')) ? 'is-invalid' : '' }}">
                                {{ t('Organization Website') }}
                                :</label>
                            <input type="url" class="form-control a11y-form-control" id="organization_website" name="organization_website"
                                   value="{{ old('organization_website', $project->organization_website) }}">
                            <span class="invalid-feedback">{{ $errors->first('organization_website') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="project_partners" class="col-form-label">{{ t('Project Partners') }}:</label>
                            <textarea id="project_partners" name="project_partners"
                                      class="form-control {{ ($errors->has('project_partners')) ? 'is-invalid' : '' }} a11y-form-control"
                            >{{ old('project_partners', $project->project_partners) }}</textarea>
                            <span class="invalid-feedback">{{ $errors->first('project_partners') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="funding_source" class="col-form-label">{{ t('Funding Source') }}:</label>
                            <textarea id="funding_source" name="funding_source"
                                      class="form-control {{ ($errors->has('funding_source')) ? 'is-invalid' : '' }} a11y-form-control"
                            >{{ old('funding_source', $project->funding_source) }}</textarea>
                            <span class="invalid-feedback">{{ $errors->first('funding_source') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="description_short" class="col-form-label required">{{ t('Short Description') }}
                                :</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('description_short')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="description_short" name="description_short"
                                   value="{{ old('description_short', $project->description_short) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('description_short') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="description_long" class="col-form-label required">{{ t('Long Description') }}
                                :</label>
                            <textarea id="description_long" name="description_long"
                                      class="form-control textarea {{ ($errors->has('description_long')) ? 'is-invalid' : '' }} a11y-form-control"
                                      required>{{ old('description_long', $project->description_long) }}</textarea>
                            <span class="invalid-feedback">{{ $errors->first('description_long') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="incentives" class="col-form-label">{{ t('Incentives') }}:</label>
                            <textarea id="incentives" name="incentives"
                                      class="form-control {{ ($errors->has('incentives')) ? 'is-invalid' : '' }} a11y-form-control"
                            >{{ old('incentives', $project->incentives) }}</textarea>
                            <span class="invalid-feedback">{{ $errors->first('incentives') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="geographic_scope" class="col-form-label">{{ t('Geographic Scope') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('geographic_scope')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="geographic_scope" name="geographic_scope"
                                   value="{{ old('geographic_scope', $project->geographic_scope) }}">
                            <span class="invalid-feedback">{{ $errors->first('geographic_scope') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="taxonomic_scope" class="col-form-label">{{ t('Taxonomic Scope') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('taxonomic_scope')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="taxonomic_scope" name="taxonomic_scope"
                                   value="{{ old('taxonomic_scope', $project->taxonomic_scope) }}">
                            <span class="invalid-feedback">{{ $errors->first('taxonomic_scope') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="temporal_scope" class="col-form-label">{{ t('Temporal Scope') }}:</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('temporal_scope')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="temporal_scope" name="temporal_scope"
                                   value="{{ old('temporal_scope', $project->temporal_scope) }}">
                            <span class="invalid-feedback">{{ $errors->first('temporal_scope') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="keywords" class="col-form-label required">{{ t('Keywords') }}:</label>
                            <input type="text" class="form-control {{ ($errors->has('keywords')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="keywords" name="keywords"
                                   value="{{ old('keywords', $project->keywords) }}" required>
                            <span class="invalid-feedback">{{ $errors->first('keywords') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="blog_url" class="col-form-label">{{ t('Blog') }}:</label>
                            <input type="url" class="form-control {{ ($errors->has('blog_url')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="blog_url" name="blog_url" placeholder="{{ t('http://blog.com') }}"
                                   value="{{ old('blog_url', $project->blog_url) }}">
                            <span class="invalid-feedback">{{ $errors->first('blog_url') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="facebook" class="col-form-label">{{ t('Facebook') }}:</label>
                            <input type="url" class="form-control {{ ($errors->has('facebook')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="facebook" name="facebook" placeholder="{{ t('http://facebook.com/example') }}"
                                   value="{{ old('facebook', $project->facebook) }}">
                            <span class="invalid-feedback">{{ $errors->first('facebook') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="twitter" class="col-form-label">{{ t('Twitter') }}:</label>
                            <input type="url" class="form-control {{ ($errors->has('twitter')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="twitter" name="twitter" placeholder="{{ t('http://twitter.com/example') }}"
                                   value="{{ old('twitter', $project->twitter) }}">
                            <span class="invalid-feedback">{{ $errors->first('twitter') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="activities" class="col-form-label">{{ t('Activities') }}:</label>
                            <input type="text" class="form-control a11y-form-control"
                                   {{ ($errors->has('activities')) ? 'is-invalid' : '' }}
                                   id="activities" name="activities"
                                   value="{{ old('activities', $project->activities) }}">
                            <span class="invalid-feedback">{{ $errors->first('activities') }}</span>
                        </div>

                        <div class="form-group">
                            <label for="language_skills" class="col-form-label">{{ t('Language Skills Required') }}
                                :</label>
                            <input type="text"
                                   class="form-control {{ ($errors->has('language_skills')) ? 'is-invalid' : '' }} a11y-form-control"
                                   id="language_skills" name="language_skills"
                                   value="{{ old('language_skills', $project->language_skills) }}">
                            <span class="invalid-feedback">{{ $errors->first('language_skills') }}</span>
                        </div>

                        <div class="form-row mt-4">
                            <div class="form-group col-sm-6 mt-4">
                                @livewire('image-upload', ['modelType' => 'Project', 'fieldName' => 'logo_path', 'maxSize' => 5120], key('logo-upload-clone'))
                                @livewire('project-manager', ['logo_path' => old('logo_path', $project->logo_path), 'projectUuid' => null], key('project-manager-clone'))
                            </div>
                            <div class="form-group col-sm-6">
                                <img class="img-fluid" style="display: inline; width: 100px; height: 100px;"
                                     src="{{ $project->present()->show_logo }}" alt="{{ $project->present()->logo_alt }}"/>
                            </div>
                        </div>

                        <div class="form-row mt-4">
                            <div class="form-group col-sm-6">
                                <label for="banner-file" class="col-form-label">{{ t('Banner File') }}:</label>
                                <input type="text" class="form-control a11y-form-control" id="banner-file" name="banner_file"
                                       value="{{ $project->present()->banner_file_name ?? 'banner-trees.jpg' }}"
                                       readonly>
                            </div>
                            <div class="form-group col-sm-4 pt-3">
                                <a href="#" data-toggle="modal" data-target="#project-banner-modal"
                                   data-hover="tooltip" title="{{ t('Click to select banner.') }}">
                                    {{ t('Click to select banner.') }}
                                    <img class="img-fluid" id="banner-img"
                                         src="{{ $project->present()->banner_file_url }}" alt="Project Banner"/>
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="resources" class="col-form-label">{{ t('Resources') }}:</label>
                            <div class="controls col-sm-12">
                                @include('admin.project.partials.assets')
                            </div>
                        </div>
                        @include('common.cancel-submit-buttons')
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('admin.partials.project-banner-modal')
@endsection
@push('scripts')
    <script src="{{ mix('js/livewire.js') }}"></script>
@endpush