<?php

namespace FriendsOfRedaxo\NavigationArray;

use Redaxo\Core\Form\Field\Checkbox;
use Redaxo\Core\MetaInfo\AsMetaSchema;
use Redaxo\Core\MetaInfo\MetaEntity;
use Redaxo\Core\MetaInfo\MetaSchema;

/**
 * Meta field of categories: „Nicht in der Navigation zeigen“ (column `meta_nav_hidden`, the same for all languages).
 *
 * A hidden category stays online, keeps its position and its children – only the navigation leaves it out, with its
 * children (unless `new Navigation(hidden: true)`). Breadcrumbs still contain it, the page is still reachable.
 *
 *     NavigationMeta::of($category)->navHidden;
 *     CategoryHandler::edit($id, $languageId, [], ['nav_hidden' => true]);
 */
#[AsMetaSchema(MetaEntity::Category)]
final class NavigationMeta extends MetaSchema
{
    #[Checkbox('Nicht in der Navigation zeigen', note: 'Die Seite bleibt online und an ihrer Stelle, nur das Menü lässt sie samt Unterseiten weg.')]
    public bool $navHidden = false;
}
