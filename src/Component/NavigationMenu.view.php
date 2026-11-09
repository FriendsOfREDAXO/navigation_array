<?php

use FriendsOfRedaxo\NavigationArray\Component\NavigationMenu;
use FriendsOfRedaxo\NavigationArray\NavigationItem;
use Redaxo\Core\View\HtmlAttributes;

use function Redaxo\Core\View\escape;

return static function (NavigationMenu $menu): void {
    $html = $menu->navigation->walk(static fn (NavigationItem $item, Closure $children): string => '<li' . ($item->active ? ' class="' . escape($menu->activeClass) . '"' : '') . '>'
        . '<a href="' . escape($item->url) . '"' . ($item->current ? ' aria-current="page"' : '') . '>' . escape($item->name) . '</a>'
        . ($item->hasChildren() ? '<ul>' . $children() . '</ul>' : '')
        . '</li>');
    if ('' === $html) {
        return;
    } ?>
    <nav <?= new HtmlAttributes(['class' => '' !== $menu->class ? [$menu->class] : [], 'aria-label' => '' !== $menu->label ? $menu->label : null]) ?>>
        <ul><?= $html ?></ul>
    </nav>
<?php };
