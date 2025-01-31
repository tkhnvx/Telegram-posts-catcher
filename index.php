<?php

/**
* Author: Alex Tikhonov.
* https://github.com/tkhnvx/
* Date: 31/01/2025
*/


// 1. Настройки безопасности
require 'config.php'; // Вынесем токены и данные в отдельный файл

// 2. Функция для отправки сообщений в Telegram
function sendMessageToTelegram($chat_id, $text, $parse_mode = "HTML") {
    global $telegram_token;
    $url = "https://api.telegram.org/bot$telegram_token/sendMessage";
    
    $post_fields = [
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => $parse_mode
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// 3. Функция для пересылки сообщений (включая медиа)
function forwardMessage($from_chat_id, $message_id, $to_chat_id) {
    global $telegram_token;
    $url = "https://api.telegram.org/bot$telegram_token/forwardMessage";

    $post_fields = [
        'chat_id'      => $to_chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id'   => $message_id
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// 4. Получение новых сообщений из каналов
function getUpdates() {
    global $telegram_token, $source_channels, $last_update_file;

    $offset = file_exists($last_update_file) ? (int)file_get_contents($last_update_file) : 0;
    $url = "https://api.telegram.org/bot$telegram_token/getUpdates?offset=$offset";

    $result = file_get_contents($url);
    $updates = json_decode($result, true);

    if (!isset($updates['result'])) {
        return [];
    }

    $last_update_id = $offset;
    $messages = [];

    foreach ($updates['result'] as $update) {
        if (isset($update['update_id']) && $update['update_id'] > $last_update_id) {
            $last_update_id = $update['update_id'];
        }

        if (isset($update['message']) && isset($update['message']['chat']['id'])) {
            $chat_id = $update['message']['chat']['id'];

            if (in_array($chat_id, $source_channels)) {
                $messages[] = $update['message'];
            }
        }
    }

    file_put_contents($last_update_file, $last_update_id + 1);
    return $messages;
}

// 5. Обработка сообщений и пересылка
function processMessages() {
    global $target_channel, $log_file;
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Запуск processMessages()\n", FILE_APPEND);
    
    $messages = getUpdates();
    
    if (empty($messages)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Нет новых сообщений\n", FILE_APPEND);
        return;
    }

    foreach ($messages as $message) {
        $from_chat_id = $message['chat']['id'];
        $message_id = $message['message_id'];
        $channel_name = isset($message['chat']['title']) ? $message['chat']['title'] : 'Неизвестный канал';

        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Сообщение из $channel_name ($from_chat_id)\n", FILE_APPEND);

        $text = isset($message['text']) ? $message['text'] : '';
        $text .= "\n\n📢 Источник: <b>$channel_name</b>";

        if (isset($message['photo']) || isset($message['video'])) {
            forwardMessage($from_chat_id, $message_id, $target_channel);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Переслано медиа из $channel_name\n", FILE_APPEND);
        } else {
            sendMessageToTelegram($target_channel, $text);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Переслано текстовое сообщение\n", FILE_APPEND);
        }
    }
}

// Запуск обработки сообщений
processMessages();
?>
