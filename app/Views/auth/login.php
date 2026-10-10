<?php echo $this->extend('auth/layout') ?>
<?php echo $this->section('content') ?>
<div class="eyebrow">BEM-VINDO DE VOLTA</div>
<h2>Entre na sua conta<span class="heading-dot">.</span></h2>
<p class="auth-subtitle">Acompanhe suas pré-vendas e mantenha sua coleção organizada.</p>
<form action="<?php echo url_to('login') ?>" method="post" class="auth-form">
    <?php echo csrf_field() ?>

    <!-- E-mail -->
    <div class="field">
        <label for="email">E-mail ou usuário</label>

        <input type="text" id="email" name="email" placeholder="Digite seu e-mail" value="<?php echo esc(old('email')) ?>"
            autocomplete="username" required>
    </div>

    <!-- Senha -->
    <div class="field">
        <div class="field-head">
            <label for="password">Senha</label>

            <a href="<?php echo url_to('magic-link') ?>">
                Esqueceu sua senha?
            </a>
        </div>

        <input type="password" id="password" name="password" placeholder="Digite sua senha"
            autocomplete="current-password" required>
    </div>

    <!-- Manter conectado -->
    <label class="remember">
        <input type="checkbox" name="remember" value="1" <?php echo old('remember') ? 'checked' : '' ?>>
        <span>Manter conectado</span>
    </label>

    <!-- Botão de login -->
    <button type="submit" class="primary-button">
        Entrar na minha conta
        <span aria-hidden="true">→</span>
    </button>
</form>
<div class="auth-divider"><span>AINDA NÃO TEM UMA CONTA?</span></div>
<p class="auth-switch">Comece a organizar sua coleção. <a href="<?php echo url_to('register') ?>">Criar conta grátis</a>
</p>
<?php echo $this->endSection() ?>