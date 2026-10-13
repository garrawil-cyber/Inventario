<?php
/**
 * ============================================================
 *  CONTROLADOR: UsuarioController.php
 *  Sistema : RopaVirtual – Módulo de Usuarios
 *  Arquitectura: MVC  |  Capa: Controller
 * ============================================================
 *
 *  Acciones públicas (deben estar en permissions.php para 'admin'):
 *    index, ver, crear, guardar, editar, actualizar,
 *    eliminar, cambiarPassword, getForSelect
 *
 *  Mensajes al usuario: exclusivamente mediante SweetAlert
 *  (SessionMiddleware::setSweetAlert / redirectWithSweetAlert)
 *
 *  SPs invocados (a través del modelo):
 *    sp_ListarUsuarios, sp_GetUsuarioById, sp_InsertUsuario,
 *    sp_UpdateUsuario, sp_DeleteUsuario, sp_ChangePassword,
 *    sp_GetUsuariosForSelect
 * ============================================================
 */

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class UsuarioController
{
    private Usuario $modelo;

    // Roles válidos (deben coincidir con los permitidos en sp_InsertUsuario)
    private const ROLES = ['admin', 'supervisor', 'almacenero', 'vendedor'];

    public function __construct()
    {
        SessionMiddleware::startSession();
        SessionMiddleware::checkAuth();
        $this->modelo = new Usuario();
    }

    /* ====================================================================
     *  INDEX — Listado paginado con filtros
     * ==================================================================== */
    public function index(): void
    {
        $filters = [
            'filtro' => trim($_GET['filtro'] ?? ''),
            'rol'    => $_GET['rol']    ?? '',
            'estado' => $_GET['estado'] ?? '',
            'limit'  => (int)($_GET['limit']  ?? 20),
            'offset' => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        $resultado     = $this->modelo->listar($filters);
        $usuarios      = $resultado['data']  ?? [];
        $totalRegistros= $resultado['total'] ?? 0;

        $paginaActual  = (int)($_GET['pagina'] ?? 1);
        $limite        = $filters['limit'];
        $totalPaginas  = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;
        $roles         = self::ROLES;
        $sweetAlert    = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/usuarios/index.php';
    }

    /* ====================================================================
     *  VER — Detalle de un usuario
     * ==================================================================== */
    public function ver(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'Error', 'ID de usuario no válido.', 'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'No encontrado', $resultado['mensaje'] ?? 'Usuario no encontrado.', 'warning'
            );
        }

        $usuario   = $resultado['usuario'];
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/usuarios/ver.php';
    }

    /* ====================================================================
     *  CREAR — Formulario de nuevo usuario
     * ==================================================================== */
