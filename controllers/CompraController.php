<?php
/**
 * ============================================================
 *  CONTROLADOR: CompraController.php
 *  Sistema : RopaVirtual – Módulo de Compras
 *  Arquitectura: MVC  |  Capa: Controller
 * ============================================================
 *
 *  Acciones públicas:
 *    index, ver, crear, guardar, editar, actualizar,
 *    anular, getForSelect, dashboardStats
 *
 *  Mensajes al usuario: exclusivamente mediante SweetAlert
 *  (SessionMiddleware::setSweetAlert / redirectWithSweetAlert)
 * ============================================================
 */

require_once __DIR__ . '/../models/Compra.php';
require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class CompraController
{
    private Compra $modelo;
    private Proveedor $proveedorModelo;
    private Producto $productoModelo;

    // Tipos de comprobante permitidos
    private const TIPOS_COMPROBANTE = ['factura', 'boleta', 'guia', 'orden_compra', 'otro'];
    private const FORMAS_PAGO = ['efectivo', 'transferencia', 'credito', 'cheque', 'yape', 'plin'];
    private const ESTADOS_PAGO = ['pagado', 'pendiente', 'parcial', 'anulado'];
    private const ESTADOS = ['completada', 'anulada', 'procesando', 'pendiente'];

    public function __construct()
    {
        SessionMiddleware::startSession();
        SessionMiddleware::checkAuth();
        $this->modelo = new Compra();
        $this->proveedorModelo = new Proveedor();
        $this->productoModelo = new Producto();
    }

    /* ====================================================================
     *  INDEX — Listado paginado con filtros
     * ==================================================================== */
    public function index(): void
    {
        // Verificar permiso (solo admin y supervisor pueden ver compras)
        $rolUsuario = SessionMiddleware::getUserData('rol');
        if (!in_array($rolUsuario, ['admin', 'supervisor'])) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=dashboard&action=index',
                'Acceso denegado',
                'No tienes permisos para ver el módulo de compras.',
                'error'
            );
        }

        $filters = [
            'filtro'        => trim($_GET['filtro'] ?? ''),
            'proveedor_id'  => (int)($_GET['proveedor_id'] ?? 0) ?: null,
            'estado'        => $_GET['estado'] ?? '',
            'estado_pago'   => $_GET['estado_pago'] ?? '',
            'fecha_desde'   => $_GET['fecha_desde'] ?? null,
            'fecha_hasta'   => $_GET['fecha_hasta'] ?? null,
            'limit'         => (int)($_GET['limit'] ?? 20),
            'offset'        => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        $resultado = $this->modelo->listar($filters);
        $compras = $resultado['data'] ?? [];
        $totalRegistros = $resultado['total'] ?? 0;

        $paginaActual = (int)($_GET['pagina'] ?? 1);
        $limite = $filters['limit'];
        $totalPaginas = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;

        // Datos para filtros
        $proveedores = $this->proveedorModelo->getForSelect();
        $tiposComprobante = self::TIPOS_COMPROBANTE;
        $formasPago = self::FORMAS_PAGO;
        $estadosPago = self::ESTADOS_PAGO;
        $estados = self::ESTADOS;

        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/compras/index.php';
    }

    /* ====================================================================
     *  VER — Detalle de una compra
     * ==================================================================== */
    public function ver(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'Error',
                'ID de compra no válido.',
                'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'No encontrado',
                $resultado['mensaje'] ?? 'Compra no encontrada.',
                'warning'
            );
        }

        $cabecera = $resultado['cabecera'];
        $detalle = $resultado['detalle'];
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/compras/ver.php';
    }

    /* ====================================================================
     *  CREAR — Formulario de nueva compra
     * ==================================================================== */
    public function crear(): void
    {
        // Verificar permiso
        $rolUsuario = SessionMiddleware::getUserData('rol');
        if (!in_array($rolUsuario, ['admin', 'supervisor'])) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'Acceso denegado',
                'No tienes permisos para crear compras.',
                'error'
            );
        }

        $proveedores = $this->proveedorModelo->getForSelect();
        $productos = $this->productoModelo->listar(['estado' => 'activo', 'limit' => 9999])['data'] ?? [];
        $tiposComprobante = self::TIPOS_COMPROBANTE;
        $formasPago = self::FORMAS_PAGO;
        $estadosPago = self::ESTADOS_PAGO;

        $csrfToken = SessionMiddleware::generateCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();

        // Recuperar datos del formulario si hubo error
        $formData = $_SESSION['form_data'] ?? [];
        unset($_SESSION['form_data']);

        require_once __DIR__ . '/../views/inventario/compras/crear.php';
    }

    /* ====================================================================
     *  GUARDAR — Procesar POST de nueva compra
     * ==================================================================== */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        // CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=crear',
                'Token inválido',
                'La sesión expiró. Intenta de nuevo.',
                'warning'
            );
        }

        // Validar datos básicos
        $proveedor_id = (int)($_POST['proveedor_id'] ?? 0);
        if ($proveedor_id <= 0) {
            SessionMiddleware::setSweetAlert('Error', 'Debe seleccionar un proveedor.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=compra&action=crear');
            exit();
        }

        $tipo_comprobante = $_POST['tipo_comprobante'] ?? 'factura';
        if (!in_array($tipo_comprobante, self::TIPOS_COMPROBANTE)) {
            $tipo_comprobante = 'factura';
        }

        $forma_pago = $_POST['forma_pago'] ?? 'credito';
        if (!in_array($forma_pago, self::FORMAS_PAGO)) {
            $forma_pago = 'credito';
        }

        // Procesar productos del detalle
        $productos_ids = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio_unitario'] ?? [];
        $lotes = $_POST['lote'] ?? [];
        $fechas_vencimiento = $_POST['fecha_vencimiento'] ?? [];

        $detalles = [];
        $subtotal = 0;
        $total_igv = 0;
        $total_descuento = 0;

        for ($i = 0; $i < count($productos_ids); $i++) {
            if (empty($productos_ids[$i]) || empty($cantidades[$i]) || empty($precios[$i])) {
                continue;
            }

            $cantidad = (int)$cantidades[$i];
            $precio = (float)$precios[$i];
            $subtotal_item = $cantidad * $precio;
            $igv_item = round($subtotal_item * 0.18, 2);
            $total_item = $subtotal_item + $igv_item;

            $detalles[] = [
                'producto_id' => (int)$productos_ids[$i],
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'lote' => !empty($lotes[$i]) ? $lotes[$i] : null,
                'fecha_vencimiento' => !empty($fechas_vencimiento[$i]) ? $fechas_vencimiento[$i] : null,
            ];

            $subtotal += $subtotal_item;
            $total_igv += $igv_item;
        }

        if (empty($detalles)) {
            SessionMiddleware::setSweetAlert('Error', 'Debe agregar al menos un producto a la compra.', 'warning');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=compra&action=crear');
            exit();
        }

        $descuento_global = (float)($_POST['descuento_global'] ?? 0);
        $total = $subtotal + $total_igv - $descuento_global;

        $data = [
            'codigo' => $_POST['codigo'] ?? null,
            'proveedor_id' => $proveedor_id,
            'id_usuario' => SessionMiddleware::getUserData('id'),
            'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
            'tipo_comprobante' => $tipo_comprobante,
            'serie' => $_POST['serie'] ?? null,
            'numero' => $_POST['numero'] ?? null,
            'subtotal' => $subtotal,
            'igv' => $total_igv,
            'descuento' => $descuento_global,
            'total' => $total,
            'forma_pago' => $forma_pago,
            'estado_pago' => $_POST['estado_pago'] ?? 'pendiente',
            'estado' => 'completada',
            'observaciones' => $_POST['observaciones'] ?? null,
            'detalles' => $detalles,
        ];

        $resultado = $this->modelo->crear($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=ver&id=' . $resultado['id_compra'],
                '¡Compra registrada!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al registrar', $resultado['mensaje'], 'error');
            $_SESSION['form_data'] = $_POST;
            header('Location: index.php?controller=compra&action=crear');
            exit();
        }
    }

    /* ====================================================================
     *  EDITAR — Formulario de edición de compra
     * ==================================================================== */
    public function editar(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'Error',
                'ID de compra no válido.',
                'error'
            );
        }

        $resultado = $this->modelo->getById($id);
        if (!$resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'No encontrado',
                $resultado['mensaje'] ?? 'Compra no encontrada.',
                'warning'
            );
        }

        // Solo permitir editar si no está anulada
        if ($resultado['cabecera']['estado'] === 'anulada') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=ver&id=' . $id,
                'No editable',
                'Las compras anuladas no se pueden editar.',
                'warning'
            );
        }

        $cabecera = $resultado['cabecera'];
        $detalle = $resultado['detalle'];
        $proveedores = $this->proveedorModelo->getForSelect();
        $tiposComprobante = self::TIPOS_COMPROBANTE;
        $formasPago = self::FORMAS_PAGO;
        $estadosPago = self::ESTADOS_PAGO;

        $csrfToken = SessionMiddleware::generateCSRFToken();
        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/compras/editar.php';
    }

    /* ====================================================================
     *  ACTUALIZAR — Procesar POST de edición
     * ==================================================================== */
    public function actualizar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'Error',
                'Método no permitido.',
                'error'
            );
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SessionMiddleware::validateCSRFToken($token)) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'Token inválido',
                'La sesión expiró. Intenta de nuevo.',
                'warning'
            );
        }

        $id_compra = (int)($_POST['id_compra'] ?? 0);
        if ($id_compra <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=index',
                'Error',
                'ID de compra no válido.',
                'error'
            );
        }

        $data = [
            'id_compra' => $id_compra,
            'tipo_comprobante' => $_POST['tipo_comprobante'] ?? null,
            'serie' => $_POST['serie'] ?? null,
            'numero' => $_POST['numero'] ?? null,
            'forma_pago' => $_POST['forma_pago'] ?? null,
            'estado_pago' => $_POST['estado_pago'] ?? null,
            'observaciones' => $_POST['observaciones'] ?? null,
        ];

        $resultado = $this->modelo->actualizar($data);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=compra&action=ver&id=' . $id_compra,
                '¡Actualizado!',
                $resultado['mensaje'],
                'success'
            );
        } else {
            SessionMiddleware::setSweetAlert('Error al actualizar', $resultado['mensaje'], 'error');
            header('Location: index.php?controller=compra&action=editar&id=' . $id_compra);
            exit();
        }
    }

    /* ====================================================================
     *  ANULAR — Anular compra (vía AJAX para SweetAlert)
     * ==================================================================== */
    public function anular(): void
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

        $id_compra = (int)($input['id_compra'] ?? 0);
        $motivo = trim($input['motivo'] ?? '');

        if ($id_compra <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de compra no válido.']);
            return;
        }

        $id_usuario = (int)SessionMiddleware::getUserData('id');
        $resultado = $this->modelo->anular($id_compra, $id_usuario, $motivo);

        echo json_encode($resultado);
    }

    /* ====================================================================
     *  GET FOR SELECT — AJAX (compras para combobox)
     * ==================================================================== */
    public function getForSelect(): void
    {
        header('Content-Type: application/json');
        $estado = $_GET['estado'] ?? null;
        $rows = $this->modelo->getForSelect($estado ?: null);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    /* ====================================================================
     *  DASHBOARD STATS — Estadísticas para dashboard (AJAX)
     * ==================================================================== */
    public function dashboardStats(): void
    {
        header('Content-Type: application/json');
        $periodo = $_GET['periodo'] ?? 'mes';
        $stats = $this->modelo->getDashboardStats($periodo);
        echo json_encode($stats);
    }
}