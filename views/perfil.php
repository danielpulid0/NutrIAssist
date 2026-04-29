<?php
require_once '../controllers/perfil_controller.php';

$page_title = 'NutrIAssist - Mi Perfil';
$extra_css  = '../assets/css/perfil.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">
    
    <div class="header-top">
        <span>Perfil y Metas</span>
    </div>

    <form action="../controllers/procesar_perfil.php" method="POST" class="perfil-body">
        
        <h2 class="section-title">Datos Biométricos</h2>
        
        <div class="bio-card">
            <div class="bio-grid">
                <div class="input-group">
                    <label class="input-label">Edad</label>
                    <div class="input-box">
                        <input type="number" name="edad" value="<?= $edad ?>" required>
                        <span>años</span>
                    </div>
                </div>
                <div class="input-group">
                    <label class="input-label">Peso</label>
                    <div class="input-box">
                        <input type="number" name="peso" value="<?= $peso ?>" step="0.1" required>
                        <span>kg</span>
                    </div>
                </div>
                <div class="input-group">
                    <label class="input-label">Altura</label>
                    <div class="input-box">
                        <input type="number" name="altura" value="<?= $altura ?>" required>
                        <span>cm</span>
                    </div>
                </div>
                <div class="input-group">
                    <label class="input-label">Sexo</label>
                    <div class="input-box">
                        <div class="select-wrapper">
                            <select name="sexo">
                                <option value="Hombre" <?= $sexo == 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                                <option value="Mujer" <?= $sexo == 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Nivel de Actividad</label>
                <div class="input-box">
                    <div class="select-wrapper">
                        <select name="actividad">
                            <option value="1" <?= $actividad == 1 ? 'selected' : '' ?>>Sedentario (Poco o nulo ejercicio)</option>
                            <option value="2" <?= $actividad == 2 ? 'selected' : '' ?>>Moderado (Ejercicio 3-5 días/sem)</option>
                            <option value="3" <?= $actividad == 3 ? 'selected' : '' ?>>Activo (Ejercicio 6-7 días/sem)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="section-title">Meta Principal</h2>
        
        <!-- Hidden input para meta -->
        <input type="hidden" name="meta_principal" id="meta_input" value="<?= htmlspecialchars($meta) ?>">

        <label class="meta-card <?= $meta == 'Perder Grasa' ? 'active' : '' ?>" onclick="selectMeta(this, 'Perder Grasa')">
            <div class="meta-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c0 0-5.5 6.5-5.5 11.5a5.5 5.5 0 1 0 11 0C17.5 8.5 12 2 12 2z"></path></svg>
            </div>
            <div class="meta-info">
                <h4>Perder Grasa</h4>
                <p>Déficit calórico controlado</p>
            </div>
            <div class="meta-radio"></div>
        </label>

        <label class="meta-card <?= $meta == 'Ganar Músculo' ? 'active' : '' ?>" onclick="selectMeta(this, 'Ganar Músculo')">
            <div class="meta-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.8 4A6.3 6.3 0 0 1 20 8.2v11.5a1.5 1.5 0 0 1-1.5 1.5H5.5A1.5 1.5 0 0 1 4 19.7V8.2A6.3 6.3 0 0 1 5.2 4"></path><path d="M14 15h.01"></path><path d="M10 15h.01"></path></svg>
            </div>
            <div class="meta-info">
                <h4>Ganar Músculo</h4>
                <p>Superávit enfocado en proteína</p>
            </div>
            <div class="meta-radio"></div>
        </label>

        <label class="meta-card <?= $meta == 'Mantener Peso' ? 'active' : '' ?>" onclick="selectMeta(this, 'Mantener Peso')">
            <div class="meta-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19h16"></path><path d="M12 4v15"></path><path d="M6 10l6-6 6 6"></path></svg>
            </div>
            <div class="meta-info">
                <h4>Mantener Peso</h4>
                <p>Equilibrio saludable</p>
            </div>
            <div class="meta-radio"></div>
        </label>

        <h2 class="section-title">Preferencias</h2>
        <div class="preferencias-card">
            <div class="pref-item">
                <div class="pref-info">
                    <h4>Modo Oscuro</h4>
                    <p>Cambiar tema visual</p>
                </div>
                <label class="theme-switch">
                    <input type="checkbox" id="theme-switch-checkbox">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <h2 class="section-title">Restricciones Médicas / Alérgenos</h2>
        
        <div class="restricciones-list">
            <?php foreach($restricciones as $rest): ?>
            <div class="restriccion-item">
                <?= htmlspecialchars($rest['nombre']) ?>
                <a href="../controllers/procesar_perfil.php?eliminar_rest=<?= $rest['id_restriccion'] ?>" class="btn-remove"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></a>
            </div>
            <?php endforeach; ?>
            
            <div class="add-row">
                <input type="text" name="nueva_restriccion" class="add-input" placeholder="Agregar nueva restricción...">
                <button type="submit" class="add-btn">⊕</button>
            </div>
        </div>

        <a href="#" class="premium-banner">
            Hazte premium <span>›</span>
        </a>

        <div class="info-box">
            <div class="info-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            </div>
            <div class="info-text">
                <h4>Importante</h4>
                <p>Este sistema es una herramienta de apoyo y no sustituye el diagnóstico de un profesional de la nutrición o medicina.</p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
            <a href="../controllers/logout.php" class="btn-logout">Cerrar sesión</a>
        </div>

    </form>

    <?php include 'includes/footer.php'; ?>

</div>

<script src="../assets/js/perfil.js?v=<?= time() ?>"></script>
</body>
</html>
