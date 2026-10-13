<?php
/**
 * InventarioFisicoController.php — CON CSRF (versión completa)
 * 
 * Cambios:
 *   - Agregado método validateCsrf() para validar token en peticiones AJAX
 *   - Agregado método validateCsrfForm() para validar token en formularios normales
 *   - Todos los métodos POST/AJAX ahora validan CSRF
 *   - Manejo de error 403 para tokens inválidos
 */

require_once __DIR__ . '/../models/InventarioFisico.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class InventarioFisicoController
{
    private InventarioFisico $modelo;
    private const TIPOS = ['total', 'parcial', 'ciclico'];

    public function __construct()
    {
        SessionMiddleware::startSession();
        SessionMiddleware::checkAuth();

        $rolUsuario = SessionMiddleware::getUserData('rol');
        if (!in_array($rolUsuario, ['admin', 'supervisor', 'almacenero'])) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=dashboard&action=index',
                'Acceso denegado',
                'No tienes permisos para gestionar inventarios fisicos.',
                'error'
            );
        }
        $this->modelo = new InventarioFisico();
    }

    // =========================================================================
    // CSRF VALIDATION
    // =========================================================================

    /**
     * Valida el token CSRF para peticiones AJAX (desde cabecera X-CSRF-Token)
     */
    private function validateCsrf(): void
{
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? $headers['X-CSRF-TOKEN'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (empty($token)) {
        $this->sendJsonError('Token CSRF no proporcionado. Recargue la página.', 403);
    }
    
    if (!SessionMiddleware::validateUniqueCSRFToken($token)) {
        $this->sendJsonError('Token CSRF inválido o expirado. Recargue la página.', 403);
    }
}
    /**
     * Valida el token CSRF para formularios normales (POST con campo hidden)
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
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=inventario&action=crear'));
        exit();
    }
    
    if (!SessionMiddleware::validateUniqueCSRFToken($token)) {
        SessionMiddleware::setSweetAlert(
            'Error de seguridad',
            'Token CSRF inválido o expirado. Recargue la página.',
            'error'
        );
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?controller=inventario&action=crear'));
        exit();
    }
}
    /**
     * Envía respuesta JSON de error con código HTTP apropiado
     */
    private function sendJsonError(string $message, int $code = 400): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(['success' => false, 'mensaje' => $message]);
        exit();
    }

    /**
     * Envía respuesta JSON de éxito
     */
    private function sendJsonSuccess(array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
        exit();
    }

    private function getNombreUsuario(): string
    {
        $nombre = SessionMiddleware::getUserData('nombre');
        if (!empty($nombre) && is_string($nombre)) return $nombre;
        $username = SessionMiddleware::getUserData('username');
        if (!empty($username) && is_string($username)) return $username;
        $email = SessionMiddleware::getUserData('email');
        if (!empty($email) && is_string($email)) return explode('@', $email)[0];
        return 'Usuario';
    }

    /* ====================================================================
     * INDEX - Listado de inventarios
     * ==================================================================== */
    public function index(): void
    {
        $filters = [
            'filtro'      => trim($_GET['filtro'] ?? ''),
            'estado'      => $_GET['estado'] ?? '',
            'tipo'        => $_GET['tipo'] ?? '',
            'id_almacen'  => (int)($_GET['id_almacen'] ?? 0) ?: null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
            'limit'       => (int)($_GET['limit'] ?? 20),
            'offset'      => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        $resultado      = $this->modelo->listar($filters);
        $inventarios    = $resultado['data'] ?? [];
        $totalRegistros = $resultado['total'] ?? 0;

        $resumen = ['planificados' => 0, 'en_proceso' => 0, 'finalizados' => 0];
        foreach ($inventarios as $inv) {
            if ($inv['estado'] === 'planificado') $resumen['planificados']++;
            elseif ($inv['estado'] === 'en_proceso') $resumen['en_proceso']++;
            elseif ($inv['estado'] === 'finalizado') $resumen['finalizados']++;
        }

        $paginaActual = (int)($_GET['pagina'] ?? 1);
        $limite = $filters['limit'];
        $totalPaginas = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;

        $almacenes = $this->modelo->getAlmacenesActivos();
        $tipos = self::TIPOS;
        $estados = ['planificado', 'en_proceso', 'finalizado', 'cancelado'];
        $sweetAlert = SessionMiddleware::getSweetAlert();
        
        // Generar token CSRF para la vista
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken(); // Unique: mismo token por sesión, compatible con validateUniqueCSRFToken()

        require_once __DIR__ . '/../views/inventario/inventario_fisico/index.php';
    }

    /* ====================================================================
     * VER - Detalle del inventario
     * ==================================================================== */
    public function ver(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'Error', 'ID de inventario no valido.', 'error'
            );
        }

        $cabeceraResultado = $this->modelo->getCabecera($id);
        if (!$cabeceraResultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'No encontrado', 'El inventario fisico no existe.', 'warning'
            );
        }

        $cabecera = $cabeceraResultado['cabecera'];
        $stats = $cabeceraResultado['stats'];
        $detalleResultado = $this->modelo->getDetalle($id);
        $detalle = $detalleResultado['data'] ?? [];
        $resumenResultado = $this->modelo->resumenDiferencias($id);
        $topProductos = $resumenResultado['topProductos'] ?? [];
        $sweetAlert = SessionMiddleware::getSweetAlert();
        
        // Generar token CSRF para la vista
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken(); // Unique: mismo token por sesión, compatible con validateUniqueCSRFToken()

        require_once __DIR__ . '/../views/inventario/inventario_fisico/ver.php';
    }

    /* ====================================================================
     * CREAR - Formulario crear inventario
     * ==================================================================== */
