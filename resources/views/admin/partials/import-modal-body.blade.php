{{-- One import section open at a time (the file upload first); each "Instructions" link shows its text. --}}
<div id="import-accordion" x-data="{ section: 'dwc-upload' }">
    <div class="card">
        <div class="card-header" id="dwc-file-import">
            <h5 class="mb-0">
                <button type="button" class="import btn" @click="section = section === 'dwc-upload' ? null : 'dwc-upload'"
                        :aria-expanded="(section === 'dwc-upload').toString()" aria-expanded="true" aria-controls="dwc-upload">
                    {{ t('Import Darwin Core File') }}
                </button>
            </h5>
        </div>

        <div id="dwc-upload" aria-labelledby="dwc-file-import" x-show="section === 'dwc-upload'" x-collapse>
            <div class="card-body">
                <form action="{{ route('admin.imports.dwcfile') }}"
                      method="post" role="form" class="form-horizontal" id="form-dwc-file"
                      enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <div class="mt-2 mb-4" x-data="{ instructions: false }">
                        <a href="#" class="btn btn-outline-primary" @click.prevent="instructions = ! instructions"
                           role="button" :aria-expanded="instructions.toString()" aria-expanded="false"
                           aria-controls="dwc-file-instruction">{{ t('Instructions') }}</a>
                        <span id="dwc-file-instruction" x-show="instructions" x-cloak>
                                                {{ t('Only zipped Darwin Core files are accepted.') }}
                                                <a href="{{ '/darwin-core-example.zip' }}"
                                                   class="link">{{ t('Download Example DWC File.') }}</a>
                                            </span>
                    </div>
                    <div class="custom-file">
                        <label class="custom-file-label"
                               for="dwc-file">{{ t('Choose file...') }}</label>
                        <input type="file" name="dwc-file" class="custom-file-input a11y-form-control" id="dwc-file" accept=".zip" required>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit"
                                class="btn btn-primary pl-4 pr-4 text-uppercase">{{ t('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="import card">
        <div class="card-header" id="record-set-import">
            <h5 class="mb-0">
                <button type="button" class="import btn" @click="section = section === 'recordset' ? null : 'recordset'"
                        :aria-expanded="(section === 'recordset').toString()" aria-expanded="false" aria-controls="recordset">
                    {{ t('Record Set Id/Url') }}
                </button>
            </h5>
        </div>
        <div id="recordset" aria-labelledby="record-set-import" x-show="section === 'recordset'" x-collapse x-cloak>
            <div class="card-body">
                <form action="{{ route('admin.imports.recordset') }}"
                      method="post" role="form" class="form-horizontal" id="form-dwc-recordset"
                      enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <div class="mt-2 mb-4" x-data="{ instructions: false }">
                        <a href="#" class="btn btn-outline-primary" @click.prevent="instructions = ! instructions"
                           role="button" :aria-expanded="instructions.toString()" aria-expanded="false"
                           aria-controls="recordset-instruction">{{ t('Instructions') }}</a>
                        <span id="recordset-instruction" x-show="instructions" x-cloak>
                                <ol class="mt-2">
                                    <li>{{ p('directions', 'Go to') }} <a
                                                href="https://www.idigbio.org/portal/publishers"
                                                target="_blank"
                                                class="link">{{ p('recordset-directions', 'iDigBio.org Publishers Page') }}</a></li>
                                    <li>{{ p('directions', 'Find the Publisher you want and select. (e.g. https://herbarium.bio.fsu.edu:8443/)') }}</li>
                                    <li>{{ p('directions', 'Click the Collection you are interested in. (e.g. Robert K. Godfrey Herbarium at Florida State University)') }}</li>
                                    <li>{{ p('directions', 'iDiogBio does not actually show the recordset id in the page, so it must be retrieved via the URL.') }}
                                        <ol>
                                            <li>{{ p('directions', 'Url: https://www.idigbio.org/portal/recordsets/b2b294ed-1742-4479-b0c8-a8891fccd7eb') }}</li>
                                            <li>{{ p('directions', 'Record Id: b2b294ed-1742-4479-b0c8-a8891fccd7eb') }}</li>
                                        </ol>
                                    </li>
                                </ol>
                            </span>
                    </div>
                    <div class="form-group">
                        <label for="dwc-recordset" class="mb-0">{{ t('Record Set Id/Url') }} <span
                                    class="color-action">*</span></label>
                        <input type="text" name="recordset" id="dwc-recordset" class="form-control a11y-form-control"
                               title="Must be valid UUID"
                               pattern="([a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}){1}"
                               required>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit"
                                class="btn btn-primary pl-4 pr-4 text-uppercase">{{ t('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="import card">
            <div class="card-header" id="dwc-url-import">
                <h5 class="mb-0">
                    <button type="button" class="import btn" @click="section = section === 'dwc-uri' ? null : 'dwc-uri'"
                            :aria-expanded="(section === 'dwc-uri').toString()" aria-expanded="false"
                            aria-controls="dwc-uri">
                        {{ t('Import Darwin Core Url') }}
                    </button>
                </h5>
            </div>
            <div id="dwc-uri" aria-labelledby="dwc-url-import" x-show="section === 'dwc-uri'" x-collapse x-cloak>
                <div class="card-body">
                    <form action="{{ route('admin.imports.dwcuri') }}"
                          method="post" role="form" class="form-horizontal" id="form-dwc-url"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                        <div class="mt-2 mb-4" x-data="{ instructions: false }">
                            <a href="#" class="btn btn-outline-primary" @click.prevent="instructions = ! instructions"
                               role="button" :aria-expanded="instructions.toString()" aria-expanded="false"
                               aria-controls="dwc-uri-instruction">{{ t('Instructions') }}</a>
                            <span id="dwc-uri-instruction" x-show="instructions" x-cloak>
                                            {{ t('Copy and paste a url link to the zip file. Only zipped Darwin Core files are accepted.') }}
                                            <a href="{{ '/darwin-core-example.zip' }}"
                                               class="link">{{ t('Download Example DWC File.') }}</a>
                                        </span>
                        </div>
                        <div class="form-group">
                            <label for="dwc-url" class="mb-0">{{ t('URL') }} <span
                                        class="color-action">*</span></label>
                            <input type="url" name="dwc-url" id="dwc-url" class="form-control a11y-form-control"
                                   required>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit"
                                    class="btn btn-primary pl-4 pr-4 text-uppercase">{{ t('Import') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>