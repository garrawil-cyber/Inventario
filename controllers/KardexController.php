<?php
/**
 * KardexController — Módulo Kardex (inventario valorizado)
 *
 * Módulo de SOLO LECTURA: el kardex lo alimenta automáticamente el trigger
 * `trg_actualizar_kardex_after_movimiento` cada vez que se inserta en
 * `movimientos_inventario`. Aquí solo se consulta y se presenta.
 *
 * Acciones (ver config/permissions.php):
 *   index            → resumen general valorizado de todos los productos
 *   producto($id)    → ficha + libro kardex detallado de un producto
 *   almacen($id)     → movimientos de kardex de todos los productos en un almacén
 *   buscar_productos → AJAX: autocomplete para el buscador de productos
 *   resumen_rapido   → AJAX: saldo actual de un producto (preview del autocomplete)
 *   costo_promedio   → alias de index(), enfocado en el reporte de costo promedio
 *   exportar($id)    → exporta a Excel el kardex filtrado de un producto
 *   pdf($id)         → vista imprimible (Ctrl+P / Guardar como PDF) del kardex
 */

require_once __DIR__ . '/../config/SessionMiddleware.php';
require_once __DIR__ . '/../models/Kardex.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Almacen.php';

class KardexController {

    private Kardex $kardexModel;
    private Producto $productoModel;
    private Almacen $almacenModel;

