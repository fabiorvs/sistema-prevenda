<?= $this->extend('auth/layout') ?>
<?= $this->section('content') ?>
<a class="back-link" href="<?= url_to('login') ?>">← Voltar ao login</a>
<div class="eyebrow">RECUPERAÇÃO DE ACESSO</div>
<h2>Recupere seu acesso<span class="heading-dot">.</span></h2>
<p class="auth-subtitle">Informe seu e-mail e enviaremos um link para entrar na sua conta com segurança.</p>
<form action="<?= url_to('magic-link') ?>" method="post" class="auth-form">
    <?= csrf_field() ?>
    <div class="field"><label for="email">E-mail cadastrado</label><input id="email" type="email" name="email" value="<?= esc(old('email')) ?>" autocomplete="email" placeholder="seuemail@exemplo.com" required autofocus></div>
    <button class="primary-button" type="submit">Enviar link de acesso <span aria-hidden="true">→</span></button>
</form>
<div class="info-note">O link de acesso será enviado ao e-mail cadastrado, caso exista uma conta correspondente.</div>
<?= $this->endSection() ?>
