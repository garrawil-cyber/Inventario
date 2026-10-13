<?php
/**
 * ============================================================
 *  CONTROLADOR: AlmacenController.php
 *  Sistema : RopaVirtual – Módulo de Almacenes
 *  Arquitectura: MVC  |  Capa: Controller
 * ============================================================
 */

require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class AlmacenController
{
    private Almacen $modelo;

    private const TIPOS = ['principal', 'secundario', 'transitorio'];
    private const ESTADOS = ['activo', 'inactivo'];

    public function __construct()
    {
        SessionMiddleware::startSession();
        SessionMiddleware::checkAuth();
        $this->modelo = new Almacen();
    }

    // ========================================================================
    //  INDEX - Listado de almacenes
    // ========================================================================
    public function index(): void
    {
        $rolUsuario = SessionMiddleware::getUserData('rol');
        if (!in_array($rolUsuario, ['admin', 'supervisor'])) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=dashboard&action=index',
                'Acceso denegado',
                'No tienes permisos para ver el módulo de almacenes.',
                'error'
            );
        }

        $filters = [
            'filtro' => trim($_GET['filtro'] ?? ''),
            'tipo'   => $_GET['tipo'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'limit'  => (int)($_GET['limit'] ?? 20),
            'offset' => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        $resultado = $this->modelo->listar($filters);
        $almacenes = $resultado['data'] ?? [];
        $totalRegistros = $resultado['total'] ?? 0;

        $paginaActual = (int)($_GET['pagina'] ?? 1);
        $limite = $filters['limit'];
        $totalPaginas = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;

        $tipos = self::TIPOS;
        $estados = self::ESTADOS;
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/almacenes/index.php';
    }

    // ========================================================================
    //  VER - Detalle de almacén
    // ========================================================================
    public function ver(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'Error',
                'ID de almacén no válido.',
                'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'No encontrado',
                $resultado['mensaje'] ?? 'Almacén no encontrado.',
                'warning'
            );
        }

        $almacen = $resultado['almacen'];
        $ubicaciones = $resultado['ubicaciones'];
        $estadisticas = $this->modelo->getEstadisticas($id);
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/almacenes/ver.php';
    }

    // ========================================================================
    //  CREAR - Formulario nuevo almacén
    // ========================================================================
    public function crear(): void
    {
        $tipos = self::TIPOS;
        $csrfToken = SessionMiddleware::generateCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);

        require_once __DIR__ . '/../views/inventario/almacenes/crear.php';
    }

    // ========================================================================
    //  GUARDAR - Procesar POST nuevo almacén
    // ========================================================================
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=crear',
                'Token inválido',
                'La sesión expiró. Intenta de nuevo.',
                'warning'
            );
        }

        $data = [
            'codigo'           => $_POST['codigo'] ?? null,
            'nombre'           => trim($_POST['nombre'] ?? ''),
            'direccion'        => $_POST['direccion'] ?? null,
            'ciudad'           => $_POST['ciudad'] ?? null,
            'capacidad_maxima' => (int)($_POST['capacidad_maxima'] ?? 0) ?: null,
            'responsable'      => trim($_POST['responsable'] ?? ''),
            'telefono'         => $_POST['telefono'] ?? null,
            'tipo'             => $_POST['tipo'] ?? 'secundario',
            'estado'           => $_POST['estado'] ?? 'activo',
        ];

        if (empty($data['nombre'])) {
            SessionMiddleware::setSweetAlert('Error', 'El nombre del almacén es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=almacen&action=crear');
            exit();
        }

        if (empty($data['responsable'])) {
            SessionMiddleware::setSweetAlert('Error', 'El responsable del almacén es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=almacen&action=crear');
            exit();
        }

        $resultado = $this->modelo->crear($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=ver&id=' . $resultado['id_almacen'],
                '¡Almacén creado!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al crear', $resultado['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=almacen&action=crear');
            exit();
        }
    }

    // ========================================================================
    //  EDITAR - Formulario edición almacén
    // ========================================================================
    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'Error',
                'ID no válido.',
                'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'No encontrado',
                $resultado['mensaje'] ?? 'Almacén no encontrado.',
                'warning'
            );
        }

        $almacen = $resultado['almacen'];
        $tipos = self::TIPOS;
        $estados = self::ESTADOS;
        $csrfToken = SessionMiddleware::generateCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/almacenes/editar.php';
    }

    // ========================================================================
    //  ACTUALIZAR - Procesar POST edición almacén
    // ========================================================================
    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'Token inválido',
                'La sesión expiró. Intenta de nuevo.',
                'warning'
            );
        }

        $id = (int)($_POST['id_almacen'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'Error',
                'ID no válido.',
                'error'
            );
        }

        $data = [
            'id_almacen'       => $id,
            'codigo'           => $_POST['codigo'] ?? '',
            'nombre'           => trim($_POST['nombre'] ?? ''),
            'direccion'        => $_POST['direccion'] ?? null,
            'ciudad'           => $_POST['ciudad'] ?? null,
            'capacidad_maxima' => (int)($_POST['capacidad_maxima'] ?? 0) ?: null,
            'responsable'      => trim($_POST['responsable'] ?? ''),
            'telefono'         => $_POST['telefono'] ?? null,
            'tipo'             => $_POST['tipo'] ?? 'secundario',
            'estado'           => $_POST['estado'] ?? 'activo',
        ];

        if (empty($data['nombre'])) {
            SessionMiddleware::setSweetAlert('Error', 'El nombre del almacén es obligatorio.', 'warning');
            header('Location: index.php?controller=almacen&action=editar&id=' . $id);
            exit();
        }

        if (empty($data['responsable'])) {
            SessionMiddleware::setSweetAlert('Error', 'El responsable del almacén es obligatorio.', 'warning');
            header('Location: index.php?controller=almacen&action=editar&id=' . $id);
            exit();
        }

        $resultado = $this->modelo->actualizar($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=ver&id=' . $id,
                '¡Actualizado!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al actualizar', $resultado['mensaje'], 'error');
            header('Location: index.php?controller=almacen&action=editar&id=' . $id);
            exit();
        }
    }

    // ========================================================================
    //  ELIMINAR - Soft delete vía AJAX
    // ========================================================================
    public function eliminar(): void
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

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID no válido.']);
            return;
        }

        $resultado = $this->modelo->eliminar($id);
        echo json_encode($resultado);
    }

    // ========================================================================
    //  GET FOR SELECT - AJAX
    // ========================================================================
    public function getForSelect(): void
    {
        header('Content-Type: application/json');
        $tipo = $_GET['tipo'] ?? null;
        $rows = $this->modelo->getForSelect($tipo ?: null);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    // ========================================================================
    //  DASHBOARD STATS - AJAX
    // ========================================================================
    public function dashboardStats(): void
    {
        header('Content-Type: application/json');
        $stats = $this->modelo->getDashboardStats();
        echo json_encode($stats);
    }

    // ========================================================================
    //  UBICACIONES - Listar ubicaciones de un almacén
    // ========================================================================
    public function ubicaciones(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'Error',
                'ID de almacén no válido.',
                'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=almacen&action=index',
                'No encontrado',
                $resultado['mensaje'] ?? 'Almacén no encontrado.',
                'warning'
            );
        }

        $almacen = $resultado['almacen'];
        $ubicaciones = $resultado['ubicaciones'];
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/almacenes/ubicaciones.php';
    }
}