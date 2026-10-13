<?php
/**
 * Kardex.php — Modelo  VERSIÓN DEPURADA
 * ─────────────────────────────────────────────────────────────────────────────
 * Historial de correcciones:
 *
 *  v2.2 (aplicadas anteriormente):
 *    · DATE(fecha_movimiento) BETWEEN ? AND ? para evitar el problema de
 *      TIMESTAMP comparado con string de fecha (0 resultados a medianoche).
 *    · getResumenProducto: subqueries correlacionadas eliminadas; se usan
 *      queries independientes con $idProducto directo.
 *
 *  v2.3 — corrección C6:
 *    · getByProducto(), getResumenProducto() y getEstadisticas() usaban
 *      date('Y-m-01') como fallback de fechas.
 *      Problema: al inicio de mes el rango era de 0 o 1 días → 0 resultados.
 *      Se cambia a -90 días, consistente con el controlador y las vistas.
 */

require_once __DIR__ . '/../config/database.php';

class Kardex {

    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Kardex de un producto (via stored procedure)
    // ─────────────────────────────────────────────────────────────────────────
    public function getByProducto($idProducto, $fechaInicio = null, $fechaFin = null) {
        try {
            // [C6] -90 días en lugar de date('Y-m-01')
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            $stmt = $this->db->prepare("CALL sp_obtener_kardex_producto(?, ?, ?)");
            $stmt->execute([$idProducto, $fechaInicio, $fechaFin]);
            $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $r;
        } catch (PDOException $e) {
            error_log("Error en getByProducto: " . $e->getMessage());
            throw new Exception("Error al obtener el kardex del producto");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Kardex de un almacén completo
    // ─────────────────────────────────────────────────────────────────────────
    public function getByAlmacen($idAlmacen, $fechaInicio = null, $fechaFin = null) {
        try {
            // [C6] -90 días en lugar de date('Y-m-01')
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            $sql = "SELECT k.*, p.codigo AS producto_codigo, p.nombre AS producto_nombre,
                           p.sku AS producto_sku, c.nombre AS categoria_nombre,
                           mi.documento_numero, mi.documento_tipo, mi.observaciones,
                           prov.razon_social AS proveedor_nombre
                    FROM kardex k
                    INNER JOIN movimientos_inventario mi ON k.id_movimiento = mi.id_movimiento
                    INNER JOIN productos p ON k.id_producto = p.id_producto
                    LEFT  JOIN categorias c ON p.id_categoria = c.id_categoria
                    LEFT  JOIN proveedores prov ON mi.id_proveedor = prov.id_proveedor
                    WHERE mi.id_almacen = ?
                      AND DATE(k.fecha_movimiento) BETWEEN ? AND ?
                    ORDER BY k.fecha_movimiento ASC, k.id_kardex ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idAlmacen, $fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getByAlmacen: " . $e->getMessage());
            throw new Exception("Error al obtener el kardex del almacén");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resumen estadístico de un producto en un período
    // ─────────────────────────────────────────────────────────────────────────
    public function getResumenProducto($idProducto, $fechaInicio = null, $fechaFin = null) {
        try {
            // [C6] -90 días en lugar de date('Y-m-01')
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            // Totales del período
            $sqlTotales = "SELECT
                    COALESCE(SUM(CASE WHEN tipo_movimiento='entrada' THEN entrada_cantidad    ELSE 0 END),0) AS total_entradas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento='entrada' THEN entrada_costo_total ELSE 0 END),0) AS valor_entradas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento='salida'  THEN salida_cantidad     ELSE 0 END),0) AS total_salidas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento='salida'  THEN salida_costo_total  ELSE 0 END),0) AS valor_salidas,
                    COUNT(*) AS total_movimientos,
                    MIN(fecha_movimiento) AS primer_movimiento,
                    MAX(fecha_movimiento) AS ultimo_movimiento
                FROM kardex
                WHERE id_producto = ? AND DATE(fecha_movimiento) BETWEEN ? AND ?";

            $stmt = $this->db->prepare($sqlTotales);
            $stmt->execute([$idProducto, $fechaInicio, $fechaFin]);
            $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

