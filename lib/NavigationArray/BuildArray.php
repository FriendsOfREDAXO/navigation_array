<?php

namespace FriendsOfRedaxo\NavigationArray;

use rex_addon;
use rex_article;
use rex_category;
use rex_clang;
use rex_extension;
use rex_extension_point;
use rex_yrewrite;

use function call_user_func;
use function in_array;
use function is_array;
use function is_callable;
use function is_int;

class BuildArray
{
    /** @var callable|null */
    private $categoryFilterCallback;

    /** @var callable|null */
    private $customDataCallback;

    private int $depth;
    private bool $ignoreOfflines;
    private int $level;

    /** @var int|array<int> */
    private int|array $start;

    /** @var list<rex_category> */
    private array $startCats = [];

    /** @var array<int> */
    private array $excludedCategories = [];

    private bool $includeArticles = false;

    public function __construct(int $start = -1, int $depth = 4, bool $ignoreOfflines = true, int $level = 0)
    {
        $this->start = $start;
        $this->depth = $depth;
        $this->ignoreOfflines = $ignoreOfflines;
        $this->level = $level;
    }

    /**
     * Set categories to exclude from the navigation (int or array of ints with category ids).
     *
     * @param int|array<int> $excludedCategories
     * @return $this
     */
    public function setExcludedCategories(int|array $excludedCategories): self
    {
        if (is_int($excludedCategories)) {
            $excludedCategories = [$excludedCategories];
        }

        $this->excludedCategories = array_values($excludedCategories);
        return $this;
    }

    /**
     * Set the start category ID or an array of category IDs (default: -1, yrewrite mountID or root).
     *
     * @param int|array<int> $start
     * @return $this
     */
    public function setStart(int|array $start): self
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Include articles (non-start) of each category in the result under the key 'articles'.
     *
     * @return $this
     */
    public function setIncludeArticles(bool $include = true): self
    {
        $this->includeArticles = $include;
        return $this;
    }

    /**
     * Set how many levels should the navigation show (default: 4).
     *
     * @param int $depth
     * @return $this
     */
    public function setDepth(int $depth): self
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * Set whether offline categories should be ignored (default: true).
     *
     * @param int $ignore 1 for true, 0 for false
     * @return $this
     */
    public function setIgnore(int $ignore): self
    {
        $this->ignoreOfflines = (bool)$ignore;
        return $this;
    }

    /**
     * @param int $lvl
     * @return $this
     */
    public function setLevel(int $lvl): self
    {
        $this->level = $lvl;
        return $this;
    }

    /**
     * Create a new instance of BuildArray.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Generate the navigation array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generate(): array
    {
        $result = [];
        $currentCat = rex_category::getCurrent();
        $currentCatpath = $currentCat ? $currentCat->getPathAsArray() : [];
        $currentCat_id = $currentCat ? $currentCat->getId() : 0;

        $this->initializeStartCategory();

        foreach ($this->startCats as $cat) {
            if ($this->isPermitted($cat)) {
                $result[] = $this->processCategory($cat, $currentCatpath, $currentCat_id);
            }
        }
        return array_filter($result);
    }

    /**
     * Set a callback to filter categories.
     *
     * @param callable $callback
     * @return $this
     */
    public function setCategoryFilterCallback(callable $callback): self
    {
        $this->categoryFilterCallback = $callback;
        return $this;
    }

    /**
     * Set a callback to add custom data to the category array.
     *
     * @param callable $callback
     * @return $this
     */
    public function setCustomDataCallback(callable $callback): self
    {
        $this->customDataCallback = $callback;
        return $this;
    }

