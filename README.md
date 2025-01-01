# REDAXO Navigation Array

Navigation Array ist eine PHP-Klasse für die einfache Erstellung einer Navigationsstruktur als Array. Diese Klasse bietet flexible Möglichkeiten, Navigationsdaten auszulesen, zu filtern und zu verarbeiten, insbesondere durch die Nutzung der `walk()`-Methode.

## Erklärung der Klasse

Die `FriendsOfRedaxo\NavigationArray\BuildArray` Klasse bietet folgende Hauptfunktionalitäten:

*   **Erstellung eines Navigationsarrays:** Generiert ein verschachteltes Array, das die Kategorien und ihre Hierarchie darstellt.
*   **Filterung von Kategorien:** Ermöglicht das Herausfiltern von Kategorien basierend auf benutzerdefinierten Kriterien.
*   **Hinzufügen benutzerdefinierter Daten:** Fügt zusätzliche Informationen zu jedem Kategorie-Array hinzu.
*   **Rekursive Navigationstraversierung:** Die `walk()`-Methode erlaubt es, die Navigationsstruktur rekursiv zu durchlaufen und dabei individuelle Operationen auszuführen.
*   **Abrufen von Kategorieinformationen:** Mit der `getCategory()`-Methode können detaillierte Informationen zu einzelnen Kategorien abgerufen werden.

### Kernfunktionen

-   Berücksichtigung von Offline-Artikeln und YCom-Rechten.
-   Frei wählbare Startkategorie.
-   Festlegbare Tiefe der Navigation.
-   Filterung und Manipulation von Kategorien über Callbacks.

### Features

-   `getCategory`-Methode zum Abrufen von Kategorieinformationen (inkl. Kindkategorien).
-   `walk()`-Methode für einfache, rekursive Navigationstraversierung.
-   Mitgelieferte Fragmente für die HTML-Ausgabe der Navigation (siehe Beispiele unten).

## Auslesen der Daten

### Array-Struktur

Das generierte Array enthält alle Kategorien und Unterkategorien bis zur angegebenen Tiefe. Offline-Kategorien und Rechte aus YCom werden vorher entfernt.

```php
array:7 [
    0 => array:11 [▶]
    1 => array:11 [▶]
    2 => array:11 [▶]
    3 => array:11 [▶]
    4 => array:11 [
        "catId" => 43
        "parentId" => 0
        "level" => 0
        "catName" => "Kontakt"
        "url" => "/kontakt/"
        "hasChildren" => true
        "children" => array:2 [
            0 => array:11 [
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

### Array generieren

#### Aufruf mit Konstruktor

```php
// define namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;
// create object
$navigationObject = new BuildArray(-1, 3);
// generate navigation array
$navigationArray = $navigationObject->generate();
```

#### Aufruf mit Methoden

```php
// define namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;
// create navigation array
$navigationArray = BuildArray::create()
    ->setStart(-1)
    ->setDepth(4)
    ->setIgnore(true)
    ->setCategoryFilterCallback(CategoryFilter())
    ->setCustomDataCallback(CustomData())
    ->generate();
