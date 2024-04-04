<?php

namespace FriendsOfRedaxo\NavigationArray;

use rex_extension;
use rex_path;

rex_extension::register('PACKAGES_INCLUDED', function ($ep): void {
    include_once rex_path::addon('navigation_array', 'inc/navigation_array_func.php');
});
