<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../config/permissions.php';
require_once __DIR__ . '/../../config/SessionMiddleware.php';

// ✅ AÑADIR — garantizar sesión activa ANTES de leer $_SESSION
SessionMiddleware::startSession();

$csrfToken = AuthController::getCsrfToken();

$userRol    = $_SESSION['usuario_rol'] ?? 'vendedor'; // ahora $_SESSION está disponible
$menuItems  = Permissions::getMenuByRole($userRol);
$flashMessage = SessionMiddleware::getFlashMessage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Almacén - Inventario<?= ucfirst($currentPage ?? 'Dashboard') ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CSS únicamente — el JS se carga al final del body en footer.php -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout_inventario.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR (barra superior fija)
══════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container-fluid">

        <!-- Botón toggle sidebar -->
        <button class="sidebar-toggle-btn" type="button" id="sidebarToggle" aria-label="Alternar menú">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Logo/Brand -->
        <a class="navbar-brand ms-2" href="<?= BASE_URL ?>/index.php?controller=dashboard&action=index">
            <i class="fas fa-warehouse text-primary me-1"></i>
            <span class="fw-bold">Sistema Almacén</span>
        </a>

        <!-- User Menu -->
        <div class="d-flex align-items-center ms-auto">
            <div class="user-profile">
                <span class="me-2 text-muted d-none d-sm-inline">
                    <i class="fas fa-user me-1"></i>
                    <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?>
                    <small class="badge bg-secondary ms-1"><?= ucfirst($_SESSION['usuario_rol'] ?? 'invitado') ?></small>
                </span>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-cogs me-2"></i>Configuración
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger"
                                href="<?= BASE_URL ?>/index.php?controller=auth&action=logout&_token=<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</nav>

<!-- ══════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-content">

        <ul class="nav flex-column">
            <?php foreach ($menuItems as $index => $item): ?>
                <?php if (isset($item['submenu'])): ?>
                    <?php
                        // Determinar si algún subítem está activo para abrir el collapse por defecto
                        $isSubmenuActive = false;
                        foreach ($item['submenu'] as $subitem) {
                            if (isset($currentPage) && $currentPage == strtolower($subitem['controller'])) {
                                $isSubmenuActive = true;
                                break;
                            }
                        }
                        $collapseId = 'submenu_' . $index;
                    ?>
                    <li class="nav-item">
                        <!-- ✅ FIX: usar data-bs-toggle="collapse" en lugar del dropdown de Bootstrap,
                             que en un sidebar vertical no funciona inline sino como popover flotante. -->
                        <a class="nav-link sidebar-submenu-toggle <?= $isSubmenuActive ? '' : 'collapsed' ?>"
                           href="#<?= $collapseId ?>"
                           data-bs-toggle="collapse"
                           data-bs-target="#<?= $collapseId ?>"
                           aria-expanded="<?= $isSubmenuActive ? 'true' : 'false' ?>"
                           aria-controls="<?= $collapseId ?>"
                           data-tooltip="<?= htmlspecialchars($item['title']) ?>">
                            <i class="<?= $item['icon'] ?>"></i>
                            <span><?= $item['title'] ?></span>
                            <i class="fas fa-chevron-down sidebar-arrow ms-auto"></i>
                        </a>
                        <div class="collapse <?= $isSubmenuActive ? 'show' : '' ?>" id="<?= $collapseId ?>">
                            <ul class="nav flex-column sidebar-submenu ps-3">
                                <?php foreach ($item['submenu'] as $subitem): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= (isset($currentPage) && $currentPage == strtolower($subitem['controller'])) ? 'active' : '' ?>"
                                           href="<?= BASE_URL ?>/index.php?controller=<?= $subitem['controller'] ?>&action=<?= $subitem['action'] ?>">
                                            <i class="<?= $subitem['icon'] ?> me-2"></i>
                                            <span><?= $subitem['title'] ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($currentPage) && $currentPage == strtolower($item['controller'])) ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/index.php?controller=<?= $item['controller'] ?>&action=<?= $item['action'] ?>"
                           data-tooltip="<?= htmlspecialchars($item['title']) ?>">
                            <i class="<?= $item['icon'] ?>"></i>
                            <span><?= $item['title'] ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

            <li class="nav-item mt-auto"><hr></li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-tooltip="Ayuda">
                    <i class="fas fa-question-circle"></i>
                    <span>Ayuda</span>
                </a>
            </li>
        </ul>

    </div>
</div>

<!-- ══════════════════════════════════════════
     CONTENT WRAPPER
     Este bloque es cerrado por footer.php:
       </main>  →  cierra el <main> de abajo
       </div>   →  cierra el .content-wrapper
     Cada vista incluye footer.php al final.
══════════════════════════════════════════ -->
<div class="content-wrapper" id="contentWrapper">
    <main class="page-content">

        <!-- Mensaje flash -->
        <?php if ($flashMessage): ?>
            <div class="alert alert-<?= $flashMessage['type'] ?> alert-dismissible fade show" role="alert">
                <i class="fas
                    <?= $flashMessage['type'] === 'success'  ? 'fa-check-circle'         :
                       ($flashMessage['type'] === 'danger'   ? 'fa-exclamation-circle'   :
                       ($flashMessage['type'] === 'warning'  ? 'fa-exclamation-triangle' : 'fa-info-circle')) ?>
                    me-2"></i>
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>