    /**
     * Generate the navigation array as JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        $array = $this->generate();
        return (string) json_encode($array, JSON_PRETTY_PRINT);
    }

    /**
     * Initialize the start category based on the provided start value
     * or fallback to yrewrite domain or root categories
     * 
     * @return void
     */
    private function initializeStartCategory(): void
    {
        // Erster Schritt: Startwert ermitteln, falls es -1 (Default) ist
        if ($this->start == -1) {
            // YRewrite Domain-Startpunkt versuchen zu holen
            if (rex_addon::get('yrewrite')->isAvailable()) {
                $domain = rex_yrewrite::getDomainByArticleId(rex_article::getCurrentId(), rex_clang::getCurrentId());
                $this->start = ($domain !== null) ? $domain->getMountId() : 0;
            } else {
                // Fallback auf Root-Kategorien
                $this->start = 0;
            }
        }
        
        // Zweiter Schritt: Kategorien basierend auf dem ermittelten Startwert laden
        // Arrays von Kategorie-IDs
        if (is_array($this->start)) {
            $this->startCats = [];
            foreach ($this->start as $startCatId) {
                $startCat = rex_category::get($startCatId);
                if ($startCat) {
                    $this->startCats[] = $startCat;
                }
            }
            return;
        }
        
        // Spezifische Kategorie-ID (nicht 0)
        if ($this->start != 0) {
            $startCat = rex_category::get($this->start);
            if ($startCat) {
                $this->startCats = $startCat->getChildren($this->ignoreOfflines);
                return;
            }
        }
        
        // Fallback auf Root-Kategorien
        $this->startCats = rex_category::getRootCategories($this->ignoreOfflines);
    }

    /**
     * Check if the category is permitted by ycom.
     *
     * @param rex_category $cat
     * @return bool
     */
    private function isCategoryPermitted(rex_category $cat): bool
    {
        // Erst prüfen, ob das ycom-Addon überhaupt installiert und aktiviert ist
        if (!rex_addon::get('ycom')->isAvailable()) {
            return true;
        }
        
        // Dann prüfen, ob das auth-Plugin verfügbar ist
        $ycom_check = rex_addon::get('ycom')->getPlugin('auth')->isAvailable();
        return !$ycom_check || $cat->isPermitted();
    }

    /**
     * Check if category meets all navigation requirements
     * Prüft ob die Kategorie alle Anforderungen erfüllt (YCom, Ausschlüsse, Filter)
     *
     * @param rex_category $cat
     * @return bool
     */
    private function isPermitted(rex_category $cat): bool
    {
        // Prüfe YCom Berechtigungen
        if (!$this->isCategoryPermitted($cat)) {
            return false;
        }

        // Prüfe ob Kategorie ausgeschlossen ist
        if (in_array($cat->getId(), $this->excludedCategories)) {
            return false;
        }

        // Prüfe Category Filter Callback
        if (is_callable($this->categoryFilterCallback) && !call_user_func($this->categoryFilterCallback, $cat)) {
            return false;
        }

        return true;
    }

    /**
     * @param rex_category $cat
     * @param array<int> $currentCatpath
     * @param int $currentCat_id
     * @return array<string, mixed>
     */
    private function processCategory(rex_category $cat, array $currentCatpath, int $currentCat_id): array
    {
        $catId = $cat->getId();

        // Base category data
        $categoryArray = [
            'catId' => $catId,
            'parentId' => $cat->getParentId(),
            'level' => $this->level,
            'catName' => $cat->getName(),
            'url' => $cat->getUrl(),
            'path' => $cat->getPathAsArray(),
            'active' => in_array($catId, $currentCatpath) || $currentCat_id == $catId,
            'current' => $currentCat_id == $catId,
        ];

        // Process children only if we haven't reached the maximum depth
        $children = [];
        if ($this->level < $this->depth) {
            $childCats = $cat->getChildren($this->ignoreOfflines);
            if ($childCats) {
                $this->level++; // Increment level for children
                foreach ($childCats as $child) {
                    if ($this->isPermitted($child)) {
                        $children[] = $this->processCategory($child, $currentCatpath, $currentCat_id);
                    }
                }
                $this->level--; // Restore level after processing children
            }
        }

        $categoryArray['hasChildren'] = !empty($children);
        $categoryArray['children'] = $children;

        // Artikel einbeziehen wenn aktiviert (Start-Artikel ausgenommen)
        if ($this->includeArticles) {
            $articles = [];
            $currentArticleId = rex_article::getCurrentId();
            foreach ($cat->getArticles($this->ignoreOfflines) as $article) {
                if ($article->isStartArticle()) {
                    continue;
                }
                $articles[] = [
                    'catId' => null,
                    'articleId' => $article->getId(),
                    'parentId' => $article->getCategoryId(),
                    'level' => $this->level + 1,
                    'catName' => $article->getName(),
                    'url' => $article->getUrl(),
                    'path' => $article->getPathAsArray(),
                    'active' => $article->getId() === $currentArticleId,
                    'current' => $article->getId() === $currentArticleId,
                    'hasChildren' => false,
                    'children' => [],
                ];
            }
            $categoryArray['articles'] = $articles;
        }

        // Add custom data if callback is set
        if (is_callable($this->customDataCallback)) {
            $customData = call_user_func($this->customDataCallback, $cat);
            if (is_array($customData)) {
                $categoryArray = array_merge($categoryArray, $customData);
            }
        }

        // Extension Point: andere AddOns können Item-Daten ergänzen/anpassen
        /** @var array<string, mixed> $categoryArray */
        $categoryArray = rex_extension::registerPoint(new rex_extension_point(
            'NAVIGATION_ARRAY_GENERATE_ITEM',
            $categoryArray,
            ['cat' => $cat, 'level' => $this->level]
        ));

        return $categoryArray;
    }