public function crear(): void
{
    // Asegurar sesión iniciada
    SessionMiddleware::startSession();
    
    $tipos = self::TIPOS;
    $almacenes = $this->modelo->getAlmacenesActivos();
    $formData = $_SESSION['form_data'] ?? [];
    unset($_SESSION['form_data']);
    $sweetAlert = SessionMiddleware::getSweetAlert();
    
    // Generar token CSRF para la vista (ya debe estar iniciada la sesión)
    $csrfToken = SessionMiddleware::generateUniqueCSRFToken(); // Unique: mismo token por sesión, compatible con validateUniqueCSRFToken()

    require_once __DIR__ . '/../views/inventario/inventario_fisico/crear.php';
}

    /* ====================================================================
     * GUARDAR - Crear inventario (formulario normal)
     * ==================================================================== */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'Error', 'Metodo no permitido.', 'error'
            );
        }
        
        // ✅ VALIDAR CSRF para formulario normal
        $this->validateCsrfForm();

        $idAlmacen = (int)($_POST['id_almacen'] ?? 0);
        if ($idAlmacen <= 0) {
            SessionMiddleware::setSweetAlert('Error', 'Debe seleccionar un almacen valido.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=inventario&action=crear');
            exit();
        }

        $tipo = $_POST['tipo'] ?? 'total';
        if (!in_array($tipo, self::TIPOS)) $tipo = 'total';

        $responsable = trim($_POST['responsable'] ?? '');
        if (empty($responsable)) $responsable = $this->getNombreUsuario();

        $userId = (int)SessionMiddleware::getUserData('id');
        if ($userId <= 0) {
            error_log('guardar(): id_usuario=0 — sesion: ' . json_encode($_SESSION));
            SessionMiddleware::setSweetAlert(
                'Sesion invalida',
                'No se pudo identificar al usuario. Por favor, vuelve a iniciar sesion.',
                'error'
            );
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=inventario&action=crear');
            exit();
        }

        $data = [
            'id_almacen'   => $idAlmacen,
            'tipo'         => $tipo,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
            'responsable'  => $responsable,
            'observaciones'=> $_POST['observaciones'] ?? null,
            'id_usuario'   => $userId,
        ];

        $resultado = $this->modelo->crear($data);
        error_log('guardar() resultado: ' . json_encode($resultado));

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=ver&id=' . $resultado['id_inventario'],
                'Inventario creado',
                $resultado['mensaje'],
                'success'
            );
        }

        SessionMiddleware::setSweetAlert('Error al crear', $resultado['mensaje'], 'error');
        $_SESSION['form_data'] = $_POST;
        header('Location: index.php?controller=inventario&action=crear');
        exit();
    }

    /* ====================================================================
     * INICIAR - Iniciar conteo (AJAX)
     * ==================================================================== */
    public function iniciar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }
        
        // ✅ VALIDAR CSRF para petición AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $idInventario = (int)($input['id_inventario'] ?? 0);
        $idUsuario = (int)SessionMiddleware::getUserData('id');

        if ($idInventario <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de inventario no válido.']);
            return;
        }

        echo json_encode($this->modelo->iniciarConteo($idInventario, $idUsuario));
    }

    /* ====================================================================
     * FINALIZAR - Finalizar inventario (AJAX)
     * ==================================================================== */
    public function finalizar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }
        
        // ✅ VALIDAR CSRF para petición AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $idInventario = (int)($input['id_inventario'] ?? 0);
        $aplicarAjustes = (bool)($input['aplicar_ajustes'] ?? true);
        $idUsuario = (int)SessionMiddleware::getUserData('id');

        if ($idInventario <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de inventario no válido.']);
            return;
        }

        $resultado = $this->modelo->finalizar($idInventario, $idUsuario, $aplicarAjustes);
        $resultado['ajustados'] = $resultado['ajustados'] ?? 0;

        echo json_encode($resultado);
    }

    /* ====================================================================
     * CANCELAR - Cancelar inventario (AJAX)
     * ==================================================================== */
    public function cancelar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }
        
        // ✅ VALIDAR CSRF para petición AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $idInventario = (int)($input['id_inventario'] ?? 0);
        $motivo = trim($input['motivo'] ?? '');
        $idUsuario = (int)SessionMiddleware::getUserData('id');

        if ($idInventario <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de inventario no válido.']);
            return;
        }

        echo json_encode($this->modelo->cancelar($idInventario, $idUsuario, $motivo));
    }

    /* ====================================================================
     * AGREGAR PRODUCTO - Agregar producto al inventario (AJAX)
     * ==================================================================== */
    public function agregarProducto(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }
        
        // ✅ VALIDAR CSRF para petición AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $idInventario = (int)($input['id_inventario'] ?? 0);
        $idProducto = (int)($input['id_producto'] ?? 0);

        if ($idInventario <= 0 || $idProducto <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        echo json_encode($this->modelo->agregarDetalle([
            'id_inventario'  => $idInventario,
            'id_producto'    => $idProducto,
            'stock_fisico'   => (int)($input['stock_fisico'] ?? 0),
            'costo_unitario' => $input['costo_unitario'] ?? null,
            'observaciones'  => $input['observaciones'] ?? null,
            'contado_por'    => $input['contado_por'] ?? $this->getNombreUsuario(),
        ]));
    }

    /* ====================================================================
     * ELIMINAR PRODUCTO - Eliminar producto del inventario (AJAX)
     * ==================================================================== */
    public function eliminarProducto(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }
        
        // ✅ VALIDAR CSRF para petición AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $idDetalle = (int)($input['id_detalle'] ?? 0);

        if ($idDetalle <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de detalle no valido.']);
            return;
        }

        echo json_encode($this->modelo->eliminarDetalle($idDetalle));
    }

    /* ====================================================================
     * ACTUALIZAR PRODUCTO - Actualizar stock contado (AJAX)
     * ==================================================================== */
    public function actualizarProducto(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }
        
        // ✅ VALIDAR CSRF para petición AJAX
        $this->validateCsrf();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $idDetalle = (int)($input['id_detalle'] ?? 0);
        $stockFisico = (int)($input['stock_fisico'] ?? 0);

        if ($idDetalle <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de detalle no valido.']);
            return;
        }

        echo json_encode($this->modelo->actualizarDetalle([
            'id_detalle'    => $idDetalle,
            'stock_fisico'  => $stockFisico,
            'contado_por'   => $this->getNombreUsuario(),
        ]));
    }

    /* ====================================================================
     * GET PRODUCTOS - Buscar productos (AJAX - solo lectura, sin CSRF obligatorio)
     * ==================================================================== */
    public function getProductos(): void
    {
        header('Content-Type: application/json');
        
        // Las peticiones GET generalmente no requieren CSRF (solo lectura)
        // Pero opcionalmente se puede validar si se envía el token
        $termino = $_GET['q'] ?? '';
        $limit = (int)($_GET['limit'] ?? 15);

        if (strlen($termino) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }

        $productos = $this->modelo->buscarProductos($termino, $limit);
        echo json_encode(['success' => true, 'data' => $productos]);
    }

    /* ====================================================================
     * REALIZAR - Pantalla de conteo
     * ==================================================================== */
    public function realizar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'Error', 'ID de inventario no valido.', 'error'
            );
        }

        $cabeceraResultado = $this->modelo->getCabecera($id);
        if (!$cabeceraResultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'Error', 'Inventario no encontrado.', 'error'
            );
        }

        $inventario = $cabeceraResultado['cabecera'];

        if (($inventario['estado'] ?? '') !== 'en_proceso') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=ver&id=' . $id,
                'Accion no permitida',
                'Solo se pueden realizar conteos en inventarios en estado "En Proceso".',
                'warning'
            );
        }

        $idAlmacen = (int)($inventario['id_almacen'] ?? 0);

        if ($idAlmacen <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=ver&id=' . $id,
                'Error de configuración',
                'El inventario no tiene un almacén asociado válido.',
                'error'
            );
        }

        $detalle = $this->modelo->getDetalle($id)['data'] ?? [];
        $productos = $this->modelo->getProductosPorAlmacen($idAlmacen);
        
        // Generar token CSRF para la vista
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken(); // Unique: mismo token por sesión, compatible con validateUniqueCSRFToken()

        require_once __DIR__ . '/../views/inventario/inventario_fisico/realizar.php';
    }

    /* ====================================================================
     * GUARDAR CONTEO - Guardar conteo desde realizar.php
     * ==================================================================== */
    public function guardarConteo(): void
    {
        $esAjax = SessionMiddleware::isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
                exit();
            }
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'Error', 'Metodo no permitido.', 'error'
            );
        }
        
        // ✅ VALIDAR CSRF - funciona tanto para AJAX como para formulario normal
        if ($esAjax) {
            $this->validateCsrf();
        } else {
            $this->validateCsrfForm();
        }

        $idInventario = (int)($_POST['id_inventario'] ?? 0);
        $conteos = $_POST['conteos'] ?? [];

        if ($idInventario <= 0 || empty($conteos)) {
            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'mensaje' => 'No hay datos para guardar.']);
                exit();
            }
            SessionMiddleware::setSweetAlert('Error', 'No hay datos para guardar.', 'warning');
            header('Location: index.php?controller=inventario&action=realizar&id=' . $idInventario);
            exit();
        }

        $actualizados = 0;
        $errores = 0;
        $nombreUsuario = $this->getNombreUsuario();

        foreach ($conteos as $idProducto => $data) {
            $stockFisico = (float)($data['stock_fisico'] ?? 0);
            $observaciones = $data['observaciones'] ?? null;
            $detalleExistente = $this->modelo->getDetalleByProducto($idInventario, (int)$idProducto);

            if ($detalleExistente) {
                $resultado = $this->modelo->actualizarDetalle([
                    'id_detalle'    => $detalleExistente['id_detalle'],
                    'stock_fisico'  => $stockFisico,
                    'observaciones' => $observaciones,
                    'contado_por'   => $nombreUsuario,
                ]);
            } else {
                $resultado = $this->modelo->agregarDetalle([
                    'id_inventario' => $idInventario,
                    'id_producto'   => (int)$idProducto,
                    'stock_fisico'  => $stockFisico,
                    'observaciones' => $observaciones,
                    'contado_por'   => $nombreUsuario,
                ]);
            }

            $resultado['success'] ? $actualizados++ : $errores++;
        }

        if ($esAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'mensaje' => "Se actualizaron $actualizados producto(s) correctamente.",
            ]);
            exit();
        }

        SessionMiddleware::setSweetAlert('Conteo guardado', "Se actualizaron $actualizados producto(s).", 'success');
        header('Location: index.php?controller=inventario&action=realizar&id=' . $idInventario);
        exit();
    }

    /* ====================================================================
     * RESULTADOS - Ver resultados del inventario
     * ==================================================================== */
    public function resultados(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'Error', 'ID de inventario no valido.', 'error'
            );
        }

        $cabeceraResultado = $this->modelo->getCabecera($id);
        if (!$cabeceraResultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=inventario&action=index',
                'Error', 'Inventario no encontrado.', 'error'
            );
        }

        $inventario = $cabeceraResultado['cabecera'];
        $stats = $cabeceraResultado['stats'];
        $detalle = $this->modelo->getDetalle($id)['data'] ?? [];

        $resumen = [
            'total_productos' => count($detalle),
            'total_sistema' => array_sum(array_column($detalle, 'stock_sistema')),
            'total_fisico' => array_sum(array_column($detalle, 'stock_fisico')),
            'sobrantes' => array_sum(array_map(fn($d) => max(0, $d['diferencia']), $detalle)),
            'faltantes' => abs(array_sum(array_map(fn($d) => min(0, $d['diferencia']), $detalle))),
            'valor_diferencia_total' => array_sum(array_column($detalle, 'valor_diferencia')),
        ];
        
        // Generar token CSRF para la vista
        $csrfToken = SessionMiddleware::generateUniqueCSRFToken(); // Unique: mismo token por sesión, compatible con validateUniqueCSRFToken()

        require_once __DIR__ . '/../views/inventario/inventario_fisico/resultados.php';
    }

    /* ====================================================================
     * EXPORTAR - Exportar a CSV (GET, no requiere CSRF)
     * ==================================================================== */
    public function exportar(): void
    {
        $idInventario = (int)($_GET['id'] ?? 0);
        if ($idInventario <= 0) {
            SessionMiddleware::redirectWithSweetAlert('index.php?controller=inventario&action=index', 'Error', 'ID no valido.', 'error');
        }

        $cabeceraResultado = $this->modelo->getCabecera($idInventario);
        if (!$cabeceraResultado['success']) {
            SessionMiddleware::redirectWithSweetAlert('index.php?controller=inventario&action=index', 'Error', 'No se pudo obtener la informacion.', 'error');
        }

        $detalle = $this->modelo->getDetalle($idInventario)['data'] ?? [];
        $cabecera = $cabeceraResultado['cabecera'];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="inventario_' . $cabecera['codigo'] . '_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['REPORTE DE INVENTARIO FISICO']);
        fputcsv($output, ['Codigo:', $cabecera['codigo']]);
        fputcsv($output, ['Almacen:', $cabecera['almacen_nombre']]);
        fputcsv($output, ['Fecha:', $cabecera['fecha_inicio_fmt']]);
        fputcsv($output, ['Tipo:', $cabecera['tipo']]);
        fputcsv($output, ['Estado:', $cabecera['estado']]);
        fputcsv($output, ['Responsable:', $cabecera['responsable']]);
        fputcsv($output, []);
        fputcsv($output, ['Cod.Producto', 'SKU', 'Producto', 'Categoria', 'Unidad', 'Stock Sistema', 'Stock Fisico', 'Diferencia', 'Costo Unit.', 'Valor Dif.', 'Contado Por', 'Verificado Por', 'Observaciones']);

        foreach ($detalle as $item) {
            fputcsv($output, [
                $item['producto_codigo'], $item['producto_sku'],
                $item['producto_nombre'], $item['categoria'] ?? '',
                $item['unidad_medida'] ?? '',
                $item['stock_sistema'], $item['stock_fisico'],
                $item['diferencia'],
                number_format($item['costo_unitario'] ?? 0, 2),
                number_format($item['valor_diferencia'] ?? 0, 2),
                $item['contado_por'] ?? '', $item['verificado_por'] ?? '',
                $item['observaciones'] ?? '',
            ]);
        }
        fclose($output);
        exit();
    }
}