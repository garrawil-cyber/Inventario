<?php
$currentPage = 'clientes';
include __DIR__ . '/../../layouts/inventario_layout.php';

if (!isset($cliente) || !$cliente) {
    header('Location: index.php?controller=cliente&action=index');
    exit();
}

/* ── Helpers de presentación ── */
$inicial  = mb_strtoupper(mb_substr($cliente['nombre_completo'] ?? '?', 0, 1));
$tipo     = $cliente['tipo_cliente'] ?? 'regular';
$estado   = $cliente['estado'] ?? 'inactivo';
$edad     = null;
if (!empty($cliente['fecha_nacimiento'])) {
    $edad = date_diff(date_create($cliente['fecha_nacimiento']), date_create('today'))->y;
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/views/inventario/clientes/css/clientes.css">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

<div class="cl-shell">

    <!-- ── TOPBAR ── -->
    <header class="cl-topbar">
        <div class="cl-topbar-left">
            <div class="cl-page-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <div>
                <h1 class="cl-page-title">Detalle del Cliente</h1>
                <nav class="cl-breadcrumb">
                    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
                    <span class="cl-breadcrumb-sep">/</span>
                    <a href="index.php?controller=cliente&action=index">Clientes</a>
                    <span class="cl-breadcrumb-sep">/</span>
                    <span><?= htmlspecialchars($cliente['nombre_completo']) ?></span>
                </nav>
            </div>
        </div>
        <div class="cl-topbar-actions">
            <a href="index.php?controller=cliente&action=index" class="cl-btn cl-btn-ghost">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="index.php?controller=cliente&action=editar&id=<?= (int)$cliente['id_cliente'] ?>"
               class="cl-btn cl-btn-primary">
                <i class="fas fa-pen-to-square"></i> Editar
            </a>
        </div>
    </header>

    <!-- ── HERO DEL CLIENTE ── -->
    <div class="cl-ver-hero">
        <div class="cl-ver-hero-avatar">
            <div class="cl-avatar cl-avatar-xl" data-tipo="<?= $tipo ?>">
                <?= $inicial ?>
            </div>
        </div>
        <div class="cl-ver-hero-info">
            <h2 class="cl-ver-nombre"><?= htmlspecialchars($cliente['nombre_completo']) ?></h2>
            <div class="cl-ver-meta">
                <span class="cl-pill cl-pill-<?= $tipo ?>">
                    <?php $iconoTipo = ['vip'=>'fa-star','frecuente'=>'fa-bolt','mayorista'=>'fa-handshake','regular'=>'fa-user'];?>
                    <i class="fas <?= $iconoTipo[$tipo] ?? 'fa-user' ?>"></i>
                    <?= ucfirst($tipo) ?>
                </span>
                <span class="cl-status <?= $estado === 'activo' ? 'cl-status-activo' : 'cl-status-inactivo' ?>">
                    <i class="fas <?= $estado === 'activo' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                    <?= ucfirst($estado) ?>
                </span>
                <span class="cl-ver-doc-pill">
                    <i class="fas fa-id-card"></i>
                    <?= htmlspecialchars($cliente['tipo_documento']) ?>
                    <?= htmlspecialchars($cliente['numero_documento']) ?>
                </span>
            </div>
        </div>
        <!-- KPIs del cliente -->
        <div class="cl-ver-kpis">
            <div class="cl-ver-kpi">
                <span class="cl-ver-kpi-val"><?= number_format($cliente['total_compras'] ?? 0) ?></span>
                <span class="cl-ver-kpi-label">Compras</span>
            </div>
            <div class="cl-ver-kpi cl-ver-kpi-sep">
                <span class="cl-ver-kpi-val">S/ <?= number_format($cliente['total_gastado'] ?? 0, 2) ?></span>
                <span class="cl-ver-kpi-label">Total gastado</span>
            </div>
            <?php if ($edad !== null): ?>
            <div class="cl-ver-kpi cl-ver-kpi-sep">
                <span class="cl-ver-kpi-val"><?= $edad ?></span>
                <span class="cl-ver-kpi-label">Años</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── GRID DE INFORMACIÓN ── -->
    <div class="cl-ver-grid">

        <!-- Columna izquierda: datos de contacto -->
        <div class="cl-ver-section">
            <div class="cl-ver-section-title">
                <i class="fas fa-address-book"></i> Contacto
            </div>
            <div class="cl-ver-fields">

                <div class="cl-ver-field">
                    <span class="cl-ver-field-label">
                        <i class="fas fa-envelope"></i> Email
                    </span>
                    <span class="cl-ver-field-val">
                        <?php if (!empty($cliente['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($cliente['email']) ?>"
                               class="cl-ver-link">
                                <?= htmlspecialchars($cliente['email']) ?>
                            </a>
                        <?php else: ?>
                            <span class="cl-ver-empty">No registrado</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="cl-ver-field">
                    <span class="cl-ver-field-label">
                        <i class="fas fa-phone"></i> Teléfono
                    </span>
                    <span class="cl-ver-field-val">
                        <?php if (!empty($cliente['telefono'])): ?>
                            <a href="tel:<?= htmlspecialchars($cliente['telefono']) ?>"
                               class="cl-ver-link">
                                <?= htmlspecialchars($cliente['telefono']) ?>
                            </a>
                        <?php else: ?>
                            <span class="cl-ver-empty">No registrado</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="cl-ver-field cl-ver-field-full">
                    <span class="cl-ver-field-label">
                        <i class="fas fa-location-dot"></i> Dirección
                    </span>
                    <span class="cl-ver-field-val">
                        <?php if (!empty($cliente['direccion'])): ?>
                            <?= nl2br(htmlspecialchars($cliente['direccion'])) ?>
                        <?php else: ?>
                            <span class="cl-ver-empty">No registrada</span>
                        <?php endif; ?>
                    </span>
                </div>

            </div>
        </div>

        <!-- Columna derecha: datos personales -->
        <div class="cl-ver-section">
            <div class="cl-ver-section-title">
                <i class="fas fa-user-tag"></i> Datos Personales
            </div>
            <div class="cl-ver-fields">

                <div class="cl-ver-field">
                    <span class="cl-ver-field-label">
                        <i class="fas fa-calendar-days"></i> Fecha Nacimiento
                    </span>
                    <span class="cl-ver-field-val">
                        <?php if (!empty($cliente['fecha_nacimiento'])): ?>
                            <?= date('d/m/Y', strtotime($cliente['fecha_nacimiento'])) ?>
                            <small class="cl-ver-age">(<?= $edad ?> años)</small>
                        <?php else: ?>
                            <span class="cl-ver-empty">No registrada</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="cl-ver-field">
                    <span class="cl-ver-field-label">
                        <i class="fas fa-venus-mars"></i> Género
                    </span>
                    <span class="cl-ver-field-val">
                        <?php if (!empty($cliente['genero'])): ?>
                            <?= htmlspecialchars($cliente['genero']) ?>
                        <?php else: ?>
                            <span class="cl-ver-empty">No especificado</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="cl-ver-field">
                    <span class="cl-ver-field-label">
                        <i class="fas fa-tag"></i> Tipo de Cliente
                    </span>
                    <span class="cl-ver-field-val">
                        <span class="cl-pill cl-pill-<?= $tipo ?>"><?= ucfirst($tipo) ?></span>
                    </span>
                </div>

                <div class="cl-ver-field">
                    <span class="cl-ver-field-label">
                        <i class="fas fa-circle-dot"></i> Estado
                    </span>
                    <span class="cl-ver-field-val">
                        <span class="cl-status <?= $estado === 'activo' ? 'cl-status-activo' : 'cl-status-inactivo' ?>">
                            <i class="fas <?= $estado === 'activo' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                            <?= ucfirst($estado) ?>
                        </span>
                    </span>
                </div>

            </div>
        </div>

    </div><!-- /cl-ver-grid -->

    <!-- ── TIMESTAMPS ── -->
    <div class="cl-ver-timestamps">
        <div class="cl-ver-ts">
            <i class="fas fa-calendar-plus"></i>
            <span class="cl-ver-ts-label">Registrado el</span>
            <span class="cl-ver-ts-val">
                <?= date('d/m/Y H:i', strtotime($cliente['fecha_registro'])) ?>
            </span>
        </div>
        <div class="cl-ver-ts">
            <i class="fas fa-clock-rotate-left"></i>
            <span class="cl-ver-ts-label">Última actualización</span>
            <span class="cl-ver-ts-val">
                <?= date('d/m/Y H:i', strtotime($cliente['fecha_actualizacion'])) ?>
            </span>
        </div>
    </div>

</div><!-- /cl-shell -->

<script>
const clienteSweetAlert = <?= (isset($sweetAlert) && $sweetAlert)
    ? json_encode([
        'title' => $sweetAlert['title'] ?? 'Aviso',
        'text'  => $sweetAlert['text']  ?? ($sweetAlert['message'] ?? ''),
        'type'  => $sweetAlert['type']  ?? 'info',
    ])
    : 'null' ?>;
</script>

<script src="<?= BASE_URL ?>/views/inventario/clientes/js/clientes.js"></script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>