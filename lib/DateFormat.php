<?php

function formatEventDate(?string $startDate, ?string $endDate): string
{
    if (empty($startDate)) {
        return 'TBD';
    }

    $start = new DateTime($startDate);

    if ($endDate === null || $endDate === '' || $endDate === '0000-00-00') {
        return $start->format('M j') . ' - TBD';
    }

    $end = new DateTime($endDate);

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

function getMatchCountdown(string $date, string $time): ?string
{
    $matchDateTime = new DateTime("$date $time");

    $now = new DateTime('2025-11-13 13:00:00');

    if ($matchDateTime <= $now) {
        return null;
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

    return implode(' ', $parts);
}

function getElapsedTime(string $date, string $time): string {
    $matchDateTime = new DateTime("$date $time");
    $now = new DateTime('2025-11-13 13:00:00');

    if ($matchDateTime > $now) {
        return "just now";
    }

    $diff = $matchDateTime->diff($now);

    if ($diff->y > 0) {
        return $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
    }

    if ($diff->m > 0) {
        return $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
    }

    if ($diff->d > 0) {
        return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    }

    if ($diff->h > 0) {
        return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    }

    if ($diff->i > 0) {
        return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
    }

    return "Just Now";
}