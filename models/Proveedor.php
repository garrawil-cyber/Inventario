<?php
/**
 * ============================================================
 *  MODELO: Proveedor.php
 *  Sistema: RopaVirtual – Módulo de Proveedores
 *
 *  Tabla principal : proveedores
 *  Tablas auxiliares: productos, compras
 * ============================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Proveedor
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    /* ============================================================
     *  SELECT — Consultas de lectura
     * ============================================================ */

    /**
     * Lista proveedores para <select> con placeholder opcional.
     */
    public function getForSelect(bool $placeholder = false, bool $includeAll = false): array
    {
        $stmt = $this->db->query(
            "SELECT id_proveedor AS value,
                    CONCAT(razon_social, ' · ', ruc) AS text
             FROM proveedores
             WHERE estado = 'activo'
             ORDER BY razon_social ASC"
        );
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if ($includeAll) {
            array_unshift($rows, ['value' => 'all', 'text' => 'Todos los proveedores']);
        }
        if ($placeholder) {
            array_unshift($rows, ['value' => '', 'text' => '-- Seleccione proveedor --']);
        }
        return $rows;
    }

    /**
     * Listado con estadísticas (productos, compras).
     * Alias público: getAll() → getAllWithStats()
     */
    public function getAll(array $filtros = []): array
    {
        return $this->getAllWithStats($filtros);
    }

    /**
     * Detalle de un proveedor por ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM proveedores WHERE id_proveedor = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca proveedor activo por RUC.
     */
    public function getByRuc(string $ruc): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM proveedores WHERE ruc = ? AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute([$ruc]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Búsqueda libre de proveedores activos.
     */
    public function search(string $term, int $limit = 10): array
    {
        try {
            $like = "%{$term}%";
            $stmt = $this->db->prepare(
                "SELECT * FROM proveedores
                 WHERE estado = 'activo'
                   AND (razon_social LIKE :s1 OR nombre_comercial LIKE :s2 OR ruc LIKE :s3)
                 ORDER BY razon_social ASC
                 LIMIT :lim"
            );
            $stmt->bindValue(':s1',  $like, \PDO::PARAM_STR);
            $stmt->bindValue(':s2',  $like, \PDO::PARAM_STR);
            $stmt->bindValue(':s3',  $like, \PDO::PARAM_STR);
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Proveedor::search — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un RUC ya existe, con exclusión opcional para editar.
     */
    public function rucExists(string $ruc, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM proveedores WHERE ruc = ?";
        $params = [$ruc];

        if ($excludeId !== null) {
            $sql    .= " AND id_proveedor != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cuenta productos activos asociados a un proveedor.
     */
    public function countProductos(int $idProveedor): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM productos
                 WHERE id_proveedor = ? AND estado = 'activo'"
            );
            $stmt->execute([$idProveedor]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('Proveedor::countProductos — ' . $e->getMessage());
            return 0;
        }
    }

    /* ============================================================
     *  MUTACIONES — CREATE / UPDATE / DELETE
     * ============================================================ */

    /**
     * Inserta un nuevo proveedor. Devuelve true si fue exitoso.
     */
    public function create(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO proveedores (
                    razon_social, ruc, nombre_comercial, direccion, ciudad, pais,
                    telefono, email, contacto_nombre, contacto_telefono, contacto_email,
                    tiempo_entrega_dias, condiciones_pago, estado
                 ) VALUES (
                    :razon_social, :ruc, :nombre_comercial, :direccion, :ciudad, :pais,
                    :telefono, :email, :contacto_nombre, :contacto_telefono, :contacto_email,
                    :tiempo_entrega_dias, :condiciones_pago, 'activo'
                 )"
            );
            return $stmt->execute($this->buildParams($data));
        } catch (\PDOException $e) {
            error_log('Proveedor::create — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un proveedor existente. Devuelve true si fue exitoso.
     */
    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE proveedores SET
                    razon_social        = :razon_social,
                    ruc                 = :ruc,
                    nombre_comercial    = :nombre_comercial,
                    direccion           = :direccion,
                    ciudad              = :ciudad,
                    pais                = :pais,
                    telefono            = :telefono,
                    email               = :email,
                    contacto_nombre     = :contacto_nombre,
                    contacto_telefono   = :contacto_telefono,
                    contacto_email      = :contacto_email,
                    tiempo_entrega_dias = :tiempo_entrega_dias,
                    condiciones_pago    = :condiciones_pago,
                    estado              = :estado
                 WHERE id_proveedor = :id"
            );
            $params          = $this->buildParams($data);
            $params[':estado'] = $data['estado'] ?? 'activo';
            $params[':id']     = $id;
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log('Proveedor::update — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactiva un proveedor (soft-delete).
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE proveedores SET estado = 'inactivo' WHERE id_proveedor = ?"
            );
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            error_log('Proveedor::delete — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reactiva un proveedor desactivado.
     */
    public function activar(int $id): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE proveedores SET estado = 'activo' WHERE id_proveedor = ?"
            );
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            error_log('Proveedor::activar — ' . $e->getMessage());
            return false;
        }
    }

    /* ============================================================
     *  ESTADÍSTICAS — Dashboard
     * ============================================================ */

    /**
     * KPIs generales del módulo.
     */
    public function getEstadisticas(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT
                     COUNT(*)                                                              AS total_proveedores,
                     SUM(estado = 'activo')                                                AS proveedores_activos,
                     SUM(estado = 'inactivo')                                              AS proveedores_inactivos,
                     SUM(estado = 'suspendido')                                            AS proveedores_suspendidos,
                     SUM(MONTH(fecha_creacion) = MONTH(CURDATE())
                         AND YEAR(fecha_creacion) = YEAR(CURDATE()))                       AS nuevos_este_mes,
                     ROUND(AVG(tiempo_entrega_dias), 1)                                    AS promedio_entrega_dias,
                     MIN(tiempo_entrega_dias)                                              AS min_entrega_dias,
                     MAX(tiempo_entrega_dias)                                              AS max_entrega_dias,
                     COALESCE((SELECT SUM(total) FROM compras WHERE estado='completada'),0) AS valor_total_compras,
                     COALESCE((SELECT SUM(total) FROM compras
                               WHERE estado='completada'
                                 AND MONTH(fecha) = MONTH(CURDATE())
                                 AND YEAR(fecha)  = YEAR(CURDATE())),0)                    AS valor_compras_mes
                 FROM proveedores"
            );
            $base = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            // Fusionar con subsets que ya incluyen los TOPs
            return array_merge([
                'total_proveedores'           => 0,
                'proveedores_activos'         => 0,
                'proveedores_inactivos'       => 0,
                'proveedores_suspendidos'     => 0,
                'nuevos_este_mes'             => 0,
                'promedio_entrega_dias'       => 0,
                'min_entrega_dias'            => 0,
                'max_entrega_dias'            => 0,
                'valor_total_compras'         => 0,
                'valor_compras_mes'           => 0,
                'top_proveedores_productos'   => $this->getTopProveedoresByProductos(5),
                'top_proveedores_compras'     => $this->getTopProveedoresByCompras(5),
                'distribucion_ciudades'       => $this->getDistribucionPorCiudad(),
                'proveedores_sin_productos'   => $this->countProveedoresSinProductos(),
                'proveedores_con_movimientos' => $this->countProveedoresConMovimientos(),
            ], $base);

        } catch (\PDOException $e) {
            error_log('Proveedor::getEstadisticas — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Top N proveedores por cantidad de productos activos.
     */
    public function getTopProveedoresByProductos(int $limit = 5): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                     p.id_proveedor, p.razon_social, p.ruc, p.nombre_comercial,
                     p.tiempo_entrega_dias,
                     COUNT(pr.id_producto)                             AS total_productos,
                     COALESCE(SUM(pr.stock_actual), 0)                 AS total_stock,
                     COALESCE(SUM(pr.stock_actual * pr.precio_compra), 0) AS valor_inventario,
                     (SELECT MAX(c.fecha) FROM compras c
                      WHERE c.proveedor_id = p.id_proveedor AND c.estado = 'completada') AS ultima_compra
                 FROM proveedores p
                 LEFT JOIN productos pr
                        ON pr.id_proveedor = p.id_proveedor AND pr.estado = 'activo'
                 WHERE p.estado = 'activo'
                 GROUP BY p.id_proveedor, p.razon_social, p.ruc,
                          p.nombre_comercial, p.tiempo_entrega_dias
                 ORDER BY total_productos DESC, valor_inventario DESC
                 LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Proveedor::getTopProveedoresByProductos — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Top N proveedores por monto total de compras.
     */
    public function getTopProveedoresByCompras(int $limit = 5): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                     p.id_proveedor, p.razon_social, p.ruc, p.nombre_comercial,
                     COUNT(DISTINCT c.id)      AS total_movimientos,
                     COALESCE(SUM(c.total), 0) AS valor_total
                 FROM proveedores p
                 INNER JOIN compras c ON c.proveedor_id = p.id_proveedor AND c.estado = 'completada'
                 WHERE p.estado = 'activo'
                 GROUP BY p.id_proveedor, p.razon_social, p.ruc, p.nombre_comercial
                 ORDER BY valor_total DESC, total_movimientos DESC
                 LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Proveedor::getTopProveedoresByCompras — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Distribución de proveedores activos por ciudad.
     */
    public function getDistribucionPorCiudad(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT COALESCE(ciudad, 'No especificada') AS ciudad, COUNT(*) AS total
                 FROM proveedores WHERE estado = 'activo'
                 GROUP BY COALESCE(ciudad, 'No especificada')
                 ORDER BY total DESC LIMIT 10"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Proveedor::getDistribucionPorCiudad — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta proveedores activos sin productos asociados.
     */
    public function countProveedoresSinProductos(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM proveedores p
                 LEFT JOIN productos pr
                        ON pr.id_proveedor = p.id_proveedor AND pr.estado = 'activo'
                 WHERE p.estado = 'activo' AND pr.id_producto IS NULL"
            );
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('Proveedor::countProveedoresSinProductos — ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cuenta proveedores activos con al menos una compra completada.
     */
    public function countProveedoresConMovimientos(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT p.id_proveedor)
                 FROM proveedores p
                 INNER JOIN compras c ON c.proveedor_id = p.id_proveedor AND c.estado = 'completada'
                 WHERE p.estado = 'activo'"
            );
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log('Proveedor::countProveedoresConMovimientos — ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Estadísticas de tiempos de entrega.
     */
    public function getTiemposEntrega(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT
                     ROUND(AVG(tiempo_entrega_dias), 1)                                       AS promedio_general,
                     MIN(tiempo_entrega_dias)                                                  AS minimo,
                     MAX(tiempo_entrega_dias)                                                  AS maximo,
                     ROUND(AVG(CASE WHEN estado='activo' THEN tiempo_entrega_dias END), 1)    AS promedio_activos,
                     COUNT(CASE WHEN tiempo_entrega_dias <= 3              THEN 1 END)         AS entrega_rapida,
                     COUNT(CASE WHEN tiempo_entrega_dias BETWEEN 4 AND 7  THEN 1 END)         AS entrega_normal,
                     COUNT(CASE WHEN tiempo_entrega_dias BETWEEN 8 AND 14 THEN 1 END)         AS entrega_lenta,
                     COUNT(CASE WHEN tiempo_entrega_dias > 14              THEN 1 END)         AS entrega_muy_lenta
                 FROM proveedores"
            );
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log('Proveedor::getTiemposEntrega — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Proveedores registrados en el mes en curso.
     */
    public function getProveedoresNuevosEsteMes(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT
                     p.id_proveedor, p.razon_social, p.ruc, p.nombre_comercial,
                     p.telefono, p.email, p.tiempo_entrega_dias,
                     DATE(p.fecha_creacion)               AS fecha_registro,
                     DATEDIFF(CURDATE(), p.fecha_creacion) AS dias_registrado,
                     IF(EXISTS(
                         SELECT 1 FROM compras c
                         WHERE c.proveedor_id = p.id_proveedor AND c.estado = 'completada'
                     ), 'Sí', 'No')                       AS tiene_compras,
                     COALESCE((
                         SELECT COUNT(*) FROM productos pr
                         WHERE pr.id_proveedor = p.id_proveedor AND pr.estado = 'activo'
                     ), 0)                                AS productos_asociados
                 FROM proveedores p
                 WHERE p.estado = 'activo'
                   AND MONTH(p.fecha_creacion) = MONTH(CURDATE())
                   AND YEAR(p.fecha_creacion)  = YEAR(CURDATE())
                 ORDER BY p.fecha_creacion DESC"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Proveedor::getProveedoresNuevosEsteMes — ' . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
     *  PRIVADOS — Helpers internos
     * ============================================================ */

    /**
     * Listado enriquecido con totales de productos y compras.
     * Usa posicionales (?) para evitar el bug de named placeholders repetidos.
     */
    private function getAllWithStats(array $filtros = []): array
    {
        $sql = "SELECT
                    p.*,
                    COALESCE((
                        SELECT COUNT(*) FROM productos pr
                        WHERE pr.id_proveedor = p.id_proveedor AND pr.estado = 'activo'
                    ), 0) AS total_productos,
                    COALESCE((
                        SELECT SUM(c.total) FROM compras c
                        WHERE c.proveedor_id = p.id_proveedor AND c.estado = 'completada'
                    ), 0) AS valor_compras,
                    COALESCE((
                        SELECT COUNT(*) FROM compras c
                        WHERE c.proveedor_id = p.id_proveedor AND c.estado = 'completada'
                    ), 0) AS total_compras
                FROM proveedores p
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['estado'])) {
            $sql     .= " AND p.estado = ?";
            $params[] = $filtros['estado'];
        } else {
            $sql .= " AND p.estado = 'activo'";
        }

        if (!empty($filtros['search'])) {
            $like     = "%{$filtros['search']}%";
            $sql     .= " AND (p.razon_social LIKE ? OR p.ruc LIKE ? OR p.nombre_comercial LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY p.razon_social ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Proveedor::getAllWithStats — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Array de parámetros nombrados para INSERT/UPDATE (sin estado ni id).
     */
    private function buildParams(array $data): array
    {
        return [
            ':razon_social'        => $data['razon_social'],
            ':ruc'                 => $data['ruc'],
            ':nombre_comercial'    => $data['nombre_comercial']    ?? null,
            ':direccion'           => $data['direccion']           ?? null,
            ':ciudad'              => $data['ciudad']              ?? null,
            ':pais'                => $data['pais']                ?? 'Perú',
            ':telefono'            => $data['telefono']            ?? null,
            ':email'               => $data['email']               ?? null,
            ':contacto_nombre'     => $data['contacto_nombre']     ?? null,
            ':contacto_telefono'   => $data['contacto_telefono']   ?? null,
            ':contacto_email'      => $data['contacto_email']      ?? null,
            ':tiempo_entrega_dias' => $data['tiempo_entrega_dias'] ?? 7,
            ':condiciones_pago'    => $data['condiciones_pago']    ?? null,
        ];
    }

    /**
 * Cuenta el número de compras (movimientos) de un proveedor
 */
public function countMovimientos(int $idProveedor): int
{
    try {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM compras 
             WHERE proveedor_id = ? AND estado = 'completada'"
        );
        $stmt->execute([$idProveedor]);
        return (int) $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log('Proveedor::countMovimientos — ' . $e->getMessage());
        return 0;
    }
}

/**
 * Obtiene el valor total de compras de un proveedor
 */
public function getValorCompras(int $idProveedor): float
{
    try {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total), 0) FROM compras 
             WHERE proveedor_id = ? AND estado = 'completada'"
        );
        $stmt->execute([$idProveedor]);
        return (float) $stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log('Proveedor::getValorCompras — ' . $e->getMessage());
        return 0.00;
    }
}
}