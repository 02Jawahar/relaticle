<?php

declare(strict_types=1);

return [
    // Lowercase singular/plural so Filament's "New :label" button reads naturally.
    'label' => 'deal',
    'plural_label' => 'deals',
    'navigation_label' => 'Deals',

    'fields' => [
        'name' => [
            'label' => 'Deal',
            'placeholder' => 'Enter deal title',
        ],
        'company_id' => [
            'label' => 'Company',
        ],
        'contact_id' => [
            'label' => 'Point of Contact',
        ],
        'creator' => [
            'label' => 'Created By',
        ],
        'creation_source' => [
            'label' => 'Creation Source',
        ],
        'created_at' => [
            'label' => 'Created At',
        ],
        'updated_at' => [
            'label' => 'Updated At',
        ],
        'deleted_at' => [
            'label' => 'Deleted At',
        ],
    ],

    'pages' => [
        'list' => [
            'actions' => [
                'import' => [
                    'label' => 'Import deals',
                ],
                'import_export' => [
                    'label' => 'Import / Export',
                ],
            ],
        ],
        'view' => [
            'actions' => [
                'edit' => [
                    'label' => 'Edit',
                ],
                'copy_page_url' => [
                    'label' => 'Copy page URL',
                ],
                'copy_record_id' => [
                    'label' => 'Copy record ID',
                ],
            ],
            'infolist' => [
                'fields' => [
                    'name' => [
                        'label' => '',
                    ],
                    'company' => [
                        'label' => 'Company',
                    ],
                    'contact' => [
                        'label' => 'Point of Contact',
                    ],
                ],
            ],
        ],
    ],
];
