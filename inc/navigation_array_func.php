<?php

use FriendsOfRedaxo\navigationArray\BuildArray;

// Function zur Generierung eines Navigationsarrays
/**
 * @deprecated will be removed in 4.0.0
 */
if (!function_exists('navArray')) {
    function navArray($start = -1, $depth = 0, $ignoreOfflines = true): array
    {
        $navArray = new BuildArray($start, $depth, $ignoreOfflines);
        $navArray->setCustomDataCallback(static function ($cat) {
            return ['catObject' => $cat];
        });
        return $navArray->generate();
    }
}
