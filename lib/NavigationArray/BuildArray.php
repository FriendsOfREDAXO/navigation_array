<?php

namespace FriendsOfRedaxo\NavigationArray;

use rex_addon;
use rex_article;
use rex_category;
use rex_clang;
use rex_exception;
use rex_logger;
use rex_yrewrite;

use function array_filter;
use function array_merge;
use function call_user_func;
use function count;
use function in_array;
use function is_array;
use function is_callable;
use function is_int;
use function json_encode;

/**
 * Navigation Array Builder for REDAXO CMS
 * 
 * Modernized implementation with PHP 8.4+ ready patterns including:
 * - Enhanced type safety with strict typing
 * - Modern array and null handling patterns  
 * - Match expressions for cleaner conditional logic
 * - Improved method signatures and documentation
 * - Ready for property hooks and asymmetric visibility when PHP 8.4 is available
 */
class BuildArray
{
    // Modern typed properties with default values  
    private int $start = -1;
    private int $depth = 4;
    private bool $ignoreOfflines = true;
    private int $level = 0;

    // Property with validation (will be modernized when PHP 8.4 property hooks are available)
    private array $excludedCategories = [];

    // Using modern type declarations with mixed for callables
    private mixed $categoryFilterCallback = null;
    private mixed $customDataCallback = null;
    private array $startCats = [];

    // Public getters for read access
    public function getStart(): int { return $this->start; }
    public function getDepth(): int { return $this->depth; }
    public function getIgnoreOfflines(): bool { return $this->ignoreOfflines; }
    public function getLevel(): int { return $this->level; }

    /**
     * Constructor with property promotion and enhanced type safety
     */
    public function __construct(
        int $start = -1,
        int $depth = 4,
        bool $ignoreOfflines = true,
        int $depthSaved = 0,
        int $level = 0
    ) {
        $this->start = $start;
        $this->depth = $depth;
        $this->ignoreOfflines = $ignoreOfflines;
        $this->level = $level;
    }

    /**
     * Set categories to exclude from the navigation (int or array of ints with category ids).
     * Enhanced validation and type handling
     */
    public function setExcludedCategories(int|array $excludedCategories): self
    {
        if (is_int($excludedCategories)) {
            $excludedCategories = [$excludedCategories];
        }
        
        if (!is_array($excludedCategories)) {
            $message = 'Excluded categories must be an integer or an array of integers.';
            rex_logger::logError(E_USER_ERROR, $message, __FILE__, __LINE__);
            throw new rex_exception($message);
        }
        
        $this->excludedCategories = $excludedCategories;
        return $this;
    }

    /**
     * Set ID of the category to start with (default: -1, yrewrite mountID or root category).
     */
    public function setStart(int $start): self
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Set how many levels should the navigation show (default: 4).
     */
    public function setDepth(int $depth): self
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * Set whether offline categories should be ignored (default: true).
     */
    public function setIgnore(int $ignore): self
    {
        $this->ignoreOfflines = (bool) $ignore;
        return $this;
    }

