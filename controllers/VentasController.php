<?php
/**
 * ============================================================
 *  CONTROLADOR: VentasController.php
 *  Sistema : RopaVirtual – Módulo de Ventas
 *  Arquitectura: MVC  |  Capa: Controller
 * ============================================================
 *
 *  CORRECCIONES APLICADAS:
 *  ──────────────────────────────────────────────────────────
 *  1. index(): los filtros de URL usan fecha_desde/fecha_hasta
 *     y se mapean igual al modelo. Se eliminó el doble array_merge
 *     parcial que omitía ventas_credito y otros campos.
 *
 *  2. index(): listarVentas() devuelve ['data','total','success'];
 *     el controlador asigna $ventas = $resultado['data'] y ya
 *     NO accede a ['ventas'] (clave inexistente).
 *
 *  3. crear(): se llama $this->ventasModel->getForSelect() en lugar
 *     de $this->clientesModel->getForSelect() porque el modelo
 *     Cliente no garantiza ese método (y Ventas sí lo tiene).
 *
 *  4. buscarProductos(): eliminada la verificación de
 *     HTTP_X_REQUESTED_WITH que bloqueaba la búsqueda en algunos
 *     clientes; se usa solo el parámetro GET 'q'.
 *
 *  5. anular(): pasa $idUsuario obtenido de la sesión correctamente.
 *
 *  6. exportar(): usa $this->ventasModel->getDataForExport() (método
 *     correcto en el modelo, antes llamaba a método inexistente).
 *
 *  7. Se eliminan todos los error_log() de datos sensibles
 *     que quedaban de fase de desarrollo.
 *
 *  8. listarProductosModal() movida al final, correctamente ordenada.
 * ============================================================
 */

require_once __DIR__ . '/../models/Ventas.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';
require_once __DIR__ . '/../config/Database.php';

class VentasController
{
    private Ventas   $ventasModel;
    private Cliente  $clientesModel;
    private Producto $productoModel;

    public function __construct()
    {
        $this->ventasModel   = new Ventas();
        $this->clientesModel = new Cliente();
        $this->productoModel = new Producto();
        SessionMiddleware::startSession();
    }