            // Saldo inicial: último registro ANTES del período
            $stmtSI = $this->db->prepare(
                "SELECT saldo_cantidad, saldo_costo_unitario FROM kardex
                 WHERE id_producto = ? AND DATE(fecha_movimiento) < ?
                 ORDER BY fecha_movimiento DESC, id_kardex DESC LIMIT 1"
            );
            $stmtSI->execute([$idProducto, $fechaInicio]);
            $si = $stmtSI->fetch(PDO::FETCH_ASSOC);
            $resumen['saldo_inicial_cantidad']       = $si ? (int)   $si['saldo_cantidad']         : 0;
            $resumen['saldo_inicial_costo_unitario'] = $si ? (float) $si['saldo_costo_unitario']   : 0.0;

            // Saldo final: último registro EN o ANTES del final del período
            $stmtSF = $this->db->prepare(
                "SELECT saldo_costo_unitario FROM kardex
                 WHERE id_producto = ? AND DATE(fecha_movimiento) <= ?
                 ORDER BY fecha_movimiento DESC, id_kardex DESC LIMIT 1"
            );
            $stmtSF->execute([$idProducto, $fechaFin]);
            $sf = $stmtSF->fetch(PDO::FETCH_ASSOC);
            $resumen['saldo_final_costo_unitario'] = $sf ? (float) $sf['saldo_costo_unitario'] : 0.0;

            // Costo promedio del período (CPP)
            $resumen['costo_promedio'] = ($resumen['total_entradas'] > 0)
                ? round($resumen['valor_entradas'] / $resumen['total_entradas'], 2)
                : $resumen['saldo_final_costo_unitario'];

