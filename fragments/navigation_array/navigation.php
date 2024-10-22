<?php foreach ($this->getVar('navigationArray') as $categoryArray): ?>
    <li>
        <a class="<?= $categoryArray['active'] ? $this->getVar('activeClass') : '' ?>" role="menuitem" href="<?= rex_escape($categoryArray['url']) ?>">
            <?= $categoryArray['catName'] ?>
        </a>
        <?php if (!empty($categoryArray['children'])): ?>
            <ul role="menu">
                <?php
                $this->subfragment('navigation_array/navigation.php', ['navigationArray' => $categoryArray['children']]);
                ?>
            </ul>
        <?php endif; ?>
    </li>
<?php endforeach; ?>