```

## Erstellen von Navigationen

### Verwendung der `walk()`-Methode

Die `walk()`-Methode ermöglicht es, die Navigation rekursiv zu durchlaufen und dabei eine Callback-Funktion auf jedes Element anzuwenden. Dies ist ideal für benutzerdefinierte Ausgaben oder Manipulationen der Navigationsdaten.

#### Verwendung

```php
public function walk(callable $callback): void
```

#### Parameter

-   `$callback`: Eine `callable`-Funktion, die für jedes Navigationselement aufgerufen wird. Die Funktion erhält zwei Parameter:
    -   `$item`: Das aktuelle Navigationselement (als `array`).
    -   `$level`: Die aktuelle Ebene der Navigation (als `int`).

#### Beispiel: Verschachtelte HTML-Liste mit `walk()`

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$htmlNavigation = '';
$currentLevel = 0;

$navigation = BuildArray::create()
    ->setDepth(3)
    ->setExcludedCategories([32,34])
    ->setCategoryFilterCallback(function(rex_category $cat){
        // Hier kannst du deine eigenen Filter setzen
        return true;
    })
    ->setCustomDataCallback(function(rex_category $cat){
      return [
        'css_id' => 'cat-'.$cat->getId(),
        'description' => $cat->getValue('description')
      ];
  });

$navigation->walk(function ($item, $level) use (&$htmlNavigation, &$currentLevel) {
    
    // Level check um unnötige Tags zu vermeiden
    if($level > $currentLevel) {
       $htmlNavigation .= '<ul>';
    }
    if($level < $currentLevel){
         $diff = $currentLevel - $level;
         $htmlNavigation .= str_repeat('</ul>', $diff);
     }
     $currentLevel = $level;
   
    $activeClass = $item['active'] ? ' class="active"' : '';

    $htmlNavigation .= '<li'.$activeClass.'>';
    $htmlNavigation .= '<a href="' . $item['url'] . '" id="'.$item['css_id'].'">';
    $htmlNavigation .=  $item['catName'] ;
    $htmlNavigation .= '</a>';
    $htmlNavigation .= '</li>';
});
// Abschließen der Tags wenn auf der höchsten Ebene
if($currentLevel > 0){
 $htmlNavigation .= str_repeat('</ul>', $currentLevel);
}

echo $htmlNavigation;
```

#### Beispiel: Logausgabe der Navigation mit `walk()`

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()->setDepth(2);

$navigation->walk(function ($item, $level) {
    echo str_repeat("  ", $level) . "Kategorie: " . $item['catName'] . ", Level: " . $level . ", URL: " . $item['url'] . "<br>";
});
```

#### Beispiel: Einfache Navigation mit `walk()` und REDAXO Fragment

Hier ein Beispiel, wie du die `walk()`-Methode in Verbindung mit einem REDAXO-Fragment für eine einfache Navigation verwenden kannst.

**1. Fragment (`simple_navigation.php`)**:
Erstelle eine Fragmentdatei im Verzeichnis `fragments/navigation_array` mit folgendem Inhalt:

```php
<?php
    $items = $this->getVar('items');
    $level = $this->getVar('level', 0);
    $activeClass = $this->getVar('activeClass', 'active');
    
    if (empty($items)) {
        return '';
    }
    $output = '<ul>';
    foreach ($items as $item) {
         $active = $item['active'] ? ' class="'.$activeClass.'"' : '';
        $output .= '<li' . $active . '><a href="'.$item['url'].'">'.$item['catName'].'</a>';
         if(isset($item['children']) && !empty($item['children'])){
           
            $output .= $this->subfragment('navigation_array/simple_navigation.php', [
                'items' => $item['children'],
                'level' => $level +1,
                'activeClass' => $activeClass
            ]);
         }
      $output .='</li>';
    }
    $output .= '</ul>';
    echo $output;
?>
```

**2. Aufruf im Template:**
In deinem REDAXO-Template kannst du die Navigation wie folgt erstellen:
```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()->setDepth(3);
$htmlNavigation = '';
$items = [];
$navigation->walk(function ($item, $level) use (&$items) {
   $items[] = $item;
});


$fragment = new rex_fragment();
$fragment->setVar('items', $items);
$fragment->setVar('activeClass', 'active');
echo $fragment->parse('navigation_array/simple_navigation.php');
```

Dieses Beispiel zeigt, wie du mit der `walk()`-Methode ein Array der Navigation erzeugen kannst und dieses dann mit einem REDAXO-Fragment in eine einfache Navigation umwandeln kannst. Die Rekursion wird nun über die Fragments realisiert, um einen wiederverwendbaren Code zu erhalten.

#### Beispiel: Breadcrumb mit `walk()`

Hier ist ein modernes Breadcrumb-Beispiel mit der `walk()`-Methode:

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navarray = BuildArray::create()->setDepth(10);
$breadcrumbItems = [];

$navarray->walk(function ($item, $level) use (&$breadcrumbItems) {
    if ($item['active']) {
      $liclass = '';
        if ('REX_ARTICLE_ID' == $item['catId']) {
            $liclass = ' class="disabled"';
            $item['url'] = '';
         }
        $breadcrumbItems[] = '<li'.$liclass.'><a href="' . $item['url'] . '">' . $item['catName'] . '</a></li>';
    }
});
echo '<ul class="breadcrumb">';
echo '<li><a title="Home" href="/"><span data-uk-icon="home"></span></a></li>';
echo implode("\n", $breadcrumbItems);
echo '</ul>';

```

