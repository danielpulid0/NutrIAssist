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

    <?php if (isset($_GET['exito'])): ?>
        <div class="alert-success" style="margin: 15px; padding: 12px; background-color: #d4edda; color: #155724; border-radius: 8px; text-align: center;">
            ¡Perfil actualizado con éxito!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert-error" style="margin: 15px; padding: 12px; background-color: #f8d7da; color: #721c24; border-radius: 8px; text-align: center;">
            <?php 
                $error = $_GET['error'];
                if ($error === 'required') echo 'Por favor, completa todos los campos obligatorios.';
                elseif ($error === 'debe_ser_numero') echo 'La edad, peso y altura deben ser números.';
                elseif ($error === 'valor_minimo') echo 'Has ingresado un valor demasiado bajo en algún campo biométrico.';
                elseif ($error === 'valor_maximo') echo 'Has ingresado un valor demasiado alto en algún campo biométrico.';
                else echo 'Hubo un error al procesar los datos.';
            ?>
        </div>
    <?php endif; ?>

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
                <button type="submit" class="add-btn">+</button>
            </div>
        </div>

        <div class="info-box">
            <div class="info-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            </div>
            <div class="info-text">
                <h4>Importante</h4>
                <p>Este sistema es una herramienta de apoyo y no sustituye el diagnóstico de un profesional de la nutrición o medicina.</p>
            </div>
        </div>

        <h2 class="section-title">Reportes</h2>
        <div class="preferencias-card pref-action-card" onclick="generarReportePDF()">
            <div class="pref-item">
                <div class="pref-info">
                    <h4>Generar Reporte Semanal</h4>
                    <p>Descarga un resumen en PDF para tu nutriólogo</p>
                </div>
                <div class="pref-icon-malachite" id="pdf-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
            </div>
        </div>

        <h2 class="section-title">Ayuda</h2>
        <div class="preferencias-card pref-action-card" onclick="resetTutorial()">
            <div class="pref-item">
                <div class="pref-info">
                    <h4>Ver Tutorial</h4>
                    <p>Repasar guía interactiva de la aplicación</p>
                </div>
                <div class="pref-icon-malachite">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
            </div>
        </div>

        <div class="preferencias-card pref-action-card" onclick="window.location.href='terminos.php'">
            <div class="pref-item">
                <div class="pref-info">
                    <h4>Términos y Condiciones</h4>
                    <p>Información legal y descargo médico</p>
                </div>
                <div class="pref-icon-gray">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Guardar Cambios</button>
            <a href="../controllers/logout.php" class="btn-logout">Cerrar sesión</a>
        </div>

    </form>

    <?php include 'includes/footer.php'; ?>

</div>

<!-- jsPDF + autoTable (generación directa, sin dependencias de DOM oculto) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
function resetTutorial() {
    localStorage.removeItem('nutriassist_tutorial_shown_v4');
    window.location.href = 'dashboard.php';
}

