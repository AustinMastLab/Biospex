<?php

return [

    'db_log' => env('DB_LOG', false),

    'expedition_size' => 20000,

    'reverb_debug' => env('REVERB_DEBUG', 0),

    'wedigbio_dashboard_rows' => env('WEDIGBIO_DASHBOARD_ROWS', 100),

    'panoptes_listener' => env('PANOPTES_LISTENER', 'prod-panoptes-pusher'),
    'panoptes_listener_enabled' => env('PANOPTES_LISTENER_ENABLED', 1),

    'api' => [
        'domain' => env('API_DOMAIN'),
    ],

    'admin' => [
        'home' => '/projects',
        'group' => 'Admin',
        'group_id' => 1,
        'user_id' => 1,
    ],

    'batch_dir' => 'batch',
    'export_dir' => 'export',
    'import_dir' => 'import',
    'report_dir' => 'report',
    'scratch_dir' => 'scratch',

    'missing_project_logo' => env('APP_URL').'/images/placeholders/project.png',
    'missing_expedition_logo' => env('APP_URL').'/images/placeholders/card-image-place-holder02.jpg',
    'missing_avatar_small' => env('APP_URL').'/images/avatars/small/missing.png',
    'missing_avatar_medium' => env('APP_URL').'/images/avatars/medium/missing.png',

    'uploads' => [
        'project_logos' => env('UPLOAD_PROJECT_LOGOS', 'uploads/projects/logos'),
        'expedition_logos' => env('UPLOAD_EXPEDITION_LOGOS', 'uploads/expeditions/logos'),
        'expedition_logos_medium' => env('UPLOAD_EXPEDITION_LOGOS_MEDIUM', 'uploads/expeditions/logos/medium'),
        'expedition_logos_original' => env('UPLOAD_EXPEDITION_LOGOS_ORIGINAL', 'uploads/expeditions/logos/original'),
        'profile_avatars' => env('UPLOAD_PROFILE_AVATARS', 'uploads/profiles/avatars'),
        'profile_avatars_small' => env('UPLOAD_PROFILE_AVATARS_SMALL', 'uploads/profiles/avatars/small'),
        'profile_avatars_medium' => env('UPLOAD_PROFILE_AVATARS_MEDIUM', 'uploads/profiles/avatars/medium'),
        'profile_avatars_original' => env('UPLOAD_PROFILE_AVATARS_ORIGINAL', 'uploads/profiles/avatars/original'),
        'resources' => env('UPLOAD_RESOURCES', 'uploads/resources'),
        'project_resources_base' => env('UPLOAD_PROJECT_RESOURCES_BASE', 'uploads/project-resources'),
        'project_resources_downloads' => env('UPLOAD_PROJECT_RESOURCES_DOWNLOADS', 'uploads/project-resources/downloads'),
        'project-assets' => env('UPLOAD_PROJECT_ASSETS', 'uploads/project-assets'),
        'site-assets' => env('UPLOAD_SITE_ASSETS', 'uploads/site-assets'),
    ],

    'ocr_stages' => [
        'Waiting for OCR to start', // 0
        'Processing Images', // 1
    ],

    'project_chart_series' => resource_path('json/projectChartSeries.json'),
    'project_chart_config' => resource_path('json/projectChartConfig.json'),

    // Whether OCR is enabled for overnight scripts.
    'ocr_enabled' => env('OCR_ENABLED', true),

    'poll_scoreboard_channel' => 'scoreboard',
    'poll_bingo_channel' => 'bingo',
    'poll_wedigbio_progress_channel' => 'wedigbio-progress',

    'project_assets' => [
        'Website URL',
        'Video URL',
        'File Download',
    ],

    /**
     * iDigBio api query url
     */
    'recordset_url' => 'https://beta-api.idigbio.org/v2/download/?rq={"recordset":"RECORDSET_ID"}',

    /**
     * DCA import row types for multimedia.
     */
    'dwcRequiredRowTypes' => [
        'http://rs.tdwg.org/ac/terms/multimedia',
        'http://rs.gbif.org/terms/1.0/image',
        'http://rs.tdwg.org/dwc/terms/occurrence',
    ],

    'dwcRequiredFields' => [
        'core' => ['id'],
        'extension' => [
            'coreid' => [],
            'accessURI' => ['http://rs.tdwg.org/ac/terms/accessURI'],
            'identifier' => [
                'http://purl.org/dc/terms/identifier',
                'http://rs.tdwg.org/ac/terms/providerManagedID',
                'http://portal.idigbio.org/terms/uuid',
                'http://portal.idigbio.org/terms/recordId',
            ],
        ],
    ],

    /**
     * Darwin Core import thresholds for memory management
     */
    'dwc_import_thresholds' => [
        'file_size_mb' => env('DWC_FILE_SIZE_THRESHOLD_MB', 30),
        'row_count' => env('DWC_ROW_COUNT_THRESHOLD', 25000),
    ],

    'dwcTranscriptFields' => [
        'stateProvince' => 'state_province',
        'StateProvince' => 'state_province',
        'State/Province' => 'state_province',
        'State Province' => 'state_province',
        'State_Province' => 'state_province',
        'State' => 'state_province',
        'County' => 'county',
        'subject_county' => 'county',
    ],

    'dwcOccurrenceFields' => [
        'stateProvince' => 'state_province',
        'State_Province' => 'state_province',
        'State Province' => 'state_province',
        'State/Province' => 'state_province',
        'State' => 'state_province',
        'County' => 'county',
    ],

    /* Beanstalk Queues */
    'queue' => [
        'chart' => 'chart',
        'classification' => 'classification',
        'default' => 'default',
        'event' => 'event',
        'export' => 'export',
        'geolocate' => 'geolocate',
        'import' => 'import',
        'ocr' => 'ocr',
        'biospex_event' => 'biospex-event',
        'pusher_handler' => 'pusher-handler',
        'wedigbio_event' => 'wedigbio-event',
        'pusher_process' => 'pusher-process',
        'reconcile' => 'reconcile',
        'workflow' => 'workflow',
        'sernec_file' => 'sernec-file',
        'sernec_row' => 'sernec-row',
    ],

    /* Images */
    /* Min and max logo and banner sizes used in Project model. Max Zoonviverse image. Thumb sizes. */
    'thumb_default_img' => 'thumbs/default_thumb.png',
    'thumb_output_dir' => 'thumbs',
    'thumb_width' => 300,
    'thumb_height' => 300,
    'logo' => '300x200',
    'banner' => '1200x250',

    /**
     * Columns used in select statement for grid.
     */
    'defaultGridVisible' => [
        'imageId',
        'exported',
        'accessURI',
        'ocr',
    ],
    'defaultSubGridVisible' => [
        'imageId',
        'institutionCode',
        'scientificName',
        'recordId',
    ],

    /**
     * Default advertise fields for PPSR_CORE
     */
    'ppsr' => [
        'ProjectGUID' => ['private' => 'uuid'],
        'ProjectName' => ['column' => 'title'],
        'ProjectDataProvider' => ['value' => config('app.name')],
        'ProjectDescription' => ['column' => 'description_long'],
        'ProjectDateLastUpdated' => ['date' => 'updated_at'],
        'ProjectContactName' => ['column' => 'contact'],
        'ProjectContactEmail' => ['column' => 'contact_email'],
        'ProjectStatus' => [],
        'ProjectOrganization' => ['column' => 'organization'],
        'ProjectVolunteerSupport' => ['column' => 'incentives'],
        'ProjectURL' => ['url' => 'slug'],
        'ProjectFacebook' => ['column' => 'facebook'],
        'ProjectTwitter' => ['column' => 'twitter'],
        'ProjectKeywords' => ['array' => ['keywords', 'geographic_scope', 'temporal_scope']],
        'fieldOfScience' => [],
        'participationType' => [],
        'participantEducation' => ['column' => 'language_skills'],
        'fundingSource' => ['column' => 'funding_source'],
        'projectBlog' => ['column' => 'blog_url'],
        'projectImage' => ['url' => 'logo'],
    ],

    'wedigbio_start_date' => env('WEDIGBIO_START_DATE'),
    'wedigbio_end_date' => env('WEDIGBIO_END_DATE'),

    'deployment_fields' => [
        'APP_ENV',
        'APP_DOMAIN',
        'APP_SERVER_USER',
        'APP_CURRENT_PATH',
        'APP_TAG',
        'AWS_SQS_BATCH_UPDATE',
        'AWS_SQS_EXPORT_UPDATE',
        'AWS_SQS_RECONCILE_UPDATE',
        'AWS_SQS_OCR_UPDATE',
        'AWS_SQS_IMAGE_TRIGGER_DLQ',
        'PANOPTES_LISTENER_ENABLED',
        'PANOPTES_LISTENER',
        'QUEUE_CHART',
        'QUEUE_CLASSIFICATION',
        'QUEUE_DEFAULT',
        'QUEUE_EVENT',
        'QUEUE_GEOLOCATE',
        'QUEUE_IMPORT',
        'QUEUE_OCR',
        'QUEUE_EXPORT',
        'QUEUE_RECONCILE',
        'QUEUE_WORKFLOW',
        'QUEUE_PUSHER_PROCESS',
        'QUEUE_BIOSPEX_EVENT',
        'QUEUE_PUSHER_HANDLER',
        'QUEUE_WEDIGBIO_EVENT',
        'QUEUE_SERNEC_FILE',
        'QUEUE_SERNEC_ROW',
        'REVERB_DEBUG',
    ],
];