    /**
     * Set current processing level
     */
    public function setLevel(int $level): self
    {
        $this->level = $level;
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
     * Generate the navigation array with modern patterns
     */
    public function generate(): array
    {
        $result = [];
        $currentCat = rex_category::getCurrent();
        $currentCatpath = $currentCat?->getPathAsArray() ?? [];
        $currentCat_id = $currentCat?->getId() ?? 0;

        $this->initializeStartCategory();

        $permittedStartCats = $this->getPermittedCategories($this->startCats);
        foreach ($permittedStartCats as $cat) {
            $result[] = $this->processCategory($cat, $currentCatpath, $currentCat_id);
        }
        
        return array_filter($result);
    }

    /**
     * Set a callback to filter categories.
     */
    public function setCategoryFilterCallback(mixed $callback): self
    {
        $this->categoryFilterCallback = $callback;
        return $this;
    }

    /**
     * Set a callback to add custom data to the category array.
     */
    public function setCustomDataCallback(mixed $callback): self
    {
        $this->customDataCallback = $callback;
        return $this;
    }

    /**
     * Generate the navigation array as JSON.
     * Uses modern JSON flags for better output quality
     */
    public function toJson(): string
    {
        $array = $this->generate();
        return json_encode(
            $array,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Initialize the start category based on the provided start value
     * or fallback to yrewrite domain or root categories
     * 
     * Uses modern PHP patterns and improved logic flow
     */
    private function initializeStartCategory(): void
    {
        // First step: determine start value if it's -1 (default)
        if ($this->start === -1) {
            $this->start = match (true) {
                rex_addon::get('yrewrite')->isAvailable() => $this->getYRewriteStartId(),
                default => 0
            };
        }
        
        // Second step: load categories based on determined start value
        $this->startCats = match (true) {
            is_array($this->start) => $this->loadCategoriesFromArray($this->start),
            $this->start !== 0 => $this->loadCategoriesFromId($this->start),
            default => rex_category::getRootCategories($this->ignoreOfflines)
        };
    }

    /**
     * Get YRewrite start ID with null coalescing
     */
    private function getYRewriteStartId(): int
    {
        $domain = rex_yrewrite::getDomainByArticleId(
            rex_article::getCurrentId(),
            rex_clang::getCurrentId()
        );
        return $domain?->getMountId() ?? 0;
    }

    /**
     * Load categories from array of IDs
     */
    private function loadCategoriesFromArray(array $startIds): array
    {
        $categories = [];
        foreach ($startIds as $startCatId) {
            $startCat = rex_category::get($startCatId);
            if ($startCat !== null) {
                $categories[] = $startCat;
            }
        }
        return $categories;
    }

    /**
     * Load categories from single ID
     */
    private function loadCategoriesFromId(int $startId): array
    {
        $startCat = rex_category::get($startId);
        return $startCat?->getChildren($this->ignoreOfflines) ?? [];
    }

    /**
     * Check if the category is permitted by ycom.
     * Uses modern null-safe operators and improved logic
     */
    private function isCategoryPermitted(rex_category $cat): bool
    {
        $ycomAddon = rex_addon::get('ycom');
        if (!$ycomAddon->isAvailable()) {
            return true;
        }
        
        $authPlugin = $ycomAddon->getPlugin('auth');
        return !$authPlugin->isAvailable() || $cat->isPermitted();
    }

    /**
     * Check if category meets all navigation requirements
     * Modern implementation with enhanced readability
     */
    private function isPermitted(rex_category $cat): bool
    {
        return $this->isCategoryPermitted($cat)
            && !in_array($cat->getId(), $this->excludedCategories, true)
            && ($this->categoryFilterCallback === null || ($this->categoryFilterCallback)($cat));
    }

    /**
     * Helper method using PHP 8.4-ready pattern for array checking
     * This demonstrates modern array handling patterns
     */
    private function hasPermittedChildren(rex_category $cat): bool
    {
        $children = $cat->getChildren($this->ignoreOfflines);
        if (empty($children)) {
            return false;
        }

        // Modern approach: check if any child is permitted
        foreach ($children as $child) {
            if ($this->isPermitted($child)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Find a category by condition using modern PHP patterns
     * Ready for PHP 8.4 array_find when available
     */
    private function findCategoryByCondition(array $categories, callable $condition): ?rex_category
    {
        foreach ($categories as $category) {
            if ($condition($category)) {
                return $category;
            }
        }
        return null;
    }

    /**
     * Get categories that meet all conditions using filter pattern
     * Demonstrates modern functional programming approach
     */
    private function getPermittedCategories(array $categories): array
    {
        return array_filter($categories, fn($cat) => $this->isPermitted($cat));
    }
    private function processCategory(rex_category $cat, array $currentCatpath, int $currentCat_id): array
    {
        $catId = $cat->getId();

        // Base category data with enhanced structure
        $categoryArray = [
            'catId' => $catId,
            'parentId' => $cat->getParentId(),
            'level' => $this->level,
            'catName' => $cat->getName(),
            'url' => $cat->getUrl(),
            'path' => $cat->getPathAsArray(),
            'active' => in_array($catId, $currentCatpath, true) || $currentCat_id === $catId,
            'current' => $currentCat_id === $catId,
        ];

        // Process children with improved logic
        $children = $this->processChildren($cat, $currentCatpath, $currentCat_id);
        
        $categoryArray['hasChildren'] = !empty($children);
        $categoryArray['children'] = $children;

        // Add custom data using modern callback syntax
        if ($this->customDataCallback !== null) {
            $customData = ($this->customDataCallback)($cat);
            if (is_array($customData)) {
                $categoryArray = array_merge($categoryArray, $customData);
            }
        }

        return $categoryArray;
    }

    /**
     * Process children categories with depth control
     * Uses modern helper methods for cleaner code
     */
    private function processChildren(rex_category $cat, array $currentCatpath, int $currentCat_id): array
    {
        if ($this->level >= $this->depth) {
            return [];
        }

        $childCats = $cat->getChildren($this->ignoreOfflines);
        if (empty($childCats)) {
            return [];
        }

        // Use modern helper method for filtering
        $permittedChildren = $this->getPermittedCategories($childCats);
        
        $children = [];
        $this->level++; // Increment level for children processing
        
        foreach ($permittedChildren as $child) {
            $children[] = $this->processCategory($child, $currentCatpath, $currentCat_id);
        }
        
        $this->level--; // Restore level after processing children
        
        return $children;
    }

    /**
     * Get category information either for current category or by ID
     * Enhanced with modern PHP patterns and better null safety
     */
    public function getCategory(?int $categoryId = null): array
    {
        // Determine category (either by ID or current) with null coalescing
        $cat = $categoryId !== null 
            ? rex_category::get($categoryId)
            : rex_category::getCurrent();

        if ($cat === null) {
            return [];
        }

        // Permission checks with improved structure
        $hasYcomPermissions = $this->isCategoryPermitted($cat);
        $isFilterPermitted = $this->categoryFilterCallback === null 
            || ($this->categoryFilterCallback)($cat);

        // Current category context
        $currentCat = rex_category::getCurrent();
        $currentCatpath = $currentCat?->getPathAsArray() ?? [];
        $currentCat_id = $currentCat?->getId() ?? 0;

        // Process children with modern approach
        $children = $this->getChildrenForCategory($cat, $currentCatpath, $currentCat_id);

        $catId = $cat->getId();
        $path = $cat->getPathAsArray();

        $categoryArray = [
            'catId' => $catId,
            'parentId' => $cat->getParentId(),
            'catName' => $cat->getName(),
            'url' => $cat->getUrl(),
            'hasChildren' => !empty($children),
            'children' => $children,
            'path' => $path,
            'pathCount' => count($path),
            'active' => in_array($catId, $currentCatpath, true) || $currentCat_id === $catId,
            'current' => $currentCat_id === $catId,
            'cat' => $cat,
            'ycom_permitted' => $hasYcomPermissions,
            'filter_permitted' => $isFilterPermitted,
            'is_permitted' => $hasYcomPermissions && $isFilterPermitted
        ];

        // Add custom data using modern callback syntax
        if ($this->customDataCallback !== null) {
            $customData = ($this->customDataCallback)($cat);
            if (is_array($customData)) {
                $categoryArray = array_merge($categoryArray, $customData);
            }
        }

        return $categoryArray;
    }

    /**
     * Get children for a specific category using modern patterns
     */
    private function getChildrenForCategory(rex_category $cat, array $currentCatpath, int $currentCat_id): array
    {
        $childCategories = $cat->getChildren($this->ignoreOfflines);
        if (empty($childCategories)) {
            return [];
        }

        // Use modern helper method
        $permittedChildren = $this->getPermittedCategories($childCategories);

        $children = [];
        foreach ($permittedChildren as $childCat) {
            $children[] = $this->processCategory($childCat, $currentCatpath, $currentCat_id);
        }

        return $children;
    }

    /**
     * Walk through the navigation and apply a callback to each item.
     * Modern implementation with enhanced type safety and clarity
     */
    public function walk(callable $callback): void
    {
        $this->initializeStartCategory();

        $currentCat = rex_category::getCurrent();
        $currentCatpath = $currentCat?->getPathAsArray() ?? [];
        $currentCat_id = $currentCat?->getId() ?? 0;

        $permittedStartCats = $this->getPermittedCategories($this->startCats);
        foreach ($permittedStartCats as $cat) {
            // Start with level 0 for root categories
            $this->walkRecursive($cat, $callback, $currentCatpath, $currentCat_id, 0);
        }
    }

    /**
     * Recursive helper function for the walk method.
     * Enhanced with modern PHP patterns and better depth control
     */
    private function walkRecursive(
        rex_category $cat,
        callable $callback,
        array $currentCatpath,
        int $currentCat_id,
        int $level
    ): void {
        // Check if we've exceeded maximum depth
        if ($level > $this->depth) {
            return;
        }

        // Process current category
        $originalLevel = $this->level;
        $this->level = $level;
        
        $item = $this->processCategory($cat, $currentCatpath, $currentCat_id);
        if (!empty($item)) {
            $callback($item, $level);
        }

        // Process children if we haven't reached maximum depth
        if ($level < $this->depth) {
            $childCats = $cat->getChildren($this->ignoreOfflines);
            $permittedChildren = $this->getPermittedCategories($childCats);
            
            foreach ($permittedChildren as $child) {
                $this->walkRecursive($child, $callback, $currentCatpath, $currentCat_id, $level + 1);
            }
        }

        // Restore original level
        $this->level = $originalLevel;
    }
}
