<?php
/**
 * Controlador: MarcaController.php
 * CRUD para marcas
 */

require_once __DIR__ . '/../models/Marca.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class MarcaController
{
    private Marca $modelo;

    public function __construct()
    {
        SessionMiddleware::startSession();
        SessionMiddleware::checkAuth();

        $rolUsuario = SessionMiddleware::getUserData('rol');
        if (!in_array($rolUsuario, ['admin', 'supervisor'])) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=dashboard&action=index',
                'Acceso denegado',
                'No tienes permisos para gestionar marcas.',
                'error'
            );
        }
        $this->modelo = new Marca();
    }

    /**
     * Listado de marcas
     */
    public function index(): void
    {
        $filters = [
            'filtro' => trim($_GET['filtro'] ?? ''),
            'estado' => $_GET['estado'] ?? '',
            'limit' => (int)($_GET['limit'] ?? 20),
            'offset' => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        $resultado = $this->modelo->listar($filters);
        $marcas = $resultado['data'] ?? [];
        $totalRegistros = $resultado['total'] ?? 0;

        $paginaActual = (int)($_GET['pagina'] ?? 1);
        $limite = $filters['limit'];
        $totalPaginas = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;

        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken();

        require_once __DIR__ . '/../views/inventario/marcas/index.php';
    }

    /**
     * Formulario crear marca
     */
    public function crear(): void
    {
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken();

        require_once __DIR__ . '/../views/inventario/marcas/crear.php';
    }

    /**
     * Guardar nueva marca
     */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=marca&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        // Validar CSRF
        $this->validateCsrfForm();

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            SessionMiddleware::setSweetAlert('Error', 'El nombre de la marca es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=marca&action=crear');
            exit();
        }

        $data = [
            'nombre' => $nombre,
            'descripcion' => $_POST['descripcion'] ?? null,
            'pais_origen' => $_POST['pais_origen'] ?? null,
            'sitio_web' => $_POST['sitio_web'] ?? null,
        ];

        $resultado = $this->modelo->crear($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=marca&action=index',
                'Marca creada',
                $resultado['mensaje'],
                'success'
            );
        }

        SessionMiddleware::setSweetAlert('Error', $resultado['mensaje'], 'error');
        $_SESSION['form_data'] = $_POST;
        header('Location: index.php?controller=marca&action=crear');
        exit();
    }

    /**
     * Formulario editar marca
     */
    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=marca&action=index',
                'Error',
                'ID de marca no válido.',
                'error'
            );
        }

        $marca = $this->modelo->getById($id);
        if (!$marca) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=marca&action=index',
                'No encontrada',
                'La marca no existe.',
                'warning'
            );
        }

        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken();

        require_once __DIR__ . '/../views/inventario/marcas/editar.php';
    }

    /**
     * Actualizar marca
     */
    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=marca&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        // Validar CSRF
        $this->validateCsrfForm();

        $id = (int)($_POST['id_marca'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=marca&action=index',
                'Error',
                'ID de marca no válido.',
                'error'
            );
        }

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            SessionMiddleware::setSweetAlert('Error', 'El nombre de la marca es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=marca&action=editar&id=' . $id);
            exit();
        }

        $data = [
            'id_marca' => $id,
            'nombre' => $nombre,
            'descripcion' => $_POST['descripcion'] ?? null,
            'pais_origen' => $_POST['pais_origen'] ?? null,
            'sitio_web' => $_POST['sitio_web'] ?? null,
            'estado' => $_POST['estado'] ?? 'activo',
        ];

        $resultado = $this->modelo->actualizar($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=marca&action=index',
                'Marca actualizada',
                $resultado['mensaje'],
                'success'
            );
        }

        SessionMiddleware::setSweetAlert('Error', $resultado['mensaje'], 'error');
        $_SESSION['form_data'] = $_POST;
        header('Location: index.php?controller=marca&action=editar&id=' . $id);
        exit();
    }

    /**
     * Eliminar marca (AJAX)
     */
    public function eliminar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

        // Validar CSRF para AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID no válido.']);
            return;
        }

        $resultado = $this->modelo->eliminar($id);
        echo json_encode($resultado);
    }

    /**
     * Activar marca (AJAX)
     */
    public function activar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

        // Validar CSRF para AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID no válido.']);
            return;
        }

        $resultado = $this->modelo->activar($id);
        echo json_encode($resultado);
    }

    /**
     * checkNombre — Verifica si un nombre de marca ya existe (GET · AJAX)
     *
     * Endpoint : index.php?controller=marca&action=checkNombre&nombre=Nike
     * Parámetros GET:
     *   nombre  string  Nombre a verificar (obligatorio)
     *   excluir int     id_marca a ignorar en la búsqueda (útil al editar)
     *
     * Respuesta JSON:
     *   { disponible: true,  mensaje: "Nombre disponible" }
     *   { disponible: false, mensaje: "Ya existe una marca con ese nombre" }
     *   { disponible: false, mensaje: "El nombre es obligatorio" }
     */
    public function checkNombre(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        // Solo peticiones AJAX — rechazar acceso directo desde URL
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
        ) {
            http_response_code(400);
            echo json_encode(['disponible' => false, 'mensaje' => 'Petición no permitida.']);
            return;
        }

        $nombre  = trim($_GET['nombre'] ?? '');
        $excluir = (int)($_GET['excluir'] ?? 0); // 0 = ninguno (flujo crear)

        if ($nombre === '') {
            echo json_encode(['disponible' => false, 'mensaje' => 'El nombre es obligatorio.']);
            return;
        }

        if (mb_strlen($nombre) > 100) {
            echo json_encode(['disponible' => false, 'mensaje' => 'El nombre no puede superar 100 caracteres.']);
            return;
        }

        $existe = $this->modelo->existeNombre($nombre, $excluir);

        if ($existe) {
            echo json_encode([
                'disponible' => false,
                'mensaje'    => 'Ya existe una marca con ese nombre.',
            ]);
        } else {
            echo json_encode([
                'disponible' => true,
                'mensaje'    => 'Nombre disponible.',
            ]);
        }
    }

    /**
     * Validar CSRF para formularios normales
     */
    private function validateCsrfForm(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (empty($token)) {
            SessionMiddleware::setSweetAlert(
                'Error de seguridad',
                'Token CSRF no proporcionado. Recargue la página.',
                'error'
            );
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=marca&action=index'));
            exit();
        }

        if (!SessionMiddleware::validateUniqueCSRFToken($token)) {
            SessionMiddleware::setSweetAlert(
                'Error de seguridad',
                'Token CSRF inválido o expirado. Recargue la página.',
                'error'
            );
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=marca&action=index'));
            exit();
        }
    }

    /**
     * Validar CSRF para peticiones AJAX
     */
    private function validateCsrf(): void
    {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? $headers['X-CSRF-TOKEN'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (empty($token)) {
            echo json_encode(['success' => false, 'mensaje' => 'Token CSRF no proporcionado. Recargue la página.']);
            exit();
        }

        if (!SessionMiddleware::validateUniqueCSRFToken($token)) {
            echo json_encode(['success' => false, 'mensaje' => 'Token CSRF inválido o expirado. Recargue la página.']);
            exit();
        }
    }
}