Dieses Beispiel zeigt, wie du die `walk()` Methode nutzen kannst um einen Breadcrumb zu generieren, und dabei von den bereitgestellten Informationen Gebrauch machst.

### Vergleich: `walk()` vs. Eigene Iteration (Gleiche Ausgabe)

Um die Vorteile der `walk()`-Methode zu verdeutlichen, zeigen wir hier, wie man die gleiche verschachtelte Navigation mit beiden Methoden erstellt. Dies ermöglicht einen direkten Code-Vergleich.

#### Mit `walk()`-Methode

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

$navigation = BuildArray::create()->setDepth(3);
$htmlOutput = '';

$navigation->walk(function($item, $level) use (&$htmlOutput) {
   $htmlOutput .= str_repeat("&nbsp;&nbsp;", $level) . '<a href="' . $item['url'] . '">' . $item['catName'] . '</a><br>';
});

echo $htmlOutput;
```

#### Mit eigener Iterationsfunktion

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;

function myCustomNavigation($items, $level = 0) {
    $output = '';
    foreach ($items as $item) {
        $output .= str_repeat("&nbsp;&nbsp;", $level) . '<a href="' . $item['url'] . '">' . $item['catName'] . '</a><br>';
        if ($item['hasChildren']) {
            $output .= myCustomNavigation($item['children'], $level + 1);
        }
    }
    return $output;
}

$navigation = BuildArray::create()->setDepth(3)->generate();
echo myCustomNavigation($navigation);
```

**Analyse:**

*   **Klarheit:** Beide Code-Beispiele erzeugen *exakt* die gleiche HTML-Ausgabe.
*   **Code-Kürze:** Die `walk()`-Methode erfordert *deutlich weniger* Code und ist lesbarer, da die Rekursionslogik intern gehandhabt wird.
*   **Wartbarkeit:** Der Code mit `walk()` ist einfacher zu warten, da die Rekursionslogik gekapselt ist.
*   **Flexibilität:** Die `walk()`-Methode ermöglicht es, die Logik der Ausgabe in einem Callback zu definieren, was die Flexibilität erhöht.

Dieser direkte Vergleich zeigt, dass die `walk()`-Methode eine prägnantere, klarere und wartungsfreundlichere Möglichkeit bietet, die Navigationsdaten zu verarbeiten.

## Weitere Beispiele

Neben der Nutzung von `walk()`, können auch die generierten Arrays direkt für die Ausgabe genutzt werden.

### Fragmente

Mit Fragmenten wird die Logik sauber vom Code getrennt. Das Fragment `navigation.php` kann kopiert, angepasst und in `project` oder `theme`-Ordner abgelegt werden.

```php
// Festlegen des Namespace
use FriendsOfRedaxo\NavigationArray\BuildArray;

// Navigation Array erstellen
$navigationObject = new BuildArray(-1, 3);
$navigationArray = $navigationObject->generate();

//Fragmente laden Navigation Array und Klasse für aktive Elemente übergeben
$fragment = new rex_fragment();
$fragment->setVar('navigationArray', $navigationArray);
$fragment->setVar('activeClass', 'active');

// Navigation ausgeben
<nav>
    <ul role="menubar">
        echo $fragment->parse('navigation_array/navigation.php');
    </ul>
</nav>
```

### Weitere PHP-Funktionen (Auswahl)

Hier sind weitere Beispiele für spezifische HTML-Ausgaben

#### Beispiel erweiterte Navigation mit Callback

