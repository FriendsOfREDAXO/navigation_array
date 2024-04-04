# REDAXO FOR NavigationArray

NavigationArray ist Teil des FriendsOfRedaxo-Projekts. Die PHP-Class erstellt ein Array der Struktur zur einfacheren Generierung individueller Navigationen. 

In YCOM definierte Rechte werden berücksichtigt

## Dump eines Navigation-Arrays: 

```
array:7 [▼
    0 => array:11 [▶]
    1 => array:11 [▶]
    2 => array:11 [▶]
    3 => array:11 [▶]
    4 => array:11 [▼
        "catId" => 43
        "parentId" => 0
        "level" => 0
        "catName" => "Kontakt"
        "url" => "/kontakt/"
        "hasChildren" => true
        "children" => array:2 [▼
            0 => array:11 [▼
                "catId" => 178
                "parentId" => 43
                "level" => 1
                "catName" => "Kontaktformular"
                "url" => "/kontakt/kontaktformular/"
                "hasChildren" => false
                "children" => []
                "path" => array:1 [▶]
                "active" => false
                "current" => false
            ]
            1 => array:11 [▶]
        ]
        "path" => []
        "active" => true
        "current" => true
    ]
    5 => array:11 [▶]
    6 => array:11 [▶]
]
```


### Aufruf 

```php
// Festlegen des Namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;
```


```php
$navArray = new BuildArray(6, 3);
```

Automatische Erkennung des Mountpoints bei YRewrite (default)

```php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$navArray = new BuildArray(-1, 3);
```

Übergabe mehrerer Kategorien

```php
$navArray = new BuildArray([6,10,102], 3);
```

oder per

## Factory

```php
$navArray = BuildArray::create()->setDepth(3)->generate();
```

## Methoden

- `setStart($start)`: Setzt die Startkategorie/n. Eingabe als INT oder Array meehrerer Kategorien z.B: `[2, 3, 6]`, Standardmäßig `-1` für automatische Erkennung.
- `setDepth($depth)`: Setzt die maximale Tiefe, bezieht sich auf den `root` der der ausgewählten Kategorie/n. Beginnend bei 0.
- `setIgnore($ignore)`: Bestimmt, ob Offline-Kategorien ignoriert werden sollen, Standard: `true`.
- `generate()`: Generiert die Navigationsstruktur als Array.

## Konstruktor

```php
public function __construct($start = -1, $depth = 5, $ignoreOfflines = true)
```

- **$start**: Startkategorie-ID oder Array von Kategorie-IDs. Standardmäßig `-1` für automatische Erkennung.
- **$depth**: Tiefenbegrenzung der Navigation, bezieht sich auf den `root` der der ausgewählten Kategorie/n. Beginnend bei 0. 
- **$ignoreOfflines**: Bestimmt, ob offline Kategorien ignoriert werden sollen. Standardmäßig `true`.
- **$depthSaved**: Interner Gebrauch für die Tiefenverwaltung. Standardmäßig `0`.


## Beispiel: Ausgabe des Arrays

```php

use FriendsOfRedaxo\NavigationArray\BuildArray;

// Initialisierung des NavigationArray mit Startkategorie-ID 0 und Tiefe 3
$navArray =  new BuildArray(0, 3);

// Generierung der Navigationsstruktur
$result = $navArray->generate();

// Ausgabe des Arrays
dump($result);
```



## Beispiel einfache Navigation 

```php
// Function to generate the navigation list
function generateNavigationList($items) {
    if (empty($items)) {
        return '';
    }

    $output = '<ul>';
    foreach ($items as $item) {
        $output .= '<li>';
        $output .= '<a href="' . $item['url'] . '">' . $item['catName'] . '</a>';

        // Check if this item has children
        if ($item['hasChildren']) {
            $output .= generateNavigationList($item['children']);
        }

        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}

use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray =  BuildArray::create()->setDepth(3)->generate();

// Generate the navigation list
$navigationList = generateNavigationList($NavigationArray);

// Output the navigation list
echo $navigationList;
```


## Beispiel erweiterte Navigation mit Callback 

