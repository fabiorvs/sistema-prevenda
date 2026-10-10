<?php
/** @var \CodeIgniter\Pager\PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<?php if ($pager->getPageCount() > 1): ?>
<nav aria-label="Paginação">
    <ul class="pagination mb-0">
        <?php if ($pager->hasPreviousPage()): ?><li class="page-item"><a class="page-link"
                href="<?= $pager->getPreviousPage() ?>" aria-label="Página anterior"><i class="bi bi-chevron-left"></i></a></li><?php endif ?>
        <?php foreach ($pager->links() as $link): ?><li class="page-item <?= $link['active'] ? 'active' : '' ?>"><a
                class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a></li><?php endforeach ?>
        <?php if ($pager->hasNextPage()): ?><li class="page-item"><a class="page-link"
                href="<?= $pager->getNextPage() ?>" aria-label="Próxima página"><i class="bi bi-chevron-right"></i></a></li><?php endif ?>
    </ul>
</nav>
<?php endif ?>
