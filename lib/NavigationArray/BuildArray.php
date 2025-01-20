<?php

namespace FriendsOfRedaxo\NavigationArray;

use rex_addon;
use rex_article;
use rex_category;
use rex_clang;
use rex_exception;
use rex_logger;
use rex_yrewrite;

use function call_user_func;
use function in_array;
use function is_array;
use function is_callable;
use function is_int;

class BuildArray
{
    private $categoryFilterCallback;
    private $customDataCallback;
    private $depth;
    private $ignoreOfflines;
    private $level;
    private $start;
    private $startCats;
    private $excludedCategories = []; // Neue Eigenschaft

    public function __construct(int $start = -1, int $depth = 4, bool $ignoreOfflines = true, $depthSaved = 0, int $level = 0)
    {
        $this->start = $start;
        $this->depth = $depth;
        $this->ignoreOfflines = $ignoreOfflines;
        $this->level = $level;
    }

    /**
     * Set categories to exclude from the navigation (int or array of ints with category ids).
     *
     * @param int|array $excludedCategories
     * @return $this
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
     *
     * @param int $start
     * @return $this
     */
    public function setStart(int $start): self
    {
        $this->start = $start;
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
     * @param bool $ignore
     * @return $this
     */
    public function setIgnore(int $ignore): self
    {
        $this->ignoreOfflines = $ignore;
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
     * @return array
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
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    /**
     * @return void
     */
    private function initializeStartCategory(): void
    {
        // Fallback if no mountpoint is available
        $domain = rex_yrewrite::getDomainByArticleId(rex_article::getCurrentId(), rex_clang::getCurrentId());
        $this->start = ($domain !== null) ? $domain->getMountId() : 0;
        // get yrewrite mount id if available
        if (is_int($this->start) && $this->start == -1 && rex_addon::get('yrewrite')->isAvailable()) {
            $this->start = rex_yrewrite::getDomainByArticleId(rex_article::getCurrentId(), rex_clang::getCurrentId())->getMountId();
        } elseif ($this->start == -1) {
            $this->start = 0;
        }

        if (is_array($this->start)) {
            $this->startCats = [];
            foreach ($this->start as $startCatId) {
                $startCat = rex_category::get($startCatId);
                if ($startCat) {
                    $this->startCats[] = $startCat;
                }
            }
        } elseif ($this->start != 0) {
            $startCat = rex_category::get($this->start);
            if ($startCat) {
                $this->startCats = $startCat->getChildren($this->ignoreOfflines);
            } else {
                $this->startCats = rex_category::getRootCategories($this->ignoreOfflines);
            }
        } else {
            $this->startCats = rex_category::getRootCategories($this->ignoreOfflines);
        }
    }

    /**
     * Check if the category is permitted by ycom.
     *
     * @param rex_category $cat
     * @return bool
     */
    private function isCategoryPermitted(rex_category $cat): bool
    {
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
     * @param array $currentCatpath
     * @param int $currentCat_id
     * @return array
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

    // Add custom data if callback is set
    if (is_callable($this->customDataCallback)) {
        $customData = call_user_func($this->customDataCallback, $cat);
        if (is_array($customData)) {
            $categoryArray = array_merge($categoryArray, $customData);
        }
    }

    return $categoryArray;
   }

    /**
     * Get category information either for current category or by ID
     * 
     * @param int|null $categoryId Optional category ID
     * @return array
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
               $this->walkRecursive($cat, $callback, $currentCatpath, $currentCat_id, 0);
           }
        }
    }

    /**
     * Recursive helper function for the walk method.
     *
     * @param rex_category $cat
     * @param callable $callback
     * @param array $currentCatpath
     * @param int $currentCat_id
     * @param int $level
     * @return void
     */
    private function walkRecursive(rex_category $cat, callable $callback, array $currentCatpath, int $currentCat_id, int $level): void
    {
       $item =  $this->processCategory($cat, $currentCatpath, $currentCat_id);
       if(!empty($item)){
           call_user_func($callback, $item, $level);
       }


        if ($level <= $this->depth) {
           
            $childCats = $cat->getChildren($this->ignoreOfflines);
            
            if(!empty($childCats)){
             foreach ($childCats as $child) {
                    if($this->isPermitted($child)){
                        $this->walkRecursive($child, $callback, $currentCatpath, $currentCat_id, $level + 1);
                    }
              }
            }
        }
    }
}