```php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$mainnavi_array = (new BuildArray())
    ->setCustomDataCallback(function($cat) {
        return ['navtype' => $cat->getValue('cat_navigationstyp')];
    })
    ->generate();

$mainnavigation_items = [];

function createNavigationItems($navi) {
    $items = [];
    $class_active = $navi['active'] ? 'active' : '';
    $class_current = $navi['current'] ? ' current' : '';
    $class_has_child = $navi['children'] ? ' has-child' : '';

    $items[] = "<li class=\"{$class_active}{$class_current}{$class_has_child}\"><a href=\"{$navi['url']}\">{$navi['catName']}</a>";

    if($navi['children']) {
        $items[] = '<button class="child-toggle">X</button>'; 
        $items[] = '<ul>';

        foreach ($navi['children'] as $nav_child) {
            $items[] = createNavigationItems($nav_child);
        }

        $items[] = '</ul>';
    }

    $items[] = '</li>';

    return implode($items);
}

foreach ($mainnavi_array as $navi) {
    $navtype_arr = explode('|', $navi['navtype']);

    if (in_array('main', $navtype_arr)) {
        $mainnavigation_items[] = createNavigationItems($navi);
    }
}

$mainnavigation = '<ul id="mainnavigation">' . implode($mainnavigation_items) . '</ul>';
```

Zu Beginn wird die Klasse `BuildArray` aus dem `FriendsOfRedaxo\NavigationArray`-Namespace verwendet, um ein Array für die Hauptnavigation zu generieren. Dabei wird eine benutzerdefinierte `Callback-Funktion` verwendet, um zusätzliche Daten für jeden Eintrag in der Navigation festzulegen. In diesem Fall wird das Feld `navtype` mit dem Wert des Feldes `cat_navigationstyp` aus der Kategorie des Eintrags befüllt.
Anschließend wird eine leere Array-Variable `$mainnavigation_items` definiert. Diese wird später mit den generierten Navigationselementen befüllt.
Die Funktion `createNavigationItems` wird definiert, um rekursiv die HTML-Struktur für jedes Navigationselement zu erstellen. Dabei werden verschiedene CSS-Klassen basierend auf den Eigenschaften des Navigationselements gesetzt, wie z.B. `active`, `current` und `has-child`. Die Funktion gibt den HTML-Code für das Navigationselement als String zurück.
In der Schleife `foreach ($mainnavi_array as $navi)` wird über jedes Element in der generierten Hauptnavigation iteriert. Das Feld ``navtype` wird in ein Array `$navtype_arr` aufgeteilt, indem der Wert anhand des Trennzeichens | gesplittet wird. Wenn der Wert `main` in `$navtype_arr` enthalten ist, wird die Funktion `createNavigationItems` aufgerufen und das generierte Navigationselement wird dem Array `$mainnavigation_items` hinzugefügt.
Schließlich wird die Hauptnavigation als HTML-Code in der Variable `$mainnavigation` gespeichert, indem die einzelnen Navigationselemente mit `implode` zu einem String zusammengefügt werden.
Das Feld `cat_navigationstyp` hat derzeit die Werte “meta,main,footer”. Es wird verwendet, um zu bestimmen, welche Navigationselemente in der Hauptnavigation angezeigt werden sollen. In diesem Fall werden nur die Elemente angezeigt, deren navtype den Wert 'main' enthält.



## Beispiel: Bootstrap 5 Navigation 

```php
<?php
function bsnavi5($data = array())
{
    $output = array();
    foreach ($data as $cat) {
        // Defaults
        $subnavi = $catname = $li = $active = $ul =  $link_extra = "";
        $aclass = 'nav-link';
        $li = ' class="nav-item"';

        // Was passiert in Level 0?
        if ($cat['level'] == 0 && $cat['hasChildren'] == true) {
            $li = ' class="nav-item dropdown"';
            $ul = ' class="dropdown-menu"';
            $link_extra = 'id="ddm' . $cat['catId'] . '" data-bs-toggle="dropdown"';
            $aclass = 'nav-link dropdown-toggle';
        }
        // Was ab Level 1 und die Kategorie hat keine Kinder
        if ($cat['level'] == 1 && $cat['hasChildren'] == false) {
            $li = ' class="nav-item dropdown"';
            $aclass = 'dropdown-item';
        }

        // Prüfe ob die Kategorie Kinder hat und rufe die Funktion rekursiv auf
        if ($cat['hasChildren'] == true) {
            $sub = [];
            // Erstelle den ul-Tag
            $sub[] = '<ul' . $ul . '>';
            // Function ruft sich selbst auf sollten Kinder gefunden werden.
            $sub[] = bsnavi5($cat['children']);
            // -------------------------------------------------------------
            $sub[] = '</ul>';
            $subnavi = join("\n", $sub);
        }
        // Auslesen des Kategorienamens
        $catname = $cat['catName'];
        // aktive Kategorie
        if ($cat['active'] == true) {
            $active = ' active';
        }
        // Generiere den Link
        $catname = '<a class="' . $aclass . $active . '" href="' . $cat['url'] . '"' . $link_extra . '>' . $catname . '</a>';
        // Generiere den li-Tag
        $output[] = '<li ' . $li . '>' . $catname . $subnavi . '</li>';
    }
    return join("\n", $output);
}
use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray =  BuildArray::create()->setDepth(4)->generate();
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <ul class="navbar-nav ms-md-auto mb-0">
            <?php echo bsnavi5(navArray($NavigationArray)) ?>
        </ul>
    </div>
