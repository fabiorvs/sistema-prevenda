<?php
/** @var \CodeIgniter\Pager\PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav aria-label="Paginação">
    <ul class="pagination mb-0">
        <?php if ($pager->hasPrevious()): ?><li class="page-item"><a class="page-link"
                href="<?= $pager->getPrevious() ?>">Anterior</a></li><?php endif ?>
        <?php foreach ($pager->links() as $link): ?><li class="page-item <?= $link['active'] ? 'active' : '' ?>"><a
                class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a></li><?php endforeach ?>
        <?php if ($pager->hasNext()): ?><li class="page-item"><a class="page-link"
                href="<?= $pager->getNext() ?>">Próxima</a></li><?php endif ?>
    </ul>
</nav>