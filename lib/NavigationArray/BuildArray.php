<?php

namespace FriendsOfRedaxo\NavigationArray;

use rex_addon;
use rex_article;
use rex_category;
use rex_clang;
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
    private $depthSaved;
    private $ignoreOfflines;
    private $level;
    private $start;
    private $startCats;

    public function __construct($start = -1, $depth = 4, $ignoreOfflines = true, $depthSaved = 0, $level = 0)
    {
        $this->start = $start;
        $this->depth = $depth;
        $this->ignoreOfflines = $ignoreOfflines;
        $this->depthSaved = $depthSaved;
        $this->level = $level;
    }

    public function setStart($start): self
    {
        $this->start = $start;
        return $this;
    }

    public function setDepth($depth): self
    {
        $this->depth = $depth;
        return $this;
    }

    public function setIgnore($ignore): self
    {
        $this->ignoreOfflines = $ignore;
        return $this;
    }

    public function setDepthSaved($saved): self
    {
        $this->depthSaved = $saved;
        return $this;
    }

    public function setLevel($lvl): self
    {
        $this->level = $lvl;
        return $this;
    }

    public static function create(): self
    {
        return new self();
    }

    public function generate(): array
    {
        $result = [];
        $currentCat = rex_category::getCurrent();
        $currentCatpath = $currentCat ? $currentCat->getPathAsArray() : [];
        $currentCat_id = $currentCat ? $currentCat->getId() : 0;

        $this->initializeStartCategory();

        foreach ($this->startCats as $cat) {
            if (!$this->isCategoryPermitted($cat)) {
                continue;
            }

            if (is_callable($this->categoryFilterCallback) && !call_user_func($this->categoryFilterCallback, $cat)) {
                continue;
            }

            $result[] = $this->processCategory($cat, $currentCatpath, $currentCat_id);
        }
        return array_filter($result);
    }

    public function setCategoryFilterCallback(callable $callback): self
    {
        $this->categoryFilterCallback = $callback;
        return $this;
    }

    public function setCustomDataCallback(callable $callback): self
    {
        $this->customDataCallback = $callback;
        return $this;
    }

    public function toJson(): string
    {
        $array = $this->generate();
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    private function initializeStartCategory(): void
    {
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
                $this->depthSaved = $this->depthSaved ?: $this->depth;
            } else {
                $this->startCats = rex_category::getRootCategories($this->ignoreOfflines);
            }
        } else {
            $this->startCats = rex_category::getRootCategories($this->ignoreOfflines);
        }
    }

    private function isCategoryPermitted($cat): bool
    {
        $ycom_check = rex_addon::get('ycom')->getPlugin('auth')->isAvailable();
        return !$ycom_check || $cat->isPermitted();
    }

    private function processCategory($cat, $currentCatpath, $currentCat_id): array
    {
        if ($this->level > $this->depth) {
            return [];
        }

        $catId = $cat->getId();

        $children = $this->level <= $this->depth && $cat->getChildren($this->ignoreOfflines)
            ? ['child' => $this->generateSubCategories($cat)]
            : ['child' => []];

        $categoryArray = [
            'catId' => $catId,
            'parentId' => $cat->getParentId(),
            'level' => $this->level,
            'catName' => $cat->getName(),
            'url' => $cat->getUrl(),
            'hasChildren' => !empty($children['child']),
            'children' => $children['child'],
            'path' => $cat->getPathAsArray(),
            'active' => in_array($catId, $currentCatpath) || $currentCat_id == $catId,
            'current' => $currentCat_id == $catId,
        ];

        if (is_callable($this->customDataCallback)) {
            $customData = call_user_func($this->customDataCallback, $cat);
            if (is_array($customData)) {
                $categoryArray = array_merge($categoryArray, $customData);
            }
        }
        return $categoryArray;
    }

    private function generateSubCategories($parentCat): array
    {
        $originalStart = $this->start;
        $this->start = $parentCat->getId();

        ++$this->level;
        $result = $this->generate();
        --$this->level;

        $this->start = $originalStart;

        return $result;
    }
}
