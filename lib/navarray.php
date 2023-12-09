l<?php
class navArray
{
    private $start;
    private $depth;
    private $ignoreOfflines;
    private $startCats;
    private $depthSaved;
    private $level;

    public function __construct($start = 0, $depth = 2, $ignoreOfflines = true, $startCats = [], $depthSaved = 0, $level = 0)
    {
        $this->start = $start;
        $this->depth = $depth;
        $this->ignoreOfflines = $ignoreOfflines;
        $this->startCats = $startCats;
        $this->depthSaved = $depthSaved;
        $this->level = $level;
    }

    public function setStart($start): void
    {
        $this->start = $start;
    }
    public function setDepth($depth): void
    {
        $this->depth = $depth;
    }
    public function setIgnore($ignore): void
    {
        $this->ignoreOfflines = $ignore;
    }
    public function setStartCats($cats): void
    {
        $this->startCats = $cats;
    }
    public function setDepthSaved($saved): void
    {
        $this->depthSaved = $saved;
    }
    public function setLevel($lvl): void
    {
        $this->level = $lvl;
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
            if (!$this->isCategoryPermitted($cat)) continue;
            $result[] = $this->processCategory($cat, $currentCatpath, $currentCat_id);
        }

        return array_filter($result);
    }

    private function initializeStartCategory()
    {
        if ($this->start != 0) {
            $startCat = rex_category::get($this->start);
            $this->depth = count($startCat->getPathAsArray()) + $this->depth;
            $this->startCats = $startCat->getChildren($this->ignoreOfflines);
            $this->depthSaved = $this->depthSaved ?: $this->depth;
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
        $catId = $cat->getId();
        $path = $cat->getPathAsArray();
        $listlevel = count($path);
        if ($listlevel > $this->depth) return [];

        $children = $listlevel <= $this->depth && $cat->getChildren($this->ignoreOfflines)
            ? ['child' => $this->generateSubcategories($cat)]
            : ['child' => []];

        return [
            'catId' => $catId, 
            'parentId' => $this->start, 
            'level' => $this->level,
            'catName' => $cat->getName(), 
            'url' => $cat->getUrl(),
            'hasChildren' => !empty($children['child']), 
            'children' => $children['child'],
            'path' => $path,
            'active' => in_array($catId, $currentCatpath) || $currentCat_id == $catId,
            'current' => $currentCat_id == $catId, 
            'catObject' => $cat
        ];
    }

    private function generateSubcategories($parentCat): array
    {
        $originalStart = $this->start;
        $this->start = $parentCat->getId();

        $this->level++;
        $result = $this->generate();
        $this->level--;

        $this->start = $originalStart; // zurücksetzen der Startkategorie 

        return $result;
    }
}
