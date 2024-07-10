<?php

declare(strict_types=1);
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$token = "7282747198:AAEPazmYlkvreCOTFt1-5bG92-QUfInWIfU";
$tgApi = "https://api.telegram.org/bot$token/";

$client = new Client([
    'base_uri' => $tgApi,
]);

function sendMessage(Client $client, string $chatId, string $text): void {
    try {
        $response = $client->post('sendMessage', [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $text,
            ]
        ]);
        $json = $response->getBody()->getContents();
        print_r(json_decode($json, true));
    } catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            echo 'Response: ' . $response->getBody()->getContents() . "\n";
        }
    }
}
$n=0;
while ($n<10) {
    try {
        $response = $client->get('getUpdates');
        $updates = json_decode($response->getBody()->getContents(), true);

        // Debug uchun loglar qo'shish
        print_r($updates);

        if (isset($updates['result'])) {
            foreach ($updates['result'] as $update) {
                if (isset($update['message']['chat']['id']) && isset($update['message']['text'])) {
                    $chatId = (string)$update['message']['chat']['id'];  // Type casting to string
                    $text = $update['message']['text'];
                    
                    // Foydalanuvchi xabarini o'qib olish va salom qaytarish
                    sendMessage($client, $chatId, "Salom, " . $update['message']['from']['first_name'] . "! Sizning xabaringiz: " . $text);
                }
            }
        }
    } catch (RequestException $e) {
        echo 'Error: ' . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            echo 'Response: ' . $response->getBody()->getContents() . "\n";
        }
    }

    // Bir oz kutish (1 soniya) va qaytadan so'rov yuborish
    sleep(1);
    $n++;
}

?>
