<?php
require_once __DIR__ . '/../config/database.php';

class Rol {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    // Obtener todos los roles
    public function getAll($activos = true) {
        $sql = "SELECT r.*, 
                       COUNT(u.id_usuario) as total_usuarios
                FROM roles r
                LEFT JOIN usuarios u ON r.id_rol = u.id_rol
                WHERE 1=1";
        
        if ($activos) {
            $sql .= " AND r.estado = 'activo'";
        }
        
        $sql .= " GROUP BY r.id_rol
                  ORDER BY r.nombre";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    // Obtener rol por ID
    public function getById($id) {
        $sql = "SELECT r.*, 
                       COUNT(u.id_usuario) as total_usuarios
                FROM roles r
                LEFT JOIN usuarios u ON r.id_rol = u.id_rol
                WHERE r.id_rol = :id
                GROUP BY r.id_rol";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    // Crear nuevo rol
    public function create($data) {
        $sql = "INSERT INTO roles (nombre, descripcion, permisos, estado)
                VALUES (:nombre, :descripcion, :permisos, 'activo')";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'],
            ':permisos' => json_encode($data['permisos'])
        ]);
    }
    
    // Actualizar rol
    public function update($id, $data) {
        $sql = "UPDATE roles SET 
                nombre = :nombre,
                descripcion = :descripcion,
                permisos = :permisos,
                estado = :estado
                WHERE id_rol = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'],
            ':permisos' => json_encode($data['permisos']),
            ':estado' => $data['estado']
        ]);
    }
    
    // Eliminar rol (cambiar estado a inactivo)
    public function delete($id) {
        // Verificar si hay usuarios asociados
        $sqlCheck = "SELECT COUNT(*) as total FROM usuarios 
                     WHERE id_rol = :id AND estado = 'activo'";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([':id' => $id]);
        $result = $stmtCheck->fetch();
        
        if ($result['total'] > 0) {
            throw new Exception("No se puede eliminar el rol porque tiene usuarios asociados.");
        }
        
        $sql = "UPDATE roles SET estado = 'inactivo' WHERE id_rol = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    // Obtener roles para select
    public function getForSelect() {
        $sql = "SELECT id_rol as value, nombre as label 
                FROM roles 
                WHERE estado = 'activo'
                ORDER BY nombre";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    // Verificar si nombre ya existe
    public function nombreExists($nombre, $excludeId = null) {
        $sql = "SELECT COUNT(*) as total FROM roles 
                WHERE nombre = :nombre";
        
        if ($excludeId) {
            $sql .= " AND id_rol != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':nombre' => $nombre];
        
        if ($excludeId) {
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }
    
    // Obtener permisos predefinidos
    public function getPermisosPredefinidos() {
        return [
            'dashboard' => ['Ver Dashboard'],
            'productos' => ['Ver Productos', 'Crear Productos', 'Editar Productos', 'Eliminar Productos'],
            'movimientos' => ['Ver Movimientos', 'Registrar Entradas', 'Registrar Salidas'],
            'kardex' => ['Ver Kardex', 'Exportar Kardex'],
            'alertas' => ['Ver Alertas', 'Gestionar Alertas'],
            'reportes' => ['Ver Reportes', 'Generar Reportes', 'Exportar Reportes'],
            'categorias' => ['Ver Categorías', 'Gestionar Categorías'],
            'proveedores' => ['Ver Proveedores', 'Gestionar Proveedores'],
            'almacenes' => ['Ver Almacenes', 'Gestionar Almacenes'],
            'usuarios' => ['Ver Usuarios', 'Gestionar Usuarios'],
            'configuracion' => ['Configurar Sistema']
        ];
    }
}
?>