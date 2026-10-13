<?php
/**
 * ReporteController — VERSIÓN DEPURADA
 * ─────────────────────────────────────────────────────────────────────────────
 * Historial de correcciones — versión FINAL:
 *  1. require_once de SessionMiddleware agregado.
 *  2. Producto.php requerido: dashboard() lo necesita para getDashboardEstadisticas(),
 *     getAlertasCriticas() y getUltimosMovimientos().
 *  3. Almacen::getAll() llamado sin argumento.
 *  4. Ruta de dashboard.php apunta a la raíz del proyecto (/../dashboard.php).
 *  5. Casteo explícito (int) en $limit y $dias provenientes de $_GET.
 *  6. Variables $datos, $titulo y $filename inicializadas antes del switch.
 *  7. checkAuth() en el constructor reemplaza startSession().
 *  8. Flash de error en exportar() migrado a SessionMiddleware::redirect().
 *  9. index() como punto de entrada canónico; delega en dashboard().
 * 10. dashboard() reescrito con TODAS las variables que la vista necesita:
 *       · $estadisticas, $alertasCriticas, $alertasPendientes
 *       · $productosStockBajo, $ultimosMovimientos, $topVendidos
 *       · $movimientosPorDia, $topCategorias, $distribucionStock
 *       · $ventasPorMes, $rotacionInventario, $productosPorVencer
 * 11. [FIX FINAL] Métodos privados getMovimientosPorDia(), getTopCategorias(),
 *     getDistribucionStock(), getVentasPorMes(), getRotacionInventario() y
 *     getProductosPorVencer() añadidos al controlador para que sea autosuficiente
 *     cuando es él quien carga dashboard.php (URL: controller=reporte&action=index).
 *     CAUSA RAÍZ: la vista usaba $movimientosPorDia, $distribucionStock, etc.
 *     que sólo DashboardController declaraba, dejándolas undefined aquí.
 * 12. [FIX FINAL] calcularMargen() definida directamente en dashboard.php (inicio
 *     del archivo) con if(!function_exists()) — ya no depende de qué controlador
 *     cargue la vista. Soluciona "Call to undefined function calcularMargen()".
 */

require_once __DIR__ . '/../config/SessionMiddleware.php'; // [FIX 1]
require_once __DIR__ . '/../models/Reporte.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Producto.php';  // [FIX 2/10] Requerido por dashboard()
// [FIX 2] Proveedor.php eliminado — no se usa en ningún método

class ReporteController {

    private Reporte  $reporteModel;
    private Almacen  $almacenModel;
    private Producto $productoModel;