            return $resumen;
        } catch (PDOException $e) {
            error_log("Error en getResumenProducto: " . $e->getMessage());
            throw new Exception("Error al obtener el resumen del producto");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Estadísticas de rotación, valorización y tendencia
    // ─────────────────────────────────────────────────────────────────────────
    public function getEstadisticas($idProducto, $fechaInicio = null, $fechaFin = null) {
        try {
            // [C6] -90 días en lugar de date('Y-m-01')
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            $stmtP = $this->db->prepare(
                "SELECT stock_actual, precio_compra, precio_venta
                 FROM productos WHERE id_producto = ?"
            );
            $stmtP->execute([$idProducto]);
            $producto = $stmtP->fetch(PDO::FETCH_ASSOC);
            if (!$producto) return [];

            $stmt = $this->db->prepare(
                "SELECT
                    COALESCE(SUM(CASE WHEN tipo_movimiento='entrada' THEN entrada_cantidad    ELSE 0 END),0) AS total_entradas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento='salida'  THEN salida_cantidad     ELSE 0 END),0) AS total_salidas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento='entrada' THEN entrada_costo_total ELSE 0 END),0) AS valor_total_entradas,
                    COALESCE(SUM(CASE WHEN tipo_movimiento='salida'  THEN salida_costo_total  ELSE 0 END),0) AS valor_total_salidas,
                    COALESCE(AVG(CASE WHEN tipo_movimiento='entrada' THEN entrada_costo_unitario END),0)     AS costo_promedio_compra,
                    COUNT(*)                               AS numero_movimientos,
                    COUNT(DISTINCT DATE(fecha_movimiento)) AS dias_con_movimiento
                FROM kardex
                WHERE id_producto = ? AND DATE(fecha_movimiento) BETWEEN ? AND ?"
            );
            $stmt->execute([$idProducto, $fechaInicio, $fechaFin]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $dias = max(1, (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400 + 1);
            $rot  = $producto['stock_actual'] > 0
                ? round($stats['total_salidas'] / $producto['stock_actual'], 2) : 0;
            $di   = $stats['total_salidas'] > 0
                ? round(($producto['stock_actual'] * $dias) / $stats['total_salidas'], 2) : 0;
            $vi   = $producto['stock_actual'] * $producto['precio_compra'];
            $vv   = $producto['stock_actual'] * $producto['precio_venta'];
            $mu   = $vv - $vi;
            $pm   = $vi > 0 ? round(($mu / $vi) * 100, 2) : 0;
            $tot  = $stats['total_entradas'] + $stats['total_salidas'];
            $dif  = $tot > 0
                ? round((($stats['total_entradas'] - $stats['total_salidas']) / $tot) * 100, 2) : 0;
            $tend = $dif > 10 ? 'creciente' : ($dif < -10 ? 'decreciente' : 'estable');
            $vel  = $rot >= 4 ? 'alta' : ($rot >= 2 ? 'media' : ($rot > 0 ? 'baja' : 'sin movimiento'));

            return [
                'indice_rotacion'         => $rot,
                'velocidad_rotacion'      => $vel,
                'dias_inventario'         => $di,
                'promedio_salidas_dia'    => round($stats['total_salidas']  / $dias, 2),
                'promedio_entradas_dia'   => round($stats['total_entradas'] / $dias, 2),
                'dias_con_movimiento'     => $stats['dias_con_movimiento'],
                'valor_inventario_actual' => $vi,
                'valor_venta_potencial'   => $vv,
                'margen_utilidad'         => $mu,
                'porcentaje_margen'       => $pm,
                'costo_promedio_compra'   => $stats['costo_promedio_compra'],
                'tendencia'               => $tend,
                'diferencia_porcentual'   => $dif,
                'dias_periodo'            => $dias,
                'numero_movimientos'      => $stats['numero_movimientos'],
            ];
        } catch (PDOException $e) {
            error_log("Error en getEstadisticas: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Obtener registro por ID
    // ─────────────────────────────────────────────────────────────────────────
    public function getById($idKardex) {
        try {
            $stmt = $this->db->prepare(
                "SELECT k.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo,
                        p.sku AS producto_sku,
                        m.documento_numero, m.documento_tipo, m.observaciones,
                        m.tipo_movimiento AS movimiento_tipo,
                        a.nombre AS almacen_nombre, prov.razon_social AS proveedor_nombre
                 FROM kardex k
                 INNER JOIN productos p ON k.id_producto = p.id_producto
                 INNER JOIN movimientos_inventario m ON k.id_movimiento = m.id_movimiento
                 LEFT  JOIN almacenes a ON m.id_almacen = a.id_almacen
                 LEFT  JOIN proveedores prov ON m.id_proveedor = prov.id_proveedor
                 WHERE k.id_kardex = ?"
            );
            $stmt->execute([$idKardex]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getById: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Obtener costo promedio actual de un producto
    // ─────────────────────────────────────────────────────────────────────────
    public function getCostoPromedio($idProducto) {
        try {
            $stmt = $this->db->prepare(
                "SELECT saldo_costo_unitario FROM kardex
                 WHERE id_producto = ? AND saldo_cantidad > 0
                 ORDER BY fecha_movimiento DESC, id_kardex DESC LIMIT 1"
            );
            $stmt->execute([$idProducto]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) return floatval($r['saldo_costo_unitario']);

            // Fallback al precio de compra registrado en el catálogo
            $stmt2 = $this->db->prepare("SELECT precio_compra FROM productos WHERE id_producto = ?");
            $stmt2->execute([$idProducto]);
            $p = $stmt2->fetch(PDO::FETCH_ASSOC);
            return $p ? floatval($p['precio_compra']) : 0.0;
        } catch (PDOException $e) {
            error_log("Error en getCostoPromedio: " . $e->getMessage());
            return 0.0;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Registrar nuevo movimiento (CPP automático)
    // ─────────────────────────────────────────────────────────────────────────
    public function registrarMovimiento($datos) {
        try {
            $this->db->beginTransaction();

            foreach (['id_producto', 'id_movimiento', 'tipo_movimiento'] as $c) {
                if (!isset($datos[$c])) throw new Exception("Campo requerido faltante: $c");
            }

            $s = $this->obtenerUltimoSaldo($datos['id_producto']);

            if ($datos['tipo_movimiento'] === 'entrada') {
                $ca = $datos['entrada_cantidad'];
                $cu = $datos['entrada_costo_unitario'];
                $ct = $ca * $cu;
                $ns = $s['saldo_cantidad'] + $ca;
                $nct = ($s['saldo_cantidad'] * $s['saldo_costo_unitario']) + $ct;
                $ncu = $ns > 0 ? $nct / $ns : $cu;
                $sql = "INSERT INTO kardex
                            (id_producto, id_movimiento, fecha_movimiento, tipo_movimiento,
                             entrada_cantidad, entrada_costo_unitario, entrada_costo_total,
                             salida_cantidad, salida_costo_unitario, salida_costo_total,
                             saldo_cantidad, saldo_costo_unitario, saldo_costo_total,
                             documento_referencia)
                        VALUES (?, ?, NOW(), 'entrada', ?, ?, ?, 0, 0, 0, ?, ?, ?, ?)";
                $this->db->prepare($sql)->execute([
                    $datos['id_producto'], $datos['id_movimiento'],
                    $ca, $cu, $ct, $ns, $ncu, $nct,
                    $datos['documento_referencia'] ?? null
                ]);
            } else {
                $ca  = $datos['salida_cantidad'];
                $cu  = $s['saldo_costo_unitario'];
                $ct  = $ca * $cu;
                $ns  = $s['saldo_cantidad'] - $ca;
                $nct = $s['saldo_costo_total'] - $ct;
                $ncu = $ns > 0 ? $nct / $ns : 0;
                $sql = "INSERT INTO kardex
                            (id_producto, id_movimiento, fecha_movimiento, tipo_movimiento,
                             entrada_cantidad, entrada_costo_unitario, entrada_costo_total,
                             salida_cantidad, salida_costo_unitario, salida_costo_total,
                             saldo_cantidad, saldo_costo_unitario, saldo_costo_total,
                             documento_referencia)
                        VALUES (?, ?, NOW(), 'salida', 0, 0, 0, ?, ?, ?, ?, ?, ?, ?)";
                $this->db->prepare($sql)->execute([
                    $datos['id_producto'], $datos['id_movimiento'],
                    $ca, $cu, $ct, $ns, $ncu, $nct,
                    $datos['documento_referencia'] ?? null
                ]);
            }

            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en registrarMovimiento: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Obtener último saldo (helper interno)
    // ─────────────────────────────────────────────────────────────────────────
    private function obtenerUltimoSaldo($idProducto) {
        try {
            $stmt = $this->db->prepare(
                "SELECT saldo_cantidad, saldo_costo_unitario, saldo_costo_total
                 FROM kardex WHERE id_producto = ?
                 ORDER BY fecha_movimiento DESC, id_kardex DESC LIMIT 1"
            );
            $stmt->execute([$idProducto]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            return $r ?: ['saldo_cantidad' => 0, 'saldo_costo_unitario' => 0, 'saldo_costo_total' => 0];
        } catch (PDOException $e) {
            return ['saldo_cantidad' => 0, 'saldo_costo_unitario' => 0, 'saldo_costo_total' => 0];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Productos con movimientos en un rango
    // ─────────────────────────────────────────────────────────────────────────
    public function getProductosConMovimientos($fechaInicio, $fechaFin) {
        try {
            $stmt = $this->db->prepare(
                "SELECT DISTINCT p.id_producto, p.codigo, p.nombre, p.sku,
                        c.nombre AS categoria_nombre,
                        COUNT(DISTINCT k.id_kardex) AS total_movimientos
                 FROM productos p
                 INNER JOIN kardex k ON p.id_producto = k.id_producto
                 LEFT  JOIN categorias c ON p.id_categoria = c.id_categoria
                 WHERE DATE(k.fecha_movimiento) BETWEEN ? AND ?
                   AND p.estado = 'activo'
                 GROUP BY p.id_producto, p.codigo, p.nombre, p.sku, c.nombre
                 ORDER BY p.nombre ASC"
            );
            $stmt->execute([$fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getProductosConMovimientos: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Datos completos para PDF
    // ─────────────────────────────────────────────────────────────────────────
    public function generarPDF($idProducto, $fechaInicio, $fechaFin) {
        try {
            $kardex = $this->getByProducto($idProducto, $fechaInicio, $fechaFin);
            if (empty($kardex)) return false;

            $resumen      = $this->getResumenProducto($idProducto, $fechaInicio, $fechaFin);
            $estadisticas = $this->getEstadisticas($idProducto, $fechaInicio, $fechaFin);

            $stmt = $this->db->prepare(
                "SELECT p.*, c.nombre AS categoria_nombre, m.nombre AS marca_nombre,
                        a.nombre AS almacen_nombre, prov.razon_social AS proveedor_nombre
                 FROM productos p
                 LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                 LEFT JOIN marcas m ON p.id_marca = m.id_marca
                 LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
                 LEFT JOIN almacenes a ON u.id_almacen = a.id_almacen
                 LEFT JOIN proveedores prov ON p.id_proveedor = prov.id_proveedor
                 WHERE p.id_producto = ?"
            );
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'producto'          => $producto,
                'kardex'            => $kardex,
                'resumen'           => $resumen,
                'estadisticas'      => $estadisticas,
                'fecha_inicio'      => $fechaInicio,
                'fecha_fin'         => $fechaFin,
                'fecha_generacion'  => date('d/m/Y H:i:s'),
            ];
        } catch (PDOException $e) {
            error_log("Error en generarPDF: " . $e->getMessage());
            return false;
        }
    }
}
?>