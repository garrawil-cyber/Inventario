<?php
/**
 * MovimientoController — VERSIÓN COMPLETA CORREGIDA
 * Incluye manejo de sweet_alert para entrada.php
 */

require_once __DIR__ . '/../config/SessionMiddleware.php';
require_once __DIR__ . '/../models/Movimiento.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/../models/Cliente.php'; // Agregado para completar

class MovimientoController {

    private Movimiento $movimientoModel;
    private Producto $productoModel;
    private Almacen $almacenModel;
    private Proveedor $proveedorModel;

    public function __construct() {
        SessionMiddleware::checkAuth();
        $this->movimientoModel = new Movimiento();
        $this->productoModel   = new Producto();
        $this->almacenModel    = new Almacen();
        $this->proveedorModel  = new Proveedor();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Formulario de entrada (CORREGIDO con sweetAlert)
    // ─────────────────────────────────────────────────────────────────────────
    public function entrada($idProducto = null) {
        $productos   = $this->productoModel->getAll(['estado' => 'activo']);
        $almacenes   = $this->almacenModel->getForSelect();
        $proveedores = $this->proveedorModel->getForSelect();

        $producto = null;
        if ($idProducto) {
            $producto = $this->productoModel->getById($idProducto);
        }
        
        // Recuperar sweetAlert de la sesión si existe
        $sweetAlert = null;
        if (isset($_SESSION['sweet_alert'])) {
            $sweetAlert = $_SESSION['sweet_alert'];
            unset($_SESSION['sweet_alert']);
        }

        require_once __DIR__ . '/../views/inventario/movimientos/entrada.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Procesar entrada (CORREGIDO con sweetAlert)
    // ─────────────────────────────────────────────────────────────────────────
    public function procesar_entrada() {
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setSweetAlert('error', 'Método no permitido', 'La solicitud debe ser POST', 2000);
            SessionMiddleware::redirect('index.php?controller=movimiento&action=entrada');
            return;
        }

        try {
            // Validar campos requeridos
            if (empty($_POST['id_producto'])) {
                throw new Exception('Debe seleccionar un producto');
            }
            
            if (empty($_POST['id_almacen'])) {
                throw new Exception('Debe seleccionar un almacén destino');
            }
            
            if (empty($_POST['cantidad']) || $_POST['cantidad'] <= 0) {
                throw new Exception('La cantidad debe ser mayor a cero');
            }
            
            if (empty($_POST['precio_unitario']) || $_POST['precio_unitario'] <= 0) {
                throw new Exception('El precio unitario debe ser mayor a cero');
            }
            
            // Obtener información del producto para el mensaje
            $producto = $this->productoModel->getById($_POST['id_producto']);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            // Preparar datos para el modelo
            $data = [
                'id_producto'       => (int)   $_POST['id_producto'],
                'id_almacen'        => (int)   $_POST['id_almacen'],
                'cantidad'          => (float) $_POST['cantidad'],
                'costo_unitario'    => (float) $_POST['precio_unitario'],
                'id_proveedor'      => !empty($_POST['id_proveedor']) ? (int) $_POST['id_proveedor'] : null,
                'documento_tipo'    => !empty($_POST['documento_tipo']) ? $_POST['documento_tipo'] : null,
                'documento_numero'  => !empty($_POST['documento_numero']) ? $_POST['documento_numero'] : null,
                'observaciones'     => !empty($_POST['observaciones']) ? $_POST['observaciones'] : null,
            ];
            
            // Registrar entrada
            $result = $this->movimientoModel->registrarEntrada($data);
            
            if ($result['success']) {
                // SweetAlert de éxito
                $this->setSweetAlert(
                    'success',
                    '¡Entrada registrada!',
                    sprintf(
                        'Se registraron %d unidades de "%s". Stock actual: %d unidades. Código: %s',
                        $data['cantidad'],
                        $producto['nombre'],
                        $result['stock_nuevo'],
                        $result['codigo']
                    ),
                    4000
                );
                
                SessionMiddleware::redirect('index.php?controller=movimiento&action=index');
            } else {
                throw new Exception($result['error'] ?? 'Error al registrar la entrada');
            }
            
        } catch (Exception $e) {
            // SweetAlert de error
            $this->setSweetAlert(
                'error',
                'Error al registrar entrada',
                $e->getMessage(),
                4000
            );
            
            // Redirigir de vuelta al formulario con el producto preseleccionado si existe
            $redirectUrl = 'index.php?controller=movimiento&action=entrada';
            if (!empty($_POST['id_producto'])) {
                $redirectUrl .= '&id=' . $_POST['id_producto'];
            }
            SessionMiddleware::redirect($redirectUrl);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Formulario de salida (CORREGIDO con sweetAlert)
    // ─────────────────────────────────────────────────────────────────────────
    public function salida($idProducto = null) {
        $productos = $this->productoModel->getAll(['estado' => 'activo']);
        $almacenes = $this->almacenModel->getForSelect();

        $producto = null;
        if ($idProducto) {
            $producto = $this->productoModel->getById($idProducto);
        }
        
        // Recuperar sweetAlert de la sesión si existe
        $sweetAlert = null;
        if (isset($_SESSION['sweet_alert'])) {
            $sweetAlert = $_SESSION['sweet_alert'];
            unset($_SESSION['sweet_alert']);
        }

        require_once __DIR__ . '/../views/inventario/movimientos/salida.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Procesar salida (CORREGIDO con sweetAlert y cliente)
    // ─────────────────────────────────────────────────────────────────────────
    public function procesar_salida() {
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setSweetAlert('error', 'Método no permitido', 'La solicitud debe ser POST', 2000);
            SessionMiddleware::redirect('index.php?controller=movimiento&action=salida');
            return;
        }

        try {
            // Validar campos requeridos
            if (empty($_POST['id_producto'])) {
                throw new Exception('Debe seleccionar un producto');
            }
            
            if (empty($_POST['id_almacen'])) {
                throw new Exception('Debe seleccionar un almacén origen');
            }
            
            if (empty($_POST['cantidad']) || $_POST['cantidad'] <= 0) {
                throw new Exception('La cantidad debe ser mayor a cero');
            }
            
            // Validar cliente si es venta (tipo_movimiento = 3)
            if (($_POST['id_tipo_movimiento'] ?? 0) == 3) {
                if (empty($_POST['id_cliente'])) {
                    throw new Exception('Para registrar una VENTA, debe seleccionar un cliente');
                }
            }
            
            // Obtener información del producto para el mensaje
            $producto = $this->productoModel->getById($_POST['id_producto']);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            // Preparar datos para el modelo
            $data = [
                'id_producto'       => (int)   $_POST['id_producto'],
                'id_almacen'        => (int)   $_POST['id_almacen'],
                'cantidad'          => (float) $_POST['cantidad'],
                'id_tipo_movimiento'=> (int)   ($_POST['id_tipo_movimiento'] ?? 3),
                'documento_tipo'    => !empty($_POST['documento_tipo']) ? $_POST['documento_tipo'] : null,
                'documento_numero'  => !empty($_POST['documento_numero']) ? $_POST['documento_numero'] : null,
                'observaciones'     => !empty($_POST['observaciones']) ? $_POST['observaciones'] : null,
            ];
            
            // Registrar salida
            $result = $this->movimientoModel->registrarSalida($data);
            
            if ($result['success']) {
                // Construir mensaje de éxito
                $mensajeExito = sprintf(
                    'Se retiraron %d unidades de "%s". Stock actual: %d unidades. Código: %s',
                    $data['cantidad'],
                    $producto['nombre'],
                    $result['stock_nuevo'],
                    $result['codigo']
                );
                
                // Agregar información del cliente si es venta
                if (($data['id_tipo_movimiento'] ?? 0) == 3 && !empty($_POST['id_cliente'])) {
                    $clienteModel = new Cliente();
                    $cliente = $clienteModel->getById($_POST['id_cliente']);
                    if ($cliente) {
                        $mensajeExito .= sprintf(' Cliente: %s', $cliente['nombre_completo']);
                    }
                }
                
                // SweetAlert de éxito
                $this->setSweetAlert(
                    'success',
                    '¡Salida registrada!',
                    $mensajeExito,
                    4000
                );
                
                SessionMiddleware::redirect('index.php?controller=movimiento&action=index');
            } else {
                throw new Exception($result['error'] ?? 'Error al registrar la salida');
            }
            
        } catch (Exception $e) {
            // SweetAlert de error
            $this->setSweetAlert(
                'error',
                'Error al registrar salida',
                $e->getMessage(),
                4000
            );
            
            // Redirigir de vuelta al formulario con el producto preseleccionado si existe
            $redirectUrl = 'index.php?controller=movimiento&action=salida';
            if (!empty($_POST['id_producto'])) {
                $redirectUrl .= '&id=' . $_POST['id_producto'];
            }
            SessionMiddleware::redirect($redirectUrl);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Método auxiliar para configurar SweetAlert en sesión
    // ─────────────────────────────────────────────────────────────────────────
    private function setSweetAlert($type, $title, $message, $timer = 3000, $showConfirmButton = false) {
        $_SESSION['sweet_alert'] = [
            'type' => $type,              // success, error, warning, info, question
            'title' => $title,
            'text' => $message,
            'timer' => $timer,
            'timerProgressBar' => true,
            'showConfirmButton' => $showConfirmButton,
            'position' => 'top-end',
            'toast' => ($timer <= 3000) ? true : false
        ];
        
        // Si es error, mostrar confirm button
        if ($type === 'error') {
            $_SESSION['sweet_alert']['showConfirmButton'] = true;
            $_SESSION['sweet_alert']['timer'] = null;
            $_SESSION['sweet_alert']['toast'] = false;
        }
        
        // Si es success y timer es largo, mostrar confirm button opcional
        if ($type === 'success' && $timer > 3000) {
            $_SESSION['sweet_alert']['showConfirmButton'] = true;
            $_SESSION['sweet_alert']['confirmButtonText'] = 'Aceptar';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Listar todos los movimientos
    // ─────────────────────────────────────────────────────────────────────────
    // ─────────────────────────────────────────────────────────────────────────
// Listar todos los movimientos (CON PAGINACIÓN)
// ─────────────────────────────────────────────────────────────────────────
public function index() {
    $filters = [];

    if (!empty($_GET['tipo_movimiento'])) {
        $filters['tipo_movimiento'] = $_GET['tipo_movimiento'];
    }

    if (!empty($_GET['id_producto'])) {
        $filters['id_producto'] = (int) $_GET['id_producto'];
    }

    if (!empty($_GET['id_almacen'])) {
        $filters['id_almacen'] = (int) $_GET['id_almacen'];
    }

    if (!empty($_GET['fecha_desde'])) {
        $filters['fecha_desde'] = $_GET['fecha_desde'];
    }

    if (!empty($_GET['fecha_hasta'])) {
        $filters['fecha_hasta'] = $_GET['fecha_hasta'];
    }

    if (!empty($_GET['documento_numero'])) {
        $filters['documento_numero'] = $_GET['documento_numero'];
    }

    // ── Paginación ──────────────────────────────────────────────
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = (int) ($_GET['per_page'] ?? 20);
    
    // Validar límites
    if ($page < 1) $page = 1;
    if ($perPage < 5) $perPage = 5;
    if ($perPage > 100) $perPage = 100;

    // Obtener movimientos con paginación
    $result = $this->movimientoModel->getAllPaginated($filters, $page, $perPage);
    
    $movimientos = $result['data'];
    $totalRegistros = $result['total'];
    $totalPaginas = $result['last_page'];
    $paginaActual = $result['current_page'];
    $registrosPorPagina = $result['per_page'];
    
    // Obtener datos para filtros
    $productos   = $this->productoModel->getAll(['estado' => 'activo']);
    $almacenes   = $this->almacenModel->getForFilters();
    
    $estadisticas = $this->movimientoModel->getEstadisticas(
        $filters['fecha_desde'] ?? date('Y-m-01'),
        $filters['fecha_hasta'] ?? date('Y-m-d')
    );

    require_once __DIR__ . '/../views/inventario/movimientos/index.php';
}
    // ─────────────────────────────────────────────────────────────────────────
    // Ver detalle de movimiento
    // ─────────────────────────────────────────────────────────────────────────
    public function ver($id) {
        $movimiento = $this->movimientoModel->getById($id);

        if (!$movimiento) {
            $this->setSweetAlert('error', 'No encontrado', 'El movimiento no existe', 3000);
            SessionMiddleware::redirect('index.php?controller=movimiento&action=index');
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'movimiento' => $movimiento]);
            exit();
        }

        header('Location: index.php?controller=movimiento&action=index');
        exit();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Exportar a Excel
    // ─────────────────────────────────────────────────────────────────────────
    public function exportar() {
        $filters = [];

        if (!empty($_GET['tipo_movimiento'])) {
            $filters['tipo_movimiento'] = $_GET['tipo_movimiento'];
        }
        if (!empty($_GET['id_producto'])) {
            $filters['id_producto'] = (int) $_GET['id_producto'];
        }
        if (!empty($_GET['id_almacen'])) {
            $filters['id_almacen'] = (int) $_GET['id_almacen'];
        }
        if (!empty($_GET['fecha_desde'])) {
            $filters['fecha_desde'] = $_GET['fecha_desde'];
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filters['fecha_hasta'] = $_GET['fecha_hasta'];
        }

        $movimientos = $this->movimientoModel->getAll($filters);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="movimientos_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');

        require_once __DIR__ . '/../views/inventario/movimientos/exportar.php';
        exit();
    }

    public function getUltimosMovimientos(int $limit = 10): array {
        try {
            $stmt = $this->db->prepare("CALL sp_GetUltimosMovimientos(?)");
            $stmt->execute([$limit]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            error_log("Producto::getUltimosMovimientos — " . $e->getMessage());
            return [];
        }
    }
    
}
?>