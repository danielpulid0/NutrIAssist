<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Helpers para íconos SVG (simplificados)
function get_icon_home($is_active) {
    $c = $is_active ? 'var(--color-malachite)' : 'var(--color-text-gray)';
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
}
function get_icon_diary($is_active) {
    $c = $is_active ? 'var(--color-malachite)' : 'var(--color-text-gray)';
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
}
function get_icon_recipes($is_active) {
    $c = $is_active ? 'var(--color-malachite)' : 'var(--color-text-gray)';
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg>';
}
function get_icon_profile($is_active) {
    $c = $is_active ? 'var(--color-malachite)' : 'var(--color-text-gray)';
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="'.$c.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
}
?>
<style>
    /* Estilos de la barra de navegación inferior plana */
    .bottom-nav {
        position: fixed; /* Cambiado a fixed para que flote siempre en mobile */
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        max-width: 430px;
        background-color: var(--color-bg);
        border-top: 1px solid var(--color-border);
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 0.6rem 0;
        padding-bottom: calc(0.6rem + env(safe-area-inset-bottom, 0px)); 
        z-index: 1000;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.03);
        border-bottom-left-radius: 16px; /* Si está en PC, empareja con el cascarón */
        border-bottom-right-radius: 16px;
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: var(--color-text-gray);
        font-size: 0.70rem;
        font-weight: 500;
        letter-spacing: 0.02em;
        transition: color 0.2s, transform 0.1s;
        flex: 1;
        gap: 3px;
    }

    .nav-item:active { transform: scale(0.95); }

    .nav-item.active {
        color: var(--color-malachite);
        font-weight: 600;
    }
</style>

<div class="bottom-nav">
    <a href="dashboard.php" class="nav-item <?= $pagina_actual == 'dashboard.php' ? 'active' : '' ?>">
        <?= get_icon_home($pagina_actual == 'dashboard.php') ?>
        <span>Inicio</span>
    </a>
    <a href="diario.php" class="nav-item <?= $pagina_actual == 'diario.php' ? 'active' : '' ?>">
        <?= get_icon_diary($pagina_actual == 'diario.php') ?>
        <span>Diario</span>
    </a>
    <a href="recetas.php" class="nav-item <?= $pagina_actual == 'recetas.php' || $pagina_actual == 'receta_detalle.php' ? 'active' : '' ?>">
        <?= get_icon_recipes($pagina_actual == 'recetas.php' || $pagina_actual == 'receta_detalle.php') ?>
        <span>Recetas</span>
    </a>
    <a href="perfil.php" class="nav-item <?= $pagina_actual == 'perfil.php' ? 'active' : '' ?>">
        <?= get_icon_profile($pagina_actual == 'perfil.php') ?>
        <span>Perfil</span>
    </a>
</div>