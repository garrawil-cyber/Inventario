<?php
require_once __DIR__ . '/../config/database.php';

class Alerta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Obtener alertas críticas
    public function getAlertasCriticas($limit = 10) {
        $sql = "SELECT a.*, p.codigo as producto_codigo, p.nombre as producto_nombre,
                       p.stock_actual, p.stock_minimo, u.nombre as usuario_nombre
                FROM alertas_stock a
                INNER JOIN productos p ON a.id_producto = p.id_producto
                LEFT JOIN usuarios u ON a.atendida_por = u.id_usuario
                WHERE a.estado = 'activa' 
                AND a.nivel_urgencia = 'critica'
                ORDER BY a.fecha_creacion DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Obtener todas las alertas activas
    public function getAll($filtros = []) {
        $sql = "SELECT a.*, p.codigo as producto_codigo, p.nombre as producto_nombre,
                       p.stock_minimo, u.nombre_completo as atendido_por_nombre
                FROM alertas_stock a
                INNER JOIN productos p ON a.id_producto = p.id_producto
                LEFT JOIN usuarios u ON a.atendida_por = u.id_usuario
                WHERE a.estado = 'activa'";
        
        $params = [];
        
        // Aplicar filtros
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
        
        $sql .= " ORDER BY 
                    CASE a.nivel_urgencia
                        WHEN 'critica' THEN 1
                        WHEN 'alta' THEN 2
                        WHEN 'media' THEN 3
                        WHEN 'baja' THEN 4
                    END,
                    a.fecha_creacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Contar alertas pendientes
    public function countPendientes() {
        $sql = "SELECT COUNT(*) as total FROM alertas_stock WHERE estado = 'activa'";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    // Contar por nivel de urgencia
    public function countByUrgencia() {
        $sql = "SELECT 
                    nivel_urgencia,
                    COUNT(*) as cantidad
                FROM alertas_stock 
                WHERE estado = 'activa'
                GROUP BY nivel_urgencia
                ORDER BY 
                    CASE nivel_urgencia
                        WHEN 'critica' THEN 1
                        WHEN 'alta' THEN 2
                        WHEN 'media' THEN 3
                        WHEN 'baja' THEN 4
                    END";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    // Obtener alerta por ID
    public function getById($id) {
        $sql = "SELECT a.*, p.codigo as producto_codigo, p.nombre as producto_nombre,
                       p.stock_actual, p.stock_minimo, p.estado_stock
                FROM alertas_stock a
                INNER JOIN productos p ON a.id_producto = p.id_producto
                WHERE a.id_alerta = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Marcar alerta como atendida
    public function atender($idAlerta, $observaciones = null) {
        $sql = "UPDATE alertas_stock SET 
                    estado = 'atendida',
                    atendida_por = ?,
                    fecha_atencion = NOW()
                WHERE id_alerta = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$_SESSION['usuario_id'], $idAlerta]);
    }
    
    // Marcar alerta como ignorada
    public function ignorar($idAlerta) {
        $sql = "UPDATE alertas_stock SET estado = 'ignorada' WHERE id_alerta = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idAlerta]);
    }
    
    // Crear alerta manualmente
    public function crear($idProducto, $tipoAlerta, $mensaje, $nivelUrgencia = 'media') {
        $sql = "INSERT INTO alertas_stock (
            id_producto, tipo_alerta, nivel_urgencia, mensaje,
            stock_actual, stock_requerido
        ) VALUES (?, ?, ?, ?, 
            (SELECT stock_actual FROM productos WHERE id_producto = ?),
            (SELECT stock_minimo FROM productos WHERE id_producto = ?)
        )";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $idProducto, $tipoAlerta, $nivelUrgencia, $mensaje,
            $idProducto, $idProducto
        ]);
    }
    
    // Eliminar alertas antiguas (más de 30 días)
    public function limpiarAntiguas() {
        $sql = "DELETE FROM alertas_stock 
                WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND estado != 'activa'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }
    
    // Verificar si hay alertas activas para un producto
    public function tieneAlertasActivas($idProducto) {
        $sql = "SELECT COUNT(*) as total 
                FROM alertas_stock 
                WHERE id_producto = ? AND estado = 'activa'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }
}
?>