<?php
// Obtenemos el nombre del archivo actual para saber qué icono pintar de verde (Estado Activo)
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Estilos de la barra de navegación inferior */
    .bottom-nav {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: var(--color-bg);
        border-top: 1px solid var(--color-border);
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 0.5rem 0;
        padding-bottom: env(safe-area-inset-bottom, 1rem); /* Para el notch de los iPhone */
        z-index: 1000;
        border-radius: 0 0 16px 16px; /* Si se ve en PC */
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: var(--color-text-gray);
        font-size: 0.75rem;
        font-weight: 500;
        transition: color 0.2s;
        flex: 1;
    }

    .nav-item.active {
        color: var(--color-malachite);
    }

    .nav-icon {
        font-size: 1.5rem;
        margin-bottom: 0.2rem;
    }

    /* El Botón Central Flotante de la IA (Gemma) */
    .nav-item-ai {
        position: relative;
        top: -15px; /* Lo empujamos hacia arriba */
        background-color: var(--color-primary);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 4px 10px rgba(55, 246, 119, 0.4); /* Sombra color Spring Green */
        color: var(--color-text-dark);
        font-size: 2rem;
        border: 4px solid var(--color-bg); /* Borde blanco para separarlo de la barra */
        transition: transform 0.2s;
    }

    .nav-item-ai:active {
        transform: scale(0.95);
        background-color: var