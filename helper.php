<?php

function milisecondsToHours(int $ms): float {
    return $ms / 3600000;
}

function getHoursFormatted(float $hours): string {
    return number_format($hours, 2, '.', '');
}