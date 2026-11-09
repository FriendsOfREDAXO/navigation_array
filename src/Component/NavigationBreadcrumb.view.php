<?php

use FriendsOfRedaxo\NavigationArray\Component\NavigationBreadcrumb;
use Redaxo\Core\View\HtmlAttributes;

use function Redaxo\Core\View\escape;

return static function (NavigationBreadcrumb $breadcrumb): void {
    if ([] === $breadcrumb->items) {
        return;
    } ?>
    <nav <?= new HtmlAttributes(['class' => '' !== $breadcrumb->class ? [$breadcrumb->class] : [], 'aria-label' => '' !== $breadcrumb->label ? $breadcrumb->label : null]) ?>>
        <ol>
            <?php foreach ($breadcrumb->items as $item): ?>
                <li><?php if ($item->current || '' === $item->url): ?><span<?= $item->current ? ' aria-current="page"' : '' ?>><?= escape($item->name) ?></span><?php else: ?><a href="<?= escape($item->url) ?>"><?= escape($item->name) ?></a><?php endif ?></li>
            <?php endforeach ?>
        </ol>
    </nav>
    <?php if ($breadcrumb->jsonLd): ?>
        <?= $breadcrumb->navigation->jsonLd($breadcrumb->category, ...$breadcrumb->append) ?>
    <?php endif ?>
<?php };