```php
<?php
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

    if ($navi['children']) {
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

#### Beispiel: Bootstrap 5 Navigation

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
function bsnavi5($data = array())
{
    $output = array();
    foreach ($data as $cat) {
        $subnavi = $catname = $li = $active =

 $ul = $link_extra = "";
        $aclass = 'nav-link';
        $li = ' class="nav-item"';

        if ($cat['level'] == 0 && $cat['hasChildren'] == true) {
            $li = ' class="nav-item dropdown"';
            $ul = ' class="dropdown-menu"';
            $link_extra = 'id="ddm' . $cat['catId'] . '" data-bs-toggle="dropdown"';
            $aclass = 'nav-link dropdown-toggle';
        }

        if ($cat['level'] == 1 && $cat['hasChildren'] == false) {
            $li = ' class="nav-item dropdown"';
            $aclass = 'dropdown-item';
        }

        if ($cat['hasChildren'] == true) {
            $sub = [];
            $sub[] = '<ul' . $ul . '>';
            $sub[] = bsnavi5($cat['children']);
            $sub[] = '</ul>';
            $subnavi = join("\n", $sub);
        }

        $catname = $cat['catName'];
        if ($cat['active'] == true) {
            $active = ' active';
        }

        $catname = '<a class="' . $aclass . $active . '" href="' . $cat['url'] . '"' . $link_extra . '>' . $catname . '</a>';
        $output[] = '<li ' . $li . '>' . $catname . $subnavi . '</li>';
    }
    return implode("\n", $output);
}


$NavigationArray = BuildArray::create()->setDepth(4)->generate();
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <ul class="navbar-nav ms-md-auto mb-0">
            <?= bsnavi5($NavigationArray) ?>
        </ul>
    </div>
</nav>
</body>
```

#### Beispiel UIkit-Drop-Down

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
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
            $liclass = ' class="' . $liclass . '"';
        }
        $catname = '<a href="' . $cat['url'] . '">' . $catname . '</a>';
        $output[] = '<li' . $liclass . '>' . $catname . $subnavi . '</li>';
    }

    if (!empty($output)) {
        return implode("\n", $output);
    }
}
// Navigation erzeugen

$NavigationArray = BuildArray::create()->setDepth(3)->generate();
$navigation = '
    <ul class="uk-navbar-nav">'
    . myNavi_demo($NavigationArray) .
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

### `getCategory()`

Liefert ein Array mit allen Informationen zu einer Kategorie. Funktioniert sowohl für die aktuelle Kategorie als auch für eine spezifische Kategorie-ID.

### Basis-Verwendung

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
// Aktuelle Kategorie
$category = BuildArray::create()->getCategory();

// Spezifische Kategorie
$category = BuildArray::create()->getCategory(5);
```

### Rückgabe-Array

```php
[
    // Hauptkategorie-Informationen
    'catId' => 5,                  // ID der Kategorie
    'parentId' => 2,               // ID der Elternkategorie
    'catName' => 'News',           // Name der Kategorie
    'url' => '/news/',             // URL der Kategorie
    'hasChildren' => true,         // Hat Unterkategorien
    'path' => [0,2,5],            // Pfad von Root zur Kategorie
    'pathCount' => 3,              // Anzahl der Ebenen von Root
    'active' => true,              // Ist im aktiven Pfad
    'current' => true,             // Ist aktuelle Kategorie
    'cat' => Object,               // REX Category Objekt
    'ycom_permitted' => true,      // YCom-Berechtigung
    'filter_permitted' => true,    // Filter-Erlaubnis
    'is_permitted' => true,        // Gesamtstatus der Berechtigungen
    
    // Kinder-Array (aus processCategory)
    'children' => [
        [
            'catId' => 15,         // ID der Kindkategorie
            'parentId' => 5,       // ID der Elternkategorie (unsere Hauptkategorie)
            'level' => 3,          // Level aus processCategory
            'catName' => 'Events', // Name der Kindkategorie
            'url' => '/news/events/', // URL der Kindkategorie
            'hasChildren' => false, // Hat diese Kindkategorie weitere Unterkategorien
            'children' => [],       // Eventuelle Kinder der Kindkategorie
            'path' => [0,2,5,15],  // Pfad dieser Kindkategorie
            'active' => false,      // Ist diese Kindkategorie aktiv
            'current' => false      // Ist diese Kindkategorie die aktuelle Kategorie
        ],
        [
            'catId' => 16,
            'parentId' => 5,
            'level' => 3,
            'catName' => 'Blog',
            // ... weitere Eigenschaften wie oben
        ],
        // ... weitere Kindkategorien
    ],

    // Zusätzliche Custom-Daten (wenn definiert)
    'custom_field' => 'Wert',
    'another_field' => 'Wert'
]
```

### Beispiele

#### 1. Kategorie-Vergleich

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$currentCat = BuildArray::create()->getCategory();
$parentCat = BuildArray::create()->getCategory($currentCat['parentId']);

echo '<div class="category-nav">';
echo '<h2>' . $parentCat['catName'] . '</h2>';
echo '<h3>Sie befinden sich hier: ' . $currentCat['catName'] . '</h3>';
echo '</div>';
```

