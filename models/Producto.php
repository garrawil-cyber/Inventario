<?php
require_once __DIR__ . '/../config/database.php';

class Producto {

    private \PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /** Expone la conexión para consultas externas puntuales. */
    public function getDB(): \PDO { return $this->db; }

    // ──────────────────────────────────────────────────────────────
    // CRUD Principal
    // ──────────────────────────────────────────────────────────────

    /**
     * Listar productos usando sp_ListarProductos.
     * Soporta filtros, búsqueda libre y paginación.
     *
     * Filtros reconocidos en $filters:
     *   busqueda    string  – búsqueda libre (nombre, código, sku, barras)
     *   categoria   int     – id_categoria
     *   almacen     int     – id_almacen
     *   estado      string  – 'activo' | 'inactivo' | 'eliminado' | 'todos'
     *   estado_stock string – 'normal' | 'bajo' | 'critico' | 'agotado'
     *   limit       int     – registros por página (default 50)
     *   offset      int     – desplazamiento       (default 0)
     *
     * @return array{data: array, total: int}
     */
    public function listar(array $filters = []): array {
        $filtro      = $filters['busqueda']    ?? null;
        $idCategoria = isset($filters['categoria'])    ? (int)$filters['categoria']    : null;
        $idAlmacen   = isset($filters['almacen'])      ? (int)$filters['almacen']      : null;
        $estado      = $filters['estado']      ?? 'activo';
        $estadoStock = $filters['estado_stock'] ?? null;
        $limit       = isset($filters['limit'])  ? (int)$filters['limit']  : 50;
        $offset      = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        // Normalizar: vacío → NULL para el SP
        $filtro      = ($filtro      === '')  ? null : $filtro;
        $estadoStock = ($estadoStock === '')  ? null : $estadoStock;
        $idCategoria = ($idCategoria === 0)   ? null : $idCategoria;
        $idAlmacen   = ($idAlmacen   === 0)   ? null : $idAlmacen;

        try {
            $stmt = $this->db->prepare(
                "CALL sp_ListarProductos(?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$filtro, $idCategoria, $idAlmacen, $estado, $estadoStock, $limit, $offset]);

            $data  = $stmt->fetchAll(\PDO::FETCH_ASSOC);   // resultado 1: filas

            // Resultado 2: total sin paginación
            $total = 0;
            if ($stmt->nextRowset()) {
                $row   = $stmt->fetch(\PDO::FETCH_ASSOC);
                $total = (int)($row['total_registros'] ?? 0);
            }

            $stmt->closeCursor();
            return ['data' => $data, 'total' => $total];

        } catch (\PDOException $e) {
            error_log("Producto::listar — " . $e->getMessage());
            return ['data' => [], 'total' => 0];
        }
    }

    /**
     * Compatibilidad retroactiva: getAll() delega en listar().
     * La vista index.php puede seguir llamando a getAll($filters).
     *
     * @return array  – solo las filas (sin total)
     */
    public function getAll(array $filters = []): array {
        return $this->listar($filters)['data'];
    }

    /**
     * Obtener un producto por ID usando sp_ObtenerProductoPorId.
     * Devuelve null si no existe o está eliminado.
     */
    public function getById(int $id): ?array {
        try {
            // Llamada al SP con parámetro OUT
            $this->db->exec("SET @p_encontrado = FALSE");
            $stmt = $this->db->prepare("CALL sp_ObtenerProductoPorId(?, @p_encontrado)");
            $stmt->execute([$id]);

            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$row) {
                return null;
            }

            // Comprobar el OUT
            $res       = $this->db->query("SELECT @p_encontrado AS encontrado");
            $encontrado = (bool)($res->fetchColumn());

            if (!$encontrado) {
                return null;
            }

            // Enriquecer con ubicaciones del almacén
            $row['ubicaciones'] = $this->getUbicacionesByAlmacen((int)($row['id_almacen'] ?? 0));

            return $row;

        } catch (\PDOException $e) {
            error_log("Producto::getById — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear producto mediante sp_InsertProducto.
     *
     * @throws \RuntimeException con el mensaje del SP en caso de error.
     * @return int  ID del nuevo producto.
     */
    public function create(array $data): int {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        // Almacén por defecto si no se especificó
        if (empty($data['id_almacen'])) {
            $stmt = $this->db->query(
                "SELECT id_almacen FROM almacenes WHERE estado = 'activo' LIMIT 1"
            );
            $default = $stmt->fetchColumn();
            if ($default) {
                $data['id_almacen'] = (int)$default;
            } else {
                throw new \RuntimeException('No hay almacenes activos disponibles.');
            }
        }

        $strOrNull = static function ($v): ?string {
            if ($v === null) return null;
            $v = trim((string)$v);
            return $v !== '' ? $v : null;
        };

        $precioMayorista = (!empty($data['precio_mayorista']) && (float)$data['precio_mayorista'] > 0)
            ? (float)$data['precio_mayorista'] : null;

        $puntoReorden = (!empty($data['punto_reorden']) && (int)$data['punto_reorden'] > 0)
            ? (int)$data['punto_reorden'] : null;

        try {
            $sql  = "CALL sp_InsertProducto(
                        ?, ?, ?, ?,   -- 01-04 código, sku, nombre, descripción
                        ?, ?, ?, ?,   -- 05-08 categoría, marca, proveedor, almacén
                        ?, ?, ?, ?, ?,-- 09-13 unidad, stock_actual, max, min, reorden
                        ?, ?, ?,      -- 14-16 p_compra, p_venta, p_mayorista
                        ?, ?, ?, ?, ?,-- 17-21 talla, color, material, género, temporada
                        ?, ?, ?, ?, ?,-- 22-26 imagen, barras, lote, vencimiento, observaciones
                        ?,            -- 27    usuario_id
                        @producto_id  -- OUT
                     )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['codigo']          ?? '',
                $strOrNull($data['sku']          ?? null),
                $data['nombre']          ?? '',
                $strOrNull($data['descripcion']  ?? null),
                (int)($data['id_categoria'] ?? 0),
                !empty($data['id_marca'])     ? (int)$data['id_marca']     : null,
                !empty($data['id_proveedor']) ? (int)$data['id_proveedor'] : null,
                (int)($data['id_almacen'] ?? 1),
                $strOrNull($data['unidad_medida'] ?? null),
                (int)($data['stock_actual']  ?? 0),
                (int)($data['stock_maximo']  ?? 100),
                (int)($data['stock_minimo']  ?? 10),
                $puntoReorden,
                (float)($data['precio_compra']    ?? 0),
                (float)($data['precio_venta']     ?? 0),
                $precioMayorista,
                $strOrNull($data['talla']    ?? null),
                $strOrNull($data['color']    ?? null),
                $strOrNull($data['material'] ?? null),
                $data['genero']    ?? 'Unisex',
                $data['temporada'] ?? 'todo_año',
                $strOrNull($data['imagen_principal'] ?? null),
                $strOrNull($data['codigo_barras']    ?? null),
                $strOrNull($data['lote']             ?? null),
                $data['fecha_vencimiento'] ?? null,
                $strOrNull($data['observaciones']    ?? null),
                $usuarioId,
            ]);
            $stmt->closeCursor();

            $result    = $this->db->query("SELECT @producto_id AS producto_id");
            $productoId = (int)($result->fetchColumn() ?? 0);

            if ($productoId <= 0) {
                throw new \RuntimeException('El procedimiento no retornó un ID válido.');
            }

            return $productoId;

        } catch (\PDOException $e) {
            error_log("Producto::create — " . $e->getMessage());
            $msg = $e->getMessage();
            if (preg_match('/1644\s+(.+)$/i', $msg, $m)) {
                throw new \RuntimeException(trim($m[1]));
            }
            throw new \RuntimeException('Error al registrar el producto en la base de datos.');
        }
    }

