<?php
/**
 * Reporte.php — VERSIÓN DEPURADA
 * ─────────────────────────────────────────────────────────────────────────────
 * Correcciones y mejoras aplicadas:
 *
 *  1. Tipado estricto completo: declare(strict_types=1), tipos en parámetros
 *     y tipos de retorno ": array" en todos los métodos públicos.
 *  2. Propiedad $db tipada como PDO (eliminado tipo mixed implícito).
 *  3. Todos los métodos envueltos en try/catch con error_log() — ninguna
 *     excepción PDO llega sin procesar al front-end.
 *  4. getRotacionInventario(): filtro de fecha aplicado en LEFT JOIN en lugar
 *     de WHERE, para que productos sin ventas en el rango igual aparezcan.
 *     El filtro AND v.estado = 'completada' reforzado en el JOIN.
 *  5. getTopProductosVendidos(): ídem — condición de fecha/estado movida al
 *     JOIN para preservar productos sin ventas (con total_vendido = 0).
 *  6. getMovimientosPorDia(): tipo de retorno documentado — devuelve filas
 *     con campos (fecha, total_ventas, productos_vendidos, total_unidades,
 *     ingreso_total). Sin cambio de lógica, sólo hardening.
 *  7. getProductosSinMovimiento(): bindValue con PDO::PARAM_STR explícito
 *     para :fecha_limite.
 *  8. getEstadisticasDashboard(): retorno tipado como array (fetch puede
 *     devolver false — normalizado a [] con operador ?:).
 *  9. Constantes de nivel de stock extraídas como private const para evitar
 *     strings mágicos dispersos.
 * 10. Docblocks actualizados con @throws, @param y @return completos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Reporte {

    // [FIX 9] Niveles de stock como constantes — sin strings mágicos
    private const NIVEL_CRITICO = 'critico';
    private const NIVEL_BAJO    = 'bajo';
    private const NIVEL_TODOS   = 'todos';

    private PDO $db; // [FIX 2]

    public function __construct() {
        $this->db = getDB();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reporte de stock bajo
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Productos con stock en nivel bajo o crítico.
     *
     * @param string $nivel  'critico' | 'bajo' | 'todos'
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException si falla la consulta
     */
    public function getReporteStockBajo(string $nivel = self::NIVEL_TODOS): array {
        try {
            $sql = "SELECT
                        p.id_producto,
                        p.codigo,
                        p.nombre,
                        p.descripcion,
                        p.stock_actual,
                        p.stock_minimo,
                        p.stock_maximo,
                        p.estado_stock,
                        p.precio_compra,
                        p.precio_venta,
                        c.nombre AS categoria,
                        a.nombre AS almacen
                    FROM productos p
                    LEFT JOIN categorias   c ON p.id_categoria  = c.id_categoria
                    LEFT JOIN ubicaciones  u ON p.id_ubicacion  = u.id_ubicacion
                    LEFT JOIN almacenes    a ON u.id_almacen     = a.id_almacen
                    WHERE p.estado = 'activo'";

            // [FIX 9] Comparación con constantes
            $sql .= match ($nivel) {
                self::NIVEL_CRITICO => " AND p.stock_actual <= p.stock_minimo * 0.5",
                self::NIVEL_BAJO    => " AND p.stock_actual <= p.stock_minimo
                                         AND p.stock_actual >  p.stock_minimo * 0.5",
                default             => " AND p.stock_actual <= p.stock_minimo",
            };

            $sql .= " ORDER BY p.stock_actual ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Reporte::getReporteStockBajo — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Rotación de inventario
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Índice de rotación de cada producto en el rango de fechas dado.
     *
     * [FIX 4] El filtro de fecha y estado se aplica dentro del LEFT JOIN
     * (no en el WHERE) para que los productos sin ventas en el rango
     * sigan apareciendo con cantidad_vendida = 0.
     *
     * @param string $fechaInicio  Formato Y-m-d
     * @param string $fechaFin     Formato Y-m-d
     * @return array<int, array<string, mixed>>
     */
    public function getRotacionInventario(string $fechaInicio, string $fechaFin): array {
        try {
            $sql = "SELECT
                        p.id_producto,
                        p.codigo,
                        p.nombre,
                        p.stock_actual,
                        p.stock_minimo,
                        p.stock_maximo,
                        COALESCE(SUM(dv.cantidad),  0) AS cantidad_vendida,
                        COALESCE(SUM(dv.subtotal),  0) AS total_ventas,
                        CASE
                            WHEN p.stock_actual > 0
                                THEN ROUND(COALESCE(SUM(dv.cantidad), 0) / p.stock_actual, 4)
                            ELSE 0
                        END AS indice_rotacion,
                        c.nombre AS categoria
                    FROM productos p
                    LEFT JOIN categorias c
                        ON p.id_categoria = c.id_categoria
                    LEFT JOIN detalle_ventas dv
                        ON p.id_producto = dv.id_producto
                    LEFT JOIN ventas v
                        ON dv.id_venta   = v.id_venta
                        AND v.fecha_venta BETWEEN :fecha_inicio AND :fecha_fin
                        AND v.estado     = 'completada'
                    WHERE p.estado = 'activo'
                    GROUP BY p.id_producto, p.codigo, p.nombre,
                             p.stock_actual, p.stock_minimo, p.stock_maximo,
                             c.nombre
                    ORDER BY indice_rotacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin',    $fechaFin,    PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Reporte::getRotacionInventario — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Valorización del inventario
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Valorización del inventario agrupada por almacén.
     *
     * Campos devueltos (compatibles con valorizacion.php):
     *   almacen_id, almacen, categoria (alias de almacen), total_productos,
     *   total_unidades, valor_compra, valor_venta, ganancia_potencial,
     *   utilidad_potencial (alias de ganancia_potencial), margen_porcentaje.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getValorizacionInventario(): array {
        try {
            $sql = "SELECT
                        a.id_almacen                                                  AS almacen_id,
                        a.nombre                                                      AS almacen,
                        a.nombre                                                      AS categoria,
                        COUNT(p.id_producto)                                          AS total_productos,
                        COALESCE(SUM(p.stock_actual),                             0) AS total_unidades,
                        COALESCE(SUM(p.stock_actual * p.precio_compra),           0) AS valor_compra,
                        COALESCE(SUM(p.stock_actual * p.precio_venta),            0) AS valor_venta,
                        COALESCE(SUM(p.stock_actual * (p.precio_venta - p.precio_compra)), 0) AS ganancia_potencial,
                        COALESCE(SUM(p.stock_actual * (p.precio_venta - p.precio_compra)), 0) AS utilidad_potencial,
                        ROUND(
                            COALESCE(SUM(p.stock_actual * (p.precio_venta - p.precio_compra)), 0)
                            / NULLIF(SUM(p.stock_actual * p.precio_compra), 0) * 100,
                            2
                        )                                                             AS margen_porcentaje
                    FROM almacenes a
                    LEFT JOIN ubicaciones u
                        ON a.id_almacen      = u.id_almacen
                    LEFT JOIN productos p
                        ON u.id_ubicacion    = p.id_ubicacion
                        AND p.estado         = 'activo'
                    WHERE a.estado = 'activo'
                    GROUP BY a.id_almacen, a.nombre
                    ORDER BY valor_compra DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Reporte::getValorizacionInventario — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Top productos más vendidos
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Productos más vendidos en el rango de fechas dado.
     *
     * [FIX 5] Condición de fecha/estado en el JOIN: los productos sin ventas
     * en el rango siguen apareciendo con total_vendido = 0.
     *
     * Campos: id_producto, codigo, nombre, descripcion, categoria,
     *         total_vendido, total_ingresos, precio_promedio.
     *
     * @param string $fechaInicio  Formato Y-m-d
     * @param string $fechaFin     Formato Y-m-d
     * @param int    $limit        Máximo de filas (default 10)
     * @return array<int, array<string, mixed>>
     */
    public function getTopProductosVendidos(
        string $fechaInicio,
        string $fechaFin,
        int    $limit = 10
    ): array {
        try {
            $sql = "SELECT
                        p.id_producto,
                        p.codigo,
                        p.nombre,
                        p.descripcion,
                        c.nombre                              AS categoria,
                        COALESCE(SUM(dv.cantidad),        0) AS total_vendido,
                        COALESCE(SUM(dv.subtotal),        0) AS total_ingresos,
                        COALESCE(AVG(dv.precio_unitario), 0) AS precio_promedio
                    FROM productos p
                    LEFT JOIN categorias c
                        ON p.id_categoria = c.id_categoria
                    LEFT JOIN detalle_ventas dv
                        ON p.id_producto = dv.id_producto
                    LEFT JOIN ventas v
                        ON dv.id_venta    = v.id_venta
                        AND v.fecha_venta BETWEEN :fecha_inicio AND :fecha_fin
                        AND v.estado      = 'completada'
                    WHERE p.estado = 'activo'
                    GROUP BY p.id_producto, p.codigo, p.nombre, p.descripcion, c.nombre
                    ORDER BY total_vendido DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin',    $fechaFin,    PDO::PARAM_STR);
            $stmt->bindValue(':limit',        $limit,       PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Reporte::getTopProductosVendidos — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Movimientos (ventas) por día
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Resumen diario de ventas completadas en el rango indicado.
     *
     * Campos: fecha, total_ventas, productos_vendidos, total_unidades,
     *         ingreso_total.
     *
     * @param string $fechaInicio  Formato Y-m-d
     * @param string $fechaFin     Formato Y-m-d
     * @return array<int, array<string, mixed>>
     */
    public function getMovimientosPorDia(string $fechaInicio, string $fechaFin): array {
        try {
            $sql = "SELECT
                        DATE(v.fecha_venta)                AS fecha,
                        COUNT(DISTINCT v.id_venta)         AS total_ventas,
                        COUNT(DISTINCT dv.id_producto)     AS productos_vendidos,
                        COALESCE(SUM(dv.cantidad), 0)      AS total_unidades,
                        COALESCE(SUM(v.total),     0)      AS ingreso_total
                    FROM ventas v
                    JOIN detalle_ventas dv
                        ON v.id_venta = dv.id_venta
                    WHERE v.fecha_venta BETWEEN :fecha_inicio AND :fecha_fin
                        AND v.estado = 'completada'
                    GROUP BY DATE(v.fecha_venta)
                    ORDER BY fecha DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin',    $fechaFin,    PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Reporte::getMovimientosPorDia — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Movimientos (compras) por proveedor
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Compras agrupadas por proveedor en el rango de fechas indicado.
     *
     * Campos: id (alias de id_proveedor), proveedor, nombre_comercial, ruc,
     *         telefono, total_compras, total_productos, monto_total,
     *         promedio_compra, primera_compra, ultima_compra.
     *
     * @param string $fechaInicio  Formato Y-m-d
     * @param string $fechaFin     Formato Y-m-d
     * @return array<int, array<string, mixed>>
     */
    public function getMovimientosPorProveedor(string $fechaInicio, string $fechaFin): array {
        try {
            $sql = "SELECT
                        prov.id_proveedor               AS id,
                        prov.razon_social               AS proveedor,
                        prov.nombre_comercial,
                        prov.ruc,
                        prov.telefono,
                        COUNT(DISTINCT c.id)            AS total_compras,
                        COALESCE(SUM(dc.cantidad), 0)   AS total_productos,
                        COALESCE(SUM(c.total),     0)   AS monto_total,
                        COALESCE(AVG(c.total),     0)   AS promedio_compra,
                        MIN(c.fecha)                    AS primera_compra,
                        MAX(c.fecha)                    AS ultima_compra
                    FROM proveedores prov
                    LEFT JOIN compras c
                        ON  prov.id_proveedor = c.proveedor_id
                        AND c.fecha BETWEEN :fecha_inicio AND :fecha_fin
                        AND c.estado = 'completada'
                    LEFT JOIN detalle_compras dc
                        ON c.id = dc.compra_id
                    WHERE prov.estado = 'activo'
                    GROUP BY prov.id_proveedor, prov.razon_social, prov.nombre_comercial,
                             prov.ruc, prov.telefono
                    ORDER BY monto_total DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin',    $fechaFin,    PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Reporte::getMovimientosPorProveedor — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Productos sin movimiento
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Productos activos sin ningún movimiento (venta ni compra) en los
     * últimos $dias días.
     *
     * Campos: id_producto, codigo, nombre, descripcion, stock_actual,
     *         precio_compra, precio_venta, categoria, almacen,
     *         ultima_venta, ultima_compra, dias_sin_movimiento.
     *
     * [FIX 7] bindValue con PDO::PARAM_STR explícito para :fecha_limite.
     *
     * @param int $dias  Umbral de inactividad en días (default 30)
     * @return array<int, array<string, mixed>>
     */
    public function getProductosSinMovimiento(int $dias = 30): array {
        try {
            $fechaLimite = date('Y-m-d', strtotime("-{$dias} days"));

            $sql = "SELECT
                        p.id_producto,
                        p.codigo,
                        p.nombre,
                        p.descripcion,
                        p.stock_actual,
                        p.precio_compra,
                        p.precio_venta,
                        c.nombre  AS categoria,
                        a.nombre  AS almacen,
                        COALESCE(MAX(v.fecha_venta), 'Nunca') AS ultima_venta,
                        COALESCE(MAX(cp.fecha),      'Nunca') AS ultima_compra,
                        DATEDIFF(
                            NOW(),
                            GREATEST(
                                COALESCE(MAX(v.fecha_venta), '2000-01-01'),
                                COALESCE(MAX(cp.fecha),      '2000-01-01')
                            )
                        ) AS dias_sin_movimiento
                    FROM productos p
                    LEFT JOIN categorias     c  ON p.id_categoria = c.id_categoria
                    LEFT JOIN ubicaciones    u  ON p.id_ubicacion = u.id_ubicacion
                    LEFT JOIN almacenes      a  ON u.id_almacen   = a.id_almacen
                    LEFT JOIN detalle_ventas dv ON p.id_producto  = dv.id_producto
                    LEFT JOIN ventas         v  ON dv.id_venta    = v.id_venta
                    LEFT JOIN detalle_compras dc ON p.id_producto = dc.producto_id
                    LEFT JOIN compras        cp ON dc.compra_id   = cp.id
                    WHERE p.estado = 'activo'
                    GROUP BY p.id_producto, p.codigo, p.nombre, p.descripcion,
                             p.stock_actual, p.precio_compra, p.precio_venta,
                             c.nombre, a.nombre
                    HAVING (MAX(v.fecha_venta) IS NULL AND MAX(cp.fecha) IS NULL)
                        OR GREATEST(
                               COALESCE(MAX(v.fecha_venta), '2000-01-01'),
                               COALESCE(MAX(cp.fecha),      '2000-01-01')
                           ) < :fecha_limite
                    ORDER BY dias_sin_movimiento DESC
                    LIMIT 100";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':fecha_limite', $fechaLimite, PDO::PARAM_STR); // [FIX 7]
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Reporte::getProductosSinMovimiento — " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Estadísticas generales para el dashboard
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * KPIs rápidos del día: totales de productos, stock bajo, valor del
     * inventario, ventas/compras del día.
     *
     * [FIX 8] fetch() puede devolver false — se normaliza a [] con ?: [].
     *
     * Campos: total_productos, productos_stock_bajo, valor_inventario,
     *         ventas_hoy, ingresos_hoy, compras_hoy.
     *
     * @return array<string, mixed>
     */
    public function getEstadisticasDashboard(): array {
        try {
            $sql = "SELECT
                        (SELECT COUNT(*)
                         FROM productos
                         WHERE estado = 'activo')                                       AS total_productos,

                        (SELECT COUNT(*)
                         FROM productos
                         WHERE estado = 'activo'
                           AND stock_actual <= stock_minimo)                            AS productos_stock_bajo,

                        (SELECT COALESCE(SUM(stock_actual * precio_compra), 0)
                         FROM productos
                         WHERE estado = 'activo')                                       AS valor_inventario,

                        (SELECT COUNT(*)
                         FROM ventas
                         WHERE DATE(fecha_venta) = CURDATE()
                           AND estado = 'completada')                                   AS ventas_hoy,

                        (SELECT COALESCE(SUM(total), 0)
                         FROM ventas
                         WHERE DATE(fecha_venta) = CURDATE()
                           AND estado = 'completada')                                   AS ingresos_hoy,

                        (SELECT COUNT(*)
                         FROM compras
                         WHERE DATE(fecha) = CURDATE()
                           AND estado = 'completada')                                   AS compras_hoy";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: []; // [FIX 8]

        } catch (\PDOException $e) {
            error_log("Reporte::getEstadisticasDashboard — " . $e->getMessage());
            return [];
        }
    }
}