async function generarReportePDF() {
    const iconDiv = document.getElementById('pdf-icon');
    const originalIcon = iconDiv.innerHTML;
    iconDiv.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>';
    iconDiv.querySelector('svg').animate([{transform:'rotate(0deg)'},{transform:'rotate(360deg)'}],{duration:1000,iterations:Infinity});

    try {
        const response = await fetch('../api/reporte_semanal_api.php');
        const data = await response.json();

        if (!data.success) {
            alert('Error al obtener datos: ' + (data.error || 'Desconocido'));
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        const W = doc.internal.pageSize.getWidth();
        const margin = 20;

        // ── Colores de marca (mismo verde que la app: #15B85E) ──
        const verde = [21, 184, 94];      // #15B85E (--color-malachite)
        const grisOsc = [30, 41, 59];    // #1E293B
        const grisMed = [100, 116, 139];  // #64748B
        const grisClaro = [241, 245, 249]; // #F1F5F9

        // ── FRANJA SUPERIOR VERDE ──
        doc.setFillColor(...verde);
        doc.rect(0, 0, W, 38, 'F');

        // ── Título en la franja ──
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(22);
        doc.setTextColor(255, 255, 255);
        doc.text('NutrIAssist', W / 2, 16, { align: 'center' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(12);
        doc.setTextColor(220, 255, 240);
        doc.text('Reporte Nutricional Semanal', W / 2, 25, { align: 'center' });

        doc.setFontSize(9);
        doc.text('Semana del ' + data.datos[0].fecha + '  al  ' + data.datos[6].fecha, W / 2, 33, { align: 'center' });

        // ── DATOS DEL PACIENTE ──
        let y = 50;
        doc.setFontSize(11);
        doc.setTextColor(...grisOsc);
        doc.setFont('helvetica', 'bold');
        doc.text('Paciente:', margin, y);
        doc.setFont('helvetica', 'normal');
        doc.text(data.nombre, margin + 28, y);

        y += 8;
        doc.setFont('helvetica', 'bold');
        doc.text('Meta Calórica Diaria:', margin, y);
        doc.setFont('helvetica', 'normal');
        doc.text(data.meta + ' kcal', margin + 52, y);

        y += 4;
        doc.setDrawColor(...verde);
        doc.setLineWidth(0.5);
        doc.line(margin, y, W - margin, y);

        // ── TABLA DE DATOS DIARIOS ──
        y += 8;
        const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        let sumCal = 0, sumPro = 0, sumCar = 0, sumGra = 0;
        let diasConRegistro = 0;

        const tableRows = data.datos.map(dia => {
            const d = new Date(dia.fecha + 'T12:00:00');
            const label = diasSemana[d.getDay()] + '  ' + dia.fecha;
            sumCal += dia.cals;
            sumPro += dia.pro;
            sumCar += dia.car;
            sumGra += dia.gra;
            if (dia.cals > 0) diasConRegistro++;
            return [label, dia.cals.toFixed(0), dia.pro.toFixed(1), dia.car.toFixed(1), dia.gra.toFixed(1)];
        });

        // Fila de promedio
        const n = diasConRegistro || 1;
        tableRows.push([
            'PROMEDIO',
            (sumCal / 7).toFixed(0),
            (sumPro / 7).toFixed(1),
            (sumCar / 7).toFixed(1),
            (sumGra / 7).toFixed(1)
        ]);

        doc.autoTable({
            startY: y,
            margin: { left: margin, right: margin },
            head: [['Día / Fecha', 'Calorías (kcal)', 'Proteínas (g)', 'Carbohidratos (g)', 'Grasas (g)']],
            body: tableRows,
            theme: 'grid',
            headStyles: {
                fillColor: verde,
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                halign: 'center',
                fontSize: 10
            },
            bodyStyles: {
                halign: 'center',
                fontSize: 10,
                textColor: grisOsc
            },
            alternateRowStyles: {
                fillColor: grisClaro
            },
            // La última fila (PROMEDIO) con estilo especial
            didParseCell: function(hookData) {
                if (hookData.section === 'body' && hookData.row.index === tableRows.length - 1) {
                    hookData.cell.styles.fillColor = [30, 41, 59];
                    hookData.cell.styles.textColor = [255, 255, 255];
                    hookData.cell.styles.fontStyle = 'bold';
                }
            },
            columnStyles: {
                0: { halign: 'left', cellWidth: 45 }
            }
        });

        // ── RESUMEN VISUAL ──
        let finalY = doc.lastAutoTable.finalY + 15;

        // Tarjetas de resumen
        const cardW = (W - margin * 2 - 15) / 4;
        const cards = [
            { label: 'Calorías', value: (sumCal / 7).toFixed(0), unit: 'kcal', color: verde },
            { label: 'Proteínas', value: (sumPro / 7).toFixed(1), unit: 'g', color: [59, 130, 246] },
            { label: 'Carbohidratos', value: (sumCar / 7).toFixed(1), unit: 'g', color: [249, 115, 22] },
            { label: 'Grasas', value: (sumGra / 7).toFixed(1), unit: 'g', color: [234, 179, 8] }
        ];

        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...grisOsc);
        doc.text('Promedios Diarios', margin, finalY);
        finalY += 8;

        cards.forEach((card, i) => {
            const x = margin + i * (cardW + 5);
            // Fondo de tarjeta
            doc.setFillColor(...grisClaro);
            doc.roundedRect(x, finalY, cardW, 30, 3, 3, 'F');
            // Barra de color superior
            doc.setFillColor(...card.color);
            doc.roundedRect(x, finalY, cardW, 5, 3, 3, 'F');
            doc.setFillColor(...grisClaro);
            doc.rect(x, finalY + 3, cardW, 2, 'F'); // Recorta la esquina inferior del color

            // Valor
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(14);
            doc.setTextColor(...card.color);
            doc.text(card.value + ' ' + card.unit, x + cardW / 2, finalY + 17, { align: 'center' });

            // Label
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(...grisMed);
            doc.text(card.label, x + cardW / 2, finalY + 25, { align: 'center' });
        });

        finalY += 42;

        // ── ADHERENCIA A META ──
        const adherencia = Math.min(100, Math.round((sumCal / 7) / data.meta * 100));
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.setTextColor(...grisOsc);
        doc.text('Adherencia a Meta Calórica', margin, finalY);
        finalY += 8;

        // Barra de progreso
        const barW = W - margin * 2;
        doc.setFillColor(226, 232, 240);
        doc.roundedRect(margin, finalY, barW, 8, 4, 4, 'F');
        const fillW = barW * (adherencia / 100);
        doc.setFillColor(...(adherencia > 110 ? [239, 68, 68] : verde));
        doc.roundedRect(margin, finalY, Math.max(fillW, 8), 8, 4, 4, 'F');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(255, 255, 255);
        if (fillW > 25) {
            doc.text(adherencia + '%', margin + fillW / 2, finalY + 6, { align: 'center' });
        }

        finalY += 14;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.setTextColor(...grisMed);
        const adherMsg = adherencia < 80 ? 'El paciente consume significativamente por debajo de su meta.'
                       : adherencia <= 110 ? 'El paciente mantiene una ingesta adecuada respecto a su meta.'
                       : 'El paciente excede su meta calórica diaria.';
        doc.text(adherMsg, margin, finalY);

        // ── OBSERVACIONES ──
        finalY += 15;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.setTextColor(...grisOsc);
        doc.text('Observaciones del Profesional', margin, finalY);
        finalY += 6;

        // Líneas punteadas para escribir
        doc.setDrawColor(203, 213, 225);
        doc.setLineDashPattern([2, 2], 0);
        for (let i = 0; i < 4; i++) {
            doc.line(margin, finalY + (i * 10), W - margin, finalY + (i * 10));
        }
        doc.setLineDashPattern([], 0);

        // ── PIE DE PÁGINA ──
        const pageH = doc.internal.pageSize.getHeight();
        doc.setDrawColor(...verde);
        doc.setLineWidth(0.5);
        doc.line(margin, pageH - 18, W - margin, pageH - 18);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(...grisMed);
        doc.text('Generado automáticamente por NutrIAssist — Este documento es informativo y no sustituye la valoración médica.', W / 2, pageH - 12, { align: 'center' });
        doc.text('Fecha de generación: ' + new Date().toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' }), W / 2, pageH - 7, { align: 'center' });

        // ── GUARDAR ──
        doc.save('Reporte_Nutricional_' + data.datos[6].fecha + '.pdf');

    } catch (error) {
        console.error('Error generando PDF:', error);
        alert('Hubo un problema al generar el reporte: ' + error.message);
    } finally {
        iconDiv.innerHTML = originalIcon;
    }
}
</script>

<script src="../assets/js/perfil.js?v=<?= time() ?>"></script>
</body>
</html>
