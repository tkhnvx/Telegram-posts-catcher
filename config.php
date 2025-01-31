<?php

/**
* Author: Alex Tikhonov.
* https://github.com/tkhnvx/
* Date: 31/01/2025
*/

// Telegram Bot API Token
$telegram_token = 'XXXX70XXXX:AAFNxxxxOSUzzXXXXX7DFn_qdspsTPXXXXX';

// Целевой канал, куда пересылать новости
$target_channel = '-100XXXXXXXXXX';

// ID каналов-источников
$source_channels = [
    -100XXXXXXXXXX,  // ID первого канала
    -100XXXXXXXXXX,  // ID второго канала
    -100XXXXXXXXXX,
    // остальные ID
];

// Файл для хранения последнего update_id
$last_update_file = 'last_update.txt';
// Файл для хранения логов
$log_file = 'log.txt';
?>
