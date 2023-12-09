# REDAXO navigationArray

navigationArray ist Teil des FriendsOfRedaxo-Projekts. Die PHP-Class erstellt ein Array der Struktur zur einfacheren Generierung individueller Navigationen. 

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
                "catObject" => rex_category {#271 ▶}
            ]
            1 => array:11 [▶]
        ]
        "path" => []
        "active" => true
        "current" => true
        "catObject" => rex_category {#123 ▶}
    ]
    5 => array:11 [▶]
    6 => array:11 [▶]
]
```


## Konstruktor

```php
public function __construct($start = 0, $depth = 2, $ignoreOfflines = true, $depthSaved = 0, $level = 0)
```

- **$start**: Startkategorie-ID oder Array von Kategorie-IDs. Standardmäßig `0` für Root-Kategorien.
- **$depth**: Tiefenbegrenzung der Navigation. Standardmäßig `2`.
- **$ignoreOfflines**: Bestimmt, ob offline Kategorien ignoriert werden sollen. Standardmäßig `true`.
- **$depthSaved**: Interner Gebrauch für die Tiefenverwaltung. Standardmäßig `0`.
- **$level**: Interner Gebrauch für die Levelverwaltung. Standardmäßig `0`.

## Methoden

- `setStart($start)`: Setzt die Startkategorie.
- `setDepth($depth)`: Setzt die maximale Tiefe.
- `setIgnore($ignore)`: Bestimmt, ob Offline-Kategorien ignoriert werden sollen.
- `setDepthSaved($saved)`: Setzt die gespeicherte Tiefe.
- `setLevel($lvl)`: Setzt das aktuelle Level.
- `create()`: Erzeugt eine neue Instanz der Klasse.
- `generate()`: Generiert die Navigationsstruktur als Array.

## Private Methoden

- `initializeStartCategory()`: Initialisiert die Startkategorie basierend auf den Konstruktorparametern.
- `isCategoryPermitted($cat)`: Überprüft, ob eine Kategorie erlaubt ist (abhängig von YCom-Plugin).
- `processCategory($cat, $currentCatpath, $currentCat_id)`: Verarbeitet eine einzelne Kategorie und baut ihre Struktur auf.
- `generateSubcategories($parentCat)`: Generiert rekursiv Unterkategorien.

## Beispiel: Ausgabe des Arrays

```php

// Initialisierung des NavigationArray mit Startkategorie-ID 0 und Tiefe 3
$navArray = new FriendsOfRedaxo\navigationArray(0, 3);

// Generierung der Navigationsstruktur
$result = $navArray->generate();

// Ausgabe des Arrays
dump($result);
```


## Beispiel 

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

$navigationArray = new FriendsOfRedaxo\navigationArray(0, 3)->generate();

// Generate the navigation list
$navigationList = generateNavigationList($navigationArray);

// Output the navigation list
echo $navigationList;
```


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
$navigationArray = new FriendsOfRedaxo\navigationArray(0, 4)->generate();
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <ul class="navbar-nav ms-md-auto mb-0">
            <?php echo bsnavi5(navArray($navigationArray) ?>
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
$navigationArray = new FriendsOfRedaxo\navigationArray(0, 4)->generate();
$navigation = '
    <ul class="uk-navbar-nav">'
    . myNavi_demo(navArray($navigationArray) .
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
// UIkit Breadcrumb uses navigation_array
function bc_uikit($data = array())
{
    foreach ($data as $cat) {
        if ($cat['active'] == false)    
        {
            continue;
        }  
        if ($cat['hasChildren'] == true) {
            $sub = [];
            $sub[] = bc_uikit($cat['children']);
            $subnavi = join("\n", $sub);
        } 
	$url = ' href="'.$cat['url'].'"';
        $catname = $cat['catName'];
        if ('REX_ARTICLE_ID' == $cat['catId']) {
            $liclass = ' class="uk-disabled" aria-current="page"';
            $url ='';
        }
        $catname = '<a'.$url.'>'.$catname.'</a>';     
        if ($cat['active'] == true)       
        {
	         $output[] = '<li'.$liclass.'>' . $catname .'</li>'.$subnavi;

        }
    }
    return join("\n", $output);
}

// Breadcrumb erzeugen ($depth muss angegeben werden)
$navigationArray = new FriendsOfRedaxo\navigationArray(0, 4, 20)->generate();
echo  '
    <nav aria-label="Breadcrumb">
    <ul class="uk-breadcrumb">'
    .bc_uikit(navArray($navigationArray).
    '</ul>
    </nav>
';
?>
```
