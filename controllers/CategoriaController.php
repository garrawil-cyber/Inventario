<?php
/**
 * CategoriaController.php — Controlador
 */
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class CategoriaController
{
    private Categoria $model;

    public function __construct()
    {
        $this->model = new Categoria();
    }

    /* ══════════════════════════════════════════════════════════
       INDEX — lista de categorías
    ══════════════════════════════════════════════════════════ */
    public function index(): void
    {
        SessionMiddleware::checkAuth();

        $activas  = !isset($_GET['mostrar']) || $_GET['mostrar'] !== 'inactivas';
        $filtro   = trim($_GET['q'] ?? '');
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $limit    = 25;
        $offset   = ($page - 1) * $limit;

        $result   = $this->model->getAll($activas, $filtro, false, $limit, $offset);
        $categorias   = $result['data'];
        $totalRegistros = $result['total'];
        $totalPaginas   = (int) ceil($totalRegistros / $limit);

        require_once __DIR__ . '/../views/inventario/categorias/index.php';
    }

    /* ══════════════════════════════════════════════════════════
       DASHBOARD STATS (AJAX)
    ══════════════════════════════════════════════════════════ */
    public function getDashboardStats(): never
    {
        header('Content-Type: application/json');

        try {
            $data = $this->model->getDashboardStats();
            echo json_encode(['success' => true, ...$data]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit();
    }

    /* ══════════════════════════════════════════════════════════
       GRÁFICOS DE CATEGORÍA (AJAX)
    ══════════════════════════════════════════════════════════ */
    public function getGraficos(): never
    {
        header('Content-Type: application/json');

        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
            exit();
        }

        try {
            $data = $this->model->getCategoriaGraficos($id);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit();
    }

    /* ══════════════════════════════════════════════════════════
       ESTADÍSTICAS DE PRODUCTOS (AJAX)
    ══════════════════════════════════════════════════════════ */
    public function getEstadisticasProductos(): never
    {
        header('Content-Type: application/json');

        try {
            $data = $this->model->getEstadisticasProductos();
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit();
    }

    /* ══════════════════════════════════════════════════════════
       CREAR — formulario
    ══════════════════════════════════════════════════════════ */
    public function crear(): void
    {
        SessionMiddleware::checkAuth();
        $formData   = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken  = SessionMiddleware::generateUniqueCSRFToken();
        require_once __DIR__ . '/../views/inventario/categorias/crear.php';
    }

    public function guardar(): never
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=categoria&action=index',
                'Error', 'Método no permitido.', 'error'
            );
        }

        // Validar CSRF
        $this->validateCsrfForm();

        $nombre = trim($_POST['nombre'] ?? '');

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            SessionMiddleware::setSweetAlert('Error', 'El nombre de la categoría es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=categoria&action=crear');
            exit();
        }

        $data = [
            'nombre'      => $nombre,
            'descripcion' => trim($_POST['descripcion'] ?? '') ?: null,
            'icono'       => trim($_POST['icono'] ?? '') ?: null,
        ];

        try {
            $result = $this->model->create($data);

            if ($result['success']) {
                SessionMiddleware::redirectWithSweetAlert(
                    'index.php?controller=categoria&action=index',
                    '¡Categoría creada!',
                    $result['mensaje'],
                    'success'
                );
            }

            SessionMiddleware::setSweetAlert('Error', $result['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=categoria&action=crear');
            exit();

        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', 'Error interno: ' . $e->getMessage(), 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=categoria&action=crear');
            exit();
        }
    }

    /* ══════════════════════════════════════════════════════════
       EDITAR — formulario
    ══════════════════════════════════════════════════════════ */
    public function editar(): void
    {
        SessionMiddleware::checkAuth();

        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $this->flashAndRedirect('ID de categoría no válido', 'error', 'index');
        }

        $categoria = $this->model->getById($id);
        if (!$categoria) {
            $this->flashAndRedirect('Categoría no encontrada', 'error', 'index');
        }

        $formData   = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken  = SessionMiddleware::generateUniqueCSRFToken();

        require_once __DIR__ . '/../views/inventario/categorias/editar.php';
    }

 /* ══════════════════════════════════════════════════════════
       ACTUALIZAR — procesa el POST de edición
       FIX: responde JSON para compatibilidad con fetch() en crear.js
══════════════════════════════════════════════════════════ */
    public function actualizar(): never
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=categoria&action=index',
                'Error', 'Método no permitido.', 'error'
            );
        }

        // Validar CSRF
        $this->validateCsrfForm();

        $id = (int) ($_POST['id_categoria'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=categoria&action=index',
                'Error', 'ID de categoría no válido.', 'error'
            );
        }

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            SessionMiddleware::setSweetAlert('Error', 'El nombre de la categoría es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=categoria&action=editar&id=' . $id);
            exit();
        }

        $data = [
            'nombre'      => $nombre,
            'descripcion' => trim($_POST['descripcion'] ?? '') ?: null,
            'icono'       => trim($_POST['icono'] ?? '') ?: null,
            'estado'      => in_array($_POST['estado'] ?? '', ['activo', 'inactivo'])
                                 ? $_POST['estado'] : 'activo',
        ];

        try {
            $result = $this->model->update($id, $data);

            if ($result['success']) {
                SessionMiddleware::redirectWithSweetAlert(
                    'index.php?controller=categoria&action=index',
                    '¡Categoría actualizada!',
                    $result['mensaje'],
                    'success'
                );
            }

            SessionMiddleware::setSweetAlert('Error', $result['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=categoria&action=editar&id=' . $id);
            exit();

        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', 'Error interno: ' . $e->getMessage(), 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=categoria&action=editar&id=' . $id);
            exit();
        }
    }

    /* ══════════════════════════════════════════════════════════
       VER — detalle de una categoría
    ══════════════════════════════════════════════════════════ */
    public function ver(): void
    {
        SessionMiddleware::checkAuth();

        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $this->flashAndRedirect('ID de categoría no válido', 'error', 'index');
        }

        $categoria = $this->model->getById($id);
        if (!$categoria) {
            $this->flashAndRedirect('Categoría no encontrada', 'error', 'index');
        }

        $graficos           = $this->model->getCategoriaGraficos($id);
        $productosActivos   = (int) ($categoria['total_productos_activos'] ?? 0);
        $productosHistorico = (int) ($categoria['total_productos_historico'] ?? 0);

        // Token CSRF para el modal de stock y botones AJAX
        $csrfToken  = SessionMiddleware::generateUniqueCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/categorias/ver.php';
    }

    /* ══════════════════════════════════════════════════════════
       ELIMINAR (desactivar)
    ══════════════════════════════════════════════════════════ */
    public function eliminar(): never
    {
        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            $this->flashAndRedirect('ID de categoría no válido', 'error', 'index');
        }

        try {
            $result = $this->model->delete($id);

            if ($result['success']) {
                SessionMiddleware::setSweetAlert('Categoría desactivada', $result['mensaje'], 'success');
            } else {
                SessionMiddleware::setSweetAlert('No se pudo desactivar', $result['mensaje'], 'warning');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }

        $this->redirect('index');
    }

    /* ══════════════════════════════════════════════════════════
       ACTIVAR
    ══════════════════════════════════════════════════════════ */
    public function activar(): never
    {
        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            $this->flashAndRedirect('ID de categoría no válido', 'error', 'index');
        }

        try {
            $result = $this->model->activate($id);

            if ($result['success']) {
                SessionMiddleware::setSweetAlert('Categoría activada', $result['mensaje'], 'success');
            } else {
                SessionMiddleware::setSweetAlert('No se pudo activar', $result['mensaje'], 'warning');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }

        $this->redirect('index');
    }

    /* ══════════════════════════════════════════════════════════
       VERIFICAR NOMBRE (AJAX)
    ══════════════════════════════════════════════════════════ */
    public function verificar_nombre(): never
    {
        header('Content-Type: application/json');

        $nombre    = trim($_GET['nombre'] ?? '');
        $excludeId = (int) ($_GET['exclude_id'] ?? 0) ?: null;

        if ($nombre === '') {
            echo json_encode(['disponible' => false, 'mensaje' => 'Nombre requerido']);
            exit();
        }

        try {
            $existe = $this->model->nombreExists($nombre, $excludeId);
            echo json_encode([
                'disponible' => !$existe,
                'mensaje'    => $existe ? 'El nombre ya está en uso' : 'Nombre disponible',
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['disponible' => false, 'mensaje' => $e->getMessage()]);
        }

        exit();
    }

    /* ══════════════════════════════════════════════════════════
       TENDENCIAS DE VENTAS (AJAX)
    ══════════════════════════════════════════════════════════ */
    public function getTendenciasVentas(): never
    {
        header('Content-Type: application/json');
        
        // Por ahora retorna vacío hasta implementar el SP
        echo json_encode([
            'success' => true,
            'data' => [
                'ventas_mensuales' => [],
                'top_categorias' => []
            ]
        ]);
        exit();
    }

    /* ══════════════════════════════════════════════════════════
       EXPORTAR EXCEL
    ══════════════════════════════════════════════════════════ */
    public function exportarExcel(): never
    {
        // Por ahora solo muestra mensaje
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Exportación a Excel no implementada aún']);
        exit();
    }

    /* ══════════════════════════════════════════════════════════
       EXPORTAR PDF
    ══════════════════════════════════════════════════════════ */
    public function exportarPdf(): never
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Exportación a PDF no implementada aún']);
        exit();
    }

    /* ══════════════════════════════════════════════════════════
       HELPERS PRIVADOS
    ══════════════════════════════════════════════════════════ */

    /* ══════════════════════════════════════════════════════════
       VALIDACIÓN CSRF
    ══════════════════════════════════════════════════════════ */

    private function validateCsrfForm(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (empty($token)) {
            SessionMiddleware::setSweetAlert(
                'Error de seguridad',
                'Token CSRF no proporcionado. Recargue la página.',
                'error'
            );
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=categoria&action=index'));
            exit();
        }

        if (!SessionMiddleware::validateUniqueCSRFToken($token)) {
            SessionMiddleware::setSweetAlert(
                'Error de seguridad',
                'Token CSRF inválido o expirado. Recargue la página.',
                'error'
            );
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=categoria&action=index'));
            exit();
        }
    }

    private function redirect(string $action): never
    {
        header('Location: index.php?controller=categoria&action=' . $action);
        exit();
    }

    private function flashAndRedirect(string $mensaje, string $tipo, string $action): never
    {
        SessionMiddleware::setSweetAlert('Aviso', $mensaje, $tipo);
        $this->redirect($action);
    }

    public function checkNombre(): void
{
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    
    // Habilitar reporte de errores para depuración
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // No mostrar errores en JSON
    
    try {
        // Solo peticiones AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['disponible' => false, 'mensaje' => 'Petición no permitida.']);
            return;
        }
        
        $nombre = trim($_GET['nombre'] ?? '');
        $excluir = (int)($_GET['excluir'] ?? 0);
        
        if ($nombre === '') {
            echo json_encode(['disponible' => false, 'mensaje' => 'El nombre es obligatorio.']);
            return;
        }
        
        if (mb_strlen($nombre) > 100) {
            echo json_encode(['disponible' => false, 'mensaje' => 'El nombre no puede superar 100 caracteres.']);
            return;
        }
        
        $existe = $this->model->nombreExists($nombre, $excluir ?: null);
        
        echo json_encode([
            'disponible' => !$existe,
            'mensaje' => $existe ? 'Ya existe una categoría con ese nombre.' : 'Nombre disponible.',
        ]);
        
    } catch (Exception $e) {
        error_log("Error en checkNombre: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'disponible' => false, 
            'mensaje' => 'Error interno del servidor. Contacte al administrador.'
        ]);
    }
}
}
?>