    public function __construct() {
        // [FIX 7] checkAuth() inicia sesión Y valida autenticación.
        // Si no hay sesión válida redirige a login.php automáticamente.
        SessionMiddleware::checkAuth();

        $this->reporteModel  = new Reporte();
        $this->almacenModel  = new Almacen();
        $this->productoModel = new Producto(); // [FIX 2/10]
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Punto de entrada canónico  [FIX 9]
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Delega en dashboard() — única fuente de verdad.
     * Salvaguarda ante llamadas directas o futuros cambios en el front controller.
     */
    public function index() {
        $this->dashboard();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de stock bajo
    // ─────────────────────────────────────────────────────────────────────────
    public function stock_bajo() {
        $nivel = $_GET['nivel'] ?? 'todos';
        $productosStockBajo = $this->reporteModel->getReporteStockBajo($nivel);
        require_once __DIR__ . '/../views/inventario/reportes/stock_bajo.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de rotación de inventario
    // ─────────────────────────────────────────────────────────────────────────
    public function rotacion() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01', strtotime('-1 month'));
        $fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');
        $rotacion    = $this->reporteModel->getRotacionInventario($fechaInicio, $fechaFin);
        require_once __DIR__ . '/../views/inventario/reportes/rotacion.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de valorización de inventario
    // ─────────────────────────────────────────────────────────────────────────
    public function valorizacion() {
        $valorizacion = $this->reporteModel->getValorizacionInventario();
        $almacenes    = $this->almacenModel->getAll(); // [FIX 3]
        require_once __DIR__ . '/../views/inventario/reportes/valorizacion.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de productos más vendidos
    // ─────────────────────────────────────────────────────────────────────────
    public function top_vendidos() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01', strtotime('-1 month'));
        $fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');
        $limit       = (int) ($_GET['limit'] ?? 10); // [FIX 5]
        $topVendidos = $this->reporteModel->getTopProductosVendidos($fechaInicio, $fechaFin, $limit);
        require_once __DIR__ . '/../views/inventario/reportes/top_vendidos.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de movimientos por día
    // ─────────────────────────────────────────────────────────────────────────
    public function movimientos_dia() {
        $fechaInicio       = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin          = $_GET['fecha_fin']    ?? date('Y-m-d');
        $movimientosPorDia = $this->reporteModel->getMovimientosPorDia($fechaInicio, $fechaFin);
        require_once __DIR__ . '/../views/inventario/reportes/movimientos_dia.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de movimientos por proveedor
    // ─────────────────────────────────────────────────────────────────────────
    public function proveedores() {
        $fechaInicio          = $_GET['fecha_inicio'] ?? date('Y-m-01', strtotime('-3 months'));
        $fechaFin             = $_GET['fecha_fin']    ?? date('Y-m-d');
        $movimientosProveedor = $this->reporteModel->getMovimientosPorProveedor($fechaInicio, $fechaFin);
        require_once __DIR__ . '/../views/inventario/reportes/proveedores.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de productos sin movimiento
    // ─────────────────────────────────────────────────────────────────────────
    public function productos_sin_movimiento() {
        $dias      = (int) ($_GET['dias'] ?? 30); // [FIX 5]
        $productos = $this->reporteModel->getProductosSinMovimiento($dias);
        require_once __DIR__ . '/../views/inventario/reportes/productos_sin_movimiento.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Exportar reporte a Excel / PDF
    // ─────────────────────────────────────────────────────────────────────────
    public function exportar() {
        $tipo    = $_GET['tipo']    ?? 'stock_bajo';
        $formato = $_GET['formato'] ?? 'excel';

        // [FIX 6] Variables inicializadas antes del switch
        $datos    = [];
        $filename = 'reporte_' . date('Ymd_His');
        $titulo   = 'Reporte';

        switch ($tipo) {
            case 'stock_bajo':
                $nivel    = $_GET['nivel'] ?? 'todos';
                $datos    = $this->reporteModel->getReporteStockBajo($nivel);
                $filename = 'reporte_stock_bajo_' . date('Ymd_His');
                $titulo   = 'Reporte de Stock Bajo';
                break;

            case 'rotacion':
                $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01', strtotime('-1 month'));
                $fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');
                $datos       = $this->reporteModel->getRotacionInventario($fechaInicio, $fechaFin);
                $filename    = 'reporte_rotacion_' . date('Ymd_His');
                $titulo      = 'Reporte de Rotación de Inventario';
                break;

            case 'valorizacion':
                $datos    = $this->reporteModel->getValorizacionInventario();
                $filename = 'reporte_valorizacion_' . date('Ymd_His');
                $titulo   = 'Reporte de Valorización de Inventario';
                break;

            default:
                // [FIX 8] API de SessionMiddleware en lugar de $_SESSION directo
                SessionMiddleware::redirect(
                    'index.php?controller=reporte&action=stock_bajo',
                    'Tipo de reporte no válido',
                    'danger'
                );
        }

        if ($formato === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            header('Cache-Control: max-age=0');
            require_once __DIR__ . '/../views/inventario/exportar_excel.php';

        } elseif ($formato === 'pdf') {
            // Implementación futura con TCPDF
            echo '<h2>' . htmlspecialchars($titulo) . '</h2>';
            echo '<pre>' . htmlspecialchars(print_r($datos, true)) . '</pre>';
        }

        exit();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Dashboard  [FIX 10]
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * CAUSA RAÍZ de todos los errores visibles en producción:
     *
     * El código anterior (a) empaquetaba resultados en $data[] en lugar de
     * variables sueltas, y (b) omitía 3 de las 6 consultas que la vista
     * necesita. La vista dashboard.php espera siempre variables individuales.
     *
     * Variables y su origen:
     *   $estadisticas       → Producto::getDashboardEstadisticas()
     *                         Claves: total_productos, total_unidades,
     *                                 productos_bajos, productos_criticos,
     *                                 valor_total_compra, valor_total_venta
     *   $alertasCriticas    → Producto::getAlertasCriticas(5)
     *   $alertasPendientes  → count($alertasCriticas)
     *   $productosStockBajo → Reporte::getReporteStockBajo('todos')
     *   $topVendidos        → Reporte::getTopProductosVendidos()
     *                         Claves SQL: total_vendido, total_ingresos
     *                         (ver FIX C en dashboard.php)
     *   $ultimosMovimientos → Producto::getUltimosMovimientos(10)
     *
     * [FIX 4-REVERTIDO] dashboard.php se encuentra en la RAÍZ del proyecto.
     * La corrección anterior apuntaba a views/inventario/reportes/dashboard.php
     * (inexistente) y era la razón directa de que los errores persistieran.
     */
    public function dashboard() {
        $fechaInicioMes = date('Y-m-01');
        $fechaFin       = date('Y-m-d');

        // ── 1. Estadísticas principales ───────────────────────────────────────
        $estadisticas = $this->productoModel->getDashboardEstadisticas() ?? [
            'total_productos'    => 0,
            'total_unidades'     => 0,
            'productos_bajos'    => 0,
            'productos_criticos' => 0,
            'valor_total_compra' => 0,
            'valor_total_venta'  => 0,
        ];

        // ── 2. Alertas ────────────────────────────────────────────────────────
        $alertasCriticas   = $this->productoModel->getAlertasCriticas(5);
        $alertasPendientes = count($alertasCriticas);

        // ── 3. Tablas y listados ──────────────────────────────────────────────
        $productosStockBajo = $this->reporteModel->getReporteStockBajo('todos');
        $ultimosMovimientos = $this->productoModel->getUltimosMovimientos(10);

        // Top 5 vendidos del mes — alias: total_vendido, total_ingresos
        $topVendidos = $this->reporteModel->getTopProductosVendidos(
            $fechaInicioMes, $fechaFin, 5
        );

        // ── 4. Variables para gráficos ────────────────────────────────────────
        // La vista dashboard.php usa estas 4 variables en Chart.js.
        // Se calculan aquí porque ReporteController es quien carga la vista.
        $movimientosPorDia = $this->getMovimientosPorDia(7);
        $topCategorias     = $this->getTopCategorias(5);
        $distribucionStock = $this->getDistribucionStock();
        $ventasPorMes      = $this->getVentasPorMes(6);

        // ── 5. Métricas adicionales ───────────────────────────────────────────
        $rotacionInventario = $this->getRotacionInventario();
        $productosPorVencer = $this->getProductosPorVencer(5);

        require_once __DIR__ . '/../dashboard.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados de cálculo para el dashboard
    // (Duplicados desde DashboardController para que ReporteController sea
    //  autosuficiente cuando es él quien carga dashboard.php)
    // ─────────────────────────────────────────────────────────────────────────

    private function getMovimientosPorDia(int $dias = 7): array {
        try {
            $db   = $this->productoModel->getDB();
            $stmt = $db->prepare(
                "SELECT DATE(fecha_movimiento) AS fecha,
                        SUM(CASE WHEN tipo_movimiento IN ('entrada','entrada_inicial') THEN cantidad ELSE 0 END) AS entradas,
                        SUM(CASE WHEN tipo_movimiento = 'salida' THEN cantidad ELSE 0 END) AS salidas
                 FROM movimientos_inventario
                 WHERE fecha_movimiento >= DATE_SUB(NOW(), INTERVAL ? DAY)
                 GROUP BY DATE(fecha_movimiento)
                 ORDER BY fecha ASC"
            );
            $stmt->execute([$dias]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $mapa = [];
            for ($i = $dias - 1; $i >= 0; $i--) {
                $mapa[date('Y-m-d', strtotime("-{$i} days"))] = ['entradas' => 0, 'salidas' => 0];
            }
            foreach ($result as $row) {
                $mapa[$row['fecha']] = [(int)$row['entradas'], (int)$row['salidas']];
                $mapa[$row['fecha']] = ['entradas' => (int)$row['entradas'], 'salidas' => (int)$row['salidas']];
            }

            $labels = $entradas = $salidas = [];
            foreach ($mapa as $fecha => $datos) {
                $labels[]   = date('d/m', strtotime($fecha));
                $entradas[] = $datos['entradas'];
                $salidas[]  = $datos['salidas'];
            }
            return ['labels' => $labels, 'entradas' => $entradas, 'salidas' => $salidas];
        } catch (PDOException $e) {
            error_log("ReporteController::getMovimientosPorDia — " . $e->getMessage());
            return ['labels' => [], 'entradas' => [], 'salidas' => []];
        }
    }

    private function getTopCategorias(int $limite = 5): array {
        try {
            $db   = $this->productoModel->getDB();
            $stmt = $db->prepare(
                "SELECT c.nombre AS categoria,
                        COUNT(p.id_producto) AS total_productos,
                        COALESCE(SUM(p.stock_actual), 0) AS total_unidades,
                        COALESCE(SUM(p.stock_actual * p.precio_venta), 0) AS valor_inventario
                 FROM categorias c
                 LEFT JOIN productos p ON c.id_categoria = p.id_categoria AND p.estado = 'activo'
                 WHERE c.estado = 'activo'
                 GROUP BY c.id_categoria, c.nombre
                 ORDER BY total_productos DESC
                 LIMIT ?"
            );
            $stmt->execute([$limite]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'labels'   => array_column($result, 'categoria'),
                'values'   => array_column($result, 'total_productos'),
                'unidades' => array_column($result, 'total_unidades'),
                'valores'  => array_column($result, 'valor_inventario'),
            ];
        } catch (PDOException $e) {
            error_log("ReporteController::getTopCategorias — " . $e->getMessage());
            return ['labels' => [], 'values' => [], 'unidades' => [], 'valores' => []];
        }
    }

    private function getDistribucionStock(): array {
        try {
            $db     = $this->productoModel->getDB();
            $stmt   = $db->query(
                "SELECT estado_stock,
                        COUNT(*) AS cantidad,
                        COALESCE(SUM(stock_actual), 0) AS total_unidades
                 FROM productos WHERE estado = 'activo'
                 GROUP BY estado_stock"
            );
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $estados = [
                'normal'  => ['nombre' => 'Normal',  'color' => '#28a745', 'cantidad' => 0, 'unidades' => 0],
                'bajo'    => ['nombre' => 'Bajo',    'color' => '#ffc107', 'cantidad' => 0, 'unidades' => 0],
                'critico' => ['nombre' => 'Crítico', 'color' => '#dc3545', 'cantidad' => 0, 'unidades' => 0],
                'agotado' => ['nombre' => 'Agotado', 'color' => '#6c757d', 'cantidad' => 0, 'unidades' => 0],
            ];
            foreach ($result as $row) {
                if (isset($estados[$row['estado_stock']])) {
                    $estados[$row['estado_stock']]['cantidad'] = (int)$row['cantidad'];
                    $estados[$row['estado_stock']]['unidades'] = (int)$row['total_unidades'];
                }
            }
            return $estados;
        } catch (PDOException $e) {
            error_log("ReporteController::getDistribucionStock — " . $e->getMessage());
            return [];
        }
    }

    private function getVentasPorMes(int $meses = 6): array {
        try {
            $db   = $this->productoModel->getDB();
            $stmt = $db->prepare(
                "SELECT DATE_FORMAT(fecha_movimiento, '%Y-%m') AS mes,
                        DATE_FORMAT(fecha_movimiento, '%b %Y') AS mes_nombre,
                        SUM(cantidad) AS total_unidades,
                        SUM(cantidad * precio_unitario) AS total_valor
                 FROM movimientos_inventario
                 WHERE tipo_movimiento = 'salida'
                   AND fecha_movimiento >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                 GROUP BY DATE_FORMAT(fecha_movimiento, '%Y-%m'), DATE_FORMAT(fecha_movimiento, '%b %Y')
                 ORDER BY mes ASC"
            );
            $stmt->execute([$meses]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'labels'   => array_column($result, 'mes_nombre'),
                'unidades' => array_column($result, 'total_unidades'),
                'valores'  => array_column($result, 'total_valor'),
            ];
        } catch (PDOException $e) {
            error_log("ReporteController::getVentasPorMes — " . $e->getMessage());
            return ['labels' => [], 'unidades' => [], 'valores' => []];
        }
    }

    private function getRotacionInventario(): array {
        try {
            $db     = $this->productoModel->getDB();
            $ventas = $db->query(
                "SELECT COALESCE(SUM(cantidad), 0) AS total_vendido
                 FROM movimientos_inventario
                 WHERE tipo_movimiento = 'salida'
                   AND fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )->fetch(PDO::FETCH_ASSOC);

            $stock = $db->query(
                "SELECT COALESCE(AVG(stock_actual), 0) AS stock_promedio
                 FROM productos WHERE estado = 'activo'"
            )->fetch(PDO::FETCH_ASSOC);

            $rotacion = $stock['stock_promedio'] > 0
                ? round($ventas['total_vendido'] / $stock['stock_promedio'], 2)
                : 0.0;

            if      ($rotacion >= 3) { $clasificacion = 'Alta';  $color = 'success'; }
            elseif  ($rotacion >= 1) { $clasificacion = 'Media'; $color = 'warning'; }
            else                     { $clasificacion = 'Baja';  $color = 'danger';  }

            return [
                'rotacion'      => $rotacion,
                'clasificacion' => $clasificacion,
                'color'         => $color,
                'total_vendido' => (int)$ventas['total_vendido'],
                'stock_promedio'=> round((float)$stock['stock_promedio'], 2),
            ];
        } catch (PDOException $e) {
            error_log("ReporteController::getRotacionInventario — " . $e->getMessage());
            return ['rotacion' => 0, 'clasificacion' => 'Sin datos', 'color' => 'secondary'];
        }
    }

    private function getProductosPorVencer(int $limite = 5): array {
        try {
            $db   = $this->productoModel->getDB();
            $stmt = $db->prepare(
                "SELECT p.id_producto, p.codigo, p.nombre, p.stock_actual,
                        p.fecha_vencimiento,
                        DATEDIFF(p.fecha_vencimiento, CURDATE()) AS dias_restantes
                 FROM productos p
                 WHERE p.fecha_vencimiento IS NOT NULL
                   AND p.estado = 'activo'
                   AND p.stock_actual > 0
                   AND DATEDIFF(p.fecha_vencimiento, CURDATE()) <= 30
                 ORDER BY p.fecha_vencimiento ASC
                 LIMIT ?"
            );
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ReporteController::getProductosPorVencer — " . $e->getMessage());
            return [];
        }
    }
}
?>