<?= $this->extend('auth/layout') ?>
<?= $this->section('content') ?>
<div class="eyebrow">SUA COLEÇÃO COMEÇA AQUI</div>
<h2>Crie sua conta<span class="heading-dot">.</span></h2>
<p class="auth-subtitle">Tudo o que você precisa para acompanhar suas miniaturas e pré-vendas.</p>
<form action="<?= url_to('register') ?>" method="post" class="auth-form">
    <?= csrf_field() ?>
    <div class="field"><label for="username">Nome de usuário</label><input id="username" type="text" name="username" value="<?= esc(old('username')) ?>" autocomplete="username" placeholder="Como podemos chamar você?" required></div>
    <div class="field"><label for="email">E-mail</label><input id="email" type="email" name="email" value="<?= esc(old('email')) ?>" autocomplete="email" placeholder="seuemail@exemplo.com" required></div>
    <div class="field"><label for="password">Senha</label><input id="password" type="password" name="password" autocomplete="new-password" placeholder="Crie uma senha segura" required></div>
    <div class="field"><label for="password_confirm">Confirme a senha</label><input id="password_confirm" type="password" name="password_confirm" autocomplete="new-password" placeholder="Digite sua senha novamente" required></div>
    <button class="primary-button" type="submit">Criar minha conta <span aria-hidden="true">→</span></button>
</form>
<div class="auth-divider"><span>JÁ TEM UMA CONTA?</span></div>
<p class="auth-switch">Bem-vindo de volta! <a href="<?= url_to('login') ?>">Fazer login</a></p>
<?= $this->endSection() ?>