    public function __construct() {
        SessionMiddleware::checkAuth();
        $this->kardexModel   = new Kardex();
        $this->productoModel = new Producto();
        $this->almacenModel  = new Almacen();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Resumen general: todos los productos con su valorización actual
    // ─────────────────────────────────────────────────────────────────────
    public function index() {
        $filtros = [];
        if (!empty($_GET['q']))              $filtros['q'] = trim($_GET['q']);
        if (!empty($_GET['id_categoria']))   $filtros['id_categoria'] = (int) $_GET['id_categoria'];
        if (!empty($_GET['sin_movimiento'])) $filtros['sin_movimiento'] = $_GET['sin_movimiento'];
        if (!empty($_GET['orden']))          $filtros['orden'] = $_GET['orden'];

        $resumen       = $this->kardexModel->getResumenGeneral($filtros);
        $estadisticas  = $this->kardexModel->getEstadisticasGenerales();

        require_once __DIR__ . '/../views/inventario/kardex/index.php';
    }

    // Alias semántico pedido por permissions.php — mismo reporte, mismo
    // resumen valorizado (que ya muestra el costo promedio por producto).
    public function costo_promedio() {
        $this->index();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Libro Kardex de un producto (PEPS / promedio ponderado móvil)
    // ─────────────────────────────────────────────────────────────────────
    public function producto($idProducto = null) {
        $idProducto = (int) ($idProducto ?? ($_GET['id'] ?? 0));

        if (!$idProducto) {
            SessionMiddleware::redirect(
                'index.php?controller=kardex&action=index',
                'Debe seleccionar un producto para ver su kardex.',
                'warning'
            );
        }

        $producto = $this->kardexModel->getProductoConDetalle($idProducto);
        if (!$producto) {
            SessionMiddleware::redirect(
                'index.php?controller=kardex&action=index',
                'El producto solicitado no existe.',
                'danger'
            );
        }

        // Rango de fechas: por defecto, todo el histórico disponible
        $fechaInicio = $_GET['fecha_desde'] ?? '2020-01-01';
        $fechaFin    = $_GET['fecha_hasta'] ?? date('Y-m-d');
        $idAlmacen   = !empty($_GET['id_almacen']) ? (int) $_GET['id_almacen'] : null;
        $tipoFiltro  = $_GET['tipo_movimiento'] ?? '';

        $kardex = $this->kardexModel->getKardexProducto($idProducto, $fechaInicio, $fechaFin, $idAlmacen);

        if ($tipoFiltro !== '' && in_array($tipoFiltro, ['entrada', 'salida', 'ajuste'], true)) {
            $kardex = array_values(array_filter(
                $kardex,
                fn($k) => ($k['tipo_movimiento'] ?? '') === $tipoFiltro
            ));
        }

        // Saldo real vigente (independiente del filtro de fechas aplicado)
        $saldoActual = $this->kardexModel->getSaldoActual($idProducto);

        // KPIs del período filtrado
        $kpi = ['entradas_cant' => 0, 'entradas_val' => 0.0, 'salidas_cant' => 0, 'salidas_val' => 0.0];
        foreach ($kardex as $k) {
            $kpi['entradas_cant'] += (int) ($k['entrada_cantidad'] ?? 0);
            $kpi['entradas_val']  += (float) ($k['entrada_costo_total'] ?? 0);
            $kpi['salidas_cant']  += (int) ($k['salida_cantidad'] ?? 0);
            $kpi['salidas_val']   += (float) ($k['salida_costo_total'] ?? 0);
        }

        $almacenes = $this->almacenModel->getForFilters();

        require_once __DIR__ . '/../views/inventario/kardex/producto.php';
    }

    // ─────────────────────────────────────────────────────────────────────
    // Kardex de todos los productos movidos en un almacén específico
    // ─────────────────────────────────────────────────────────────────────
    public function almacen($idAlmacen = null) {
        $idAlmacen = (int) ($idAlmacen ?? ($_GET['id'] ?? 0));
        $almacenes = $this->almacenModel->getForFilters();

        $almacenSel = null;
        $kardex = [];

        if ($idAlmacen) {
            foreach ($almacenes as $a) {
                if ((int) ($a['id_almacen'] ?? 0) === $idAlmacen) { $almacenSel = $a; break; }
            }

            $fechaInicio = $_GET['fecha_desde'] ?? date('Y-m-01');
            $fechaFin    = $_GET['fecha_hasta'] ?? date('Y-m-d');
            $kardex = $this->kardexModel->getKardexPorAlmacen($idAlmacen, $fechaInicio, $fechaFin);
        }

        require_once __DIR__ . '/../views/inventario/kardex/almacen.php';
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX — Autocomplete del buscador de productos
    // ─────────────────────────────────────────────────────────────────────
    public function buscar_productos() {
        header('Content-Type: application/json; charset=utf-8');

        if (!SessionMiddleware::isAjaxRequest()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Solicitud inválida']);
            exit();
        }

        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) {
            echo json_encode(['success' => true, 'productos' => []]);
            exit();
        }

        $productos = $this->kardexModel->buscarProductos($q);
        echo json_encode(['success' => true, 'productos' => $productos]);
        exit();
    }

    // ─────────────────────────────────────────────────────────────────────
    // AJAX — Preview rápido del saldo valorizado de un producto
    // ─────────────────────────────────────────────────────────────────────
    public function resumen_rapido($idProducto = null) {
        header('Content-Type: application/json; charset=utf-8');

        if (!SessionMiddleware::isAjaxRequest()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Solicitud inválida']);
            exit();
        }

        $idProducto = (int) ($idProducto ?? ($_GET['id'] ?? 0));
        if (!$idProducto) {
            echo json_encode(['success' => false, 'error' => 'Producto no especificado']);
            exit();
        }

        $saldo = $this->kardexModel->getSaldoActual($idProducto);
        echo json_encode([
            'success' => true,
            'saldo'   => $saldo ?: [
                'saldo_cantidad' => 0, 'saldo_costo_unitario' => 0, 'saldo_costo_total' => 0,
            ],
        ]);
        exit();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Exportar a Excel el kardex filtrado de un producto
    // ─────────────────────────────────────────────────────────────────────
    public function exportar($idProducto = null) {
        $idProducto = (int) ($idProducto ?? ($_GET['id'] ?? 0));
        $producto = $this->kardexModel->getProductoConDetalle($idProducto);

        if (!$producto) {
            SessionMiddleware::redirect(
                'index.php?controller=kardex&action=index',
                'Producto no encontrado para exportar.',
                'danger'
            );
        }

        $fechaInicio = $_GET['fecha_desde'] ?? '2020-01-01';
        $fechaFin    = $_GET['fecha_hasta'] ?? date('Y-m-d');
        $idAlmacen   = !empty($_GET['id_almacen']) ? (int) $_GET['id_almacen'] : null;

        $kardex = $this->kardexModel->getKardexProducto($idProducto, $fechaInicio, $fechaFin, $idAlmacen);

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="kardex_' . $producto['codigo'] . '_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');

        require_once __DIR__ . '/../views/inventario/kardex/exportar.php';
        exit();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Vista imprimible (el usuario usa Ctrl+P → Guardar como PDF)
    // ─────────────────────────────────────────────────────────────────────
    public function pdf($idProducto = null) {
        $idProducto = (int) ($idProducto ?? ($_GET['id'] ?? 0));
        $producto = $this->kardexModel->getProductoConDetalle($idProducto);

        if (!$producto) {
            SessionMiddleware::redirect(
                'index.php?controller=kardex&action=index',
                'Producto no encontrado.',
                'danger'
            );
        }

        $fechaInicio = $_GET['fecha_desde'] ?? '2020-01-01';
        $fechaFin    = $_GET['fecha_hasta'] ?? date('Y-m-d');
        $idAlmacen   = !empty($_GET['id_almacen']) ? (int) $_GET['id_almacen'] : null;

        $kardex      = $this->kardexModel->getKardexProducto($idProducto, $fechaInicio, $fechaFin, $idAlmacen);
        $saldoActual = $this->kardexModel->getSaldoActual($idProducto);

        require_once __DIR__ . '/../views/inventario/kardex/pdf.php';
    }
}