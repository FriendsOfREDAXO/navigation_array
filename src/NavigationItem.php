<?php

namespace FriendsOfRedaxo\NavigationArray;

use JsonSerializable;
use Override;
use Redaxo\Core\Content\Article;
use Redaxo\Core\Content\Category;

/**
 * An entry of the navigation: a category, or an article of a category (with `Navigation::$articles`).
 */
final readonly class NavigationItem implements JsonSerializable
{
    /**
     * @param list<int> $path ids of the parent categories
     * @param list<self> $children items of the next level
     * @param list<self> $articles articles of the category, without its start article
     * @param array<string, mixed> $data own data (`Navigation::$data`, extension point {@see ExtensionPoint\NavigationItemGenerated})
     */
    public function __construct(
        public Category|Article $element,
        public int $id,
        public int $parentId,
        public string $name,
        public string $url,
        public int $level,
        public array $path,
        /** In the path of the current category (or the current article) */
        public bool $active,
        public bool $current,
        public array $children = [],
        public array $articles = [],
        public array $data = [],
    ) {}

    public function hasChildren(): bool
    {
        return [] !== $this->children;
    }

    public function isArticle(): bool
    {
        return $this->element instanceof Article;
    }

    /** @param array<string, mixed> $data merged into the existing data */
    public function withData(array $data): self
    {
        return clone ($this, ['data' => [...$this->data, ...$data]]);
    }

    /** @return array<string, mixed> */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'parentId' => $this->parentId,
            'name' => $this->name,
            'url' => $this->url,
            'level' => $this->level,
            'path' => $this->path,
            'active' => $this->active,
            'current' => $this->current,
            'article' => $this->isArticle(),
            'children' => $this->children,
            'articles' => $this->articles,
            'data' => $this->data,
        ];
    }
}
