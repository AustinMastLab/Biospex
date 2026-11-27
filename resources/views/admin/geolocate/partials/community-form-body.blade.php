{{-- GeoLocate Community & Data Source Form, loaded into the global modal (resources/js/admin-modals.js). --}}
<div class="col-md-10 mx-auto mt-3 mb-3">
    <div x-data="{ example: false }">
        <p class="text-center">
            <a href="#coge-example" @click.prevent="example = ! example" role="button"
               :aria-expanded="example.toString()" aria-expanded="false"
               aria-controls="coge-example">Example</a>
        </p>
        <div id="coge-example" x-show="example" x-collapse x-cloak>
            <img src="{{ asset('images/examples/coge-community-data.jpg') }}" width="525"
                 alt="coge example image"/>
        </div>
    </div>
    @livewire('admin.geo-locate-community-form', ['expedition' => $expedition])
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
