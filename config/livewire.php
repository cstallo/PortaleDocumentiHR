<?php

$maxUploadKilobytes = (int) env('LIVEWIRE_UPLOAD_MAX_KB', 204800);
$maxUploadTime = (int) env('LIVEWIRE_UPLOAD_MAX_TIME', 20);

return [
    'temporary_file_upload' => [
        'disk' => 'local',
        'max_size' => $maxUploadKilobytes,
        'rules' => ['required', 'file', 'max:'.$maxUploadKilobytes],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:60,1',
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => $maxUploadTime,
        'cleanup' => true,
    ],
];
