<?php
/**
 * ============================================================
 *  CONTROLADOR: ProveedorController.php
 *  Sistema: RopaVirtual – Módulo de Proveedores
 *  Patrón: MVC · JSON para mutaciones · SweetAlert en frontend
 *
 *  ACCIONES:
 *   index         GET  → vista listado
 *   crear         GET  → vista formulario
 *   guardar       POST → JSON  (AJAX desde crear.php)
 *   editar        GET  → vista formulario
 *   actualizar    POST → JSON  (AJAX desde editar.php)
 *   eliminar      GET  → JSON  (AJAX desde index/ver)
 *   activar       GET  → JSON  (AJAX desde index/ver)
 *   ver           GET  → vista detalle
 *   verificar_ruc GET  → JSON
 *   getProveedoresJson GET → JSON
 *   exportar      GET  → CSV descarga
 * ============================================================
 */

require_once __DIR__ . '/../config/SessionMiddleware.php';
require_once __DIR__ . '/../models/Proveedor.php';

class ProveedorController
{
    private Proveedor $model;

    public function __construct()
    {
        SessionMiddleware::checkAuth();
        $this->model = new Proveedor();
    }

    /* ============================================================
     *  UTILIDADES PRIVADAS
     * ============================================================ */

    /** Responde JSON y termina ejecución */
    private function json(array $payload, int $status = 200): never
    {
        if (ob_get_level()) ob_clean();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /** Guarda un mensaje flash en sesión (para vistas que hacen redirect) */
    private function flash(string $type, string $title, string $text): void
    {
        $_SESSION['sweet_alert'] = compact('type', 'title', 'text');
    }

    /** Extrae y borra el flash de sesión */
    private function getFlash(): ?array
    {
        $data = $_SESSION['sweet_alert'] ?? null;
        unset($_SESSION['sweet_alert']);
        return $data;
    }

    /** Valida que la petición sea POST, si no responde JSON de error */
    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'mensaje' => 'Método no permitido'], 405);
        }
    }

    /** Sanitiza y recoge los campos del formulario de proveedor */
    private function collectFormData(bool $withEstado = false): array
    {
        $str = fn($k) => !empty($_POST[$k]) ? trim($_POST[$k]) : null;
        $int = fn($k, $d) => !empty($_POST[$k]) ? (int) $_POST[$k] : $d;

        $data = [
            'razon_social'       => $str('razon_social'),
            'ruc'                => $str('ruc'),
            'nombre_comercial'   => $str('nombre_comercial'),
            'direccion'          => $str('direccion'),
            'ciudad'             => $str('ciudad'),
            'pais'               => $str('pais') ?? 'Perú',
            'telefono'           => $str('telefono'),
            'email'              => $str('email'),
            'contacto_nombre'    => $str('contacto_nombre'),
            'contacto_telefono'  => $str('contacto_telefono'),
            'contacto_email'     => $str('contacto_email'),
            'tiempo_entrega_dias'=> $int('tiempo_entrega_dias', 7),
            'condiciones_pago'   => $str('condiciones_pago'),
        ];

        if ($withEstado) {
            $data['estado'] = in_array($_POST['estado'] ?? '', ['activo', 'inactivo', 'suspendido'])
                ? $_POST['estado']
                : 'activo';
        }

        return $data;
    }

    /** Valida campos comunes; lanza Exception si hay error */
    private function validateData(array $data, ?int $excludeId = null): void
    {
        if (empty($data['razon_social'])) {
            throw new \InvalidArgumentException('La razón social es obligatoria.');
        }
        if (empty($data['ruc'])) {
            throw new \InvalidArgumentException('El RUC es obligatorio.');
        }
        if (!preg_match('/^\d{11}$/', $data['ruc'])) {
            throw new \InvalidArgumentException('El RUC debe tener exactamente 11 dígitos numéricos.');
        }
        if ($this->model->rucExists($data['ruc'], $excludeId)) {
            throw new \InvalidArgumentException(
                $excludeId
                    ? 'El RUC ya está registrado por otro proveedor.'
                    : 'El RUC ya está registrado en el sistema.'
            );
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El formato del email no es válido.');
        }
        if (!empty($data['contacto_email']) && !filter_var($data['contacto_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El formato del email de contacto no es válido.');
        }
        if ($data['tiempo_entrega_dias'] < 1 || $data['tiempo_entrega_dias'] > 365) {
            throw new \InvalidArgumentException('El tiempo de entrega debe estar entre 1 y 365 días.');
        }
    }

    /* ============================================================
     *  index — Listado principal
     * ============================================================ */
    public function index(): void
    {
        $filters = array_filter([
            'estado' => $_GET['estado'] ?? null,
            'search' => $_GET['search'] ?? null,
        ]);

        $proveedores  = $this->model->getAll($filters);
        $estadisticas = $this->model->getEstadisticas();

        // Garantizar claves que la vista puede necesitar
        $estadisticas += [
            'top_proveedores_productos'  => $this->model->getTopProveedoresByProductos(5),
            'top_proveedores_compras'    => $this->model->getTopProveedoresByCompras(5),
            'distribucion_ciudades'      => $this->model->getDistribucionPorCiudad(),
            'proveedores_sin_productos'  => $this->model->countProveedoresSinProductos(),
            'proveedores_con_movimientos'=> $this->model->countProveedoresConMovimientos(),
        ];

        $sweetAlert = $this->getFlash();

        require_once __DIR__ . '/../views/inventario/proveedores/index.php';
    }

    /* ============================================================
     *  crear — Formulario de alta
     * ============================================================ */
    public function crear(): void
    {
        $sweetAlert = $this->getFlash();
        require_once __DIR__ . '/../views/inventario/proveedores/crear.php';
    }

    /* ============================================================
     *  guardar — POST/AJAX → JSON
     * ============================================================ */
    public function guardar(): never
    {
        $this->requirePost();

        try {
            $data = $this->collectFormData();
            $this->validateData($data);

            $ok = $this->model->create($data);

            if (!$ok) {
                throw new \RuntimeException('Error al registrar el proveedor. Inténtalo nuevamente.');
            }

            $this->json([
                'success' => true,
                'mensaje' => "El proveedor \"{$data['razon_social']}\" se ha registrado exitosamente.",
                'redirect'=> 'index.php?controller=proveedor&action=index',
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            error_log('ProveedorController::guardar — ' . $e->getMessage());
            $this->json(['success' => false, 'mensaje' => 'Error interno del servidor.'], 500);
        }
    }

    /* ============================================================
     *  editar — Formulario de edición
     * ============================================================ */
    public function editar(int $id): void
    {
        $proveedor = $this->model->getById($id);

        if (!$proveedor) {
            $this->flash('error', 'No encontrado', 'El proveedor solicitado no existe.');
            SessionMiddleware::redirect('index.php?controller=proveedor&action=index');
            return;
        }

        $sweetAlert = $this->getFlash();
        require_once __DIR__ . '/../views/inventario/proveedores/editar.php';
    }

    /* ============================================================
     *  actualizar — POST/AJAX → JSON
     * ============================================================ */
    public function actualizar(int $id): never
    {
        $this->requirePost();

        try {
            if (!$this->model->getById($id)) {
                throw new \InvalidArgumentException('Proveedor no encontrado.');
            }

            $data = $this->collectFormData(withEstado: true);
            $this->validateData($data, excludeId: $id);

            $ok = $this->model->update($id, $data);

            if (!$ok) {
                throw new \RuntimeException('Error al actualizar el proveedor. Inténtalo nuevamente.');
            }

            $this->json([
                'success' => true,
                'mensaje' => "El proveedor \"{$data['razon_social']}\" se actualizó correctamente.",
                'redirect'=> 'index.php?controller=proveedor&action=index',
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            error_log('ProveedorController::actualizar — ' . $e->getMessage());
            $this->json(['success' => false, 'mensaje' => 'Error interno del servidor.'], 500);
        }
    }

    /* ============================================================
     *  eliminar — GET/AJAX → JSON
     * ============================================================ */
    public function eliminar(int $id): never
    {
        try {
            $proveedor = $this->model->getById($id);
            if (!$proveedor) {
                throw new \InvalidArgumentException('Proveedor no encontrado.');
            }

            $totalProductos = $this->model->countProductos($id);
            $ok             = $this->model->delete($id);     // siempre desactiva

            if (!$ok) {
                throw new \RuntimeException('No se pudo desactivar el proveedor.');
            }

            $nombre  = $proveedor['razon_social'];
            $mensaje = $totalProductos > 0
                ? "Proveedor \"{$nombre}\" desactivado. Tiene {$totalProductos} producto(s) asociado(s)."
                : "Proveedor \"{$nombre}\" desactivado exitosamente.";

            $this->json(['success' => true, 'mensaje' => $mensaje]);

        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            error_log('ProveedorController::eliminar — ' . $e->getMessage());
            $this->json(['success' => false, 'mensaje' => 'Error interno del servidor.'], 500);
        }
    }

    /* ============================================================
     *  activar — GET/AJAX → JSON
     * ============================================================ */
    public function activar(int $id): never
    {
        try {
            $proveedor = $this->model->getById($id);
            if (!$proveedor) {
                throw new \InvalidArgumentException('Proveedor no encontrado.');
            }

            $ok = $this->model->activar($id);
            if (!$ok) {
                throw new \RuntimeException('No se pudo activar el proveedor.');
            }

            $this->json([
                'success' => true,
                'mensaje' => "Proveedor \"{$proveedor['razon_social']}\" activado exitosamente.",
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            error_log('ProveedorController::activar — ' . $e->getMessage());
            $this->json(['success' => false, 'mensaje' => 'Error interno del servidor.'], 500);
        }
    }

    /* ============================================================
     *  ver — Detalle de un proveedor
     * ============================================================ */
    public function ver(int $id): void
    {
        $proveedor = $this->model->getById($id);

        if (!$proveedor) {
            $this->flash('error', 'No encontrado', 'El proveedor solicitado no existe.');
            SessionMiddleware::redirect('index.php?controller=proveedor&action=index');
            return;
     }

    $totalProductos = $this->model->countProductos($id);
    $totalMovimientos = $this->model->countMovimientos($id);  // ← NUEVO: contar compras
    $valorCompras = $this->model->getValorCompras($id);      // ← NUEVO: valor total de compras
    $sweetAlert = $this->getFlash();

    require_once __DIR__ . '/../views/inventario/proveedores/ver.php';
}

    /* ============================================================
     *  verificar_ruc — AJAX → JSON
     * ============================================================ */
    public function verificar_ruc(): never
    {
        $ruc       = trim($_GET['ruc']        ?? '');
        $excludeId = !empty($_GET['exclude_id']) ? (int) $_GET['exclude_id'] : null;

        if ($ruc === '') {
            $this->json(['disponible' => false, 'mensaje' => 'Ingrese un RUC.']);
        }

        if (!preg_match('/^\d{11}$/', $ruc)) {
            $this->json(['disponible' => false, 'mensaje' => 'El RUC debe tener 11 dígitos.']);
        }

        $existe = $this->model->rucExists($ruc, $excludeId);
        $this->json([
            'disponible' => !$existe,
            'mensaje'    => $existe ? 'El RUC ya está registrado.' : 'RUC disponible.',
        ]);
    }

    /* ============================================================
     *  getProveedoresJson — AJAX → JSON (para selects dinámicos)
     * ============================================================ */
    public function getProveedoresJson(): never
    {
        $proveedores = $this->model->getAll(['estado' => 'activo']);
        $this->json(['success' => true, 'proveedores' => $proveedores]);
    }

    /* ============================================================
     *  exportar — Descarga CSV
     * ============================================================ */
    public function exportar(): never
    {
        $filters = array_filter([
            'estado' => $_GET['estado'] ?? null,
            'search' => $_GET['search'] ?? null,
        ]);

        $proveedores = $this->model->getAll($filters);

        $filename = 'proveedores_' . date('Ymd_His') . '.csv';

        if (ob_get_level()) ob_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'ID', 'Razón Social', 'RUC', 'Nombre Comercial',
            'Ciudad', 'País', 'Teléfono', 'Email',
            'Contacto', 'Tel. Contacto', 'Email Contacto',
            'Entrega (días)', 'Condiciones Pago',
            'Total Productos', 'Valor Compras', 'Estado',
        ], ';');

        foreach ($proveedores as $p) {
            fputcsv($out, [
                $p['id_proveedor'],
                $p['razon_social'],
                $p['ruc'],
                $p['nombre_comercial']  ?? '',
                $p['ciudad']            ?? '',
                $p['pais']              ?? '',
                $p['telefono']          ?? '',
                $p['email']             ?? '',
                $p['contacto_nombre']   ?? '',
                $p['contacto_telefono'] ?? '',
                $p['contacto_email']    ?? '',
                $p['tiempo_entrega_dias'] ?? 0,
                $p['condiciones_pago']  ?? '',
                $p['total_productos']   ?? 0,
                number_format((float)($p['valor_compras'] ?? 0), 2, '.', ''),
                $p['estado'],
            ], ';');
        }

        fclose($out);
        exit();
    }
}