    /**
     * Actualizar producto mediante sp_UpdateProducto.
     *
     * @throws \RuntimeException
     */
    public function update(int $id, array $data): bool {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        $precioMayorista = (!empty($data['precio_mayorista']) && (float)$data['precio_mayorista'] > 0)
            ? (float)$data['precio_mayorista'] : null;

        $puntoReorden = (!empty($data['punto_reorden']) && (int)$data['punto_reorden'] > 0)
            ? (int)$data['punto_reorden'] : null;

        try {
            $stmt = $this->db->prepare(
                "CALL sp_UpdateProducto(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?
                 )"
            );
            $stmt->execute([
                $id,                                         // 01 p_id_producto
                $data['codigo'],                             // 02 p_codigo
                $data['nombre'],                             // 03 p_nombre
                $data['descripcion']       ?? '',            // 04 p_descripcion
                $data['id_categoria'],                       // 05 p_id_categoria
                $data['id_almacen']        ?: null,          // 06 p_id_almacen
                (int)($data['stock_actual'] ?? 0),           // 07 p_stock_actual
                $data['unidad_medida']     ?: null,          // 08 p_unidad_medida
                (int)($data['stock_minimo'] ?? 0),           // 09 p_stock_minimo
                (int)($data['stock_maximo'] ?? 100),         // 10 p_stock_maximo
                $puntoReorden,                               // 11 p_punto_reorden
                (float)$data['precio_compra'],               // 12 p_precio_compra
                (float)$data['precio_venta'],                // 13 p_precio_venta
                $precioMayorista,                            // 14 p_precio_mayorista
                $data['genero']            ?? 'Unisex',      // 15 p_genero
                $data['temporada']         ?? 'todo_año',    // 16 p_temporada
                $data['estado']            ?? 'activo',      // 17 p_estado
                $data['id_marca']          ?: null,          // 18 p_id_marca
                $data['id_proveedor']      ?: null,          // 19 p_id_proveedor
                $data['talla']             ?: null,          // 20 p_talla
                $data['color']             ?: null,          // 21 p_color
                $data['material']          ?? null,          // 22 p_material
                $usuarioId,                                  // 23 p_usuario_id
            ]);
            $stmt->closeCursor();
            return true;

        } catch (\PDOException $e) {
            error_log("Producto::update — " . $e->getMessage());
            $msg = $e->getMessage();
            if (preg_match('/1644\s*(.+)$/i', $msg, $m)) {
                throw new \RuntimeException(trim($m[1]));
            }
            throw new \RuntimeException('Error al actualizar el producto en la base de datos.');
        }
    }

    /**
     * Eliminación lógica usando sp_EliminarProducto.
     *
     * @throws \RuntimeException con el mensaje del SP.
     * @return bool TRUE si se eliminó correctamente.
     */
    public function delete(int $id): bool {
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        try {
            // Preparar variable OUT
            $this->db->exec("SET @p_eliminado = FALSE");

            $stmt = $this->db->prepare(
                "CALL sp_EliminarProducto(?, ?, @p_eliminado)"
            );
            $stmt->execute([$id, $usuarioId]);
            $stmt->closeCursor();

            // Leer el OUT
            $res       = $this->db->query("SELECT @p_eliminado AS eliminado");
            $eliminado = (bool)$res->fetchColumn();

            return $eliminado;

        } catch (\PDOException $e) {
            error_log("Producto::delete — " . $e->getMessage());
            $msg = $e->getMessage();

            // Extraer mensaje SIGNAL de MySQL (SQLSTATE 45000)
            if (preg_match('/1644\s*(.+)$/i', $msg, $m)) {
                throw new \RuntimeException(trim($m[1]));
            }

            throw new \RuntimeException('No se pudo eliminar el producto. Intente nuevamente.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Consultas de negocio
    // ──────────────────────────────────────────────────────────────

    /**
     * Búsqueda rápida para autocompletado (ventas, movimientos, etc.)
     */
    public function buscar(string $termino, int $limite = 20): array {
        try {
            $sql = "SELECT p.id_producto, p.codigo, p.sku, p.nombre, p.descripcion,
                           p.stock_actual, p.stock_minimo, p.stock_maximo,
                           p.precio_compra, p.precio_venta, p.estado_stock,
                           c.nombre AS categoria_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                    WHERE p.estado = 'activo'
                      AND (p.codigo LIKE ? OR p.nombre LIKE ? OR p.sku LIKE ? OR p.descripcion LIKE ?)
                    ORDER BY p.nombre ASC
                    LIMIT ?";
            $t    = "%{$termino}%";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$t, $t, $t, $t, $limite]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Producto::buscar — " . $e->getMessage());
            return [];
        }
    }

    /** Productos con stock bajo (usa SP de dashboard). */
    public function getProductosStockBajo(int $limit = 10): array {
        try {
            $stmt = $this->db->prepare("CALL sp_GetProductosStockBajo(?)");
            $stmt->execute([$limit]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            error_log("Producto::getProductosStockBajo — " . $e->getMessage());
            return [];
        }
    }

    /** Actualizar stock con recálculo de estado_stock. */
    public function actualizarStock(int $idProducto, int $nuevoStock): bool {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "UPDATE productos
                 SET stock_actual  = ?,
                     estado_stock  = CASE
                         WHEN ? = 0                    THEN 'agotado'
                         WHEN ? < (stock_minimo * 0.5) THEN 'critico'
                         WHEN ? <= stock_minimo        THEN 'bajo'
                         ELSE 'normal'
                     END,
                     fecha_actualizacion = NOW()
                 WHERE id_producto = ?"
            );
            $stmt->execute([$nuevoStock, $nuevoStock, $nuevoStock, $nuevoStock, $idProducto]);
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Producto::actualizarStock — " . $e->getMessage());
            return false;
        }
    }

    /** Validar stock disponible antes de una salida. */
    public function validarStockDisponible(int $idProducto, int $cantidadSolicitada): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT stock_actual, nombre FROM productos WHERE id_producto = ?"
            );
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$producto) {
                return ['disponible' => false, 'stock_actual' => 0, 'mensaje' => 'Producto no encontrado'];
            }

            $stock = (int)$producto['stock_actual'];
            if ($stock < $cantidadSolicitada) {
                return [
                    'disponible'   => false,
                    'stock_actual' => $stock,
                    'mensaje'      => "Stock insuficiente. Disponible: {$stock}, Solicitado: {$cantidadSolicitada}",
                ];
            }

            return ['disponible' => true, 'stock_actual' => $stock, 'mensaje' => 'Stock disponible'];

        } catch (\PDOException $e) {
            error_log("Producto::validarStockDisponible — " . $e->getMessage());
            return ['disponible' => false, 'stock_actual' => 0, 'mensaje' => 'Error al validar stock'];
        }
    }

    /** Productos más vendidos en un período (vía kardex). */
    public function getProductosMasVendidos(string $fechaInicio, string $fechaFin, int $limite = 10): array {
        try {
            $sql = "SELECT p.id_producto, p.codigo, p.nombre, p.stock_actual,
                           c.nombre AS categoria_nombre,
                           SUM(k.salida_cantidad)    AS total_vendido,
                           SUM(k.salida_costo_total) AS valor_total_ventas,
                           COUNT(DISTINCT k.id_kardex) AS numero_ventas
                    FROM productos p
                    INNER JOIN kardex    k ON p.id_producto    = k.id_producto
                    LEFT  JOIN categorias c ON p.id_categoria  = c.id_categoria
                    WHERE k.tipo_movimiento = 'salida'
                      AND k.fecha_movimiento BETWEEN ? AND ?
                      AND p.estado = 'activo'
                    GROUP BY p.id_producto, p.codigo, p.nombre, p.stock_actual, c.nombre
                    ORDER BY total_vendido DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio, $fechaFin, $limite]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Producto::getProductosMasVendidos — " . $e->getMessage());
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────────────────────

    public function getDashboardEstadisticas(): array {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) AS total_productos,
                        COALESCE(SUM(stock_actual), 0)                         AS total_unidades,
                        COUNT(CASE WHEN estado_stock = 'bajo'    THEN 1 END)   AS productos_bajos,
                        COUNT(CASE WHEN estado_stock = 'critico' THEN 1 END)   AS productos_criticos,
                        COALESCE(SUM(stock_actual * precio_compra), 0)         AS valor_total_compra,
                        COALESCE(SUM(stock_actual * precio_venta),  0)         AS valor_total_venta
                 FROM productos
                 WHERE estado = 'activo'"
            );
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: $this->emptyDashboard();
        } catch (\PDOException $e) {
            error_log("Producto::getDashboardEstadisticas — " . $e->getMessage());
            return $this->emptyDashboard();
        }
    }

    /** Alias para compatibilidad con vistas. */
    public function getEstadisticas(): array {
        return $this->getDashboardEstadisticas();
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

    public function getAlertasCriticas(int $limit = 5): array {
        try {
            $stmt = $this->db->prepare("CALL sp_GetAlertasCriticas(?)");
            $stmt->execute([$limit]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            error_log("Producto::getAlertasCriticas — " . $e->getMessage());
            return [];
        }
    }

    public function getTopProductosVendidos(int $limit = 5, int $dias = 30): array {
        try {
            $stmt = $this->db->prepare("CALL sp_GetTopProductosVendidos(?, ?)");
            $stmt->execute([$limit, $dias]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } catch (\PDOException $e) {
            error_log("Producto::getTopProductosVendidos — " . $e->getMessage());
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Listas para selects
    // ──────────────────────────────────────────────────────────────

    public function getCategorias(): array {
        try {
            return $this->db
                ->query("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC")
                ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Producto::getCategorias — " . $e->getMessage());
            return [];
        }
    }

    public function getAlmacenes(): array {
        try {
            return $this->db
                ->query("SELECT * FROM almacenes WHERE estado = 'activo' ORDER BY nombre ASC")
                ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Producto::getAlmacenes — " . $e->getMessage());
            return [];
        }
    }

    public function getAlmacenById(int $id_almacen): ?array {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM almacenes WHERE id_almacen = ? AND estado = 'activo'"
            );
            $stmt->execute([$id_almacen]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            error_log("Producto::getAlmacenById — " . $e->getMessage());
            return null;
        }
    }

    public function getUbicacionesByAlmacen(int $id_almacen): array {
        try {
            if ($id_almacen === 0) {
                $sql  = "SELECT u.*, a.nombre AS almacen_nombre
                         FROM ubicaciones u
                         INNER JOIN almacenes a ON u.id_almacen = a.id_almacen
                         WHERE u.estado = 'disponible' AND a.estado = 'activo'
                         ORDER BY a.nombre ASC, u.codigo ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            } else {
                $sql  = "SELECT u.*, a.nombre AS almacen_nombre
                         FROM ubicaciones u
                         INNER JOIN almacenes a ON u.id_almacen = a.id_almacen
                         WHERE u.id_almacen = ? AND u.estado = 'disponible' AND a.estado = 'activo'
                         ORDER BY u.codigo ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id_almacen]);
            }
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Producto::getUbicacionesByAlmacen — " . $e->getMessage());
            return [];
        }
    }

    public function getProveedores(): array {
        try {
            return $this->db
                ->query("SELECT * FROM proveedores WHERE estado = 'activo' ORDER BY razon_social ASC")
                ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Producto::getProveedores — " . $e->getMessage());
            return [];
        }
    }

    public function getHistorialPrecios(int $idProducto, int $limite = 10): array {
        try {
            $sql = "SELECT k.fecha_movimiento,
                           k.tipo_movimiento,
                           CASE
                               WHEN k.tipo_movimiento = 'entrada' THEN k.entrada_costo_unitario
                               WHEN k.tipo_movimiento = 'salida'  THEN k.salida_costo_unitario
                           END AS precio_unitario,
                           k.documento_referencia,
                           mi.observaciones
                    FROM kardex k
                    INNER JOIN movimientos_inventario mi ON k.id_movimiento = mi.id_movimiento
                    WHERE k.id_producto = ?
                      AND (k.entrada_costo_unitario > 0 OR k.salida_costo_unitario > 0)
                    ORDER BY k.fecha_movimiento DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idProducto, $limite]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Producto::getHistorialPrecios — " . $e->getMessage());
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Generación de códigos
    // ──────────────────────────────────────────────────────────────

    public function existeCodigo(string $codigo): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM productos WHERE codigo = ? AND estado != 'eliminado'"
            );
            $stmt->execute([$codigo]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Producto::existeCodigo — " . $e->getMessage());
            return false;
        }
    }

    public function generarCodigoProducto(string $prefijo = 'PROD-'): string {
        try {
            $stmt = $this->db->prepare(
                "SELECT codigo FROM productos
                 WHERE codigo LIKE ? AND estado != 'eliminado'
                   AND LENGTH(codigo) = LENGTH(?) + 6
                 ORDER BY id_producto DESC LIMIT 1"
            );
            $stmt->execute([$prefijo . '%', $prefijo]);
            $ultimo = $stmt->fetchColumn();

            if ($ultimo) {
                $parteNumerica = substr($ultimo, strlen($prefijo));
                if (ctype_digit($parteNumerica)) {
                    return $prefijo . str_pad((string)((int)$parteNumerica + 1), 6, '0', STR_PAD_LEFT);
                }
            }

            $maxId = (int)$this->db
                ->query("SELECT MAX(id_producto) FROM productos WHERE estado != 'eliminado'")
                ->fetchColumn();

            return $prefijo . str_pad((string)(min($maxId + 1, 999999)), 6, '0', STR_PAD_LEFT);

        } catch (\PDOException $e) {
            error_log("Producto::generarCodigoProducto — " . $e->getMessage());
            return $prefijo . date('YmdHis');
        }
    }

    public function verificarCodigoBarras(string $codigoBarras): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM productos
                 WHERE codigo_barras = ? AND estado != 'eliminado'"
            );
            $stmt->execute([$codigoBarras]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Producto::verificarCodigoBarras — " . $e->getMessage());
            return false;
        }
    }

    public function generarCodigoBarras(): string {
        for ($i = 0; $i < 10; $i++) {
            $codigo = $this->generarEAN13();
            if (!$this->verificarCodigoBarras($codigo)) {
                return $codigo;
            }
        }
        return '775' . substr((string)time(), -10);
    }

    public function generarCodigoBarrasPorId(int $idProducto): string {
        $base     = str_pad((string)$idProducto, 12, '0', STR_PAD_LEFT);
        $suma     = 0;
        for ($i = 0; $i < 12; $i++) {
            $suma += ($i % 2 === 0) ? (int)$base[$i] : (int)$base[$i] * 3;
        }
        $codigo = $base . ((10 - ($suma % 10)) % 10);

        try {
            $stmt = $this->db->prepare(
                "UPDATE productos SET codigo_barras = ? WHERE id_producto = ?"
            );
            $stmt->execute([$codigo, $idProducto]);
        } catch (\PDOException $e) {
            error_log("Producto::generarCodigoBarrasPorId — " . $e->getMessage());
        }

        return $codigo;
    }

    public function getDefaultAlmacen(): ?int {
        try {
            $id = $this->db
                ->query("SELECT id_almacen FROM almacenes WHERE estado = 'activo' LIMIT 1")
                ->fetchColumn();
            return $id ? (int)$id : null;
        } catch (\PDOException $e) {
            error_log("Producto::getDefaultAlmacen — " . $e->getMessage());
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────────

    private function generarEAN13(): string {
        $prefijo  = '775';
        $aleatorio = str_pad((string)mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        $codigo12 = $prefijo . $aleatorio;
        $suma     = 0;
        for ($i = 0; $i < 12; $i++) {
            $suma += ($i % 2 === 0) ? (int)$codigo12[$i] : (int)$codigo12[$i] * 3;
        }
        return $codigo12 . ((10 - ($suma % 10)) % 10);
    }

    private function generarSKU(int $id_categoria): string {
        $prefix = 'SKU-';
        try {
            $stmt = $this->db->prepare(
                "SELECT LEFT(nombre, 3) AS prefijo FROM categorias WHERE id_categoria = ?"
            );
            $stmt->execute([$id_categoria]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $prefix = strtoupper($result['prefijo']) . '-';
            }
        } catch (\PDOException $e) {
            error_log("Producto::generarSKU — " . $e->getMessage());
        }
        return $prefix . time() . mt_rand(1000, 9999);
    }

    private function emptyDashboard(): array {
        return [
            'total_productos'    => 0,
            'total_unidades'     => 0,
            'productos_bajos'    => 0,
            'productos_criticos' => 0,
            'valor_total_compra' => 0,
            'valor_total_venta'  => 0,
        ];
    }
}