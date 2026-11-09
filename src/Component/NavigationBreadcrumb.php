<?php

namespace FriendsOfRedaxo\NavigationArray\Component;

use FriendsOfRedaxo\NavigationArray\BreadcrumbItem;
use FriendsOfRedaxo\NavigationArray\Navigation;
use Redaxo\Core\Content\Category;
use Redaxo\Core\View\Renderable;
use Redaxo\Core\View\RendersView;

/**
 * Breadcrumb as `<nav><ol>…</ol></nav>`, the last item with `aria-current="page"`, on request with schema.org JSON-LD.
 *
 *     echo new NavigationBreadcrumb(new Navigation(), label: 'Sie sind hier', jsonLd: true)->render();
 *
 * The markup is in NavigationBreadcrumb.view.php – replaceable with `ViewResolver::override()`.
 */
final class NavigationBreadcrumb implements Renderable
{
    use RendersView;

    /** @var list<BreadcrumbItem> */
    public array $items;

    /** @param list<BreadcrumbItem> $append own items at the end, e.g. the record of a detail page */
    public function __construct(
        public Navigation $navigation,
        public string $label = '',
        public bool $jsonLd = false,
        public string $class = '',
        public Category|int|null $category = null,
        public array $append = [],
    ) {
        $this->items = $navigation->breadcrumb($category, ...$append);
    }
}