#### 2. Mit Custom-Daten

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$category = BuildArray::create()
    ->setCustomDataCallback(function($cat) {
        return [
            'image' => $cat->getValue('cat_image'),
            'description' => $cat->getValue('cat_description')
        ];
    })
    ->getCategory(5);

if ($category['is_permitted']) {
    echo '<div class="category-info">';
    echo '<h1>' . $category['catName'] . '</h1>';
    if ($category['image']) {
        echo '<img src="' . $category['image'] . '">';
    }
    echo '</div>';
}
```

#### 3. Mehrere Kategorien verarbeiten

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
$builder = BuildArray::create()
    ->setCategoryFilterCallback(function($cat) {
        return $cat->getValue('show_in_nav') == 1;
    });

$categories = [
    $builder->getCategory(3),  // Kategorie mit ID 3
    $builder->getCategory(4),  // Kategorie mit ID 4
    $builder->getCategory()    // Aktuelle Kategorie
];

foreach ($categories as $category) {
    if ($category['is_permitted']) {
        echo '<div class="cat-box' . 
             ($category['current'] ? ' current' : '') . 
             ($category['active'] ? ' active' : '') . 
             '">';
        echo $category['catName'];
        echo '</div>';
    }
}
```

## Methoden

### Konstruktor

```php
public function __construct(
    int $start = -1,          // Start-Kategorie ID (-1 für Root)
    int $depth = 4,           // Maximale Tiefe
    bool $ignoreOfflines = true, // Offline-Kategorien ignorieren
    $depthSaved = 0,          // Gespeicherte Tiefe
    int $level = 0            // Aktuelles Level
)
```

Erstellt eine neue Instanz der NavigationArray-Klasse.

*   `$start` (`int`, optional): Die ID der Startkategorie. Verwenden Sie `-1` für die YRewrite Mount-ID oder die Root-Kategorie. Standard: `-1`.
*   `$depth` (`int`, optional): Die maximale Tiefe der Navigation. Standard: `4`.
*   `$ignoreOfflines` (`bool`, optional): Gibt an, ob Offline-Kategorien ignoriert werden sollen. Standard: `true`.
*   `$depthSaved` (`int`, optional): Gespeicherte Tiefe, wird intern verwendet. Standard: `0`.
*   `$level` (`int`, optional): Aktuelles Level, wird intern verwendet. Standard: `0`.

### Statische Methoden

#### `create()`

```php
public static function create(): self
```

Factory-Methode zum einfachen Erstellen einer neuen Instanz.

*   Rückgabewert: Eine neue Instanz von `BuildArray` (`self`).

### Hauptmethoden

#### `generate()`

```php
public function generate(): array
```

Generiert das Navigations-Array mit allen Kategorien und Unterkategorien basierend auf den konfigurierten Einstellungen.

*   Rückgabewert: Das generierte Navigations-Array (`array`).

#### `toJson()`

```php
public function toJson(): string
```

Generiert das Navigations-Array und gibt es als JSON-formatierte Zeichenkette zurück.

*   Rückgabewert: Das generierte Navigations-Array als JSON-String (`string`).

### Setter-Methoden

#### `setExcludedCategories()`

```php
public function setExcludedCategories(int|array $excludedCategories): self
```

Legt fest, welche Kategorien von der Navigation ausgeschlossen werden sollen.

*   `$excludedCategories` (`int` oder `array` von `int`): Eine einzelne Kategorie-ID oder ein Array von Kategorie-IDs.
*   Rückgabewert: Die aktuelle Instanz von `BuildArray` (`self`).

#### `setStart()`

```php
public function setStart(int|array $start): self
```

