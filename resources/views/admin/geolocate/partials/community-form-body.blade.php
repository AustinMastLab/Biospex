<div class="col-md-10 mx-auto mt-3 mb-3">
    <p class="text-center">
        <a href="#coge-example" data-toggle="collapse" aria-expanded="false"
           aria-controls="cogeExample">Example</a>
    </p>
    <div class="collapse" id="coge-example">
        <img src="{{ asset('images/examples/coge-community-data.jpg') }}" width="525"
             alt="coge example image"/>
    </div>

    <form id="geolocate-community-form" class="modal-form" method="post"
          action="{{ route('admin.geolocate-community.update', [$expedition]) }}"
          role="form">
        @csrf
        <div class="form-row justify-content-center">
            <div class="controls form-group col-sm-6">
                <label for="community-form-select"
                       class="col-form-label">{{ t('GeoLocate Community') }}:</label>
                <select id="community-form-select" class="form-control custom-select select-primary"
                        name="community_id">
                    <option value="" class="text-uppercase">{{ t('New') }}</option>
                    @foreach($expedition->project->geoLocateCommunities as $community)
                        @php($selected = isset($expedition->geoLocateDataSource->geo_locate_community_id) && $expedition->geoLocateDataSource->geo_locate_community_id === $community->id ? 'selected' : '')
                        <option value="{{ $community->id }}" {{ $selected }}>{{ $community->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="community-row" class="form-row justify-content-center collapse">
            <div class="form-group col-sm-6">
                <div class="form-group form-inline">
                    <label id="community-label" for="community"
                           class="col-form-label">{{ t('New GeoLocate Community') }}:</label>
                    <input type="text"
                           class="w-100 form-control a11y-form-control"
                           id="community" name="community"
                           placeholder="{{ t('Enter new community name') }}">
                </div>
            </div>
        </div>
        <div class="form-row justify-content-center">
            <div class="form-group col-sm-6">
                <div class="form-group form-inline">
                    <label for="data-source" class="col-form-label required">{{ t('Data Source') }}:</label>
                    <input type="text"
                           class="w-100 form-control a11y-form-control"
                           id="data-source" name="data_source"
                           value="{{ isset($expedition->geoLocateDataSource->data_source) ? $expedition->geoLocateDataSource->data_source : '' }}"
                           placeholder="Enter data source" required>
                </div>
            </div>
        </div>
        <div class="form-row justify-content-center">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
        <div class="feedback text-center mt-3"></div>
    </form>
</div>

<div class="col-md-10 mx-auto mt-5 mb-3">
    <h2 class="">{{ t('Communities') }}</h2>
    <table id="geolocate-tbl" class="table table-striped table-bordered data-table">
        <thead>
        <tr>
            <th scope="col"><span class="sr-only">{{ t('Delete') }}</span></th>
            <th scope="col">{{ t('Community') }}</th>
            <th scope="col">{{ t('# Assigned DataSources') }}</th>
        </tr>
        </thead>
        <tbody>
        @if($expedition->project->geoLocateCommunities->isNotEmpty())
            @foreach($expedition->project->geoLocateCommunities as $community)
                @include('admin.geolocate.partials.geolocate-loop')
            @endforeach
        @else
            <tr>
                <td colspan="3">{{ t('No Communities Found') }}</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>
