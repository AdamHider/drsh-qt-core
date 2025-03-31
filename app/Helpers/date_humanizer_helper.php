<?php
if(!function_exists('get_time_ago')) {
    function get_time_ago($datetime) {
        $time = strtotime($datetime); // Преобразуем строку в timestamp
        $now = time(); // Текущее время
        $diff = $now - $time; // Разница во времени в секундах
        // Функция для склонения
        if(!function_exists('pluralForm')) {
            function pluralForm($number, $forms) {
                $number = abs($number) % 100;
                $lastDigit = $number % 10;

                if ($number > 10 && $number < 20) {
                    return $forms[2]; // Например, "дней"
                }
                if ($lastDigit > 1 && $lastDigit < 5) {
                    return $forms[1]; // Например, "дня"
                }
                if ($lastDigit == 1) {
                    return $forms[0]; // Например, "день"
                }
                return $forms[2]; // Например, "дней"
            }
        }

        if ($diff < 60) {
            return "меньше минуты назад";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "$minutes " . pluralForm($minutes, ["минута", "минуты", "минут"]) . " назад";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "$hours " . pluralForm($hours, ["час", "часа", "часов"]) . " назад";
        } elseif ($diff < 172800) {
            return "Вчера";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "$days " . pluralForm($days, ["день", "дня", "дней"]) . " назад";
        } else {
            return date("d.m.Y", $time);
        }
    }
}