    /**
     * Get category information either for current category or by ID
     *
     * @param int|null $categoryId Optional category ID
     * @return array<string, mixed>
     */
    public function getCategory(?int $categoryId = null): array
    {
        // Kategorie ermitteln (entweder durch ID oder current)
        $cat = null;
        if ($categoryId !== null) {
            $cat = rex_category::get($categoryId);
        } else {
            $cat = rex_category::getCurrent();
        }

        if (!$cat) {
            return [];
        }

        // YCom-Berechtigungen prüfen
        $hasYcomPermissions = $this->isCategoryPermitted($cat);

        // Filter-Status prüfen
        $isFilterPermitted = true;
        if (is_callable($this->categoryFilterCallback)) {
            $isFilterPermitted = call_user_func($this->categoryFilterCallback, $cat);
        }

        $catId = $cat->getId();
        $path = $cat->getPathAsArray();
        $currentCat = rex_category::getCurrent();
        $currentCatpath = $currentCat ? $currentCat->getPathAsArray() : [];
        $currentCat_id = $currentCat ? $currentCat->getId() : 0;

        // Kinder mit processCategory verarbeiten
        $children = [];
        $childCategories = $cat->getChildren($this->ignoreOfflines);
        if ($childCategories) {
            foreach ($childCategories as $childCat) {
                if ($this->isPermitted($childCat)) {
                    $children[] = $this->processCategory($childCat, $currentCatpath, $currentCat_id);
                }
            }
        }

        $categoryArray = [
            'catId' => $catId,
            'parentId' => $cat->getParentId(),
            'catName' => $cat->getName(),
            'url' => $cat->getUrl(),
            'hasChildren' => !empty($children),
            'children' => $children,  // Kinder aus processCategory
            'path' => $path,
            'pathCount' => count($path),
            'active' => in_array($catId, $currentCatpath) || $currentCat_id == $catId,
            'current' => $currentCat_id == $catId,
            'cat' => $cat,
            'ycom_permitted' => $hasYcomPermissions,
            'filter_permitted' => $isFilterPermitted,
            'is_permitted' => $hasYcomPermissions && $isFilterPermitted
        ];

        // Custom Data hinzufügen wenn ein Callback definiert ist
        if (is_callable($this->customDataCallback)) {
            $customData = call_user_func($this->customDataCallback, $cat);
            if (is_array($customData)) {
                $categoryArray = array_merge($categoryArray, $customData);
            }
        }

        return $categoryArray;
    }

