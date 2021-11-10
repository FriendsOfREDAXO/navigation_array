<?php
// Function zur Generierung eines Navigationsarrays
// Benötigt für den Aufruf werden nur $start,$depth und $ignoreOffline
// Alle weiteren Angaben dienen der internen Verarbeitung
// Alle weiteren Informationen aus rex_structure findet man in catObject
if (!function_exists('navArray')) {
    function navArray($start = 0, $depth = 0, $ignoreOfflines = true, $depth_saved = 0, $level = 0, $startCats = [])
    {
        $result     = array();
        // get current category
        $currentCat = rex_category::getCurrent();
        $ycom_check = rex_addon::get('ycom')->getPlugin('auth')->isAvailable();
        if ($currentCat) {
            $currentCatpath = $currentCat->getPathAsArray();
            $currentCat_id  = $currentCat->getId();
        } else {
            $currentCatpath = array();
            $currentCat_id  = 0;
        }
        if ($start != 0) {
            $startCat  = rex_category::get($start);
            $startPath = $startCat->getPathAsArray();
            $depth     = count($startPath) + $depth;
            $startCats = $startCat->getChildren($ignoreOfflines);
            if ($depth_saved != 0) {
                $depth = $depth_saved;
            } else {
                $depth_saved = $depth;
            }
        } elseif (!$startCats) {
            $startCats = rex_category::getRootCategories($ignoreOfflines);
            $depth     = $depth;
        }
        if ($startCats) {
            foreach ($startCats as $cat) {

                $children['child'] = array();
                $hasChildren       = false;
                $catId             = $cat->getId();
                $path              = $cat->getPathAsArray();
                $listlevel         = count($path);
                
                if ($ycom_check && !$cat->isPermitted())
                {
                continue;
                }
                if ($listlevel > $depth) {
                    continue;
                }
                if ($listlevel <= $depth && $depth != 0 && $cat && $cat->getChildren($ignoreOfflines)) {
                    $level++;
                    $hasChildren       = true;
                    // get sub categories
                    $children['child'] = navArray($catId, $depth, $ignoreOfflines, $depth_saved, $level);
                    $level--;
                }

                // set category name
                $catName = $cat->getName();
                // determine Url
                $catUrl  = $cat->getUrl();
                // Aktiven Pfad ermitteln
                $active  = false;
                if (in_array($catId, $currentCatpath) or $currentCat_id == $catId) {
                    $active = true;
                }
                // Set current category
                $current = false; 
                if ($currentCat_id == $catId) {
                    $current = true;
                }
                // save result
                $result[] = array(
                    'catId' => $catId,
                    'parentId' => $start,
                    'level' => $level,
                    'catName' => $catName,
                    'url' => $catUrl,
                    'hasChildren' => $hasChildren,
                    'children' => $children['child'],
                    'path' => $path,
                    'active' => $active,
                    'current' => $current,
                    'catObject' => $cat
                );

            }
        }
        return $result;
    }
}
