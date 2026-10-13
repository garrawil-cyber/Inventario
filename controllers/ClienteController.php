<?php
/**
 * Controlador: ClienteController.php
 * CRUD para clientes
 */

require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class ClienteController
{
    private Cliente $modelo;

    public function __construct()
    {
        SessionMiddleware::startSession();
        SessionMiddleware::checkAuth();

        $this->modelo = new Cliente();
    }

    /**
     * Listado de clientes
     */
    public function index(): void
    {
        $filters = [
            'filtro' => trim($_GET['filtro'] ?? ''),
            'tipo_cliente' => $_GET['tipo_cliente'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'limit' => (int)($_GET['limit'] ?? 20),
            'offset' => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        $resultado = $this->modelo->listar($filters);
        $clientes = $resultado['data'] ?? [];
        $totalRegistros = $resultado['total'] ?? 0;

        $paginaActual = (int)($_GET['pagina'] ?? 1);
        $limite = $filters['limit'];
        $totalPaginas = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;

        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken();

                require_once __DIR__ . '/../views/inventario/clientes/index.php';
    }

    /**
     * Formulario crear cliente
     */
    public function crear(): void
    {
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken();

        require_once __DIR__ . '/../views/inventario/clientes/crear.php';
    }

    /**
     * Guardar nuevo cliente
     */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=cliente&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        // Validar CSRF
        $this->validateCsrfForm();

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        if (empty($nombreCompleto)) {
            SessionMiddleware::setSweetAlert('Error', 'El nombre completo es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=cliente&action=crear');
            exit();
        }

        $numeroDocumento = trim($_POST['numero_documento'] ?? '');
        if (empty($numeroDocumento)) {
            SessionMiddleware::setSweetAlert('Error', 'El número de documento es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=cliente&action=crear');
            exit();
        }

        $data = [
            'tipo_documento' => $_POST['tipo_documento'] ?? 'DNI',
            'numero_documento' => $numeroDocumento,
            'nombre_completo' => $nombreCompleto,
            'email' => $_POST['email'] ?? null,
            'telefono' => $_POST['telefono'] ?? null,
            'direccion' => $_POST['direccion'] ?? null,
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'genero' => $_POST['genero'] ?? null,
            'tipo_cliente' => $_POST['tipo_cliente'] ?? 'regular',
        ];

        try {
            $result = $this->modelo->crear($data);

            if ($result['success']) {
                SessionMiddleware::redirectWithSweetAlert(
                    'index.php?controller=cliente&action=index',
                    '¡Cliente creado!',
                    $result['mensaje'],
                    'success'
                );
            }

            SessionMiddleware::setSweetAlert('Error', $result['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=cliente&action=crear');
            exit();

        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', 'Error interno: ' . $e->getMessage(), 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=cliente&action=crear');
            exit();
        }
    }

    /**
     * Formulario editar cliente
     */
    public function editar(): void
    {
        SessionMiddleware::checkAuth();

        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            $this->flashAndRedirect('ID de cliente no válido', 'error', 'index');
        }

        $cliente = $this->modelo->getById($id);
        if (!$cliente) {
            $this->flashAndRedirect('Cliente no encontrado', 'error', 'index');
        }

        $formData   = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);
        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken  = SessionMiddleware::generateUniqueCSRFToken();

        require_once __DIR__ . '/../views/inventario/clientes/editar.php';
    }

    /**
     * Actualizar cliente
     */
    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=cliente&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        // Validar CSRF
        $this->validateCsrfForm();

        $id = (int)($_POST['id_cliente'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=cliente&action=index',
                'Error',
                'ID de cliente no válido.',
                'error'
            );
        }

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        if (empty($nombreCompleto)) {
            SessionMiddleware::setSweetAlert('Error', 'El nombre completo es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=cliente&action=editar&id=' . $id);
            exit();
        }

        $numeroDocumento = trim($_POST['numero_documento'] ?? '');
        if (empty($numeroDocumento)) {
            SessionMiddleware::setSweetAlert('Error', 'El número de documento es obligatorio.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=cliente&action=editar&id=' . $id);
            exit();
        }

        $data = [
            'id_cliente' => $id,
            'tipo_documento' => $_POST['tipo_documento'] ?? 'DNI',
            'numero_documento' => $numeroDocumento,
            'nombre_completo' => $nombreCompleto,
            'email' => $_POST['email'] ?? null,
            'telefono' => $_POST['telefono'] ?? null,
            'direccion' => $_POST['direccion'] ?? null,
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'genero' => $_POST['genero'] ?? null,
            'tipo_cliente' => $_POST['tipo_cliente'] ?? 'regular',
            'estado' => $_POST['estado'] ?? 'activo',
        ];

        $resultado = $this->modelo->actualizar($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=cliente&action=index',
                'Cliente actualizado',
                $resultado['mensaje'],
                'success'
            );
        }

        SessionMiddleware::setSweetAlert('Error', $resultado['mensaje'], 'error');
        $_SESSION['form_data'] = $_POST;
        header('Location: index.php?controller=cliente&action=editar&id=' . $id);
        exit();
    }

    /**
     * Eliminar cliente (AJAX)
     */
    public function eliminar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            $this->flashAndRedirect('ID de cliente no válido', 'error', 'index');
        }

        try {
            $result = $this->modelo->eliminar($id);

            if ($result['success']) {
                SessionMiddleware::setSweetAlert('Cliente desactivado', $result['mensaje'], 'success');
            } else {
                SessionMiddleware::setSweetAlert('No se pudo desactivar', $result['mensaje'], 'warning');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }

        $this->redirect('index');
  
    }

    /**
     * Activar cliente (AJAX)
     */
    public function activar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

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
 * Verificar disponibilidad de documento (AJAX)
 * Para validación en tiempo real desde el formulario
 */
