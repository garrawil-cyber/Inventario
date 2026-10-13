<?php
/**
 * DashboardController.php — VERSIÓN DEPURADA
 * ─────────────────────────────────────────────────────────────────────────────
 * Correcciones aplicadas:
 *  1. checkAuth() movido al inicio del constructor (antes de instanciar modelos)
 *     para cortar la ejecución inmediatamente si no hay sesión válida.
 *  2. calcularMargen() añadido como método PÚBLICO estático para que pueda
 *     ser llamado desde la vista dashboard.php sin necesidad de $this.
 *     CAUSA RAÍZ del fatal error: "Call to undefined method
 *     ReporteController::calcularMargen()" — la vista llama
 *     $this->calcularMargen() pero $this no existe en el scope de una vista.
 *  3. getTopProductosVendidos() del modelo Producto recibe los parámetros en
 *     el orden correcto: (int $limite, int $dias).
 *  4. Todos los métodos privados tipan sus parámetros y retornos.
 *  5. getProductosStockBajo() del modelo Producto recibe sólo $limite (int).
 *  6. Se agrega método calcularRangoDias() para centralizar la lógica
 *     del selector de rango evitando duplicación en get_datos_graficos().
 *  7. index() inicializa $estadisticas con fallback seguro para evitar
 *     "Trying to access array offset on value of type null".
 *  8. calcularMargen() se expone como helper function global al final del
 *     archivo para máxima compatibilidad con la vista (PHP puro sin clases).
 */

require_once __DIR__ . '/../config/SessionMiddleware.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Alerta.php';
require_once __DIR__ . '/../models/Movimiento.php';
require_once __DIR__ . '/../models/Reporte.php';

class DashboardController {

    private Producto  $productoModel;
    private Alerta    $alertaModel;
    private Movimiento $movimientoModel;
    private Reporte   $reporteModel;

