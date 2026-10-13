<?php
require_once __DIR__ . '/../models/Alerta.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class AlertaController {
    private Alerta $alertaModel;
    private Producto $productoModel;
    
    public function __construct() {
        $this->alertaModel = new Alerta();
        $this->productoModel = new Producto();
        
        // Iniciar sesión si no está iniciada
        SessionMiddleware::checkAuth();
    }
    
    // ============================================================
    // MÉTODOS PRINCIPALES
    // ============================================================
    
    /**
     * Listar alertas con filtros
     */
    public function index() {
        $filtros = [];
        
        // Aplicar filtros desde GET
        if (!empty($_GET['tipo_alerta'])) {
            $filtros['tipo_alerta'] = $_GET['tipo_alerta'];
        }
        
        if (!empty($_GET['nivel_urgencia'])) {
            $filtros['nivel_urgencia'] = $_GET['nivel_urgencia'];
        }
        
        if (!empty($_GET['producto_id'])) {
            $filtros['producto_id'] = $_GET['producto_id'];
        }
        
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        } else {
            $filtros['estado'] = 'activa'; // Por defecto mostrar solo activas
        }
        
        // Obtener datos
        $alertas = $this->alertaModel->getAll($filtros);
        $estadisticasUrgencia = $this->alertaModel->countByUrgencia();
        $totales = $this->alertaModel->countTotal();
        $productos = $this->productoModel->getAll(['estado' => 'activo']);
        
        // Tipos de alerta disponibles
        $tiposAlerta = [
            'stock_bajo' => 'Stock Bajo',
            'stock_critico' => 'Stock Crítico',
            'stock_agotado' => 'Stock Agotado',
            'proximo_vencer' => 'Próximo a Vencer',
            'vencido' => 'Vencido'
        ];
        
        // Niveles de urgencia
        $nivelesUrgencia = [
            'critica' => 'Crítica',
            'alta' => 'Alta',
            'media' => 'Media',
            'baja' => 'Baja'
        ];
        
        // Estados
        $estados = [
            'activa' => 'Activa',
            'atendida' => 'Atendida',
            'ignorada' => 'Ignorada'
        ];
        
        // Verificar si la vista existe
        $viewPath = __DIR__ . '/../views/inventario/alertas/index.php';
        if (!file_exists($viewPath)) {
            die("Error: La vista no existe en: $viewPath");
        }
        
        require_once $viewPath;
    }
    
    /**
     * Ver detalle de alerta
     */
    public function ver($id) {
        $alerta = $this->alertaModel->getById($id);
        
        if (!$alerta) {
            SessionMiddleware::setSweetAlert('Error', 'Alerta no encontrada', 'error');
            header('Location: index.php?controller=alerta&action=index');
            exit();
        }
        
        // Tipos de alerta para mostrar
        $tiposAlerta = [
            'stock_bajo' => 'Stock Bajo',
            'stock_critico' => 'Stock Crítico',
            'stock_agotado' => 'Stock Agotado',
            'proximo_vencer' => 'Próximo a Vencer',
            'vencido' => 'Vencido'
        ];
        
        // Niveles de urgencia
        $nivelesUrgencia = [
            'critica' => 'Crítica',
            'alta' => 'Alta',
            'media' => 'Media',
            'baja' => 'Baja'
        ];
        
        require_once __DIR__ . '/../views/inventario/alertas/ver.php';
    }
    
    // ============================================================
    // MÉTODOS DE ACCIÓN SOBRE ALERTAS
    // ============================================================
    
    /**
     * Marcar alerta como atendida
     */
    public function atender($id) {
        try {
            $success = $this->alertaModel->atender($id);
            
            if ($success) {
                SessionMiddleware::setSweetAlert('Éxito', 'Alerta marcada como atendida correctamente', 'success');
            } else {
                throw new Exception('Error al marcar la alerta como atendida');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }
        
        header('Location: index.php?controller=alerta&action=index');
        exit();
    }
    
    /**
     * Marcar alerta como ignorada
     */
    public function ignorar($id) {
        try {
            $success = $this->alertaModel->ignorar($id);
            
            if ($success) {
                SessionMiddleware::setSweetAlert('Alerta Ignorada', 'La alerta ha sido marcada como ignorada', 'warning');
            } else {
                throw new Exception('Error al marcar la alerta como ignorada');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }
        
        header('Location: index.php?controller=alerta&action=index');
        exit();
    }
    
    /**
     * Atender múltiples alertas a la vez
     */
    public function atenderMultiple() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=alerta&action=index');
            exit();
        }
        
        try {
            $ids = $_POST['alertas'] ?? [];
            
            if (empty($ids)) {
                throw new Exception('No se seleccionaron alertas para atender');
            }
            
            $contador = 0;
            foreach ($ids as $id) {
                if ($this->alertaModel->atender($id)) {
                    $contador++;
                }
            }
            
            SessionMiddleware::setSweetAlert('Éxito', "{$contador} alertas marcadas como atendidas", 'success');
            
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }
        
        header('Location: index.php?controller=alerta&action=index');
        exit();
    }
    
    // ============================================================
    // MÉTODOS DE CREACIÓN Y GESTIÓN
    // ============================================================
    
    /**
     * Mostrar formulario para crear alerta manual
     */
    public function crear() {
        $productos = $this->productoModel->getAll(['estado' => 'activo']);
        
        // Tipos de alerta
        $tiposAlerta = [
            'stock_bajo' => 'Stock Bajo',
            'stock_critico' => 'Stock Crítico',
            'stock_agotado' => 'Stock Agotado',
            'proximo_vencer' => 'Próximo a Vencer',
            'vencido' => 'Vencido'
        ];
        
        // Niveles de urgencia
        $nivelesUrgencia = [
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Crítica'
        ];
        
        require_once __DIR__ . '/../views/inventario/alertas/crear.php';
    }
    
    /**
     * Procesar creación de alerta manual
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=alerta&action=crear');
            exit();
        }
        
        try {
            $idProducto = $_POST['id_producto'] ?? '';
            $tipoAlerta = $_POST['tipo_alerta'] ?? '';
            $mensaje = trim($_POST['mensaje'] ?? '');
            $nivelUrgencia = $_POST['nivel_urgencia'] ?? 'media';
            
            // Validaciones
            if (empty($idProducto)) {
                throw new Exception('Debe seleccionar un producto');
            }
            
            if (empty($tipoAlerta)) {
                throw new Exception('Debe seleccionar un tipo de alerta');
            }
            
            if (empty($mensaje)) {
                throw new Exception('El mensaje de la alerta es obligatorio');
            }
            
            // Verificar si ya existe alerta activa para este producto
            if ($this->alertaModel->existeAlertaActiva($idProducto, $tipoAlerta)) {
                SessionMiddleware::setSweetAlert('Advertencia', 'Ya existe una alerta activa de este tipo para el producto seleccionado', 'warning');
                header('Location: index.php?controller=alerta&action=crear');
                exit();
            }
            
            $success = $this->alertaModel->crear($idProducto, $tipoAlerta, $mensaje, $nivelUrgencia);
            
            if ($success) {
                SessionMiddleware::setSweetAlert('Éxito', 'Alerta creada exitosamente', 'success');
                header('Location: index.php?controller=alerta&action=index');
            } else {
                throw new Exception('Error al crear la alerta');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
            header('Location: index.php?controller=alerta&action=crear');
        }
        exit();
    }
    
    // ============================================================
    // MÉTODOS DE MANTENIMIENTO
    // ============================================================
    
    /**
     * Ejecutar limpieza de alertas antiguas
     */
    public function limpiar() {
        try {
            $eliminadas = $this->alertaModel->limpiarAntiguas();
            
            if ($eliminadas !== false) {
                SessionMiddleware::setSweetAlert('Éxito', "Se eliminaron {$eliminadas} alertas antiguas", 'success');
            } else {
                throw new Exception('Error al limpiar alertas antiguas');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }
        
        header('Location: index.php?controller=alerta&action=index');
        exit();
    }
    
    /**
     * Generar alertas automáticas
     */
    public function generar_automaticas() {
        try {
            $generadas = $this->alertaModel->generarAlertasAutomaticas();
            
            if ($generadas !== false) {
                SessionMiddleware::setSweetAlert('Éxito', "Se generaron {$generadas} nuevas alertas automáticas", 'success');
            } else {
                throw new Exception('Error al generar alertas automáticas');
            }
        } catch (Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
        }
        
        header('Location: index.php?controller=alerta&action=index');
        exit();
    }
    
    // ============================================================
    // MÉTODOS AJAX/API
    // ============================================================
    
    /**
     * Obtener estadísticas de alertas (AJAX)
     */
    public function estadisticas() {
        // Verificar si es petición AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (!$isAjax) {
            header('Location: index.php?controller=alerta&action=index');
            exit();
        }
        
        try {
            $estadisticas = [
                'pendientes' => $this->alertaModel->countPendientes(),
                'por_urgencia' => $this->alertaModel->countByUrgencia(),
                'totales' => $this->alertaModel->countTotal(),
                'resumen_tipo' => $this->alertaModel->getResumenPorTipo(),
                'criticas' => $this->alertaModel->getAlertasCriticas(10)
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'estadisticas' => $estadisticas,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
    
    /**
     * Obtener alertas recientes para dashboard (AJAX)
     */
    public function recientes() {
        // Verificar si es petición AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (!$isAjax) {
            header('Location: index.php?controller=alerta&action=index');
            exit();
        }
        
        $limit = $_GET['limit'] ?? 10;
        $alertas = $this->alertaModel->getAll([
            'estado' => 'activa',
            'nivel_urgencia' => 'critica'
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'alertas' => array_slice($alertas, 0, $limit),
            'total' => count($alertas)
        ]);
        exit();
    }
    
    /**
     * Marcar alerta como atendida (AJAX)
     */
    public function atenderAjax() {
        // Verificar si es petición AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (!$isAjax) {
            header('Location: index.php?controller=alerta&action=index');
            exit();
        }
        
        $id = $_POST['id'] ?? 0;
        
        try {
            if (empty($id)) {
                throw new Exception('ID de alerta no válido');
            }
            
            $success = $this->alertaModel->atender($id);
            
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Alerta atendida correctamente'
                ];
            } else {
                throw new Exception('Error al atender la alerta');
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}
?>