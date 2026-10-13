<?php
// ── config.php PRIMERO: define SESSION_NAME, BASE_URL, carga autoload y database ──
require_once __DIR__ . '/config/config.php';

// Iniciar sesión con el nombre y parámetros definidos en config.php
SessionMiddleware::startSession();

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/index.php?controller=dashboard&action=index');
    exit();
}

$db = getDB();

// Procesar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Usuario y contraseña son requeridos';
    } else {
        try {
            // Consulta directa para obtener el usuario
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND estado = 'activo'");
            $stmt->execute([$username]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Verificar contraseña usando password_verify()
                if (password_verify($password, $usuario['password'])) {
                    SessionMiddleware::setUserData([
                        'id' => $usuario['id_usuario'],
                        'nombre' => $usuario['nombre_completo'],
                        'username' => $usuario['username'],
                        'email' => $usuario['email'],
                        'rol' => $usuario['rol'],
                        'permisos' => Permissions::getRolePermissions($usuario['rol'])
                    ]);

                    $_SESSION['usuario_id'] = $usuario['id_usuario'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
                    $_SESSION['usuario_username'] = $usuario['username'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    $_SESSION['usuario_rol'] = $usuario['rol'];
                    $_SESSION['fecha_login'] = date('Y-m-d H:i:s');
                    
                    // Actualizar último acceso
                    $stmt_update = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?");
                    $stmt_update->execute([$usuario['id_usuario']]);
                    
                    // Registrar en bitácora
                    $stmt_bitacora = $db->prepare("
                        INSERT INTO bitacora_sistema (id_usuario, accion, modulo, detalles) 
                        VALUES (?, 'LOGIN', 'AUTENTICACION', ?)
                    ");
                    $detalles = json_encode([
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'user_agent' => $_SERVER['HTTP_USER_AGENT']
                    ]);
                    $stmt_bitacora->execute([$usuario['id_usuario'], $detalles]);
                    
                    // Redirigir según rol
                    $redirect_url = BASE_URL . '/index.php?controller=dashboard&action=index';
                    switch ($usuario['rol']) {
                        case 'almacenero':
                            $redirect_url = BASE_URL . '/index.php?controller=inventario&action=index';
                            break;
                        case 'vendedor':
                            $redirect_url = BASE_URL . '/index.php?controller=ventas&action=index';
                            break;
                    }
                    
                    header('Location: ' . $redirect_url);
                    exit();
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
            
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error en el sistema. Por favor, intente más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Almacén</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">   
</head>
<body>
    <!-- Formas flotantes decorativas -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Container principal -->
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-container">
                    <i class="fas fa-warehouse"></i>
                </div>
                <h1>Sistema de Almacén</h1>
                <p>Control e Inventario Inteligente</p>
                <span class="version-badge">v2.0.0</span>
            </div>

            <!-- Alertas -->
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Usuario
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username" 
                            placeholder="Ingrese su usuario" 
                            required 
                            autofocus
                        >
                        <span class="input-icon">
                            <i class="fas fa-user-circle"></i>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Ingrese su contraseña" 
                            required
                        >
                        <span class="input-icon" id="togglePassword">
                            <!--<i class="fas fa-eye"></i>!-->
                        </span>
                    </div>
                </div>

                <div class="custom-checkbox">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Recordar mi sesión</label>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </button>
            </form>

            <!-- Footer -->
            <div class="login-footer">
                <div class="footer-links">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <i class="fas fa-question-circle"></i> Ayuda
                    </a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#recoveryModal">
                        <i class="fas fa-key"></i> Recuperar Contraseña
                    </a>
                </div>

                <!-- Credenciales de demo -->
                <div class="demo-credentials">
                    <small><i class="fas fa-info-circle me-1"></i> Usuario de prueba:</small><br>
                    <span class="credential-item">admin</span>
                    <span class="credential-item">password</span>
                </div>

                <div class="copyright">
                    © <?= date('Y') ?> Sistema de Almacén. Todos los derechos reservados.
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-question-circle text-primary me-2"></i>
                        Centro de Ayuda
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3">¿Problemas para iniciar sesión?</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Verifica que tus credenciales sean correctas
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Asegúrate de que tu cuenta esté activa
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Contacta al administrador si persiste el problema
                        </li>
                    </ul>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-envelope me-2"></i>
                        Soporte: garrawil@gmail.com
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Recuperación -->
    <div class="modal fade" id="recoveryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-key text-primary me-2"></i>
                        Recuperar Contraseña
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Para recuperar tu contraseña, contacta al administrador del sistema.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        Por seguridad, la recuperación de contraseña debe ser gestionada por el administrador.
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-envelope me-2"></i>
                        Email de soporte: garrawil@gmail.com
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                this.querySelector('i').className = type === 'password' ? 
                    'fas fa-eye' : 'fas fa-eye-slash';
            });

            // Form submit animation
            const form = document.getElementById('loginForm');
            const btnLogin = document.getElementById('btnLogin');
            
            form.addEventListener('submit', function() {
                btnLogin.classList.add('loading');
                btnLogin.innerHTML = '';
            });

            // Auto-fill demo credentials (solo para desarrollo)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('demo') === '1') {
                document.getElementById('username').value = 'admin';
                document.getElementById('password').value = 'password';
            }

            // Input focus animations
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.2s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>