    public function __construct() {
        // [FIX 1] Auth PRIMERO — si no hay sesión corta sin instanciar nada
        SessionMiddleware::checkAuth();

        $this->productoModel   = new Producto();
        $this->alertaModel     = new Alerta();
        $this->movimientoModel = new Movimiento();
        $this->reporteModel    = new Reporte();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Punto de entrada principal
    // ─────────────────────────────────────────────────────────────────────────
    public function index(): void {

        // ── 1. Estadísticas principales ──────────────────────────────────────
        // [FIX 7] Fallback completo si el modelo devuelve null
        $estadisticas = $this->productoModel->getDashboardEstadisticas() ?? [
            'total_productos'    => 0,
            'total_unidades'     => 0,
            'productos_bajos'    => 0,
            'productos_criticos' => 0,
            'valor_total_compra' => 0.0,
            'valor_total_venta'  => 0.0,
        ];

        // ── 2. Datos para gráficos ────────────────────────────────────────────
        $movimientosPorDia = $this->getMovimientosPorDia(7);
        $topCategorias     = $this->getTopCategorias(5);
        $distribucionStock = $this->getDistribucionStock();
        $ventasPorMes      = $this->getVentasPorMes(6);

        // ── 3. Tablas y listados ──────────────────────────────────────────────
        // [FIX 5] getProductosStockBajo sólo acepta $limite
        $productosStockBajo = $this->productoModel->getProductosStockBajo(10);
        $ultimosMovimientos = $this->productoModel->getUltimosMovimientos(10);
        $alertasCriticas    = $this->productoModel->getAlertasCriticas(5);

        // [FIX 3] getTopProductosVendidos($limite, $dias) — orden correcto
        $topVendidos = $this->productoModel->getTopProductosVendidos(5, 30);

        // ── 4. Métricas adicionales ───────────────────────────────────────────
        $alertasPendientes   = $this->alertaModel->countAlertasPendientes();
        $productosPorVencer  = $this->getProductosPorVencer(5);
        $valorPorCategoria   = $this->getValorInventarioPorCategoria();
        $rotacionInventario  = $this->getRotacionInventario();

        // ── 5. Selector de rango ──────────────────────────────────────────────
        $rango = $_GET['rango'] ?? 'semana';
        $fechas = $this->calcularRangoFechas($rango);

        // ── 6. Cargar vista ───────────────────────────────────────────────────
        require_once __DIR__ . '/../dashboard.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Endpoint AJAX — actualiza gráficos según rango
    // ─────────────────────────────────────────────────────────────────────────
    public function get_datos_graficos(): void {
        header('Content-Type: application/json');

        $rango  = $_GET['rango'] ?? 'semana';
        $fechas = $this->calcularRangoFechas($rango);
        $dias   = $this->calcularRangoDias($rango);         // [FIX 6]

        echo json_encode([
            'success'     => true,
            'movimientos' => $this->getMovimientosPorDia($dias),
            'ventas'      => $this->getVentasPorMes($rango === 'trimestre' ? 3 : 6),
            'fechas'      => $fechas,
        ]);
        exit();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // [FIX 2] calcularMargen — PÚBLICO ESTÁTICO
    // La vista NO puede usar $this; con static puede llamarlo como
    // DashboardController::calcularMargen($venta, $compra)
    // O bien usar la función global calcularMargen() definida al pie.
    // ─────────────────────────────────────────────────────────────────────────
    public static function calcularMargen(float $venta, float $compra): string {
        if ($compra <= 0.0) return '0.0';
        return number_format((($venta - $compra) / $compra) * 100, 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Métodos privados — consultas SQL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Movimientos agrupados por día.
     */
    private function getMovimientosPorDia(int $dias = 7): array {
        try {
            $db  = $this->productoModel->getDB();
            $sql = "SELECT
                        DATE(fecha_movimiento) AS fecha,
                        SUM(CASE WHEN tipo_movimiento IN ('entrada','entrada_inicial') THEN cantidad ELSE 0 END) AS entradas,
                        SUM(CASE WHEN tipo_movimiento = 'salida' THEN cantidad ELSE 0 END) AS salidas
                    FROM movimientos_inventario
                    WHERE fecha_movimiento >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(fecha_movimiento)
                    ORDER BY fecha ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([$dias]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Rellenar días sin movimientos para que el gráfico no tenga huecos
            $mapa = [];
            for ($i = $dias - 1; $i >= 0; $i--) {
                $mapa[date('Y-m-d', strtotime("-{$i} days"))] = ['entradas' => 0, 'salidas' => 0];
            }
            foreach ($result as $row) {
                $mapa[$row['fecha']] = [
                    'entradas' => (int) $row['entradas'],
                    'salidas'  => (int) $row['salidas'],
                ];
            }

            $labels   = [];
            $entradas = [];
            $salidas  = [];
            foreach ($mapa as $fecha => $datos) {
                $labels[]   = date('d/m', strtotime($fecha));
                $entradas[] = $datos['entradas'];
                $salidas[]  = $datos['salidas'];
            }

            return ['labels' => $labels, 'entradas' => $entradas, 'salidas' => $salidas];

        } catch (PDOException $e) {
            error_log("Dashboard::getMovimientosPorDia — " . $e->getMessage());
            return ['labels' => [], 'entradas' => [], 'salidas' => []];
        }
    }

    /**
     * Top categorías con más productos.
     */
    private function getTopCategorias(int $limite = 5): array {
        try {
            $db  = $this->productoModel->getDB();
            $sql = "SELECT
                        c.nombre                                      AS categoria,
                        COUNT(p.id_producto)                          AS total_productos,
                        COALESCE(SUM(p.stock_actual), 0)              AS total_unidades,
                        COALESCE(SUM(p.stock_actual * p.precio_venta), 0) AS valor_inventario
                    FROM categorias c
                    LEFT JOIN productos p
                        ON c.id_categoria = p.id_categoria AND p.estado = 'activo'
                    WHERE c.estado = 'activo'
                    GROUP BY c.id_categoria, c.nombre
                    ORDER BY total_productos DESC
                    LIMIT ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$limite]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'labels'   => array_column($result, 'categoria'),
                'values'   => array_column($result, 'total_productos'),
                'unidades' => array_column($result, 'total_unidades'),
                'valores'  => array_column($result, 'valor_inventario'),
            ];

        } catch (PDOException $e) {
            error_log("Dashboard::getTopCategorias — " . $e->getMessage());
            return ['labels' => [], 'values' => [], 'unidades' => [], 'valores' => []];
        }
    }

    /**
     * Distribución de stock por estado.
     */
    private function getDistribucionStock(): array {
        try {
            $db  = $this->productoModel->getDB();
            $sql = "SELECT
                        estado_stock,
                        COUNT(*)                          AS cantidad,
                        COALESCE(SUM(stock_actual), 0)    AS total_unidades
                    FROM productos
                    WHERE estado = 'activo'
                    GROUP BY estado_stock";

            $stmt   = $db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $estados = [
                'normal'  => ['nombre' => 'Normal',   'color' => '#28a745', 'cantidad' => 0, 'unidades' => 0],
                'bajo'    => ['nombre' => 'Bajo',      'color' => '#ffc107', 'cantidad' => 0, 'unidades' => 0],
                'critico' => ['nombre' => 'Crítico',   'color' => '#dc3545', 'cantidad' => 0, 'unidades' => 0],
                'agotado' => ['nombre' => 'Agotado',   'color' => '#6c757d', 'cantidad' => 0, 'unidades' => 0],
            ];

            foreach ($result as $row) {
                if (isset($estados[$row['estado_stock']])) {
                    $estados[$row['estado_stock']]['cantidad'] = (int) $row['cantidad'];
                    $estados[$row['estado_stock']]['unidades'] = (int) $row['total_unidades'];
                }
            }

            return $estados;

        } catch (PDOException $e) {
            error_log("Dashboard::getDistribucionStock — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ventas agrupadas por mes.
     */
    private function getVentasPorMes(int $meses = 6): array {
        try {
            $db  = $this->productoModel->getDB();
            $sql = "SELECT
                        DATE_FORMAT(fecha_movimiento, '%Y-%m')   AS mes,
                        DATE_FORMAT(fecha_movimiento, '%b %Y')   AS mes_nombre,
                        SUM(cantidad)                            AS total_unidades,
                        SUM(cantidad * precio_unitario)          AS total_valor
                    FROM movimientos_inventario
                    WHERE tipo_movimiento = 'salida'
                        AND fecha_movimiento >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                    GROUP BY DATE_FORMAT(fecha_movimiento, '%Y-%m'),
                             DATE_FORMAT(fecha_movimiento, '%b %Y')
                    ORDER BY mes ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([$meses]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'labels'   => array_column($result, 'mes_nombre'),
                'unidades' => array_column($result, 'total_unidades'),
                'valores'  => array_column($result, 'total_valor'),
            ];

        } catch (PDOException $e) {
            error_log("Dashboard::getVentasPorMes — " . $e->getMessage());
            return ['labels' => [], 'unidades' => [], 'valores' => []];
        }
    }

    /**
     * Productos próximos a vencer (con fecha_vencimiento).
     */
    private function getProductosPorVencer(int $limite = 5): array {
        try {
            $db  = $this->productoModel->getDB();
            $sql = "SELECT
                        p.id_producto,
                        p.codigo,
                        p.nombre,
                        p.stock_actual,
                        p.fecha_vencimiento,
                        DATEDIFF(p.fecha_vencimiento, CURDATE()) AS dias_restantes,
                        CASE
                            WHEN DATEDIFF(p.fecha_vencimiento, CURDATE()) <= 0  THEN 'Vencido'
                            WHEN DATEDIFF(p.fecha_vencimiento, CURDATE()) <= 7  THEN 'Por vencer (7 días)'
                            WHEN DATEDIFF(p.fecha_vencimiento, CURDATE()) <= 30 THEN 'Por vencer (30 días)'
                            ELSE 'Vigente'
                        END AS estado_vencimiento
                    FROM productos p
                    WHERE p.fecha_vencimiento IS NOT NULL
                        AND p.estado = 'activo'
                        AND p.stock_actual > 0
                        AND DATEDIFF(p.fecha_vencimiento, CURDATE()) <= 30
                    ORDER BY p.fecha_vencimiento ASC
                    LIMIT ?";

            $stmt = $db->prepare($sql);
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Dashboard::getProductosPorVencer — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valor del inventario desglosado por categoría.
     */
    private function getValorInventarioPorCategoria(): array {
        try {
            $db  = $this->productoModel->getDB();
            $sql = "SELECT
                        c.nombre                                          AS categoria,
                        COALESCE(SUM(p.stock_actual * p.precio_venta),  0) AS valor_venta,
                        COALESCE(SUM(p.stock_actual * p.precio_compra), 0) AS valor_compra
                    FROM categorias c
                    LEFT JOIN productos p
                        ON c.id_categoria = p.id_categoria AND p.estado = 'activo'
                    WHERE c.estado = 'activo'
                    GROUP BY c.id_categoria, c.nombre
                    HAVING valor_venta > 0
                    ORDER BY valor_venta DESC
                    LIMIT 8";

            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Dashboard::getValorInventarioPorCategoria — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Rotación general de inventario (ventas / stock promedio del último mes).
     */
    private function getRotacionInventario(): array {
        try {
            $db = $this->productoModel->getDB();

            $stmt  = $db->query(
                "SELECT COALESCE(SUM(cantidad), 0) AS total_vendido
                 FROM movimientos_inventario
                 WHERE tipo_movimiento = 'salida'
                   AND fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $ventas = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt  = $db->query(
                "SELECT COALESCE(AVG(stock_actual), 0) AS stock_promedio
                 FROM productos
                 WHERE estado = 'activo'"
            );
            $stock = $stmt->fetch(PDO::FETCH_ASSOC);

            $rotacion = $stock['stock_promedio'] > 0
                ? round($ventas['total_vendido'] / $stock['stock_promedio'], 2)
                : 0.0;

            if ($rotacion >= 3)      { $clasificacion = 'Alta';  $color = 'success'; }
            elseif ($rotacion >= 1)  { $clasificacion = 'Media'; $color = 'warning'; }
            else                     { $clasificacion = 'Baja';  $color = 'danger';  }

            return [
                'rotacion'       => $rotacion,
                'clasificacion'  => $clasificacion,
                'color'          => $color,
                'total_vendido'  => (int) $ventas['total_vendido'],
                'stock_promedio' => round((float) $stock['stock_promedio'], 2),
            ];

        } catch (PDOException $e) {
            error_log("Dashboard::getRotacionInventario — " . $e->getMessage());
            return ['rotacion' => 0, 'clasificacion' => 'Sin datos', 'color' => 'secondary'];
        }
    }

    /**
     * Convierte el selector de rango a una fecha de inicio y etiqueta.
     */
    private function calcularRangoFechas(string $rango): array {
        $hoy = date('Y-m-d');
        switch ($rango) {
            case 'mes':       $inicio = date('Y-m-d', strtotime('-30 days'));  $label = 'Último mes';        break;
            case 'trimestre': $inicio = date('Y-m-d', strtotime('-90 days'));  $label = 'Último trimestre';  break;
            default:          $inicio = date('Y-m-d', strtotime('-7 days'));   $label = 'Última semana';     break;
        }
        return ['inicio' => $inicio, 'fin' => $hoy, 'label' => $label, 'rango' => $rango];
    }

    /**
     * [FIX 6] Convierte el selector de rango a número de días (evita duplicar lógica).
     */
    private function calcularRangoDias(string $rango): int {
        return match ($rango) {
            'mes'       => 30,
            'trimestre' => 90,
            default     => 7,
        };
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// [FIX 2] Helper global — la vista dashboard.php usa calcularMargen()
// directamente (sin $this ni nombre de clase).
// ─────────────────────────────────────────────────────────────────────────────
if (!function_exists('calcularMargen')) {
    function calcularMargen(float $venta, float $compra): string {
        return DashboardController::calcularMargen($venta, $compra);
    }
}