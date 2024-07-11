<?php

$apiToken = "7282747198:AAEPazmYlkvreCOTFt1-5bG92-QUfInWIfU";
$apiUrl = "https://api.telegram.org/bot$apiToken/";
$exchangeApiUrl = "https://api.exchangerate-api.com/v4/latest/";

function getUpdates($offset) {
    global $apiUrl;
    $url = $apiUrl . "getUpdates?timeout=100&offset=" . $offset;
    $response = file_get_contents($url);
    return json_decode($response, true);
}

function sendMessage($chatId, $message, $replyMarkup = null) {
    global $apiUrl;
    $url = $apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($message) . "&parse_mode=Markdown";
    if ($replyMarkup) {
        $url .= "&reply_markup=" . urlencode(json_encode($replyMarkup));
    }
    file_get_contents($url);
}

function getExchangeRate($currency) {
    global $exchangeApiUrl;
    $url = $exchangeApiUrl . $currency;
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    if (isset($data['rates']['UZS'])) {
        return $data['rates'];
    } else {
        return false;
    }
}

function showMainMenu($chatId) {
    $mainMenu = [
        ['USD 💵', 'EUR 💶', 'GBP 💷'],
        ['RUB 🇷🇺', 'CNY 🇨🇳', 'JPY 🇯🇵'],
        ['UZS -> Other']
    ];
    $keyboard = ['keyboard' => $mainMenu, 'one_time_keyboard' => true, 'resize_keyboard' => true];
    sendMessage($chatId, "*Valyuta turini tanlang:*", $keyboard);
}

function showUZSConversionMenu($chatId) {
    $currencies = [
        ['USD 💵', 'EUR 💶', 'GBP 💷'],
        ['RUB 🇷🇺', 'CNY 🇨🇳', 'JPY 🇯🇵']
    ];
    $keyboard = ['keyboard' => $currencies, 'one_time_keyboard' => true, 'resize_keyboard' => true];
    sendMessage($chatId, "Qaysi valyutaga aylantirmoqchisiz:", $keyboard);
}

function showAllCurrencies($chatId) {
    $currencies = [
        ['USD 💵', 'EUR 💶', 'GBP 💷'],
        ['RUB 🇷🇺', 'CNY 🇨🇳', 'JPY 🇯🇵'],
        ['AUD 🇦🇺', 'CAD 🇨🇦', 'CHF 🇨🇭'],
        ['HKD 🇭🇰', 'INR 🇮🇳', 'KRW 🇰🇷'],
        ['TRY 🇹🇷', 'ZAR 🇿🇦', 'SEK 🇸🇪']
        // Add more currencies as needed
    ];
    $keyboard = ['keyboard' => $currencies, 'one_time_keyboard' => true, 'resize_keyboard' => true];
    sendMessage($chatId, "*Valyuta turini tanlang:*", $keyboard);
}

function returnToMainMenu($chatId) {
    showMainMenu($chatId);
}

$updateId = 0;

while (true) {
    $updates = getUpdates($updateId);

    foreach ($updates['result'] as $update) {
        $updateId = $update['update_id'] + 1;
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'];

        if (preg_match('/^\/start/', $text)) {
            showMainMenu($chatId);
        } elseif ($text === 'UZS -> Other') {
            showAllCurrencies($chatId);
            $file = fopen("user_data_$chatId.txt", "w");
            fwrite($file, "UZS");
            fclose($file);
        } elseif (preg_match('/^[A-Z]{3}/', $text)) {
            $currency = strtoupper(substr($text, 0, 3)); // Emoji va boshqa belgilarni olib tashlash
            $file_path = "user_data_$chatId.txt";
            if (file_exists($file_path)) {
                $file = fopen($file_path, "r");
                $savedCurrency = fread($file, filesize($file_path));
                fclose($file);

                if ($savedCurrency === "UZS") {
                    sendMessage($chatId, "_Miqdorini kiriting (UZS):_");
                    $file = fopen("user_data_$chatId.txt", "w");
                    fwrite($file, "UZS_TO_$currency");
                    fclose($file);
                } else {
                    sendMessage($chatId, "_Miqdorini kiriting:_");
                    $file = fopen("user_data_$chatId.txt", "w");
                    fwrite($file, $currency);
                    fclose($file);
                }
            } else {
                sendMessage($chatId, "_Miqdorini kiriting:_");
                $file = fopen("user_data_$chatId.txt", "w");
                fwrite($file, $currency);
                fclose($file);
            }
        } elseif (is_numeric($text)) {
            $file_path = "user_data_$chatId.txt";
            if (file_exists($file_path)) {
                $amount = floatval($text);
                $file = fopen($file_path, "r");
                $currencyData = fread($file, filesize($file_path));
                fclose($file);

                if (strpos($currencyData, "UZS_TO_") === 0) {
                    $currency = str_replace("UZS_TO_", "", $currencyData);
                    $rate = getExchangeRate('UZS');
                    if ($rate !== false) {
                        $result = $amount / $rate[$currency];
                        sendMessage($chatId, "*$amount UZS* -> *$result $currency*");
                    } else {
                        sendMessage($chatId, "Valyuta kursini olishda *xatolik* yuz berdi. Qayta urinib ko'ring.");
                    }
                } else {
                    $rate = getExchangeRate($currencyData);
                    if ($rate !== false) {
                        if ($currencyData === "UZS") {
                            $result = $amount / $rate['UZS'];
                            sendMessage($chatId, "*$amount UZS* -> *$result $currencyData*");
                        } else {
                            $result = $amount * $rate['UZS'];
                            sendMessage($chatId, "*$amount $currencyData* -> *$result UZS*");
                        }
                    } else {
                        sendMessage($chatId, "Valyuta kursini olishda *xatolik* yuz berdi. Qayta urinib ko'ring.");
                    }
                }

                returnToMainMenu($chatId);
                unlink($file_path);
            } else {
                sendMessage($chatId, "Miqdorini kiritishdan oldin *valyuta turini tanlang.*");
            }
        } else {
            sendMessage($chatId, "Noto'g'ri format. Qayta urinib ko'ring.");
        }
    }
}
?>
