<?php

function formatCurrency(int $amount): string
{
    $formatted = number_format($amount, 0, '.', ',');

    return $formatted;
}