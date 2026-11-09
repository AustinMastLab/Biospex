<?php

return [
    /*
     * Lets workflow:manage fetch GeoLocate stats automatically (GeoLocateStatsJob) for expeditions already in
     * GeoLocate. Per server, so development and local can stay off GeoLocate's API. Whether expeditions can
     * change to the GeoLocate workflow is the workflow's "enabled" setting in the admin dashboard instead.
     */
    'enabled' => env('GEOLOCATE_ENABLED', false),
    'actor_id' => 4,
    'workflow_id' => 6,
    'fields_file' => resource_path('json/geolocate-fields.json'),
    'api' => [
        'geolocate_token' => env('GEOLOCATE_TOKEN'),
        'base_stats_url' => 'https://coge.geo-locate.org/api/stats',
        'base_download_url' => 'https://coge.geo-locate.org/api/downloads',
    ],
    'dir' => [
        'parent' => 'geolocate',
        'csv' => 'geolocate/csv',
        'export' => 'geolocate/export',
        'geo-reconciled' => 'geolocate/geo-reconciled',
        'kml' => 'geolocate/kml',
    ],
];
