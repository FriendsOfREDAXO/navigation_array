<?php

namespace FriendsOfRedaxo\NavigationArray\Component;

use FriendsOfRedaxo\NavigationArray\Navigation;
use Redaxo\Core\View\Renderable;
use Redaxo\Core\View\RendersView;

/**
 * Menu as a nested list: `<nav><ul>…</ul></nav>`, the current item with `aria-current="page"`.
 *
 *     echo new NavigationMenu(new Navigation(depth: 1), label: 'Hauptmenü')->render();
 *
 * The markup is in NavigationMenu.view.php – a project replaces it with
 * `ViewResolver::override(NavigationMenu::class, __DIR__ . '/views/menu.php')`.
 */
final class NavigationMenu implements Renderable
{
    use RendersView;

    public function __construct(
        public Navigation $navigation,
        /** Accessible name of the `<nav>` */
        public string $label = '',
        /** Class of the items in the current path */
        public string $activeClass = 'active',
        public string $class = '',
    ) {}
}
