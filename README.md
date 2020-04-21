# navigation_array
Helper function zur Generierung von REDAXO-Navigationen

Das AddOn liefert eine Function zur Generierung eines Navigationsarrays. 

In YCOM definierte Rechte werden berücksichtigt

## Aufruf:
```php
navArray($start = 0, $depth = 0, $ignoreOfflines = true)
```

**$Start**

numerisch

Hier wird die Id der Start-Kategorie angegeben ab der das Array erzeugt wird.

Das kann auch eine Mount-Id aus Yrewrite sein

`rex_yrewrite::getDomainByArticleId(rex_article::getCurrentId(), rex_clang::getCurrentId())->getMountId();`

**$depth**
numerisch

Hier wird die gewünschte Tiefe der Navigation festgelegt, wobei 0 für die Hauptebene root und alle weiteren für Ebenen stehen. 
Also 1 wäre die erste Unterebene. 

**$ignoreOffline**

true / false

Bei true werden Offline-Kategorien ignoriert. 

Die Navigation kann anschließend mit einer eigenen rekursiven Function verarbeitet und gestaltet werden. 

## Beispiel UIkit-Drop-Down

**Navigations-Array auslesen**

```php
<?php

//
// UiKit-Aufklappmenü
//
function myNavi($data = array())
{
  $output = array();
    foreach ($data as $cat) {
        $subnavi = $catname = $li = $ul = "";
        if ($cat['level'] == 0 && $cat['hasChildren'] == true)
         {
            $li = ' class="uk-parent"';
            $ul = ' class="uk-nav-sub"';
         }
        if ($cat['hasChildren'] == true) {
            $sub = [];
            $sub[] = '<ul'.$ul.'>';
            // Function ruft sich selbst auf sollten Kinder gefunden werden.
            $sub[] = myNavi($cat['children']);
            // -------------------------------------------------------------
            $sub[] = '</ul>';
            $subnavi = join("\n", $sub);
        }
        
        $catname = $cat['catName'];
        
        if ($cat['active'] == true) {
            $catname = '<strong>' . $catname . '</strong>';
        }
        
        $catname = '<a href="'.$cat['url'].'">'.$catname.'</a>';
       
        $output[] = '<li'.$li.'>' . $catname . $subnavi . '</li>';
    }
    return join("\n", $output);
}
```

**Navigation mit eigener Funktiom erzeugen**

```php
// Navigation erzeugen
$navigation = '<div>
    <ul class="uk-nav-default uk-nav-parent-icon" uk-nav>'
    .myNavi(navArray($start = 0, $depth = 4, true)).
    '</ul>
</div>';
```

**Mögliche Ausgabe (hier UiKit)**

```php
<div class="nav" data-uk-sticky="top: 200; animation: uk-animation-slide-top">
    <nav class="uk-navbar-container" data-uk-navbar>
        <div class="uk-navbar-left">
            <a class="uk-navbar-item uk-logo" href="/">LOGO</a>
        </div>
        <div class="uk-navbar-right">
            <button class="uk-navbar-toggle" uk-icon="icon: menu; ratio: 2" type="button" uk-toggle="target: #sidebar-navi"></button>
            <div id="sidebar-navi" uk-offcanvas="overlay: true; flip: false;">
                <div class="uk-offcanvas-bar uk-dark">
                    <button class="uk-offcanvas-close" type="button" uk-close></button>
                    <?=$navigation;?>
                </div>
            </div>
        </div>
    </nav>
</div>
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
        $catname = $cat['catName'];
        if ('REX_ARTICLE_ID' == $cat['catId']) {
            $liclass = ' class="uk-disabled"';
            $cat['url']='';
        }
        $catname = '<a href="'.$cat['url'].'">'.$catname.'</a>';     
        if ($cat['active'] == true)       
        {
	         $output[] = '<li'.$liclass.'>' . $catname .'</li>'.$subnavi;

        }
    }
    return join("\n", $output);
}

// Breadcrumb erzeugen ($depth muss angegeben werden)
echo  '
    <ul class="uk-breadcrumb">'
    .bc_uikit(navArray($start = 0, $depth = 8, true)).
    '</ul>
';
?>
```

Dump eines Navigation-Arrays: 

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





