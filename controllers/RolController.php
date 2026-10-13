<?php
require_once __DIR__ . '/../models/Rol.php';
require_once __DIR__ . '/../../config/permissions.php';

class RolController {
    private Rol $rolModel;
    
    public function __construct() {
        $this->rolModel = new Rol();
        
        // Verificar permisos de administrador
        session_start();
        if ($_SESSION['usuario_rol'] !== 'admin') {
            SessionMiddleware::setFlashMessage('Acceso denegado. Se requiere rol de administrador.', 'danger');
            header('Location: index.php?controller=dashboard&action=index');
            exit();
        }
    }
    
    // Listar roles
    public function index() {
        $activas = $_GET['mostrar'] !== 'inactivas';
        $roles = $this->rolModel->getAll($activas);
        
        require_once __DIR__ . '/../views/roles/index.php';
    }
    
    // Mostrar formulario de creación
    public function crear() {
        $permisosPredefinidos = $this->rolModel->getPermisosPredefinidos();
        require_once __DIR__ . '/../views/roles/crear.php';
    }
    
    // Procesar creación de rol
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=rol&action=crear');
            exit();
        }
        
        try {
            // Obtener permisos seleccionados
            $permisos = [];
            foreach ($_POST['permisos'] ?? [] as $modulo => $acciones) {
                $permisos[$modulo] = $acciones;
            }
            
            $data = [
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'],
                'permisos' => $permisos
            ];
            
            // Validar que nombre no exista
            if ($this->rolModel->nombreExists($data['nombre'])) {
                throw new Exception('El nombre del rol ya existe');
            }
            
            $success = $this->rolModel->create($data);
            
            if ($success) {
                SessionMiddleware::setFlashMessage('Rol creado exitosamente', 'success');
                header('Location: index.php?controller=rol&action=index');
            } else {
                throw new Exception('Error al crear el rol');
            }
        } catch (Exception $e) {
            SessionMiddleware::setFlashMessage($e->getMessage(), 'danger');
            header('Location: index.php?controller=rol&action=crear');
        }
        exit();
    }
    
    // Mostrar formulario de edición
    public function editar($id) {
        $rol = $this->rolModel->getById($id);
        
        if (!$rol) {
            SessionMiddleware::setFlashMessage('Rol no encontrado', 'danger');
            header('Location: index.php?controller=rol&action=index');
            exit();
        }
        
        // Decodificar permisos
        $rol['permisos'] = json_decode($rol['permisos'], true) ?? [];
        
        $permisosPredefinidos = $this->rolModel->getPermisosPredefinidos();
        require_once __DIR__ . '/../views/roles/editar.php';
    }
    
    // Procesar actualización de rol
    public function actualizar($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=rol&action=editar&id=' . $id);
            exit();
        }
        
        try {
            // Obtener permisos seleccionados
            $permisos = [];
            foreach ($_POST['permisos'] ?? [] as $modulo => $acciones) {
                $permisos[$modulo] = $acciones;
            }
            
            $data = [
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'],
                'permisos' => $permisos,
                'estado' => $_POST['estado']
            ];
            
            // Validar que nombre no exista (excluyendo el actual)
            if ($this->rolModel->nombreExists($data['nombre'], $id)) {
                throw new Exception('El nombre del rol ya existe');
            }
            
            $success = $this->rolModel->update($id, $data);
            
            if ($success) {
                SessionMiddleware::setFlashMessage('Rol actualizado exitosamente', 'success');
                header('Location: index.php?controller=rol&action=index');
            } else {
                throw new Exception('Error al actualizar el rol');
            }
        } catch (Exception $e) {
            SessionMiddleware::setFlashMessage($e->getMessage(), 'danger');
            header('Location: index.php?controller=rol&action=editar&id=' . $id);
        }
        exit();
    }
    
    // Eliminar rol
    public function eliminar($id) {
        try {
            $success = $this->rolModel->delete($id);
            
            if ($success) {
                SessionMiddleware::setFlashMessage('Rol eliminado exitosamente', 'success');
            } else {
                throw new Exception('Error al eliminar el rol');
            }
        } catch (Exception $e) {
            SessionMiddleware::setFlashMessage($e->getMessage(), 'danger');
        }
        
        header('Location: index.php?controller=rol&action=index');
        exit();
    }
    
    // Activar rol
    public function activar($id) {
        try {
            $rol = $this->rolModel->getById($id);
            
            if (!$rol) {
                throw new Exception('Rol no encontrado');
            }
            
            $data = [
                'nombre' => $rol['nombre'],
                'descripcion' => $rol['descripcion'],
                'permisos' => json_decode($rol['permisos'], true),
                'estado' => 'activo'
            ];
            
            $success = $this->rolModel->update($id, $data);
            
            if ($success) {
                SessionMiddleware::setFlashMessage('Rol activado exitosamente', 'success');
            } else {
                throw new Exception('Error al activar el rol');
            }
        } catch (Exception $e) {
            SessionMiddleware::setFlashMessage($e->getMessage(), 'danger');
        }
        
        header('Location: index.php?controller=rol&action=index');
        exit();
    }
    
    // Ver detalle de rol
    public function ver($id) {
        $rol = $this->rolModel->getById($id);
        
        if (!$rol) {
            SessionMiddleware::setFlashMessage('Rol no encontrado', 'danger');
            header('Location: index.php?controller=rol&action=index');
            exit();
        }
        
        // Decodificar permisos
        $rol['permisos'] = json_decode($rol['permisos'], true) ?? [];
        
        require_once __DIR__ . '/../views/roles/ver.php';
    }
    
    // AJAX: Verificar nombre disponible
    public function verificar_nombre() {
        $nombre = $_GET['nombre'] ?? '';
        $excludeId = $_GET['exclude_id'] ?? null;
        
        $existe = $this->rolModel->nombreExists($nombre, $excludeId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'disponible' => !$existe,
            'mensaje' => $existe ? 'El nombre ya está en uso' : 'Nombre disponible'
        ]);
        exit();
    }
}
?>