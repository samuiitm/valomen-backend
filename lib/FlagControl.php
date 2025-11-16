<?php
function getFlagPath(string $countryCode): string {
    $safeCode = htmlspecialchars($countryCode);
    $path = "assets/icons/badges/{$safeCode}.png";

    if (!file_exists($path)) {
        $path = "assets/icons/badges/tbd.png";
    }

    return $path;
}
