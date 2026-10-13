<?php
/**
 * ============================================================
 *  CONTROLADOR: UbicacionController.php
 *  Sistema : RopaVirtual – Módulo de Ubicaciones
 *  Arquitectura: MVC  |  Capa: Controller
 * ============================================================
 */

require_once __DIR__ . '/../models/Ubicacion.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class UbicacionController
{
    private Ubicacion $modelo;
    private Almacen $almacenModelo;
    private Producto $productoModelo;

    private const ESTADOS = ['disponible', 'ocupado', 'mantenimiento'];

    public function __construct()
    {
        SessionMiddleware::startSession();
        SessionMiddleware::checkAuth();
        $this->modelo = new Ubicacion();
        $this->almacenModelo = new Almacen();
        $this->productoModelo = new Producto();
    }

    // ========================================================================
    //  INDEX - Listado de ubicaciones
    // ========================================================================
    public function index(): void
    {
        $rolUsuario = SessionMiddleware::getUserData('rol');
        if (!in_array($rolUsuario, ['admin', 'supervisor', 'almacenero'])) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=dashboard&action=index',
                'Acceso denegado',
                'No tienes permisos para ver el módulo de ubicaciones.',
                'error'
            );
        }

        $filters = [
            'filtro'      => trim($_GET['filtro'] ?? ''),
            'id_almacen'  => (int)($_GET['id_almacen'] ?? 0) ?: null,
            'estado'      => $_GET['estado'] ?? '',
            'limit'       => (int)($_GET['limit'] ?? 20),
            'offset'      => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        $resultado = $this->modelo->listar($filters);
        $ubicaciones = $resultado['data'] ?? [];
        $totalRegistros = $resultado['total'] ?? 0;

        $paginaActual = (int)($_GET['pagina'] ?? 1);
        $limite = $filters['limit'];
        $totalPaginas = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;

        $almacenes = $this->almacenModelo->getForSelect();
        $estados = self::ESTADOS;
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/ubicaciones/index.php';
    }

    // ========================================================================
    //  VER - Detalle de ubicación
    // ========================================================================
    public function ver(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'Error',
                'ID de ubicación no válido.',
                'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'No encontrado',
                $resultado['mensaje'] ?? 'Ubicación no encontrada.',
                'warning'
            );
        }

        $ubicacion = $resultado['ubicacion'];
        $productos = $resultado['productos'];
        $movimientos = $resultado['movimientos'];
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/ubicaciones/ver.php';
    }

    // ========================================================================
    //  CREAR - Formulario nueva ubicación
    // ========================================================================
    public function crear(): void
    {
        $almacenes = $this->almacenModelo->getForSelect();
        $estados = self::ESTADOS;
        $csrfToken = SessionMiddleware::generateCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);

        require_once __DIR__ . '/../views/inventario/ubicaciones/crear.php';
    }

    // ========================================================================
    //  GUARDAR - Procesar POST nueva ubicación
    // ========================================================================
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=crear',
                'Token inválido',
                'La sesión expiró. Intenta de nuevo.',
                'warning'
            );
        }

        $id_almacen = (int)($_POST['id_almacen'] ?? 0);
        if ($id_almacen <= 0) {
            SessionMiddleware::setSweetAlert('Error', 'Debe seleccionar un almacén.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=ubicacion&action=crear');
            exit();
        }

        $data = [
            'id_almacen'   => $id_almacen,
            'codigo'       => trim($_POST['codigo'] ?? ''),
            'pasillo'      => $_POST['pasillo'] ?? null,
            'nivel'        => $_POST['nivel'] ?? null,
            'posicion'     => $_POST['posicion'] ?? null,
            'capacidad'    => (int)($_POST['capacidad'] ?? 0) ?: null,
            'descripcion'  => $_POST['descripcion'] ?? null,
            'estado'       => $_POST['estado'] ?? 'disponible',
        ];

        if (empty($data['codigo'])) {
            SessionMiddleware::setSweetAlert('Error', 'El código de la ubicación es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=ubicacion&action=crear');
            exit();
        }

        $resultado = $this->modelo->crear($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=ver&id=' . $resultado['id_ubicacion'],
                '¡Ubicación creada!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al crear', $resultado['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=ubicacion&action=crear');
            exit();
        }
    }

    // ========================================================================
    //  EDITAR - Formulario edición ubicación
    // ========================================================================
    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'Error',
                'ID no válido.',
                'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'No encontrado',
                $resultado['mensaje'] ?? 'Ubicación no encontrada.',
                'warning'
            );
        }

        $ubicacion = $resultado['ubicacion'];
        $almacenes = $this->almacenModelo->getForSelect();
        $estados = self::ESTADOS;
        $csrfToken = SessionMiddleware::generateCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/ubicaciones/editar.php';
    }

    // ========================================================================
    //  ACTUALIZAR - Procesar POST edición ubicación
    // ========================================================================
    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'Token inválido',
                'La sesión expiró. Intenta de nuevo.',
                'warning'
            );
        }

        $id = (int)($_POST['id_ubicacion'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=index',
                'Error',
                'ID no válido.',
                'error'
            );
        }

        $data = [
            'id_ubicacion' => $id,
            'codigo'       => trim($_POST['codigo'] ?? ''),
            'pasillo'      => $_POST['pasillo'] ?? null,
            'nivel'        => $_POST['nivel'] ?? null,
            'posicion'     => $_POST['posicion'] ?? null,
            'capacidad'    => (int)($_POST['capacidad'] ?? 0) ?: null,
            'descripcion'  => $_POST['descripcion'] ?? null,
            'estado'       => $_POST['estado'] ?? 'disponible',
        ];

        if (empty($data['codigo'])) {
            SessionMiddleware::setSweetAlert('Error', 'El código de la ubicación es obligatorio.', 'warning');
            header('Location: index.php?controller=ubicacion&action=editar&id=' . $id);
            exit();
        }

        $resultado = $this->modelo->actualizar($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ubicacion&action=ver&id=' . $id,
                '¡Actualizado!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al actualizar', $resultado['mensaje'], 'error');
            header('Location: index.php?controller=ubicacion&action=editar&id=' . $id);
            exit();
        }
    }

    // ========================================================================
    //  ELIMINAR - Eliminar ubicación vía AJAX
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
        $idAlmacen = (int)($_GET['id_almacen'] ?? 0) ?: null;
        $estado = $_GET['estado'] ?? null;
        $rows = $this->modelo->getForSelect($idAlmacen, $estado);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    // ========================================================================
    //  DISPONIBLES - Ubicaciones disponibles para asignar productos
    // ========================================================================
    public function disponibles(): void
    {
        header('Content-Type: application/json');
        $idAlmacen = (int)($_GET['id_almacen'] ?? 0);
        $capacidadMinima = (int)($_GET['capacidad_minima'] ?? 0) ?: null;

        if ($idAlmacen <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de almacén no válido.']);
            return;
        }

        $rows = $this->modelo->getDisponibles($idAlmacen, $capacidadMinima);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    // ========================================================================
    //  ACTUALIZAR ESTADO - Actualizar estado según ocupación
    // ========================================================================
    public function actualizarEstado(): void
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

        $resultado = $this->modelo->actualizarEstado($id);
        echo json_encode($resultado);
    }

    // ========================================================================
    //  ASIGNAR PRODUCTO - Asignar producto a ubicación
    // ========================================================================
    public function asignarProducto(): void
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

        $idProducto = (int)($input['id_producto'] ?? 0);
        $idUbicacion = (int)($input['id_ubicacion'] ?? 0);
        $idUsuario = (int)SessionMiddleware::getUserData('id');

        if ($idProducto <= 0 || $idUbicacion <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        $resultado = $this->modelo->asignarProducto($idProducto, $idUbicacion, $idUsuario);
        echo json_encode($resultado);
    }

    // ========================================================================
    //  RESUMEN POR ALMACÉN - Estadísticas
    // ========================================================================
    public function resumenPorAlmacen(): void
    {
        header('Content-Type: application/json');
        $idAlmacen = (int)($_GET['id_almacen'] ?? 0) ?: null;
        $rows = $this->modelo->getResumenPorAlmacen($idAlmacen);
        echo json_encode(['success' => true, 'data' => $rows]);
    }
}