/**
 * Verificar disponibilidad de documento (AJAX)
 */
public function checkDocumento(): void
{
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    
    try {
        // Solo peticiones AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['disponible' => false, 'mensaje' => 'Petición no permitida.']);
            return;
        }
        
        $tipo = trim($_GET['tipo'] ?? 'DNI');
        $numero = trim($_GET['numero'] ?? '');
        $excluir = (int)($_GET['excluir'] ?? 0);
        
        error_log("checkDocumento - tipo: $tipo, numero: $numero, excluir: $excluir");
        
        if ($numero === '') {
            echo json_encode(['disponible' => false, 'mensaje' => 'El número de documento es obligatorio.']);
            return;
        }
        
        // Validar formato
        switch($tipo) {
            case 'DNI':
                if (!preg_match('/^\d{8}$/', $numero)) {
                    echo json_encode(['disponible' => false, 'mensaje' => 'DNI debe tener 8 dígitos numéricos.']);
                    return;
                }
                break;
            case 'RUC':
                if (!preg_match('/^\d{11}$/', $numero)) {
                    echo json_encode(['disponible' => false, 'mensaje' => 'RUC debe tener 11 dígitos numéricos.']);
                    return;
                }
                break;
            case 'CE':
                if (!preg_match('/^\d{8,12}$/', $numero)) {
                    echo json_encode(['disponible' => false, 'mensaje' => 'Carné de Extranjería debe tener entre 8 y 12 dígitos.']);
                    return;
                }
                break;
            case 'Pasaporte':
                if (strlen($numero) < 6 || strlen($numero) > 20) {
                    echo json_encode(['disponible' => false, 'mensaje' => 'Pasaporte debe tener entre 6 y 20 caracteres.']);
                    return;
                }
                break;
        }
        
        $existe = $this->modelo->documentoExists($tipo, $numero, $excluir ?: null);
        
        echo json_encode([
            'disponible' => !$existe,
            'mensaje' => $existe ? 'Ya existe un cliente con este ' . $tipo . '.' : 'Documento disponible.',
        ]);
        
    } catch (Exception $e) {
        error_log("Error en checkDocumento: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'disponible' => false, 
            'mensaje' => 'Error interno del servidor.'
        ]);
    }
}

    private function redirect(string $action): never
    {
        header('Location: index.php?controller=cliente&action=' . $action);
        exit();
    }

    private function flashAndRedirect(string $mensaje, string $tipo, string $action): never
    {
        SessionMiddleware::setSweetAlert('Aviso', $mensaje, $tipo);
        $this->redirect($action);
    }


    /**
     * Ver detalle de un cliente
     */
    public function ver(): void
    {
        SessionMiddleware::checkAuth();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=cliente&action=index',
                'Error', 'ID de cliente no válido.', 'error'
            );
        }

        $cliente = $this->modelo->getById($id);
        if (!$cliente) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=cliente&action=index',
                'No encontrado', 'El cliente no existe.', 'warning'
            );
        }

        $sweetAlert = SessionMiddleware::getSweetAlert();
        $csrfToken  = SessionMiddleware::generateUniqueCSRFToken();

        require_once __DIR__ . '/../views/inventario/clientes/ver.php';
    }

    /**
     * Exportar clientes a Excel (XLS)
     */
    public function exportar(): void
    {
        SessionMiddleware::checkAuth();

        // Aplicar los mismos filtros activos del listado
        $filters = [
            'filtro'       => trim($_GET['filtro'] ?? ''),
            'tipo_cliente' => $_GET['tipo_cliente'] ?? '',
            'estado'       => $_GET['estado'] ?? '',
            'limit'        => 9999,   // exportar todos
            'offset'       => 0,
        ];

        $resultado = $this->modelo->listar($filters);
        $clientes  = $resultado['data'] ?? [];

        // Headers de descarga
        $filename = 'clientes_' . date('Y-m-d') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        require_once __DIR__ . '/../views/inventario/clientes/exportar.php';
        exit();
    }

    /**
     * Buscar cliente por documento (AJAX)
     */
    public function buscarPorDocumento(): void
    {
        header('Content-Type: application/json');

        $tipo = $_GET['tipo'] ?? 'DNI';
        $numero = $_GET['numero'] ?? '';

        if (empty($numero)) {
            echo json_encode(['success' => false, 'data' => null]);
            return;
        }

        $cliente = $this->modelo->buscarPorDocumento($tipo, $numero);
        echo json_encode(['success' => true, 'data' => $cliente]);
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
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=cliente&action=index'));
            exit();
        }

        if (!SessionMiddleware::validateUniqueCSRFToken($token)) {
            SessionMiddleware::setSweetAlert(
                'Error de seguridad',
                'Token CSRF inválido o expirado. Recargue la página.',
                'error'
            );
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=cliente&action=index'));
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