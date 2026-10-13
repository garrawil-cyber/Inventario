<?php
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Marca.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';

class ProductoController {

    private Producto  $productoModel;
    private Categoria $categoriaModel;
    private Almacen   $almacenModel;
    private Proveedor $proveedorModel;
    private Marca     $marcaModel;

    public function __construct() {
        $this->productoModel  = new Producto();
        $this->categoriaModel = new Categoria();
        $this->almacenModel   = new Almacen();
        $this->proveedorModel = new Proveedor();
        $this->marcaModel     = new Marca();
    }

    // ──────────────────────────────────────────────────────────────
    // Listar productos
    // ──────────────────────────────────────────────────────────────

    public function index(): void {
        $filters = [];

        foreach (['categoria', 'almacen', 'estado'] as $key) {
            if (!empty($_GET[$key])) {
                $filters[$key] = $_GET[$key];
            }
        }

        // ✅ Manejar el estado correctamente
        if (!empty($_GET['estado'])) {
        $filters['estado'] = $_GET['estado']; // 'activos', 'eliminados', etc.
        }

        $productos  = $this->productoModel->getAll($filters);
        $categorias = $this->categoriaModel->getForSelect();
        $almacenes  = $this->almacenModel->getForSelect();

        require_once __DIR__ . '/../views/inventario/productos/index.php';
    }

    // ──────────────────────────────────────────────────────────────
    // Ver detalle
    // ──────────────────────────────────────────────────────────────

    public function ver(): void {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $this->flashAndRedirect('ID de producto no válido', 'error', 'index');
        }

        $producto = $this->productoModel->getById($id);

        if (!$producto) {
            $this->flashAndRedirect('Producto no encontrado', 'error', 'index');
        }