</nav>
</body>
```

## Beispiel UIkit-Drop-Down

**Navigations-Array auslesen**

```php
<?php
// UIkit NAVIGATION uses navigation_array AddOn
function myNavi_demo($data = array())
{
    $output = [];
    foreach ($data as $cat) {
        $subnavi = $liclass = $ul = "";
        $subclass = 'uk-default';
        if ($cat['level'] == 0 && $cat['hasChildren'] == true) {
            $liclass = 'uk-parent ';
            $ul = ' class="uk-nav uk-navbar-dropdown-nav"';
            $subclass = 'uk-navbar-dropdown uk-margin-remove uk-padding-small';
        }
        if ($cat['hasChildren'] == true) {
            $sub = [];
            $sub[] = '<div class="' . $subclass . '">';
            $sub[] = '<ul' . $ul . '>';
            $sub[] = myNavi_demo($cat['children']);
            $sub[] = '</ul>';
            $sub[] = '</div>';
            $subnavi = join("\n", $sub);
        }
        $catname = $cat['catName'];
        if ($cat['active'] == true) {
            $catname = '' . $catname . '';
            $liclass .= 'uk-active';
        }
        if ($liclass != '') {
            $liclass =  ' class="' . $liclass . '"';
        }
        $catname = '<a href="' . $cat['url'] . '">' . $catname . '</a>';
        $output[] = '<li' . $liclass . '>' . $catname . $subnavi . '</li>';
    }

    if (!empty($output)) {
        return join("\n", $output);
    }
}
// Navigation erzeugen
use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray = BuildArray::create()->setDepth(3)->generate();
$navigation = '
    <ul class="uk-navbar-nav">'
    . myNavi_demo(navArray($NavigationArray) .
    '</ul>
';
?>
<!--- NAVI-BAR --->
<div class="nav">
    <nav class="uk-box-shadow-small uk-background-secondary uk-light " uk-navbar>
        <div class="uk-navbar-left">
            <a class="uk-navbar-item uk-logo" title="Logo: <?=rex::getServerName()?>" href="/">
            <img class="logo" name="Logo" src="<?=rex_url::media()?>logo.svg" alt="">
            </a>
        </div>
        <div class="uk-visible@m uk-navbar-center uk-text-large">
            <?= $navigation; ?>
        </div>

        <div class="uk-navbar-right">
        <a class="uk-icon-button uk-margin-right" href="https://github.com/FriendsOfREDAXO" uk-icon="icon: github"></a>
           
        </div>
    </nav>
</div>
<!--- ENDE NAVI-BAR --->
```



## Beispiel: Breadcrumb 

```php
<?php 
function bc_uikit($data = array())
{
    $output = []; // Initialisierung der Output-Variable
    foreach ($data as $cat) {
        $subnavi = ''; // Initialisierung der Subnavigation
        if ($cat['hasChildren']) {
            // Rekursiver Aufruf für Unterkategorien
            $sub = bc_uikit($cat['children']);
            $subnavi = join("\n", $sub);
        }
        $url = ' href="'.$cat['url'].'"';
        $liclass = '';
        if ('REX_ARTICLE_ID' == $cat['catId']) {
            $liclass = ' class="uk-disabled" aria-current="page"';
            $url = '';
        }
        $catname = '<a'.$url.'>'.$cat['catName'].'</a>';
        
        $output[] = '<li'.$liclass.'>' . $catname .'</li>'.$subnavi;
    }
    return join("\n", $output);
}

// Breadcrumb erzeugen
use FriendsOfRedaxo\NavigationArray\BuildArray;
$NavigationArray = new BuildArray(0, 4, 20);
$result = $NavigationArray->setCategoryFilterCallback(function ($cat) {
        // Nur aktive Kategorien auswählen
        return $cat['active'];
 })->generate();
echo '
    <nav aria-label="Breadcrumb">
    <ul class="uk-breadcrumb">'
    . bc_uikit($result) .
    '</ul>
    </nav>
';
?>
```

## CallbackFilter: `setCategoryFilterCallback`

### Beschreibung
Die `setCategoryFilterCallback` Methode ermöglicht es, einen benutzerdefinierten Filter für die Kategorien zu definieren, die in der Navigation angezeigt werden sollen. Dieser Filter ist ein Callback, der für jede Kategorie aufgerufen wird. Wenn der Callback `true` zurückgibt, wird die Kategorie in die generierte Navigationsstruktur aufgenommen. Andernfalls wird sie übersprungen.

### Verwendung
```php
setCategoryFilterCallback(callable $callback): self
```

### Parameter
- `$callback` - Ein `callable`, das als Filter-Callback dient. Dieser Callback nimmt ein Kategorie-Objekt als Parameter und gibt einen booleschen Wert zurück (`true` für die Aufnahme der Kategorie, `false` für deren Ausschluss).

### Rückgabewert
Die Methode gibt das `NavigationArray`-Objekt zurück, was das Methoden-Chainen ermöglicht.

### Beispiel
Das folgende Beispiel zeigt, wie man einen Filter definieren kann, der alle Kategorien mit der Bezeichnung `ìrgendwas` herausfiltert:

```php
$navigation =  new BuildArray();
$navigation->setCategoryFilterCallback(function($cat) {
    return $cat->getName() !== 'irgendwas';
});
```

### Tipp:
Der Filter-Callback sollte so effizient wie möglich gestaltet werden, um die Leistung nicht negativ zu beeinflussen, besonders bei großen Kategoriestrukturen.


## `setCustomDataCallback`

### Beschreibung
Die Methode `setCustomDataCallback` ermöglicht das Hinzufügen benutzerdefinierter Daten zu jedem Kategorie-Array in der Navigationsstruktur. Durch die Bereitstellung eines Callbacks können zusätzliche Informationen oder Attribute für jede Kategorie definiert werden.

### Verwendung
```php
setCustomDataCallback(callable $callback): self
```

### Parameter
- `$callback`: Ein `callable`, das als Callback für benutzerdefinierte Daten dient. Dieser Callback nimmt ein Kategorie-Objekt als Parameter und gibt ein Array zurück, das die zusätzlichen Daten enthält, die in das Kategorie-Array aufgenommen werden sollen.

### Rückgabewert
Die Methode gibt das `NavigationArray`-Objekt zurück, was das Methoden-Chainen ermöglicht.

### Beispiel
```php
$navigation = new BuildArray();
$navigation->setCustomDataCallback(function($cat) {
    return ['extraColor' => $cat->getValue('cat_color')];
});
```
In diesem Beispiel wird die `setCustomDataCallback` Methode verwendet, um benutzerdefinierte Daten hinzuzufügen, die für jede Kategorie zusätzliche Informationen enthalten.

### Hinweis
Der Callback sollte effizient gestaltet werden, um die Leistung nicht zu beeinträchtigen.
