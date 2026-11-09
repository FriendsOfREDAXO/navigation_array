<?php

namespace FriendsOfRedaxo\NavigationArray\ExtensionPoint;

use FriendsOfRedaxo\NavigationArray\NavigationItem;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;

/**
 * An item of the navigation was built (categories, not articles): extensions return it with own data.
 *
 *     Extension::register(NavigationItemGenerated::class, static fn (NavigationItemGenerated $ep): NavigationItem
 *         => $ep->subject->withData(['icon' => (string) $ep->subject->element->getValue('cat_icon')]));
 *
 * @extends ExtensionPoint<NavigationItem>
 */
final class NavigationItemGenerated extends ExtensionPoint
{
    public function __construct(NavigationItem $item)
    {
        parent::__construct(self::class, $item);
    }
}
