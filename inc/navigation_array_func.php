<?php
// Function zur Generierung eines Navigationsarrays
if (!function_exists('navArray')) {
    function navArray($start = 0, $depth = 0, $ignoreOfflines = true)
    {
        $navArray = new FriendsOfRedaxo\navigationArray($start, $depth, $ignoreOfflines);
        $navigation->setCustomDataCallback(function ($cat) {
            return ['catObject' => $cat];
        });
        $result = $navArray->generate();
        return $result;
    }
}
