<?php
return [
    'google_drive' => [
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', env('APP_URL').'/google-drive/callback'),
        'root_folder_name' => env('GOOGLE_DRIVE_ROOT_FOLDER_NAME', 'Advocate Management'),
        'max_upload_mb' => (int) env('GOOGLE_DRIVE_MAX_UPLOAD_MB', 20),
    ],
];
