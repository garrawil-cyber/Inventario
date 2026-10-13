<?php
require_once __DIR__ . '/../config/database.php';

class Alerta {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Obtener todas las alertas con filtros
     */
    public function getAll($filtros = []) {
        $sql = "SELECT a.*, 
                       p.nombre as producto_nombre,
                       p.codigo as producto_codigo,
                       p.stock_actual as producto_stock,
                       p.stock_minimo as producto_stock_minimo,
                       u.nombre_completo as atendida_por_nombre
                FROM alertas_stock a
                INNER JOIN productos p ON a.id_producto = p.id_producto
                LEFT JOIN usuarios u ON a.atendida_por = u.id_usuario
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['tipo_alerta'])) {
            $sql .= " AND a.tipo_alerta = ?";
            $params[] = $filtros['tipo_alerta'];
        }
        
        if (!empty($filtros['nivel_urgencia'])) {
            $sql .= " AND a.nivel_urgencia = ?";
            $params[] = $filtros['nivel_urgencia'];
        }
        
        if (!empty($filtros['producto_id'])) {
            $sql .= " AND a.id_producto = ?";
            $params[] = $filtros['producto_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND a.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        $sql .= " ORDER BY 
            CASE a.nivel_urgencia 
                WHEN 'critica' THEN 1
                WHEN 'alta' THEN 2
                WHEN 'media' THEN 3
                WHEN 'baja' THEN 4
                ELSE 5
            END ASC, 
            a.fecha_creacion DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Alerta::getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener alerta por ID
     */
    public function getById($id) {
        $sql = "SELECT a.*, 
                       p.nombre as producto_nombre,
                       p.codigo as producto_codigo,
                       p.stock_actual as producto_stock,
                       p.stock_minimo as producto_stock_minimo,
                       p.precio_compra as producto_precio_compra,
                       u.nombre_completo as atendida_por_nombre
                FROM alertas_stock a
                INNER JOIN productos p ON a.id_producto = p.id_producto
                LEFT JOIN usuarios u ON a.atendida_por = u.id_usuario
                WHERE a.id_alerta = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Alerta::getById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crear nueva alerta manual
     */
    public function crear($id_producto, $tipo_alerta, $mensaje, $nivel_urgencia) {
        $sql = "INSERT INTO alertas_stock 
                (id_producto, tipo_alerta, mensaje, nivel_urgencia, estado, fecha_creacion) 
                VALUES (?, ?, ?, ?, 'activa', NOW())";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_producto, $tipo_alerta, $mensaje, $nivel_urgencia]);
        } catch (PDOException $e) {
            error_log("Error en Alerta::crear: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marcar alerta como atendida
     */
    public function atender($id) {
        $sql = "UPDATE alertas_stock 
                SET estado = 'atendida', 
                    fecha_atencion = NOW(),
                    atendida_por = ?
                WHERE id_alerta = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            return $stmt->execute([$usuario_id, $id]);
        } catch (PDOException $e) {
            error_log("Error en Alerta::atender: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marcar alerta como ignorada
     */
    public function ignorar($id) {
        $sql = "UPDATE alertas_stock 
                SET estado = 'ignorada', 
                    fecha_atencion = NOW(),
                    atendida_por = ?
                WHERE id_alerta = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $usuario_id = $_SESSION['usuario_id'] ?? 0;
            return $stmt->execute([$usuario_id, $id]);
        } catch (PDOException $e) {
            error_log("Error en Alerta::ignorar: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Contar alertas pendientes
     */
    public function countPendientes() {
        $sql = "SELECT COUNT(*) as total FROM alertas_stock WHERE estado = 'activa'";
        
        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error en Alerta::countPendientes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Contar alertas por nivel de urgencia
     */
    public function countByUrgencia() {
        $sql = "SELECT 
                    nivel_urgencia,
                    COUNT(*) as cantidad,
                    CASE nivel_urgencia
                        WHEN 'critica' THEN 1
                        WHEN 'alta' THEN 2
                        WHEN 'media' THEN 3
                        WHEN 'baja' THEN 4
                        ELSE 5
                    END as orden
                FROM alertas_stock 
                WHERE estado = 'activa'
                GROUP BY nivel_urgencia
                ORDER BY orden ASC";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Alerta::countByUrgencia: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar total de alertas por estado
     */
    public function countTotal() {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as cantidad
                FROM alertas_stock 
                GROUP BY estado";
        
        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totales = [
                'activa' => 0,
                'atendida' => 0,
                'ignorada' => 0,
                'total' => 0
            ];
            
            foreach ($result as $row) {
                $totales[$row['estado']] = $row['cantidad'];
                $totales['total'] += $row['cantidad'];
            }
            
            return $totales;
        } catch (PDOException $e) {
            error_log("Error en Alerta::countTotal: " . $e->getMessage());
            return ['activa' => 0, 'atendida' => 0, 'ignorada' => 0, 'total' => 0];
        }
    }
    
    /**
     * Limpiar alertas antiguas (más de 30 días)
     */
    public function limpiarAntiguas() {
        $sql = "DELETE FROM alertas_stock 
                WHERE estado IN ('atendida', 'ignorada') 
                AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error en Alerta::limpiarAntiguas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar alertas automáticas basadas en stock actual
     */
    public function generarAlertasAutomaticas() {
        try {
            // Obtener productos con stock bajo o crítico sin alertas activas
            $sql = "SELECT 
                        p.id_producto,
                        p.nombre,
                        p.stock_actual,
                        p.stock_minimo,
                        p.estado_stock,
                        COUNT(a.id_alerta) as alertas_activas
                    FROM productos p
                    LEFT JOIN alertas_stock a ON p.id_producto = a.id_producto 
                        AND a.estado = 'activa'
                    WHERE p.estado = 'activo'
                        AND p.estado_stock IN ('bajo', 'critico', 'agotado')
                    GROUP BY p.id_producto, p.nombre, p.stock_actual, p.stock_minimo, p.estado_stock
                    HAVING alertas_activas = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $alertasGeneradas = 0;
            
            foreach ($productos as $producto) {
                $tipoAlerta = '';
                $nivelUrgencia = '';
                $mensaje = '';
                
                if ($producto['stock_actual'] == 0) {
                    $tipoAlerta = 'stock_agotado';
                    $nivelUrgencia = 'critica';
                    $mensaje = "AGOTADO: {$producto['nombre']} sin stock disponible";
                } elseif ($producto['estado_stock'] == 'critico') {
                    $tipoAlerta = 'stock_critico';
                    $nivelUrgencia = 'critica';
                    $mensaje = "CRÍTICO: {$producto['nombre']} tiene stock muy bajo ({$producto['stock_actual']} unidades)";
                } elseif ($producto['estado_stock'] == 'bajo') {
                    $tipoAlerta = 'stock_bajo';
                    $nivelUrgencia = 'alta';
                    $mensaje = "ALERTA: {$producto['nombre']} requiere reposición ({$producto['stock_actual']} unidades)";
                }
                
                if ($tipoAlerta) {
                    $sqlInsert = "INSERT INTO alertas_stock 
                                  (id_producto, tipo_alerta, nivel_urgencia, mensaje, 
                                   stock_actual, stock_requerido, estado, fecha_creacion)
                                  VALUES (?, ?, ?, ?, ?, ?, 'activa', NOW())";
                    
                    $stmtInsert = $this->db->prepare($sqlInsert);
                    $success = $stmtInsert->execute([
                        $producto['id_producto'],
                        $tipoAlerta,
                        $nivelUrgencia,
                        $mensaje,
                        $producto['stock_actual'],
                        $producto['stock_minimo']
                    ]);
                    
                    if ($success) {
                        $alertasGeneradas++;
                    }
                }
            }
            
            return $alertasGeneradas;
            
        } catch (PDOException $e) {
            error_log("Error en Alerta::generarAlertasAutomaticas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener alertas críticas para dashboard
     */
    public function getAlertasCriticas($limit = 5) {
        $sql = "SELECT a.*, 
                       p.nombre as producto_nombre, 
                       p.codigo as producto_codigo,
                       p.stock_actual as producto_stock
                FROM alertas_stock a
                INNER JOIN productos p ON a.id_producto = p.id_producto
                WHERE a.estado = 'activa' AND a.nivel_urgencia = 'critica'
                ORDER BY a.fecha_creacion DESC
                LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Alerta::getAlertasCriticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener resumen de alertas por tipo
     */
    public function getResumenPorTipo() {
        $sql = "SELECT 
                    tipo_alerta,
                    COUNT(*) as cantidad,
                    SUM(CASE WHEN nivel_urgencia = 'critica' THEN 1 ELSE 0 END) as criticas
                FROM alertas_stock 
                WHERE estado = 'activa'
                GROUP BY tipo_alerta
                ORDER BY cantidad DESC";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Alerta::getResumenPorTipo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si ya existe alerta activa para un producto
     */
    public function existeAlertaActiva($id_producto, $tipo_alerta = null) {
        $sql = "SELECT COUNT(*) as total 
                FROM alertas_stock 
                WHERE id_producto = ? 
                    AND estado = 'activa'";
        
        $params = [$id_producto];
        
        if ($tipo_alerta) {
            $sql .= " AND tipo_alerta = ?";
            $params[] = $tipo_alerta;
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['total'] ?? 0) > 0;
        } catch (PDOException $e) {
            error_log("Error en Alerta::existeAlertaActiva: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Alias para countPendientes (compatibilidad)
     */
    public function countAlertasPendientes() {
        return $this->countPendientes();
    }
}
?>