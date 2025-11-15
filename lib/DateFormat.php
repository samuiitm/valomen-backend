<?php

function formatEventDate(string $startDate, string $endDate): string
{
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);

    return $start->format('M j') . ' - ' . $end->format('M j');
}

function formatMatchDate(string $date): string
{
    $d = new DateTime($date);
    return $d->format('D, F j, Y');
}

function formatMatchHour(string $time): string
{
    $t = DateTime::createFromFormat('H:i:s', $time);
    return $t ? $t->format('g:i A') : $time;
}

function getMatchStatusInfo(string $date, string $time): array
{
    $matchDateTime = new DateTime($date . ' ' . $time);
    
    $now = new DateTime('2025-11-13 14:00:00');

    if ($matchDateTime <= $now) {
        return [
            'cssClass'  => 'live',
            'label'     => 'LIVE',
            'countdown' => null,
        ];
    }

    $diffSeconds = $matchDateTime->getTimestamp() - $now->getTimestamp();

    $days = intdiv($diffSeconds, 86400);
    $diffSeconds %= 86400;
    $hours = intdiv($diffSeconds, 3600);
    $diffSeconds %= 3600;
    $minutes = intdiv($diffSeconds, 60);

    $parts = [];

    if ($days > 0) {
        $parts[] = $days . 'd';
    }

    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }

    if ($days === 0 && $hours === 0 && $minutes > 0) {
        $parts[] = $minutes . 'm';
    }

    $countdown = implode(' ', $parts);

    return [
        'cssClass'  => 'upcoming',
        'label'     => 'Upcoming',
        'countdown' => $countdown,
    ];
}