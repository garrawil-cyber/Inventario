<?php
require_once __DIR__ . '/../config/database.php';

class Movimiento {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Registrar entrada de mercancía (MEJORADO)
     */
    public function registrarEntrada($data) {
        try {
            $this->db->beginTransaction();
            
            // Generar código único para el movimiento
            $codigo = $this->generarCodigo('ENT');
            
            // Obtener stock actual
            $sqlStock = "SELECT stock_actual, precio_compra FROM productos WHERE id_producto = ?";
            $stmtStock = $this->db->prepare($sqlStock);
            $stmtStock->execute([$data['id_producto']]);
            $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }
            
            $stockAnterior = $producto['stock_actual'];
            $stockNuevo = $stockAnterior + $data['cantidad'];
            
            // Calcular costos
            $costoUnitario = $data['costo_unitario'];
            $costoTotal = $data['cantidad'] * $costoUnitario;
            
            // Insertar movimiento
            $sql = "INSERT INTO movimientos_inventario (
                        codigo, id_producto, id_tipo_movimiento, id_almacen, 
                        tipo_movimiento, cantidad, stock_anterior, stock_nuevo,
                        costo_unitario, costo_total, documento_tipo, documento_numero,
                        id_proveedor, observaciones, usuario_registro, fecha_movimiento
                    ) VALUES (?, ?, ?, ?, 'entrada', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $codigo,
                $data['id_producto'],
                $data['id_tipo_movimiento'] ?? 1,
                $data['id_almacen'],
                $data['cantidad'],
                $stockAnterior,
                $stockNuevo,
                $costoUnitario,
                $costoTotal,
                $data['documento_tipo'] ?? null,
                $data['documento_numero'] ?? null,
                $data['id_proveedor'] ?? null,
                $data['observaciones'] ?? null,
                $_SESSION['usuario_id'] ?? 1
            ]);
            
            if (!$success) {
                throw new Exception("Error al insertar movimiento");
            }
            
            // Actualizar stock del producto
            $sqlUpdate = "UPDATE productos 
                         SET stock_actual = ?,
                             precio_compra = ?,
                             fecha_actualizacion = NOW()
                         WHERE id_producto = ?";
            
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$stockNuevo, $costoUnitario, $data['id_producto']]);
            
            // Actualizar estado del stock
            $this->actualizarEstadoStock($data['id_producto']);
            
            $this->db->commit();
            return [
                'success' => true,
                'codigo' => $codigo,
                'stock_nuevo' => $stockNuevo
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en Movimiento::registrarEntrada: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar salida de mercancía (MEJORADO)
     */
    public function registrarSalida($data) {
        try {
            $this->db->beginTransaction();
            
            // Generar código único para el movimiento
            $codigo = $this->generarCodigo('SAL');
            
            // Verificar stock disponible
            $sqlStock = "SELECT stock_actual, precio_compra FROM productos WHERE id_producto = ?";
            $stmtStock = $this->db->prepare($sqlStock);
            $stmtStock->execute([$data['id_producto']]);
            $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }
            
            if ($producto['stock_actual'] < $data['cantidad']) {
                throw new Exception("Stock insuficiente. Disponible: " . $producto['stock_actual']);
            }
            
            $stockAnterior = $producto['stock_actual'];
            $stockNuevo = $stockAnterior - $data['cantidad'];
            
            // El costo unitario es el precio de compra (no el de venta)
            $costoUnitario = $producto['precio_compra'];
            $costoTotal = $data['cantidad'] * $costoUnitario;
            
            // Insertar movimiento
            $sql = "INSERT INTO movimientos_inventario (
                        codigo, id_producto, id_tipo_movimiento, id_almacen, 
                        tipo_movimiento, cantidad, stock_anterior, stock_nuevo,
                        costo_unitario, costo_total, documento_tipo, documento_numero,
                        observaciones, usuario_registro, fecha_movimiento
                    ) VALUES (?, ?, ?, ?, 'salida', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $codigo,
                $data['id_producto'],
                $data['id_tipo_movimiento'] ?? 3,
                $data['id_almacen'],
                $data['cantidad'],
                $stockAnterior,
                $stockNuevo,
                $costoUnitario,
                $costoTotal,
                $data['documento_tipo'] ?? null,
                $data['documento_numero'] ?? null,
                $data['observaciones'] ?? null,
                $_SESSION['usuario_id'] ?? 1
            ]);
            
            if (!$success) {
                throw new Exception("Error al insertar movimiento");
            }
            
            // Actualizar stock del producto
            $sqlUpdate = "UPDATE productos 
                         SET stock_actual = ?,
                             fecha_actualizacion = NOW()
                         WHERE id_producto = ?";
            
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$stockNuevo, $data['id_producto']]);
            
            // Actualizar estado del stock
            $this->actualizarEstadoStock($data['id_producto']);
            
            $this->db->commit();
            return [
                'success' => true,
                'codigo' => $codigo,
                'stock_nuevo' => $stockNuevo
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en Movimiento::registrarSalida: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
     /**
     * Generar código único para movimientos
     */
    private function generarCodigo($prefijo = 'MOV') {
        $fecha = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefijo}-{$fecha}-{$random}";
    }
    
    /**
     * Actualizar estado del stock del producto
     */
    private function actualizarEstadoStock($idProducto) {
        $sql = "UPDATE productos 
                SET estado_stock = CASE
                    WHEN stock_actual = 0 THEN 'agotado'
                    WHEN stock_actual <= (stock_minimo * 0.5) THEN 'critico'
                    WHEN stock_actual <= stock_minimo THEN 'bajo'
                    WHEN stock_actual >= stock_maximo THEN 'exceso'
                    ELSE 'normal'
                END
                WHERE id_producto = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProducto]);
        } catch (PDOException $e) {
            error_log("Error al actualizar estado stock: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de movimientos
     */
    public function getEstadisticas($fechaInicio = null, $fechaFin = null) {
        if (!$fechaInicio) $fechaInicio = date('Y-m-01');
        if (!$fechaFin) $fechaFin = date('Y-m-d');
        
        $sql = "SELECT 
                    tipo_movimiento,
                    COUNT(*) as total_movimientos,
                    SUM(cantidad) as total_cantidad,
                    SUM(costo_total) as total_valor
                FROM movimientos_inventario
                WHERE DATE(fecha_movimiento) BETWEEN ? AND ?
                GROUP BY tipo_movimiento
                ORDER BY total_movimientos DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Movimiento::getEstadisticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener movimientos por producto
     */
    public function getByProducto($idProducto, $limit = 20) {
        $sql = "SELECT m.*, 
                       a.nombre as almacen_nombre, 
                       u.nombre_completo as usuario_nombre,
                       tm.nombre as tipo_movimiento_nombre
                FROM movimientos_inventario m
                INNER JOIN almacenes a ON m.id_almacen = a.id_almacen
                INNER JOIN usuarios u ON m.usuario_registro = u.id_usuario
                LEFT JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
                WHERE m.id_producto = ?
                ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC
                LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProducto, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Movimiento::getByProducto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste($data) {
        try {
            $this->db->beginTransaction();
            
            // Generar código único
            $tipo = $data['cantidad'] > 0 ? 'AJU-POS' : 'AJU-NEG';
            $codigo = $this->generarCodigo($tipo);
            
            // Obtener stock actual
            $sqlStock = "SELECT stock_actual, precio_compra FROM productos WHERE id_producto = ?";
            $stmtStock = $this->db->prepare($sqlStock);
            $stmtStock->execute([$data['id_producto']]);
            $producto = $stmtStock->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }
            
            $stockAnterior = $producto['stock_actual'];
            $stockNuevo = $stockAnterior + $data['cantidad']; // cantidad puede ser negativa
            
            if ($stockNuevo < 0) {
                throw new Exception("El ajuste resultaría en stock negativo");
            }
            
            $costoUnitario = $producto['precio_compra'];
            $costoTotal = abs($data['cantidad']) * $costoUnitario;
            
            // Insertar movimiento
            $sql = "INSERT INTO movimientos_inventario (
                        codigo, id_producto, id_tipo_movimiento, id_almacen, 
                        tipo_movimiento, cantidad, stock_anterior, stock_nuevo,
                        costo_unitario, costo_total, observaciones, 
                        usuario_registro, fecha_movimiento
                    ) VALUES (?, ?, ?, ?, 'ajuste', ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $codigo,
                $data['id_producto'],
                $data['cantidad'] > 0 ? 6 : 7, // 6 = Ajuste positivo, 7 = Ajuste negativo
                $data['id_almacen'],
                abs($data['cantidad']),
                $stockAnterior,
                $stockNuevo,
                $costoUnitario,
                $costoTotal,
                $data['observaciones'] ?? 'Ajuste de inventario',
                $_SESSION['usuario_id'] ?? 1
            ]);
            
            if (!$success) {
                throw new Exception("Error al insertar movimiento");
            }
            
            // Actualizar stock
            $sqlUpdate = "UPDATE productos 
                         SET stock_actual = ?,
                             fecha_modificacion = NOW()
                         WHERE id_producto = ?";
            
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $stmtUpdate->execute([$stockNuevo, $data['id_producto']]);
            
            // Actualizar estado del stock
            $this->actualizarEstadoStock($data['id_producto']);
            
            $this->db->commit();
            return [
                'success' => true,
                'codigo' => $codigo,
                'stock_nuevo' => $stockNuevo
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en Movimiento::registrarAjuste: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener todos los movimientos con filtros
     */
    public function getAll($filtros = []) {
        $sql = "SELECT m.*, 
                       p.nombre as producto_nombre, 
                       p.codigo as producto_codigo,
                       a.nombre as almacen_nombre, 
                       u.nombre_completo as usuario_nombre,
                       tm.nombre as tipo_movimiento_nombre,
                       prov.razon_social as proveedor_nombre
                FROM movimientos_inventario m
                INNER JOIN productos p ON m.id_producto = p.id_producto
                INNER JOIN almacenes a ON m.id_almacen = a.id_almacen
                INNER JOIN usuarios u ON m.usuario_registro = u.id_usuario
                LEFT JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
                LEFT JOIN proveedores prov ON m.id_proveedor = prov.id_proveedor
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['tipo_movimiento'])) {
            $sql .= " AND m.tipo_movimiento = ?";
            $params[] = $filtros['tipo_movimiento'];
        }
        
        if (!empty($filtros['id_producto'])) {
            $sql .= " AND m.id_producto = ?";
            $params[] = $filtros['id_producto'];
        }
        
        if (!empty($filtros['id_almacen'])) {
            $sql .= " AND m.id_almacen = ?";
            $params[] = $filtros['id_almacen'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(m.fecha_movimiento) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(m.fecha_movimiento) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        
        if (!empty($filtros['documento_numero'])) {
            $sql .= " AND m.documento_numero LIKE ?";
            $params[] = '%' . $filtros['documento_numero'] . '%';
        }
        
        $sql .= " ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC";
        
        // Límite opcional
        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limit'];
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Movimiento::getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener movimiento por ID
     */
    public function getById($id) {
        $sql = "SELECT m.*, 
                       p.nombre as producto_nombre, 
                       p.codigo as producto_codigo,
                       a.nombre as almacen_nombre, 
                       u.nombre_completo as usuario_nombre,
                       tm.nombre as tipo_movimiento_nombre,
                       prov.razon_social as proveedor_nombre
                FROM movimientos_inventario m
                INNER JOIN productos p ON m.id_producto = p.id_producto
                INNER JOIN almacenes a ON m.id_almacen = a.id_almacen
                INNER JOIN usuarios u ON m.usuario_registro = u.id_usuario
                LEFT JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
                LEFT JOIN proveedores prov ON m.id_proveedor = prov.id_proveedor
                WHERE m.id_movimiento = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Movimiento::getById: " . $e->getMessage());
            return null;
        }
    }
 
    
/**
 * Obtener todos los movimientos con paginación
 */
public function getAllPaginated($filtros = [], $page = 1, $perPage = 20) {
    try {
        // Calcular offset
        $offset = ($page - 1) * $perPage;
        
        // Construir consulta COUNT para total
        $countSql = "SELECT COUNT(*) as total 
                     FROM movimientos_inventario m
                     INNER JOIN productos p ON m.id_producto = p.id_producto
                     INNER JOIN almacenes a ON m.id_almacen = a.id_almacen
                     INNER JOIN usuarios u ON m.usuario_registro = u.id_usuario
                     LEFT JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
                     LEFT JOIN proveedores prov ON m.id_proveedor = prov.id_proveedor
                     WHERE 1=1";
        
        $countParams = [];
        
        // Aplicar filtros al COUNT
        if (!empty($filtros['tipo_movimiento'])) {
            $countSql .= " AND m.tipo_movimiento = ?";
            $countParams[] = $filtros['tipo_movimiento'];
        }
        if (!empty($filtros['id_producto'])) {
            $countSql .= " AND m.id_producto = ?";
            $countParams[] = $filtros['id_producto'];
        }
        if (!empty($filtros['id_almacen'])) {
            $countSql .= " AND m.id_almacen = ?";
            $countParams[] = $filtros['id_almacen'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $countSql .= " AND DATE(m.fecha_movimiento) >= ?";
            $countParams[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $countSql .= " AND DATE(m.fecha_movimiento) <= ?";
            $countParams[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['documento_numero'])) {
            $countSql .= " AND m.documento_numero LIKE ?";
            $countParams[] = '%' . $filtros['documento_numero'] . '%';
        }
        
        // Ejecutar COUNT
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Construir consulta principal con LIMIT
        $sql = "SELECT m.*, 
                       p.nombre as producto_nombre, 
                       p.codigo as producto_codigo,
                       a.nombre as almacen_nombre, 
                       u.nombre_completo as usuario_nombre,
                       tm.nombre as tipo_movimiento_nombre,
                       prov.razon_social as proveedor_nombre
                FROM movimientos_inventario m
                INNER JOIN productos p ON m.id_producto = p.id_producto
                INNER JOIN almacenes a ON m.id_almacen = a.id_almacen
                INNER JOIN usuarios u ON m.usuario_registro = u.id_usuario
                LEFT JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
                LEFT JOIN proveedores prov ON m.id_proveedor = prov.id_proveedor
                WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filtros['tipo_movimiento'])) {
            $sql .= " AND m.tipo_movimiento = ?";
            $params[] = $filtros['tipo_movimiento'];
        }
        if (!empty($filtros['id_producto'])) {
            $sql .= " AND m.id_producto = ?";
            $params[] = $filtros['id_producto'];
        }
        if (!empty($filtros['id_almacen'])) {
            $sql .= " AND m.id_almacen = ?";
            $params[] = $filtros['id_almacen'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(m.fecha_movimiento) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(m.fecha_movimiento) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['documento_numero'])) {
            $sql .= " AND m.documento_numero LIKE ?";
            $params[] = '%' . $filtros['documento_numero'] . '%';
        }
        
        $sql .= " ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = (int) $perPage;
        $params[] = (int) $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
        
    } catch (PDOException $e) {
        error_log("Error en Movimiento::getAllPaginated: " . $e->getMessage());
        return [
            'data' => [],
            'total' => 0,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => 0
        ];
    }
}
}

?>