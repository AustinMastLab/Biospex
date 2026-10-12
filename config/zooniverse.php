<?php

return [
    'enabled' => env('ZOONIVERSE_ENABLED', true),
    'actor_id' => 2,
    'project_url' => 'https://www.zooniverse.org/projects/PROJECT_SLUG',
    'reconcile_problem_regex' => '/No (?:select|text) match on|Only 1 transcript in|There was 1 number in/i',
    'talk_api_uri' => 'https://talk.zooniverse.org/comments?http_cache=true&section=project-PROJECT_ID&focus_id=SUBJECT_ID&focus_type=Subject&page=1&sort=-created_at',
    'new_expedition_notification' => [2 => 'ZooniverseNewExpedition'],
    'participate_url' => 'https://www.zooniverse.org/projects/PROJECT_SLUG/classify?workflow=WORKFLOW_ID',
    'pusher' => [
        'id' => env('ZOONIVERSE_PUSHER_ID'),
        'cluster' => 'mt1',
        'channel' => 'panoptes',
    ],

    'directory' => [
        'parent' => 'zooniverse',
        'classification' => 'zooniverse/classification',
        'reconciled' => 'zooniverse/reconciled',
        'reconciled-with-expert' => 'zooniverse/reconciled-with-expert',
        'reconciled-with-user' => 'zooniverse/reconciled-with-user',
        'summary' => 'zooniverse/summary',
        'transcript' => 'zooniverse/transcript',
        'explained' => 'zooniverse/explained',
        'lambda-reconciliation' => 'zooniverse/lambda-reconciliation',
        'lambda-ocr' => 'zooniverse/lambda-ocr',
        'lambda-ocr-wip' => 'zooniverse/lambda-ocr-wip',
    ],

    'file_types' => [
        'classification',
        'transcript',
        'reconciled',
        'summary',
    ],

    'search_urls' => [
        'eol' => 'http://www.eol.org/search?q=SCIENTIFIC_NAME',
        'mol' => 'https://www.mol.org/species/SCIENTIFIC_NAME',
        'idigbio' => 'https://www.idigbio.org/portal/search?rq={%22scientificname%22:%22SCIENTIFIC_NAME%22}',
    ],

    'csv_map' => [
        'subjectId' => 'id',
        'imageName' => 'id',
        'references' => ['occurrence' => 'references'],
        'scientificName' => ['occurrence' => 'scientificName'],
        'country' => ['occurrence' => 'country'],
        'stateProvince' => ['occurrence' => 'stateProvince'],
        'county' => ['occurrence' => 'county'],
        'eol' => ['occurrence' => 'scientificName'],
        'mol' => ['occurrence' => 'scientificName'],
        'idigbio' => ['occurrence' => 'scientificName'],
        '#institutionCode' => ['occurrence' => 'institutionCode'],
        '#collectionCode' => ['occurrence' => 'collectionCode'],
        '#catalogNumber' => ['occurrence' => 'catalogNumber'],
        '#occurrenceRecordId' => ['occurrence' => ['recordID', 'recordId']],
        '#occurrernceId' => ['occurrence' => 'id'],
        '#imageId' => 'imageId',
        '#expeditionId' => '',
        '#expeditionTitle' => '',
    ],

    'panoptes' => [
        'client_id' => env('ZOONIVERSE_PANOPTES_CLIENT_ID'),
        'client_secret' => env('ZOONIVERSE_PANOPTES_CLIENT_SECRET'),
        'api_uri' => 'https://www.zooniverse.org/api',
        'token_uri' => 'https://www.zooniverse.org/oauth/token',
        'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
        'scopes' => 'user+project+group+collection+classification+subject',
    ],

    // See TranscriptionHelper.php
    'reserved_encoded' => [
        '_id',
        'classification_id',
        'workflow_id',
        'user_name',
        'create_date',
        'classification_started_at',
        'classification_finished_at',
        'updated_at',
        'created_at',
        'problem',
        'columns',
        'reviewed',
        'Country',
        'County',
        'Location',
    ],
    'mapped_transcription_fields' => [
        'province' => [
            'StateProvince',
            'State_Province',
            'State Province',
            'State/Province',
            'subject_stateProvince',
        ],
        'collector' => [
            'Collected By',
            'Collected_By',
            'CollectedBy',
            'Collected By (first collector only)',
            'subject_collectedBy',
        ],
        'taxon' => [
            'Scientific Name',
            'Scientific_Name',
            'ScientificName',
            'subject_scientificName',
        ],
    ],

    'reconcile' => [
        'file_path' => env('ZOONIVERSE_RECONCILE_FILE_PATH'),
        'python_path' => env('ZOONIVERSE_RECONCILE_PYTHON_PATH'),
    ],
];
