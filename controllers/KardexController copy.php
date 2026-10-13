<?php
/**
 * KardexController — VERSIÓN DEPURADA
 * ─────────────────────────────────────────────────────────────────────────────
 * Correcciones aplicadas:
 *
 *  C1 [CAUSA RAÍZ del error de la imagen]
 *     verificarPermiso() leía $_SESSION['rol'] → siempre NULL/unset porque
 *     SessionMiddleware guarda el rol en $_SESSION['usuario_rol'].
 *     Consecuencia: la excepción "Sesión no válida" se lanzaba inmediatamente
 *     y manejarError() redirigía de vuelta al index sin mostrar el kardex.
 *
 *  C2  Roles 'almacenero' y 'consultor' no existen en el sistema.
 *      Los roles definidos en permissions.php son:
 *        'admin', 'inventario', 'ventas', 'consulta'
 *      Se actualiza verificarPermiso() con los roles correctos en cada llamada.
 *
 *  C3  checkAuth() nunca se llamaba en el constructor.
 *      El front-controller (index.php) ya lo hace, pero el controlador debe
 *      ser autosuficiente para no depender del orden de llamadas externas.
 *
 *  C4  `use Dompdf\Dompdf` y `use Dompdf\Options` a nivel de archivo causan
 *      Fatal Error si Dompdf no está instalado (la clase no existe).
 *      Se mueven dentro del método pdf(), precedidos por el guard class_exists.
 *
 *  C5  Fallback de fechas en producto(), almacen() y exportar() usaba
 *      date('Y-m-01') → solo el mes actual → 0 resultados al inicio de mes.
 *      Se cambia a -90 días, consistente con la vista index.php.
 */

require_once __DIR__ . '/../models/Kardex.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Almacen.php';
require_once __DIR__ . '/../config/SessionMiddleware.php';
require_once 'vendor/autoload.php';  

class KardexController {

    private Kardex $kardexModel;
    private Producto $productoModel;
    private Almacen $almacenModel;

