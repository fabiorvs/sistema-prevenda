<?= $this->include('partials/header') ?><main class="dc-content">
    <div class="dc-page-heading">
        <div>
            <a class="dc-back-button" href="/vendedores"><i class="bi bi-arrow-left"></i> Voltar para vendedores</a>
            <h1 class="dc-page-title mt-2"><?= $seller ? 'Editar vendedor' : 'Novo vendedor' ?></h1>
            <p class="dc-page-subtitle">Dados de contato e identificação da loja.</p>
        </div>
    </div>
    <?php if(session('errors')): ?><div class="alert alert-danger"><?= implode('<br>',session('errors')) ?></div>
    <?php endif ?><form method="post" enctype="multipart/form-data"
        action="<?= $seller ? '/vendedores/'.$seller['id'] : '/vendedores' ?>" class="card card-body">
        <?= csrf_field() ?><div class="row g-3">
            <div class="col-md-8"><label class="form-label">Nome</label><input class="form-control mb-3" name="name"
                    required value="<?= esc(old('name',$seller['name'] ?? '')) ?>"><label
                    class="form-label">Contato</label><input class="form-control mb-3" name="contact_name"
                    value="<?= esc(old('contact_name',$seller['contact_name'] ?? '')) ?>"><label
                    class="form-label">WhatsApp</label><input class="form-control mb-3" name="whatsapp"
                    value="<?= esc(old('whatsapp',$seller['whatsapp'] ?? '')) ?>"><label
                    class="form-label">Instagram</label><input class="form-control mb-3" name="instagram"
                    value="<?= esc(old('instagram',$seller['instagram'] ?? '')) ?>"><label
                    class="form-label">Site</label><input class="form-control mb-3" name="website"
                    value="<?= esc(old('website',$seller['website'] ?? '')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Logo ou
                    foto</label><?php if(!empty($seller['image_path'])): ?><img
                    class="img-fluid rounded border d-block mb-2" style="max-height:180px"
                    src="/imagens/vendedores/<?= $seller['id'] ?>" alt="Logo atual"><?php endif ?><input
                    class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp"><small
                    class="text-secondary">JPEG, PNG ou WebP, até 5 MB.</small></div>
        </div><label class="form-label mt-3">Observações</label><textarea class="form-control mb-3"
            name="notes"><?= esc(old('notes',$seller['notes'] ?? '')) ?></textarea><button
            class="btn btn-primary">Salvar vendedor</button></form>
</main><?= $this->include('partials/footer') ?>