    /**
     * Walk through the navigation and apply a callback to each item.
     *
     * @param callable $callback The callback function to apply to each item.
     *                            It will receive the item (category array) and the level as arguments.
     * @return void
     */
    public function walk(callable $callback): void
    {
        $this->initializeStartCategory();

        $currentCat = rex_category::getCurrent();
        $currentCatpath = $currentCat ? $currentCat->getPathAsArray() : [];
        $currentCat_id = $currentCat ? $currentCat->getId() : 0;

        foreach ($this->startCats as $cat) {
            if ($this->isPermitted($cat)) {
                // Start mit Level 0 für die Root-Kategorien
                $this->walkRecursive($cat, $callback, $currentCatpath, $currentCat_id, 0);
            }
        }
    }

    /**
     * Returns the breadcrumb path from the root down to the current or given category.
     *
     * Additional items – e.g. a detail-page title from the URL addon or a YForm dataset –
     * can be appended via $append:
     *
     * ```php
     * $nav->getBreadcrumb(append: [['name' => 'Detail', 'url' => rex_getUrl(42)]]);
     * ```
     *
     * @param int|null $categoryId  Target category (null = current)
     * @param array<array<string, string>> $append  Extra items appended at the end
     * @return array<int, array<string, mixed>>
     */
    public function getBreadcrumb(?int $categoryId = null, array $append = []): array
    {
        $cat = $categoryId !== null ? rex_category::get($categoryId) : rex_category::getCurrent();

        $path = [];

        if ($cat !== null) {
            $current = $cat;
            $visited = [];
            while ($current !== null) {
                if (in_array($current->getId(), $visited, true)) {
                    break;
                }
                $visited[] = $current->getId();
                array_unshift($path, [
                    'catId' => $current->getId(),
                    'catName' => $current->getName(),
                    'url' => $current->getUrl(),
                    'current' => false,
                ]);
                $parentId = $current->getParentId();
                $current = $parentId > 0 ? rex_category::get($parentId) : null;
            }
        }

        foreach ($append as $item) {
            $path[] = [
                'catId' => null,
                'catName' => $item['name'] ?? '',
                'url' => $item['url'] ?? '',
                'current' => false,
            ];
        }

        if ($path !== []) {
            $path[array_key_last($path)]['current'] = true;
        }

        return $path;
    }

    /**
     * Returns a Schema.org BreadcrumbList as a JSON-LD <script> tag.
     *
     * Accepts the same parameters as getBreadcrumb() – including $append for
     * custom last-path items (article title, detail pages, etc.).
     *
     * @param int|null $categoryId  Target category (null = current)
     * @param array<array<string, string>> $append  Extra items appended at the end
     * @return string
     */
    public function toJsonLd(?int $categoryId = null, array $append = []): string
    {
        $breadcrumb = $this->getBreadcrumb($categoryId, $append);

        $items = [];
        foreach ($breadcrumb as $position => $item) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['catName'],
                'item' => $item['url'],
            ];
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];

        return '<script type="application/ld+json">'
            . (string) json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '</script>';
    }

    /**
     * Recursive helper function for the walk method.
     *
     * @param rex_category $cat
     * @param callable $callback
     * @param array<int> $currentCatpath
     * @param int $currentCat_id
     * @param int $level
     * @return void
     */
    private function walkRecursive(rex_category $cat, callable $callback, array $currentCatpath, int $currentCat_id, int $level): void
    {
        // Prüfe zuerst, ob wir die maximale Tiefe überschritten haben
        if ($level > $this->depth) {
            return;
        }

        $item = $this->processCategory($cat, $currentCatpath, $currentCat_id);
        if (!empty($item)) {
            call_user_func($callback, $item, $level);
        }

        // Hole Kindkategorien nur wenn wir noch nicht die maximale Tiefe erreicht haben
        if ($level < $this->depth) {
            $childCats = $cat->getChildren($this->ignoreOfflines);
            if (!empty($childCats)) {
                foreach ($childCats as $child) {
                    if ($this->isPermitted($child)) {
                        $this->walkRecursive($child, $callback, $currentCatpath, $currentCat_id, $level + 1);
                    }
                }
            }
        }
    }
}