    public function __construct() {
        SessionMiddleware::checkAuth(); // [C3] Autenticación garantizada antes de cualquier acción
        $this->kardexModel   = new Kardex();
        $this->productoModel = new Producto();
        $this->almacenModel  = new Almacen();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vista principal
    // ─────────────────────────────────────────────────────────────────────────
    public function index() {
        try {
            $productos = $this->productoModel->getAll(['estado' => 'activo']);
            $almacenes = $this->almacenModel->getForSelect();

            require_once __DIR__ . '/../views/inventario/kardex/index.php';
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al cargar el módulo de Kardex');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Kardex de un producto específico
    // ─────────────────────────────────────────────────────────────────────────
    public function producto() {
        try {
            $idProducto  = filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);
            $fechaInicio = $this->validarFecha($_GET['fecha_inicio'] ?? null);
            $fechaFin    = $this->validarFecha($_GET['fecha_fin']    ?? null);

            if (!$idProducto) {
                throw new InvalidArgumentException('Debe seleccionar un producto válido');
            }

            // [C2] Roles correctos del sistema (permissions.php)
            $this->verificarPermiso(['admin', 'inventario', 'ventas', 'consulta']);

            $producto = $this->productoModel->getById($idProducto);
            if (!$producto) {
                throw new InvalidArgumentException('Producto no encontrado o inactivo');
            }

            // [C5] Fallback -90 días en lugar de date('Y-m-01')
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            if (strtotime($fechaFin) < strtotime($fechaInicio)) {
                throw new InvalidArgumentException('La fecha final no puede ser menor que la fecha inicial');
            }

            $kardex       = $this->kardexModel->getByProducto($idProducto, $fechaInicio, $fechaFin);
            $resumen      = $this->kardexModel->getResumenProducto($idProducto, $fechaInicio, $fechaFin);
            $estadisticas = $this->kardexModel->getEstadisticas($idProducto, $fechaInicio, $fechaFin);
            $resumen      = array_merge($producto, $resumen, $estadisticas);

            if (count($kardex) > 0) {
                SessionMiddleware::setSweetAlert(
                    'Kardex Cargado',
                    'Se encontraron ' . count($kardex) . ' movimiento(s) en el período seleccionado',
                    'success',
                    ['timer' => 2500, 'showConfirmButton' => false,
                     'toast' => true, 'position' => 'top-end', 'timerProgressBar' => true]
                );
            } else {
                SessionMiddleware::setSweetAlert(
                    'Sin Movimientos',
                    'No se encontraron movimientos para este producto en el período seleccionado',
                    'info',
                    ['confirmButtonText' => 'Entendido']
                );
            }

            require_once __DIR__ . '/../views/inventario/kardex/detalle.php';

        } catch (InvalidArgumentException $e) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=kardex&action=index',
                'Advertencia', $e->getMessage(), 'warning'
            );
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al consultar el Kardex del producto');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Kardex de un almacén completo
    // ─────────────────────────────────────────────────────────────────────────
    public function almacen() {
        try {
            $idAlmacen   = filter_input(INPUT_GET, 'id_almacen', FILTER_VALIDATE_INT);
            $fechaInicio = $this->validarFecha($_GET['fecha_inicio'] ?? null);
            $fechaFin    = $this->validarFecha($_GET['fecha_fin']    ?? null);

            if (!$idAlmacen) {
                throw new InvalidArgumentException('Debe seleccionar un almacén válido');
            }

            // [C2] Roles correctos
            $this->verificarPermiso(['admin', 'inventario', 'ventas', 'consulta']);

            $almacen = $this->almacenModel->getById($idAlmacen);
            if (!$almacen) {
                throw new InvalidArgumentException('Almacén no encontrado');
            }

            // [C5] Fallback -90 días
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            if (strtotime($fechaFin) < strtotime($fechaInicio)) {
                throw new InvalidArgumentException('La fecha final no puede ser menor que la fecha inicial');
            }

            $kardex = $this->kardexModel->getByAlmacen($idAlmacen, $fechaInicio, $fechaFin);

            if (count($kardex) > 0) {
                SessionMiddleware::setSweetAlert(
                    'Kardex del Almacén',
                    'Se encontraron ' . count($kardex) . ' movimiento(s)',
                    'success',
                    ['timer' => 2000, 'showConfirmButton' => false,
                     'toast' => true, 'position' => 'top-end']
                );
            }

            require_once __DIR__ . '/../views/inventario/kardex/almacen.php';

        } catch (InvalidArgumentException $e) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=kardex&action=index',
                'Advertencia', $e->getMessage(), 'warning'
            );
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al consultar el Kardex del almacén');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generar PDF con Dompdf  [C4]
    // ─────────────────────────────────────────────────────────────────────────
    public function pdf() {
        try {
            $idProducto  = filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);
            $fechaInicio = $this->validarFecha($_GET['fecha_inicio'] ?? null);
            $fechaFin    = $this->validarFecha($_GET['fecha_fin']    ?? null);

            if (!$idProducto) {
                throw new InvalidArgumentException('Debe seleccionar un producto válido');
            }

            // [C2] Solo admin e inventario pueden exportar
            $this->verificarPermiso(['admin', 'inventario']);

            // [C4] Guard dentro del método; los alias solo se crean si la clase existe
            if (!class_exists('Dompdf\\Dompdf')) {
                SessionMiddleware::redirectWithSweetAlert(
                    'index.php?controller=kardex&action=index',
                    'Librería No Disponible',
                    'La librería Dompdf no está instalada. Ejecute: composer require dompdf/dompdf',
                    'error'
                );
                return;
            }

            $producto = $this->productoModel->getById($idProducto);
            if (!$producto) {
                throw new InvalidArgumentException('Producto no encontrado');
            }

            // [C5] Fallback -90 días
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            $kardex       = $this->kardexModel->getByProducto($idProducto, $fechaInicio, $fechaFin);
            $resumen      = $this->kardexModel->getResumenProducto($idProducto, $fechaInicio, $fechaFin);
            $estadisticas = $this->kardexModel->getEstadisticas($idProducto, $fechaInicio, $fechaFin);
            $resumen      = array_merge($producto, $resumen, $estadisticas);

            // [C4] Instanciación con namespace completo (sin `use` global)
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new \Dompdf\Dompdf($options);

            ob_start();
            include __DIR__ . '/../views/inventario/kardex/templates/pdf_template.php';
            $html = ob_get_clean();

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $nombreArchivo = 'kardex_' . $producto['codigo'] . '_' . date('Ymd_His') . '.pdf';
            $dompdf->stream($nombreArchivo, ['Attachment' => true]);
            exit();

        } catch (InvalidArgumentException $e) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=kardex&action=index',
                'Error de Validación', $e->getMessage(), 'warning'
            );
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al generar el PDF del Kardex');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Exportar a Excel (.xls)
    // ─────────────────────────────────────────────────────────────────────────
    public function exportar() {
        try {
            $idProducto  = filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);
            $fechaInicio = $this->validarFecha($_GET['fecha_inicio'] ?? null);
            $fechaFin    = $this->validarFecha($_GET['fecha_fin']    ?? null);

            if (!$idProducto) {
                throw new InvalidArgumentException('Debe seleccionar un producto válido');
            }

            // [C2] Solo admin e inventario pueden exportar
            $this->verificarPermiso(['admin', 'inventario']);

            $producto = $this->productoModel->getById($idProducto);
            if (!$producto) {
                throw new InvalidArgumentException('Producto no encontrado');
            }

            // [C5] Fallback -90 días
            $fechaInicio = $fechaInicio ?? date('Y-m-d', strtotime('-90 days'));
            $fechaFin    = $fechaFin    ?? date('Y-m-d');

            $kardex       = $this->kardexModel->getByProducto($idProducto, $fechaInicio, $fechaFin);
            $resumen      = $this->kardexModel->getResumenProducto($idProducto, $fechaInicio, $fechaFin);
            $estadisticas = $this->kardexModel->getEstadisticas($idProducto, $fechaInicio, $fechaFin);
            $resumen      = array_merge($producto, $resumen, $estadisticas);

            $nombreArchivo = 'kardex_' . $producto['codigo'] . '_' . date('Ymd_His') . '.xls';

            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            require_once __DIR__ . '/../views/inventario/kardex/exportar.php';
            exit();

        } catch (InvalidArgumentException $e) {
            SessionMiddleware::redirectWithSweetAlert(
                'index.php?controller=kardex&action=index',
                'Error de Validación', $e->getMessage(), 'warning'
            );
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al exportar el Kardex');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Costo promedio ponderado
    // ─────────────────────────────────────────────────────────────────────────
    public function costo_promedio() {
        try {
            header('Content-Type: application/json; charset=UTF-8');

            $idProducto = filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);
            if (!$idProducto) {
                echo json_encode(['success' => false, 'message' => 'Producto no especificado']);
                exit();
            }

            // [C2] Roles correctos
            $this->verificarPermiso(['admin', 'inventario', 'ventas', 'consulta']);

            $costoPromedio = $this->kardexModel->getCostoPromedio($idProducto);
            echo json_encode([
                'success'                  => true,
                'costo_promedio'           => number_format($costoPromedio, 2, '.', ''),
                'costo_promedio_formatted' => 'S/ ' . number_format($costoPromedio, 2),
            ]);
            exit();

        } catch (Exception $e) {
            echo json_encode(['success' => false,
                'message' => 'Error al calcular el costo promedio: ' . $e->getMessage()]);
            exit();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Búsqueda de productos
    // ─────────────────────────────────────────────────────────────────────────
    public function buscar_productos() {
        try {
            header('Content-Type: application/json; charset=UTF-8');

            // FILTER_SANITIZE_STRING deprecado PHP 8.1 → FILTER_DEFAULT + htmlspecialchars
            $termino = filter_input(INPUT_GET, 'termino', FILTER_DEFAULT);
            $termino = $termino !== null
                ? trim(htmlspecialchars($termino, ENT_QUOTES, 'UTF-8'))
                : '';

            if (empty($termino) || strlen($termino) < 2) {
                echo json_encode(['success' => false, 'message' => 'Debe ingresar al menos 2 caracteres']);
                exit();
            }

            $productos = $this->productoModel->buscar($termino);
            echo json_encode(['success' => true, 'productos' => $productos]);
            exit();

        } catch (Exception $e) {
            echo json_encode(['success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage()]);
            exit();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX: Resumen rápido
    // ─────────────────────────────────────────────────────────────────────────
    public function resumen_rapido() {
        try {
            header('Content-Type: application/json; charset=UTF-8');

            $idProducto = filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);
            if (!$idProducto) {
                echo json_encode(['success' => false, 'message' => 'Producto no especificado']);
                exit();
            }

            $resumen = $this->kardexModel->getResumenProducto($idProducto);
            echo json_encode(['success' => true, 'resumen' => $resumen]);
            exit();

        } catch (Exception $e) {
            echo json_encode(['success' => false,
                'message' => 'Error al obtener resumen: ' . $e->getMessage()]);
            exit();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function validarFecha($fecha) {
        if ($fecha === null || $fecha === '') return null;

        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if ($d && $d->format('Y-m-d') === $fecha) return $fecha;

        throw new InvalidArgumentException('Formato de fecha inválido. Use: YYYY-MM-DD');
    }

    /**
     * [C1] CORRECCIÓN CRÍTICA:
     *   ANTES: leía $_SESSION['rol']       → siempre NULL → excepción → redirect
     *   AHORA: lee $_SESSION['usuario_rol'] → clave real que guarda SessionMiddleware
     *
     * [C2] Los roles válidos del sistema (permissions.php) son:
     *   'admin', 'inventario', 'ventas', 'consulta'
     *   (NO existen 'almacenero' ni 'consultor')
     */
    private function verificarPermiso($rolesPermitidos) {
        // [C1] $_SESSION['usuario_rol'] es la clave correcta de SessionMiddleware
        if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol'])) {
            throw new Exception('Sesión no válida. Por favor inicie sesión.');
        }

        // [C1] 'usuario_rol', no 'rol'
        if (!in_array($_SESSION['usuario_rol'], $rolesPermitidos)) {
            throw new Exception('No tiene permisos para acceder a esta funcionalidad');
        }
    }

    private function manejarError(Exception $e, $mensajeUsuario = 'Ha ocurrido un error') {
        error_log(
            date('[Y-m-d H:i:s] ') .
            'ERROR en KardexController: ' . $e->getMessage() .
            "\n" . $e->getTraceAsString()
        );

        SessionMiddleware::setSweetAlert(
            'Error', $mensajeUsuario, 'error',
            ['confirmButtonText' => 'Entendido', 'confirmButtonColor' => '#d33',
             'showCloseButton' => true]
        );

        // [C1] 'usuario_rol', no 'rol'
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
            error_log('Detalles técnicos: ' . $e->getMessage());
            error_log('Archivo: ' . $e->getFile() . ' en línea ' . $e->getLine());
        }

        header('Location: index.php?controller=kardex&action=index');
        exit();
    }
}
?>