public function crear(): void
{
    $roles      = self::ROLES;
    $sweetAlert = SessionMiddleware::getSweetAlert();

    // ✅ Se genera AQUÍ pero la vista lo leerá de sesión directamente
    // para evitar colisión con $csrfToken del layout
    SessionMiddleware::generateCSRFToken(); // pre-genera el token
    
    require_once __DIR__ . '/../views/inventario/usuarios/crear.php';
}

    /* ====================================================================
     *  GUARDAR — Procesar POST de nuevo usuario (sp_InsertUsuario)
     * ==================================================================== */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'Error', 'Método no permitido.', 'error'
            );
        }

        // ── CSRF ──────────────────────────────────────────────────────────
        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=crear',
                'Token inválido', 'La sesión expiró o el formulario fue manipulado. Intenta de nuevo.', 'warning'
            );
        }

        // ── Validación básica del lado del servidor ───────────────────────
        $username       = trim($_POST['username']        ?? '');
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $email          = trim($_POST['email']           ?? '');
        $password       = $_POST['password']             ?? '';
        $passwordConf   = $_POST['password_confirm']     ?? '';
        $rol            = $_POST['rol']                  ?? 'vendedor';
        $estado         = $_POST['estado']               ?? 'activo';

        $errores = $this->validarDatosUsuario(
            $username, $nombreCompleto, $email, $password, $passwordConf, $rol, $estado
        );

        if (!empty($errores)) {
            SessionMiddleware::setSweetAlert(
                'Datos inválidos',
                implode('<br>', $errores),
                'warning'
            );
            // Volver al formulario manteniendo los datos en sesión
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=usuario&action=crear');
            exit();
        }

        // ── Hash de contraseña ────────────────────────────────────────────
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $resultado = $this->modelo->crear([
            'username'        => $username,
            'password'        => $passwordHash,
            'nombre_completo' => $nombreCompleto,
            'email'           => $email,
            'rol'             => $rol,
            'estado'          => $estado,
        ]);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                '¡Usuario creado!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al crear', $resultado['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=usuario&action=crear');
            exit();
        }
    }

    /* ====================================================================
     *  EDITAR — Formulario de edición (sp_GetUsuarioById)
     * ==================================================================== */
    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'Error', 'ID no válido.', 'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'No encontrado', $resultado['mensaje'] ?? 'Usuario no encontrado.', 'warning'
            );
        }

        $usuario   = $resultado['usuario'];
        $roles     = self::ROLES;
        $csrfToken = SessionMiddleware::generateCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/usuarios/editar.php';
    }

    /* ====================================================================
     *  ACTUALIZAR — Procesar POST de edición (sp_UpdateUsuario)
     * ==================================================================== */
    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'Error', 'Método no permitido.', 'error'
            );
        }

        // ── CSRF ──────────────────────────────────────────────────────────
        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'Token inválido', 'La sesión expiró. Intenta de nuevo.', 'warning'
            );
        }

        $id             = (int)($_POST['id_usuario'] ?? 0);
        $username       = trim($_POST['username']        ?? '');
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $email          = trim($_POST['email']           ?? '');
        $password       = $_POST['password']             ?? '';   // vacío = no cambia
        $passwordConf   = $_POST['password_confirm']     ?? '';
        $rol            = $_POST['rol']                  ?? 'vendedor';
        $estado         = $_POST['estado']               ?? 'activo';

        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=usuario&action=index',
                'Error', 'ID de usuario inválido.', 'error'
            );
        }

        // Validar solo si se envía nueva contraseña
        $errores = $this->validarDatosUsuario(
            $username, $nombreCompleto, $email,
            $password ?: null,   // null = no validar contraseña
            $passwordConf ?: null,
            $rol, $estado,
            false // $esNuevo = false
        );

        if (!empty($errores)) {
            SessionMiddleware::setSweetAlert('Datos inválidos', implode('<br>', $errores), 'warning');
            $_SESSION['form_data'] = $_POST;
            header("Location: index.php?controller=usuario&action=editar&id=$id");
            exit();
        }

        // Hash solo si se envió contraseña nueva
        $passwordHash = '';
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        }

        $resultado = $this->modelo->actualizar([
            'id_usuario'      => $id,
            'username'        => $username,
            'password'        => $passwordHash,   // SP ignora si está vacío
            'nombre_completo' => $nombreCompleto,
            'email'           => $email,
            'rol'             => $rol,
            'estado'          => $estado,
        ]);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                "index.php?controller=usuario&action=ver&id=$id",
                '¡Actualizado!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al actualizar', $resultado['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header("Location: index.php?controller=usuario&action=editar&id=$id");
            exit();
        }
    }

    /* ====================================================================
     *  ELIMINAR — Soft delete vía AJAX (sp_DeleteUsuario)
     *  Responde JSON para ser consumido desde JS con SweetAlert de confirmación
     * ==================================================================== */
    public function eliminar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $token = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

        if (!SessionMiddleware::validateCSRFToken($token)) {
            echo json_encode(['success' => false, 'mensaje' => 'Token CSRF inválido.']);
            return;
        }

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID no válido.']);
            return;
        }

        // Prevenir auto-eliminación
        $idActual = (int)SessionMiddleware::getUserData('id');
        if ($id === $idActual) {
            echo json_encode(['success' => false, 'mensaje' => 'No puedes desactivar tu propia cuenta.']);
            return;
        }

        $resultado = $this->modelo->eliminar($id);
        echo json_encode($resultado);
    }

    /* ====================================================================
     *  CAMBIAR CONTRASEÑA — AJAX (sp_ChangePassword)
     *  Flujo:
     *    1. JS pide confirmación con SweetAlert input (contraseña actual)
     *    2. POST aquí con id, password_actual, password_nueva
     *    3. PHP verifica bcrypt de la actual contra el hash en BD
     *    4. Si OK → SP actualiza con el nuevo hash
     *    5. Responde JSON → JS muestra SweetAlert de resultado
     * ==================================================================== */
    public function cambiarPassword(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $token = $input['csrf_token'] ?? '';

        if (!SessionMiddleware::validateCSRFToken($token)) {
            echo json_encode(['success' => false, 'mensaje' => 'Token CSRF inválido.']);
            return;
        }

        $id              = (int)($input['id_usuario']      ?? 0);
        $passwordActual  = $input['password_actual']       ?? '';
        $passwordNueva   = $input['password_nueva']        ?? '';
        $passwordConfirm = $input['password_nueva_confirm']?? '';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de usuario no válido.']);
            return;
        }

        if (empty($passwordActual)) {
            echo json_encode(['success' => false, 'mensaje' => 'Debes ingresar tu contraseña actual.']);
            return;
        }

        if (strlen($passwordNueva) < 6) {
            echo json_encode(['success' => false, 'mensaje' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            return;
        }

        if ($passwordNueva !== $passwordConfirm) {
            echo json_encode(['success' => false, 'mensaje' => 'Las contraseñas nuevas no coinciden.']);
            return;
        }

        // ── Verificar contraseña actual contra el hash en BD ──────────────
        $hashActual = $this->modelo->getPasswordHash($id);
        if ($hashActual === null) {
            echo json_encode(['success' => false, 'mensaje' => 'Usuario no encontrado o inactivo.']);
            return;
        }

        if (!password_verify($passwordActual, $hashActual)) {
            echo json_encode(['success' => false, 'mensaje' => 'La contraseña actual es incorrecta.']);
            return;
        }

        // ── Hash de la nueva contraseña ───────────────────────────────────
        $nuevaHash = password_hash($passwordNueva, PASSWORD_BCRYPT);

        $resultado = $this->modelo->cambiarPassword($id, $passwordActual, $nuevaHash);
        echo json_encode($resultado);
    }

    /* ====================================================================
     *  GET FOR SELECT — AJAX (sp_GetUsuariosForSelect)
     * ==================================================================== */
    public function getForSelect(): void
    {
        header('Content-Type: application/json');
        $rol  = $_GET['rol'] ?? null;
        $rows = $this->modelo->getForSelect($rol ?: null);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    // =========================================================================
    //  HELPER PRIVADO — Validación de datos de usuario
    //  $esNuevo = true  → contraseña obligatoria
    //  $esNuevo = false → contraseña opcional (si viene, se valida)
    // =========================================================================
    private function validarDatosUsuario(
        string  $username,
        string  $nombreCompleto,
        string  $email,
        ?string $password,
        ?string $passwordConf,
        string  $rol,
        string  $estado,
        bool    $esNuevo = true
    ): array {
        $errores = [];

        if (empty($username)) {
            $errores[] = 'El nombre de usuario es obligatorio.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errores[] = 'El usuario debe tener entre 3 y 50 caracteres alfanuméricos.';
        }

        if (empty($nombreCompleto)) {
            $errores[] = 'El nombre completo es obligatorio.';
        } elseif (strlen($nombreCompleto) < 3) {
            $errores[] = 'El nombre completo debe tener al menos 3 caracteres.';
        }

        if (empty($email)) {
            $errores[] = 'El email es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no tiene un formato válido.';
        }

        if ($esNuevo) {
            // Nuevo usuario: contraseña obligatoria
            if (empty($password)) {
                $errores[] = 'La contraseña es obligatoria.';
            } elseif (strlen($password) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif ($password !== $passwordConf) {
                $errores[] = 'Las contraseñas no coinciden.';
            }
        } elseif (!empty($password)) {
            // Edición: solo valida si se proporcionó
            if (strlen($password) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif ($password !== $passwordConf) {
                $errores[] = 'Las contraseñas no coinciden.';
            }
        }

        if (!in_array($rol, self::ROLES, true)) {
            $errores[] = 'El rol seleccionado no es válido.';
        }

        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $errores[] = 'El estado seleccionado no es válido.';
        }

        return $errores;
    }
}