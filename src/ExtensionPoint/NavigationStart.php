<?php

namespace FriendsOfRedaxo\NavigationArray\ExtensionPoint;

use Redaxo\Core\ExtensionPoint\ExtensionPoint;

/**
 * Start of a navigation without `start` (the default): the start of the current site, e.g. the subtree of a domain.
 * The subject is the start like `Navigation::$start` – 0 is the root (without an extension).
 *
 *     Extension::register(NavigationStart::class, static fn (NavigationStart $ep): int|array => MyDomains::mountId() ?? $ep->subject);
 *
 * @extends ExtensionPoint<int|list<int>>
 */
final class NavigationStart extends ExtensionPoint
{
    public function __construct(
        /** Article the navigation is built for (the current one) */
        public readonly int $articleId,
        public readonly int $languageId,
    ) {
        parent::__construct(self::class, 0);
    }
}