Setzt die Start-Kategorie(n) für die Navigation.

*   `$start` (`int` oder `array` von `int`): Die ID der Startkategorie oder ein Array von Kategorie-IDs. Verwenden Sie `-1` für die YRewrite Mount-ID oder die Root-Kategorie, `0` für die Root-Kategorie oder eine positive Zahl für eine spezifische Kategorie-ID.
*   Rückgabewert: Die aktuelle Instanz von `BuildArray` (`self`).

#### `setDepth()`

```php
public function setDepth(int $depth): self
```

Legt die maximale Tiefe der Navigation fest.

*   `$depth` (`int`): Die maximale Tiefe der Navigation.
*   Rückgabewert: Die aktuelle Instanz von `BuildArray` (`self`).

#### `setIgnore()`

```php
public function setIgnore(int $ignore): self
```

Legt fest, ob Offline-Kategorien ignoriert werden sollen.

*   `$ignore` (`int`): `true` (1) oder `false` (0) um Offline-Kategorien zu ignorieren.
*   Rückgabewert: Die aktuelle Instanz von `BuildArray` (`self`).

#### `setLevel()`

```php
public function setLevel(int $lvl): self
```

Setzt das aktuelle Level in der Navigation.

*   `$lvl` (`int`): Das aktuelle Level der Navigation.
*   Rückgabewert: Die aktuelle Instanz von `BuildArray` (`self`).

### Callback-Methoden

#### `setCategoryFilterCallback()`

```php
public function setCategoryFilterCallback(callable $callback): self
```

Setzt eine Callback-Funktion zum Filtern von Kategorien.

*   `$callback` (`callable`): Eine Funktion, die ein `rex_category` Objekt als Parameter erhält und `true` (für die Aufnahme der Kategorie) oder `false` (für deren Ausschluss) zurückgibt.
*   Rückgabewert: Die aktuelle Instanz von `BuildArray` (`self`).

#### `setCustomDataCallback()`

```php
public function setCustomDataCallback(callable $callback): self
```

Setzt eine Callback-Funktion zum Hinzufügen benutzerdefinierter Daten.

*   `$callback` (`callable`): Eine Funktion, die ein `rex_category` Objekt als Parameter erhält und ein `array` mit zusätzlichen Daten zurückgibt.
*   Rückgabewert: Die aktuelle Instanz von `BuildArray` (`self`).

### Verwendung von `toJson()`

```php
<?php
use FriendsOfRedaxo\NavigationArray\BuildArray;
// Grundlegende Verwendung
$nav = BuildArray::create()->setDepth(3);
$jsonString = $nav->toJson();

// Mit allen Optionen
$nav = BuildArray::create()
    ->setStart(5)             // Startet bei Kategorie ID 5
    ->setDepth(2)            // Zwei Ebenen tief
    ->setIgnore(true)        // Ignoriert Offline-Kategorien
    ->setExcludedCategories([10, 15]) // Schließt Kategorien aus
    ->toJson();              // Gibt JSON zurück

// JSON in Variable speichern und ausgeben
$jsonNavigation = $nav->toJson();
echo $jsonNavigation;

// Direkt als AJAX Response verwenden
header('Content-Type: application/json');
echo BuildArray::create()
    ->setDepth(3)
    ->toJson();

// JSON decodieren für weitere Verarbeitung
$navigationArray = json_decode($nav->toJson(), true);
```

Das generierte JSON sieht etwa so aus:

```json
[
    {
        "catId": 1,
        "parentId": 0,
        "level": 0,
        "catName": "Home",
        "url": "/",
        "hasChildren": true,
        "children": [
            {
                "catId": 5,
                "parentId": 1,
                "level": 1,
                "catName": "Über uns",
                "url": "/ueber-uns/",
                "hasChildren": false,
                "children": [],
                "path": [1, 5],
                "active": false,
                "current": false
            }
        ],
        "path": [1],
        "active": true,
        "current": false
    }
]
```

## Autor

**Friends Of REDAXO**

-   <http://www.redaxo.org>
-   <https://github.com/FriendsOfREDAXO>

**Projektleitung**

[Thomas Skerbis](https://github.com/skerbis)
