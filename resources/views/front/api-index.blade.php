@extends('front.layout.default')

{{-- Web site Title --}}
@section('title')
    {{ t('API') }}
@stop

{{-- Content --}}
@section('header')
    <header style="background-image: url(/images/page-banners/banner-field.jpg);">
        <nav class="header-admin navbar navbar-expand-md box-shadow">
            <a href="/"><img src="/images/page/biospex_logo.svg" alt="BIOSPEX"
                             class="my-0 mr-md-auto top-logo-admin font-weight-normal"/></a>
            @include('common.nav')
        </nav>
    </header>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 my-4 mx-auto">
            <div class="card white px-4 box-shadow h-100">
                <h3 class="text-center pt-4 text-uppercase">{{ t('Biospex API') }}</h3>
                <hr class="header mx-auto" style="width:300px;">
                <div class="d-flex align-items-start justify-content-between my-4 mx-auto">
                    <a class="btn btn-primary mr-4" href="https://biospex.docs.apiary.io/#">{{ t('API Docs') }}</a>
                    <a class="btn btn-primary mr-4" href="https://github.com/iDigBio/Biospex">{{ t('GitHub') }}</a>
                </div>

                <h4 class="mt-2">{{ t('Authentication') }}</h4>
                <p>
                    {{ t('Every request needs an API token sent as a bearer token, plus an Accept header for JSON. Tokens for public project and expedition data carry the public:read ability.') }}
                    <a href="{{ route('front.contact.index') }}">{{ t('Contact us') }}</a> {{ t('to request one.') }}
                </p>
                <pre class="bg-light p-3"><code>curl -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json" {{ route('api.v1.projects.index') }}</code></pre>
                <p>{{ t('Requests are rate limited per client; going over the limit returns HTTP 429.') }}</p>

                <h4 class="mt-2">{{ t('Public projects and expeditions') }}</h4>
                <p>{{ t('Read-only. Only projects and expeditions shown on the public site are returned; anything else returns 404.') }}</p>
                <table class="table table-sm table-bordered">
                    <thead>
                    <tr>
                        <th>{{ t('Endpoint') }}</th>
                        <th>{{ t('Returns') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><code>GET /v1/projects</code></td>
                        <td>{{ t('Paginated list of public projects.') }}</td>
                    </tr>
                    <tr>
                        <td><code>GET /v1/projects/{uuid}</code></td>
                        <td>{{ t('One project.') }}</td>
                    </tr>
                    <tr>
                        <td><code>GET /v1/expeditions</code></td>
                        <td>{{ t('Paginated list of public expeditions.') }}</td>
                    </tr>
                    <tr>
                        <td><code>GET /v1/expeditions/{uuid}</code></td>
                        <td>{{ t('One expedition.') }}</td>
                    </tr>
                    </tbody>
                </table>

                <h5>{{ t('Query parameters') }}</h5>
                <ul>
                    <li><code>per_page</code> &mdash; {{ t('results per page, 1 to 100 (default 25). Lists only.') }}</li>
                    <li><code>page</code> &mdash; {{ t('page number. Lists only.') }}</li>
                    <li><code>project_uuid</code> &mdash; {{ t('only expeditions belonging to this project.') }}</li>
                    <li><code>status</code> &mdash; {{ t('active or completed expeditions; omit for both.') }}</li>
                </ul>

                <h5>{{ t('Response') }}</h5>
                <p>{{ t('Lists return the items in data, with links (first, last, prev, next) and meta (current_page, per_page, total) for paging. A single item is returned in data.') }}</p>
                <pre class="bg-light p-3"><code>{
  "data": {
    "uuid": "…",
    "title": "…",
    "description": "…",
    "keywords": "…",
    "completed": false,
    "logo_url": "https://…",
    "project": { "uuid": "…", "title": "…" },
    "stats": { "transcriptions_completed": 1200, "transcriber_count": 85, "percent_completed": 40.5 },
    "created_at": "2026-01-15T14:02:11+00:00",
    "links": {
      "self": "{{ route('api.v1.expeditions.show', 'UUID') }}",
      "project": "{{ route('api.v1.projects.show', 'UUID') }}",
      "participate": "https://www.zooniverse.org/…"
    }
  }
}</code></pre>
                <p>
                    {{ t('Projects return uuid, title, slug, organization, organization_website, description_short, description_long, geographic_scope, taxonomic_scope, temporal_scope, keywords, logo_url, expeditions_count, transcriptions_completed, created_at and links (self, expeditions, page).') }}
                    {{ t('An expedition\'s participate link is null unless it is currently live on Zooniverse.') }}
                </p>
            </div>
        </div>
    </div>
@endsection
