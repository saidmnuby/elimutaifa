<?php
declare(strict_types=1);

/** Selection modules share the selection/ directory. */
function et_selection_sidebar(string $active): void
{
    $items = [
        'home' => ['../../', 'fa-house', 'Nyumbani'],
        'form-one' => ['../form-one/', 'fa-list-check', 'Form One Selection'],
        'form-five' => ['../form-five/', 'fa-graduation-cap', 'Form Five / Vyuo vya kati'],
        'contribution' => ['../../contribution/', 'fa-comments', 'Contribute / Maoni'],
    ];
    ?>
    <aside class="sidebar" id="sidebar">
        <nav aria-label="Selection navigation"><ul class="sidebar-menu">
            <?php foreach ($items as $key => [$href, $icon, $label]): ?>
                <li><a href="<?= $href ?>" class="sidebar-item<?= $key === $active ? ' active' : '' ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><i class="fa-solid <?= $icon ?>" aria-hidden="true"></i><span><?= $label ?></span></a></li>
            <?php endforeach; ?>
        </ul></nav>
    </aside>
    <?php
}
