<?php

namespace FriendsOfRedaxo\NavigationArray;

use Closure;
use FriendsOfRedaxo\NavigationArray\ExtensionPoint\NavigationItemGenerated;
use FriendsOfRedaxo\NavigationArray\ExtensionPoint\NavigationStart;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\Category;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Language\Language;

use function array_filter;
use function array_key_last;
use function array_keys;
use function array_map;
use function array_values;
use function in_array;
use function is_array;
use function json_encode;
use function ltrim;
use function preg_match;

use const JSON_HEX_TAG;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The navigation as a tree of {@see NavigationItem}: categories (optionally with their articles), the current path,
 * own data – the output is entirely up to you.
 *
 *     $nav = new Navigation(depth: 1, exclude: [12]);
 *     foreach ($nav->items() as $item) {
 *         // $item->name, $item->url, $item->active, $item->current, $item->children …
 *     }
 *
 * Offline categories are left out (unless `ignoreOffline: false`), as are categories without permission
 * (`Category::isPermitted()`, e.g. of a login addon).
 */
final readonly class Navigation
{
    /**
     * @param int|list<int>|null $start null = start of the current site (extension point {@see NavigationStart}, otherwise
     *                                  the root), 0 = root, an id = its children, a list of ids = exactly these categories
     * @param int $depth levels below the first one (0 = only the first level)
     * @param list<int> $exclude categories to leave out, with their children
     * @param (Closure(Category): bool)|null $filter `false` leaves the category (with its children) out
     * @param (Closure(Category): array<string, mixed>)|null $data own data of each item (`$item->data`)
     * @param bool $articles include the articles of each category (`$item->articles`, without the start article)
     * @param int|null $languageId language of the navigation (default: the current one)
     */
    public function __construct(
        public int|array|null $start = null,
        public int $depth = 4,
        public bool $ignoreOffline = true,
        public array $exclude = [],
        public ?Closure $filter = null,
        public ?Closure $data = null,
        public bool $articles = false,
        public ?int $languageId = null,
    ) {}

    /** @return list<NavigationItem> */
    public function items(): array
    {
        [$path, $currentId] = $this->current();

        $items = [];
        foreach ($this->startCategories() as $category) {
            if ($this->allowed($category)) {
                $items[] = $this->item($category, 0, $path, $currentId);
            }
        }

        return $items;
    }

    /**
     * The recommended way to output the navigation: the callback renders one item and gets a closure that renders its
     * children the same way, so nested markup needs no bookkeeping of levels.
     *
     *     echo '<ul>' . $nav->walk(static fn (NavigationItem $item, Closure $children): string => '<li>'
     *         . '<a href="' . escape($item->url) . '">' . escape($item->name) . '</a>'
     *         . ($item->hasChildren() ? '<ul>' . $children() . '</ul>' : '')
     *         . '</li>') . '</ul>';
     *
     * Without output (e.g. collecting items) the callback just returns `''` – and calls `$children()` to go deeper.
     *
     * @param Closure(NavigationItem, Closure(): string): string $callback
     */
    public function walk(Closure $callback): string
    {
        return $this->render($this->items(), $callback);
    }

    /** Item of the current or the given category, with its children – also outside of the start */
    public function find(Category|int|null $category = null): ?NavigationItem
    {
        $category = $this->category($category);
        if (!$category || !$this->allowed($category)) {
            return null;
        }
        [$path, $currentId] = $this->current();

        return $this->item($category, 0, $path, $currentId);
    }

    /**
     * Path from the root to the current or the given category. For the current page it ends with the current article,
     * if that is not the start article of its category. Own entries are added at the end, e.g. the record of a detail
     * page: `$nav->breadcrumb(null, new BreadcrumbItem($record->get('title'), $url))`.
     *
     * @return list<BreadcrumbItem>
     */
    public function breadcrumb(Category|int|null $category = null, BreadcrumbItem ...$append): array
    {
        $article = null === $category ? Article::getCurrent($this->languageId) : null;
        $category = $this->category($category);
        $items = $category ? array_map(BreadcrumbItem::of(...), [...$category->getParentTree(), $category]) : [];
        if ($article && !$article->isStartArticle()) {
            $items[] = new BreadcrumbItem($article->name, $article->getUrl());
        }
        $items = [...$items, ...array_values($append)];
        $last = array_key_last($items);

        return array_map(static fn (BreadcrumbItem $i, int $k): BreadcrumbItem => $k === $last ? $i->asCurrent() : $i, $items, array_keys($items));
    }

    /**
     * The breadcrumb as schema.org `BreadcrumbList` (`<script type="application/ld+json">`) with absolute urls, as search
     * engines require them (on the console the env var `REX_BASE_URL` is needed for that).
     */
    public function jsonLd(Category|int|null $category = null, BreadcrumbItem ...$append): string
    {
        // scheme and host of the site (REX_BASE_URL, otherwise the request); the urls of the items carry the path
        $origin = (string) preg_replace('~^(https?://[^/]+).*$~', '$1', Url::absoluteBase());
        $list = [];
        foreach ($this->breadcrumb($category, ...$append) as $position => $item) {
            $url = '' === $item->url || preg_match('~^https?://~', $item->url) ? $item->url : $origin . '/' . ltrim($item->url, '/');
            $list[] = ['@type' => 'ListItem', 'position' => $position + 1, 'name' => $item->name, 'item' => $url];
        }

        return '<script type="application/ld+json">'
            . json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
            . '</script>';
    }

    /**
     * @param list<NavigationItem> $items
     * @param Closure(NavigationItem, Closure(): string): string $callback
     */
    private function render(array $items, Closure $callback): string
    {
        $out = '';
        foreach ($items as $item) {
            $out .= $callback($item, fn (): string => $this->render($item->children, $callback));
        }

        return $out;
    }

    /** @return array{list<int>, int} ancestors and id of the current category (0 without one) */
    private function current(): array
    {
        $current = Category::getCurrent($this->languageId);

        return [$current->path ?? [], $current->id ?? 0];
    }

    private function category(Category|int|null $category): ?Category
    {
        return match (true) {
            $category instanceof Category => $category,
            null === $category => Category::getCurrent($this->languageId),
            default => Category::get($category, $this->languageId),
        };
    }

    /** @return list<Category> */
    private function startCategories(): array
    {
        $start = $this->start ?? Extension::dispatch(new NavigationStart(Article::getCurrentId(), $this->languageId ?? Language::getCurrentId()));

        return match (true) {
            is_array($start) => array_values(array_filter(array_map(fn (int $id): ?Category => Category::get($id, $this->languageId), $start))),
            0 !== $start && null !== ($category = Category::get($start, $this->languageId)) => $category->getChildren($this->ignoreOffline),
            default => Category::getRootCategories($this->ignoreOffline, $this->languageId),
        };
    }

    private function allowed(Category $category): bool
    {
        return $category->isPermitted()
            && !in_array($category->id, $this->exclude, true)
            && (null === $this->filter || ($this->filter)($category));
    }

    /** @param list<int> $currentPath */
    private function item(Category $category, int $level, array $currentPath, int $currentId): NavigationItem
    {
        $children = [];
        if ($level < $this->depth) {
            foreach ($category->getChildren($this->ignoreOffline) as $child) {
                if ($this->allowed($child)) {
                    $children[] = $this->item($child, $level + 1, $currentPath, $currentId);
                }
            }
        }

        $item = new NavigationItem(
            element: $category,
            id: $category->id,
            parentId: $category->parentId ?? 0,
            name: $category->name,
            url: $category->getUrl(),
            level: $level,
            path: $category->path,
            active: $currentId === $category->id || in_array($category->id, $currentPath, true),
            current: $currentId === $category->id,
            children: $children,
            articles: $this->articles ? $this->articles($category, $level + 1) : [],
            data: null !== $this->data ? ($this->data)($category) : [],
        );

        return Extension::dispatch(new NavigationItemGenerated($item));
    }

    /** @return list<NavigationItem> */
    private function articles(Category $category, int $level): array
    {
        $currentId = Article::getCurrentId();
        $items = [];
        foreach ($category->getArticles($this->ignoreOffline) as $article) {
            if ($article->isStartArticle() || !$article->isPermitted()) {
                continue;
            }
            $items[] = new NavigationItem(
                element: $article,
                id: $article->id,
                parentId: $article->categoryId ?? 0,
                name: $article->name,
                url: $article->getUrl(),
                level: $level,
                path: $article->path,
                active: $currentId === $article->id,
                current: $currentId === $article->id,
            );
        }

        return $items;
    }
}