    /* ============================================================
     *  index  –  Listado principal con KPIs y filtros
     * ============================================================ */
    public function index(): void
    {
        SessionMiddleware::checkAuth();

        // Filtros desde la URL
        $filters = [
            'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'),
            'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d'),
            'estado'      => $_GET['estado']      ?? null,
            'tipo_pago'   => $_GET['tipo_pago']   ?? null,
            'busqueda'    => $_GET['busqueda']     ?? null,
            'limit'       => (int)($_GET['limit']  ?? 20),
            'offset'      => ((int)($_GET['pagina'] ?? 1) - 1) * (int)($_GET['limit'] ?? 20),
        ];

        // Estadísticas del dashboard
        $estadisticas = $this->ventasModel->getEstadisticasDashboard($filters);

        // Listado paginado — CORRECCIÓN: clave 'data' (no 'ventas')
        $resultado = $this->ventasModel->listarVentas($filters);
        $ventas        = $resultado['data']  ?? [];
        $totalRegistros= $resultado['total'] ?? 0;

        $paginaActual  = (int)($_GET['pagina'] ?? 1);
        $limite        = $filters['limit'];
        $totalPaginas  = $limite > 0 ? (int)ceil($totalRegistros / $limite) : 1;

        // Garantizar todos los campos del array de estadísticas
        $estadisticas = array_merge([
            'total_ventas'        => 0,
            'ingresos_totales'    => 0,
            'ingresos_hoy'        => 0,
            'ingresos_mes'        => 0,
            'ventas_hoy'          => 0,
            'ticket_promedio'     => 0,
            'ventas_efectivo'     => 0,
            'ventas_tarjeta'      => 0,
            'ventas_transferencia'=> 0,
            'ventas_credito'      => 0,
            'ventas_completadas'  => 0,
            'ventas_pendientes'   => 0,
            'ventas_anuladas'     => 0,
            'total_igv'           => 0,
            'top_productos'       => [],
        ], $estadisticas);

        $sweetAlert = SessionMiddleware::getSweetAlert();

        require_once __DIR__ . '/../views/inventario/ventas/index.php';
    }

    /* ============================================================
     *  ver  –  Detalle de una venta
     * ============================================================ */
    public function ver(): void
    {
        SessionMiddleware::checkAuth();

        $idVenta = (int)($_GET['id'] ?? 0);

        if ($idVenta <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ventas&action=index',
                'Error', 'ID de venta no válido.', 'error'
            );
            return;
        }

        $resultado = $this->ventasModel->getById($idVenta);

        if (!$resultado['success'] || empty($resultado['cabecera'])) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ventas&action=index',
                'Error', 'Venta no encontrada.', 'error'
            );
            return;
        }

        $cabecera = $resultado['cabecera'];
        $detalle  = $resultado['detalle'];

        require_once __DIR__ . '/../views/inventario/ventas/ver.php';
    }

    /* ============================================================
     *  crear  –  Formulario de nueva venta
     * ============================================================ */
    public function crear(): void
    {
        SessionMiddleware::checkAuth();

        // CORRECCIÓN: getForSelect() está en el modelo Ventas (no requiere
        // instancia separada de Cliente para esta pantalla).
        $clientes         = $this->ventasModel->getForSelect();
        $metodosPago      = $this->ventasModel->getMetodosPago();
        $tiposComprobante = $this->ventasModel->getTiposComprobante();

        require_once __DIR__ . '/../views/inventario/ventas/crear.php';
    }

    /* ============================================================
     *  registrar  –  Registrar venta (POST/JSON)
     * ============================================================ */
    public function registrar(): void
    {
        SessionMiddleware::checkAuth();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido.']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !is_array($input)) {
                throw new \Exception('Datos de solicitud inválidos.');
            }

            $items = $input['items'] ?? [];
            if (empty($items)) {
                throw new \Exception('Debe agregar al menos un producto.');
            }

            foreach ($items as $item) {
                if (empty($item['id_producto']) || (int)$item['cantidad'] <= 0 || (float)$item['precio'] <= 0) {
                    throw new \Exception('Datos de producto inválidos.');
                }
            }

            $userId = (int)SessionMiddleware::getUserData('id');

            $data = [
                'id_cliente'         => $input['id_cliente']         ?? null,
                'id_usuario'         => $userId,
                'tipo_comprobante'   => $input['tipo_comprobante']   ?? 'boleta',
                'serie'              => $input['serie']              ?? null,
                'numero_comprobante' => $input['numero_comprobante'] ?? null,
                'descuento'          => (float)($input['descuento']  ?? 0),
                'forma_pago'         => $input['forma_pago']         ?? 'efectivo',
                'estado_pago'        => $input['estado_pago']        ?? 'pagado',
                'observaciones'      => $input['observaciones']      ?? null,
            ];

            $detalles = array_map(fn($item) => [
                'id_producto' => (int)$item['id_producto'],
                'cantidad'    => (int)$item['cantidad'],
                'precio'      => (float)$item['precio'],
                'descuento'   => (float)($item['descuento'] ?? 0),
            ], $items);

            $resultado = $this->ventasModel->registrarVenta($data, $detalles);
            echo json_encode($resultado);

        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /* ============================================================
     *  anular  –  Anular una venta
     * ============================================================ */
    public function anular(): void
    {
        SessionMiddleware::checkAuth();

        $idVenta  = (int)($_GET['id']     ?? 0);
        $motivo   = trim($_GET['motivo']  ?? '');

        if ($idVenta <= 0) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ventas&action=index',
                'Error', 'ID de venta no válido.', 'error'
            );
            return;
        }

        // CORRECCIÓN: obtener id_usuario de la sesión correctamente
        $idUsuario = (int)SessionMiddleware::getUserData('id');

        $resultado = $this->ventasModel->anularVenta($idVenta, $idUsuario, $motivo);

        if ($resultado['success']) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ventas&action=index',
                'Venta Anulada', $resultado['mensaje'], 'success'
            );
        } else {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=ventas&action=ver&id=' . $idVenta,
                'Error', $resultado['mensaje'], 'error'
            );
        }
    }

    /* ============================================================
     *  exportar  –  Exportar CSV
     * ============================================================ */
    public function exportar(): void
    {
        SessionMiddleware::checkAuth();

        $filters = [
            'fecha_desde' => $_GET['fecha_desde'] ?? date('Y-m-01'),
            'fecha_hasta' => $_GET['fecha_hasta'] ?? date('Y-m-d'),
            'estado'      => $_GET['estado']      ?? null,
            'tipo_pago'   => $_GET['tipo_pago']   ?? null,
            'busqueda'    => $_GET['busqueda']     ?? null,
        ];

        // CORRECCIÓN: método correcto en el modelo
        $datos = $this->ventasModel->getDataForExport($filters);

        $nombreArchivo = 'ventas_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, [
            'ID', 'Código', 'Fecha', 'Comprobante', 'Cliente', 'Documento',
            'Vendedor', 'Subtotal', 'IGV', 'Descuento', 'Total',
            'Forma Pago', 'Estado Pago', 'Estado',
        ], ';');

        foreach ($datos as $row) {
            fputcsv($output, [
                $row['id_venta'],
                $row['codigo'],
                $row['fecha_venta'],
                $row['comprobante'],
                $row['cliente'],
                $row['documento'],
                $row['vendedor'],
                number_format($row['subtotal'],  2, '.', ''),
                number_format($row['igv'],       2, '.', ''),
                number_format($row['descuento'], 2, '.', ''),
                number_format($row['total'],     2, '.', ''),
                $row['forma_pago'],
                $row['estado_pago'],
                $row['estado'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    /* ============================================================
     *  buscarProductos  –  AJAX para el campo de búsqueda en crear
     * ============================================================ */
    public function buscarProductos(): void
    {
        try {
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json');
            header('X-Content-Type-Options: nosniff');

            $termino = trim($_GET['q'] ?? '');

            if (strlen($termino) < 2) {
                echo json_encode([]);
                return;
            }

            $productos = $this->ventasModel->buscarProductosVenta($termino);
            echo json_encode(is_array($productos) ? $productos : []);

        } catch (\Exception $e) {
            error_log("VentasController::buscarProductos — " . $e->getMessage());
            echo json_encode(['error' => 'Error interno al buscar productos.']);
        }
    }

    /* ============================================================
     *  getProducto  –  AJAX: datos de un producto individual
     * ============================================================ */
    public function getProducto(): void
    {
        SessionMiddleware::checkAuth();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID inválido.']);
            return;
        }

        $producto = $this->ventasModel->getProductoById($id);
        if (!$producto) {
            echo json_encode(['success' => false, 'mensaje' => 'Producto no encontrado.']);
            return;
        }

        echo json_encode(['success' => true, 'producto' => $producto]);
    }

    /* ============================================================
     *  listarProductosModal  –  AJAX: productos paginados para modal
     * ============================================================ */
public function listarProductosModal(): void
{
    try {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Obtener productos
        $productos = $this->ventasModel->getProductosPaginados($search, $limit, $offset);
        $total = $this->ventasModel->getTotalProductosActivos($search);
        
        $totalPages = $limit > 0 ? (int)ceil($total / $limit) : 1;
        
        echo json_encode([
            'success' => true,
            'productos' => $productos,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $total,
                'per_page' => $limit,
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log("VentasController::listarProductosModal — " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

public function getSiguienteNumero(): void
{
    try {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        
        SessionMiddleware::startSession();
        
        $userId = SessionMiddleware::getUserData('id');
        if (!$userId) {
            echo json_encode([
                'success' => false,
                'error'   => 'Sesión no iniciada',
                'numero'  => '00000001'
            ]);
            return;
        }
        
        $tipo  = $_GET['tipo']  ?? 'boleta';
        $serie = $_GET['serie'] ?? 'B001';
        
        $siguienteNumero = $this->ventasModel->getSiguienteNumeroComprobante($tipo, $serie);
        
        echo json_encode(['success' => true, 'numero' => $siguienteNumero]);
        
    } catch (Exception $e) {
        error_log("Error en getSiguienteNumero: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error'   => $e->getMessage(),
            'numero'  => '00000001'
        ]);
    }
}

}