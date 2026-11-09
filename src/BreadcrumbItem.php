<?php

namespace FriendsOfRedaxo\NavigationArray;

use Redaxo\Core\Content\Category;

/**
 * An entry of the breadcrumb: a category, or an own entry (e.g. the record of a detail page).
 */
final readonly class BreadcrumbItem
{
    public function __construct(
        public string $name,
        public string $url,
        public bool $current = false,
        public ?Category $category = null,
    ) {}

    public static function of(Category $category): self
    {
        return new self($category->name, $category->getUrl(), category: $category);
    }

    public function asCurrent(): self
    {
        return clone ($this, ['current' => true]);
    }
}