        require_once __DIR__ . '/../views/inventario/productos/ver.php';
    }

    // ──────────────────────────────────────────────────────────────
    // Crear
    // ──────────────────────────────────────────────────────────────

    public function crear(): void {
        $categorias  = $this->categoriaModel->getForSelect();
        $almacenes   = $this->almacenModel->getForSelect();
        $proveedores = $this->proveedorModel->getForSelect();
        $marcas      = $this->marcaModel->getForSelect();

        $ubicaciones = $this->productoModel->getUbicacionesByAlmacen(0);

        require_once __DIR__ . '/../views/inventario/productos/crear.php';
    }

    /**
     * Guardar producto (crear)
     */
    public function guardar(): void {
        SessionMiddleware::startSession();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                SessionMiddleware::redirect('index.php?controller=producto&action=crear');
                return;
            /*header('Location: index.php?controller=producto&action=crear');
            exit();*/
        }

        try {
            // ── Recolección y normalización de datos ──────────────────
            $data = [
                'codigo'            => trim($_POST['codigo']        ?? ''),
                'sku'               => trim($_POST['sku']           ?? ''),
                'nombre'            => trim($_POST['nombre']        ?? ''),
                'descripcion'       => trim($_POST['descripcion']   ?? ''),
                'id_categoria'      => !empty($_POST['id_categoria'])   ? (int)$_POST['id_categoria']   : null,
                'id_marca'          => !empty($_POST['id_marca'])        ? (int)$_POST['id_marca']        : null,
                'id_proveedor'      => !empty($_POST['id_proveedor'])    ? (int)$_POST['id_proveedor']    : null,
                'id_almacen'        => !empty($_POST['id_almacen'])      ? (int)$_POST['id_almacen']      : null,
                'id_ubicacion'      => !empty($_POST['id_ubicacion'])    ? (int)$_POST['id_ubicacion']    : null,
                'unidad_medida'     => !empty($_POST['unidad_medida'])   ? trim($_POST['unidad_medida'])   : null,
                'stock_actual'      => isset($_POST['stock_actual'])     ? (int)$_POST['stock_actual']    : 0,
                'stock_maximo'      => !empty($_POST['stock_maximo'])    ? (int)$_POST['stock_maximo']    : 100,
                'stock_minimo'      => !empty($_POST['stock_minimo'])    ? (int)$_POST['stock_minimo']    : 10,
                'punto_reorden'     => !empty($_POST['punto_reorden'])   ? (int)$_POST['punto_reorden']   : null,
                'precio_compra'     => isset($_POST['precio_compra'])    ? (float)$_POST['precio_compra'] : 0,
                'precio_venta'      => isset($_POST['precio_venta'])     ? (float)$_POST['precio_venta']  : 0,
                // precio_mayorista: solo se envía si tiene valor real > 0
                'precio_mayorista'  => (!empty($_POST['precio_mayorista']) && (float)$_POST['precio_mayorista'] > 0)
                                        ? (float)$_POST['precio_mayorista'] : null,
                'talla'             => trim($_POST['talla']          ?? ''),
                'color'             => trim($_POST['color']          ?? ''),
                'material'          => trim($_POST['material']       ?? ''),
                'genero'            => $_POST['genero']              ?? 'Unisex',
                'temporada'         => $_POST['temporada']           ?? 'todo_año',
                'imagen_principal'  => trim($_POST['imagen_principal'] ?? ''),
                'codigo_barras'     => trim($_POST['codigo_barras']  ?? ''),
                'lote'              => trim($_POST['lote']           ?? ''),
                'fecha_vencimiento' => !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null,
                'observaciones'     => trim($_POST['observaciones']  ?? ''),
            ];

            // ── Validaciones en el controlador (UX rápida) ────────────
            if (empty($data['codigo']) || empty($data['nombre'])) {
                throw new \InvalidArgumentException('El código y nombre del producto son obligatorios.');
            }

            if (!$data['id_categoria']) {
                throw new \InvalidArgumentException('La categoría es obligatoria.');
            }

            if ($data['precio_compra'] <= 0) {
                throw new \InvalidArgumentException('El precio de compra debe ser mayor a 0.');
            }

            if ($data['precio_venta'] <= 0) {
                throw new \InvalidArgumentException('El precio de venta debe ser mayor a 0.');
            }

            if ($data['precio_venta'] < $data['precio_compra']) {
                throw new \InvalidArgumentException('El precio de venta debe ser mayor o igual al precio de compra.');
            }

            if ($data['stock_actual'] < 0) {
                throw new \InvalidArgumentException('El stock actual no puede ser negativo.');
            }

            if ($data['stock_minimo'] > $data['stock_maximo']) {
                throw new \InvalidArgumentException('El stock mínimo no puede ser mayor al stock máximo.');
            }

            if ($data['fecha_vencimiento'] !== null && $data['fecha_vencimiento'] < date('Y-m-d')) {
                throw new \InvalidArgumentException('La fecha de vencimiento no puede ser una fecha pasada.');
            }

            // ── Delegar al modelo (puede lanzar RuntimeException con msg del SP) ──
            $id = $this->productoModel->create($data);

            SessionMiddleware::setSweetAlert('¡Producto Creado!', 'El producto fue registrado correctamente.', 'success');
            header('Location: index.php?controller=producto&action=index');

        } catch (\Exception $e) {
            SessionMiddleware::setSweetAlert('Error', $e->getMessage(), 'error');
            header('Location: index.php?controller=producto&action=crear');
        }

        exit();
    }

    // ──────────────────────────────────────────────────────────────
    // Editar
    // ──────────────────────────────────────────────────────────────

    public function editar(): void {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $this->flashAndRedirect('ID de producto no válido', 'error', 'index');
        }

        $producto = $this->productoModel->getById($id);

        if (!$producto) {
            $this->flashAndRedirect('Producto no encontrado', 'error', 'index');
        }

        $categorias  = $this->categoriaModel->getForSelect();
        $almacenes   = $this->almacenModel->getForSelect();
        $proveedores = $this->proveedorModel->getForSelect();
        $marcas      = $this->marcaModel->getForSelect();

        $ubicaciones = $this->productoModel->getUbicacionesByAlmacen(0);

        require_once __DIR__ . '/../views/inventario/productos/editar.php';
    }

    public function actualizar(): void {
        SessionMiddleware::startSession();

        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            SessionMiddleware::setSweetAlert('Error', 'ID de producto no válido', 'error');
            SessionMiddleware::redirect('index.php?controller=producto&action=index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            SessionMiddleware::redirect('index.php?controller=producto&action=editar&id=' . $id);
            return;
        }

        try {
            // ── Recolección completa de datos (espeja guardar()) ──────
            $data = [
                'codigo'            => trim($_POST['codigo']        ?? ''),
                'nombre'            => trim($_POST['nombre']        ?? ''),
                'descripcion'       => trim($_POST['descripcion']   ?? ''),
                'id_categoria'      => !empty($_POST['id_categoria'])    ? (int)$_POST['id_categoria']    : null,
                'id_marca'          => !empty($_POST['id_marca'])         ? (int)$_POST['id_marca']         : null,
                'id_proveedor'      => !empty($_POST['id_proveedor'])     ? (int)$_POST['id_proveedor']     : null,
                'id_almacen'      => !empty($_POST['id_almacen'])     ? (int)$_POST['id_almacen']     : null,
                'unidad_medida'     => !empty($_POST['unidad_medida'])    ? trim($_POST['unidad_medida'])    : null,
                'stock_actual'      => isset($_POST['stock_actual'])      ? (int)$_POST['stock_actual']     : 0,
                'stock_minimo'      => !empty($_POST['stock_minimo'])     ? (int)$_POST['stock_minimo']     : 0,
                'stock_maximo'      => !empty($_POST['stock_maximo'])     ? (int)$_POST['stock_maximo']     : 100,
                'punto_reorden'     => !empty($_POST['punto_reorden'])    ? (int)$_POST['punto_reorden']    : null,
                'precio_compra'     => isset($_POST['precio_compra'])     ? (float)$_POST['precio_compra']  : 0,
                'precio_venta'      => isset($_POST['precio_venta'])      ? (float)$_POST['precio_venta']   : 0,
                'precio_mayorista'  => (!empty($_POST['precio_mayorista']) && (float)$_POST['precio_mayorista'] > 0)
                                        ? (float)$_POST['precio_mayorista'] : null,
                'talla'             => trim($_POST['talla']          ?? ''),
                'color'             => trim($_POST['color']          ?? ''),
                'genero'            => $_POST['genero']              ?? 'Unisex',
                'temporada'         => $_POST['temporada']           ?? 'todo_año',
                'estado'            => $_POST['estado']              ?? 'activo',
                'material'          => $_POST['material']            ?? null,
            ];

            // ── Validaciones ──────────────────────────────────────────
            if (empty($data['codigo']) || empty($data['nombre'])) {
                throw new \InvalidArgumentException('El código y nombre del producto son obligatorios.');
            }

            if (!$data['id_categoria']) {
                throw new \InvalidArgumentException('La categoría es obligatoria.');
            }

            if ($data['precio_compra'] <= 0) {
                throw new \InvalidArgumentException('El precio de compra debe ser mayor a 0.');
            }

            if ($data['precio_venta'] <= 0) {
                throw new \InvalidArgumentException('El precio de venta debe ser mayor a 0.');
            }

            if ($data['precio_venta'] < $data['precio_compra']) {
                throw new \InvalidArgumentException('El precio de venta debe ser mayor o igual al precio de compra.');
            }

            if ($data['stock_actual'] < 0) {
                throw new \InvalidArgumentException('El stock actual no puede ser negativo.');
            }

            if ($data['stock_minimo'] > $data['stock_maximo']) {
                throw new \InvalidArgumentException('El stock mínimo no puede ser mayor al stock máximo.');
            }

            // ── Delegar al modelo ─────────────────────────────────────
            $this->productoModel->update($id, $data);

            SessionMiddleware::setSweetAlert('¡Producto Actualizado!', 'Los cambios fueron guardados correctamente.', 'success');
            SessionMiddleware::redirect('index.php?controller=producto&action=index');

        } catch (\Exception $e) {
            SessionMiddleware::setSweetAlert('Error del Sistema', $e->getMessage(), 'error');
            SessionMiddleware::redirect('index.php?controller=producto&action=editar&id=' . $id);
        }

        exit();
    }

    // ──────────────────────────────────────────────────────────────
    // Eliminar
    // ──────────────────────────────────────────────────────────────

    public function eliminar(): void {
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            SessionMiddleware::setSweetAlert('Error', 'ID de producto no válido', 'error');
            //header('Location: index.php?controller=producto&action=index');
            SessionMiddleware::redirect('index.php?controller=producto&action=index');
            exit();
        }

        try {
            if ($this->productoModel->delete($id)) {
                SessionMiddleware::setSweetAlert('¡Eliminado!', 'El producto fue eliminado exitosamente.', 'success');
            } else {
                throw new \RuntimeException('No se pudo eliminar el producto.');
            }
        } catch (\Exception $e) {
            SessionMiddleware::setSweetAlert('Error al Eliminar', $e->getMessage(), 'error');
        }

        //header('Location: index.php?controller=producto&action=index');
        SessionMiddleware::redirect('index.php?controller=producto&action=index');
        exit();
    }

    // ──────────────────────────────────────────────────────────────
    // AJAX
    // ──────────────────────────────────────────────────────────────

    public function obtener_detalles(): void {
        $id       = (int)($_GET['id'] ?? 0);
        $producto = $this->productoModel->getById($id);

        header('Content-Type: application/json');

        echo json_encode($producto
            ? ['success' => true,  'producto' => $producto]
            : ['success' => false, 'message'  => 'Producto no encontrado']
        );

        exit();
    }

    public function get_ubicaciones(): void {
        $idAlmacen = (int)($_GET['id_almacen'] ?? 0);

        header('Content-Type: application/json');

        $ubicaciones = $this->productoModel->getUbicacionesByAlmacen($idAlmacen);
        echo json_encode(['ubicaciones' => $ubicaciones]);
        exit();
    }

    /**
     * Generar código de producto correlativo (AJAX)
     */
    public function generar_codigo(): void {
        header('Content-Type: application/json');

        try {
            $codigo = $this->productoModel->generarCodigoProducto('PROD-');

            // Doble-verificación de unicidad usando el propio modelo
            if ($this->productoModel->existeCodigo($codigo)) {
                $codigo = 'PROD-' . date('YmdHis');
            }

            echo json_encode([
                'success' => true,
                'codigo'  => $codigo,
                'message' => 'Código generado correctamente',
            ]);

        } catch (\Exception $e) {
            error_log('ProductoController::generar_codigo — ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar el código: ' . $e->getMessage(),
            ]);
        }

        exit();
    }

    /**
     * Verificar si un código de barras ya existe (AJAX)
     */
    public function verificar_codigo_barras(): void {
        header('Content-Type: application/json');

        $codigo = trim($_GET['codigo'] ?? '');

        if (empty($codigo)) {
            echo json_encode(['existe' => false]);
            exit();
        }

        try {
            $existe = $this->productoModel->verificarCodigoBarras($codigo);

            echo json_encode([
                'existe' => $existe,
                'codigo' => $codigo,
            ]);
        } catch (\Exception $e) {
            error_log('ProductoController::verificar_codigo_barras — ' . $e->getMessage());
            echo json_encode(['existe' => false, 'error' => $e->getMessage()]);
        }

        exit();
    }

    /**
     * Generar código de barras EAN-13 único (AJAX)
     */
    public function generar_codigo_barras(): void {
        header('Content-Type: application/json');

        try {
            $codigoBarras = $this->productoModel->generarCodigoBarras();

            echo json_encode([
                'success' => true,
                'codigo'  => $codigoBarras,
                'message' => 'Código de barras generado correctamente',
            ]);
        } catch (\Exception $e) {
            error_log('ProductoController::generar_codigo_barras — ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar código de barras: ' . $e->getMessage(),
            ]);
        }

        exit();
    }

    // ──────────────────────────────────────────────────────────────
    // Helper privado
    // ──────────────────────────────────────────────────────────────

    /**
     * Guarda un SweetAlert en sesión y redirige.
     * CORREGIDO: usa SessionMiddleware en vez de $_SESSION directo.
     */
    private function flashAndRedirect(string $message, string $type, string $action): never {
        SessionMiddleware::setSweetAlert('Aviso', $message, $type);
        header('Location: index.php?controller=producto&action=' . $action);
        exit();
    }


}