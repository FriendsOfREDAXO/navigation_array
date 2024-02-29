<?php
namespace FriendsOfRedaxo\NavigationArray;

use rex_addon;
use rex_extension;

rex_extension::register('PACKAGES_INCLUDED', function($ep) {
    include_once rex_path::addon('navigation_array', 'inc/navigation_array_func.php');
});
