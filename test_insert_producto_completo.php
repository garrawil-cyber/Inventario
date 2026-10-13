<?php
/**
 * test_insert_producto_completo.php
 * Script completo para probar sp_InsertProducto
 * Incluye pruebas exitosas y pruebas de error
 * 
 * USO: http://tudominio.com/test_insert_producto_completo.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Producto.php';

header('Content-Type: text/plain; charset=utf-8');

echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║              PRUEBA COMPLETA DE sp_InsertProducto                         ║\n";
echo "║                    (Éxitos + Errores)                                     ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n\n";

$db = getDB();
$productoModel = new Producto();

// ──────────────────────────────────────────────────────────────────────────────
// 1. VERIFICACIÓN DE DATOS DE REFERENCIA
// ──────────────────────────────────────────────────────────────────────────────
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║ 1. VERIFICANDO DATOS DE REFERENCIA                                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";

// Verificar categoría activa
$stmt = $db->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 'activo' LIMIT 1");
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$categoria) {
    die("❌ ERROR CRÍTICO: No hay categorías activas. Crea una categoría primero.\n");
}
echo "✓ Categoría activa: ID {$categoria['id_categoria']} - {$categoria['nombre']}\n";

// Verificar almacén activo
$stmt = $db->query("SELECT id_almacen, nombre FROM almacenes WHERE estado = 'activo' LIMIT 1");
$almacen = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$almacen) {
    die("❌ ERROR CRÍTICO: No hay almacenes activos. Crea un almacén primero.\n");
}
echo "✓ Almacén activo: ID {$almacen['id_almacen']} - {$almacen['nombre']}\n";

// Verificar usuario
$stmt = $db->query("SELECT id_usuario, nombre FROM usuarios LIMIT 1");
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$usuarioId = $usuario ? $usuario['id_usuario'] : 1;
echo "✓ Usuario: ID {$usuarioId} - " . ($usuario ? $usuario['nombre'] : 'Usuario por defecto') . "\n\n";

// ──────────────────────────────────────────────────────────────────────────────
// 2. PRUEBAS EXITOSAS
// ──────────────────────────────────────────────────────────────────────────────
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║ 2. PRUEBAS EXITOSAS (deben crear productos correctamente)                ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";

$exitosas = 0;
$erroresEsperados = 0;
$erroresInesperados = 0;

// ─── Prueba exitosa 1: Producto completo ──────────────────────────────────────
echo "\n▶ PRUEBA 1: Producto completo (todos los campos)\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataExito1 = [
    'codigo'            => 'TEST-EXITO-001',
    'sku'               => null,
    'nombre'            => 'Laptop Gamer Ultra Test',
    'descripcion'       => 'Laptop con Intel i9, 32GB RAM, RTX 4080, 2TB SSD',
    'id_categoria'      => (int)$categoria['id_categoria'],
    'id_marca'          => null,
    'id_proveedor'      => null,
    'id_almacen'        => (int)$almacen['id_almacen'],
    'unidad_medida'     => 'unidad',
    'stock_actual'      => 100,
    'stock_maximo'      => 500,
    'stock_minimo'      => 20,
    'punto_reorden'     => 50,
    'precio_compra'     => 3500.00,
    'precio_venta'      => 4999.00,
    'precio_mayorista'  => 4500.00,
    'talla'             => '15.6 pulgadas',
    'color'             => 'Gris Espacial',
    'material'          => 'Aluminio',
    'genero'            => 'Unisex',
    'temporada'         => 'todo_año',
    'imagen_principal'  => '/img/test/laptop-ultra.jpg',
    'codigo_barras'     => '7751111111111',
    'lote'              => 'LOT-EXITO-001',
    'fecha_vencimiento' => '2028-12-31',
    'observaciones'     => 'Producto premium de prueba',
];

try {
    $id = $productoModel->create($dataExito1);
    echo "   ✅ CREADO - ID: {$id}\n";
    
    // Verificar datos insertados
    $producto = $productoModel->getById($id);
    echo "      • Código: {$producto['codigo']}\n";
    echo "      • SKU generado: {$producto['sku']}\n";
    echo "      • Estado stock: {$producto['estado_stock']}\n";
    $exitosas++;
} catch (Exception $e) {
    echo "   ❌ ERROR INESPERADO: " . $e->getMessage() . "\n";
    $erroresInesperados++;
}

// ─── Prueba exitosa 2: Producto mínimo ─────────────────────────────────────────
echo "\n▶ PRUEBA 2: Producto mínimo (solo campos obligatorios)\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataExito2 = [
    'codigo'            => 'TEST-EXITO-002',
    'sku'               => null,
    'nombre'            => 'Mouse USB Óptico',
    'descripcion'       => null,
    'id_categoria'      => (int)$categoria['id_categoria'],
    'id_marca'          => null,
    'id_proveedor'      => null,
    'id_almacen'        => (int)$almacen['id_almacen'],
    'unidad_medida'     => 'unidad',
    'stock_actual'      => 50,
    'stock_maximo'      => 200,
    'stock_minimo'      => 10,
    'punto_reorden'     => null,
    'precio_compra'     => 12.00,
    'precio_venta'      => 29.90,
    'precio_mayorista'  => null,
    'talla'             => null,
    'color'             => null,
    'material'          => null,
    'genero'            => 'Unisex',
    'temporada'         => 'todo_año',
    'imagen_principal'  => null,
    'codigo_barras'     => null,
    'lote'              => null,
    'fecha_vencimiento' => null,
    'observaciones'     => null,
];

try {
    $id = $productoModel->create($dataExito2);
    echo "   ✅ CREADO - ID: {$id}\n";
    
    $producto = $productoModel->getById($id);
    echo "      • Código: {$producto['codigo']}\n";
    echo "      • Punto reorden (calculado): {$producto['punto_reorden']}\n";
    $exitosas++;
} catch (Exception $e) {
    echo "   ❌ ERROR INESPERADO: " . $e->getMessage() . "\n";
    $erroresInesperados++;
}

// ─── Prueba exitosa 3: SKU personalizado ───────────────────────────────────────
echo "\n▶ PRUEBA 3: SKU personalizado (NO auto-generado)\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataExito3 = [
    'codigo'            => 'TEST-EXITO-003',
    'sku'               => 'SKU-CUSTOM-TEST-001',
    'nombre'            => 'Teclado Mecánico RGB',
    'descripcion'       => 'Teclado switch rojo, RGB, anti-ghosting',
    'id_categoria'      => (int)$categoria['id_categoria'],
    'id_marca'          => null,
    'id_proveedor'      => null,
    'id_almacen'        => (int)$almacen['id_almacen'],
    'unidad_medida'     => 'unidad',
    'stock_actual'      => 30,
    'stock_maximo'      => 150,
    'stock_minimo'      => 15,
    'punto_reorden'     => 20,
    'precio_compra'     => 180.00,
    'precio_venta'      => 350.00,
    'precio_mayorista'  => 299.00,
    'talla'             => 'Completo',
    'color'             => 'Negro',
    'material'          => 'ABS + Aluminio',
    'genero'            => 'Unisex',
    'temporada'         => 'todo_año',
    'imagen_principal'  => null,
    'codigo_barras'     => '7752222222222',
    'lote'              => 'LOT-EXITO-002',
    'fecha_vencimiento' => null,
    'observaciones'     => 'Prueba de SKU personalizado',
];

try {
    $id = $productoModel->create($dataExito3);
    echo "   ✅ CREADO - ID: {$id}\n";
    
    $producto = $productoModel->getById($id);
    echo "      • SKU personalizado: {$producto['sku']}\n";
    echo "      • Coincide?: " . ($producto['sku'] === 'SKU-CUSTOM-TEST-001' ? '✓ Sí' : '✗ No') . "\n";
    $exitosas++;
} catch (Exception $e) {
    echo "   ❌ ERROR INESPERADO: " . $e->getMessage() . "\n";
    $erroresInesperados++;
}

// ─── Prueba exitosa 4: Producto con alerta por stock bajo ──────────────────────
echo "\n▶ PRUEBA 4: Producto con stock bajo (debe generar alerta)\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataExito4 = [
    'codigo'            => 'TEST-EXITO-004',
    'sku'               => null,
    'nombre'            => 'Monitor 27" Curvo',
    'descripcion'       => 'Monitor curvo 165Hz, 1ms, QHD',
    'id_categoria'      => (int)$categoria['id_categoria'],
    'id_marca'          => null,
    'id_proveedor'      => null,
    'id_almacen'        => (int)$almacen['id_almacen'],
    'unidad_medida'     => 'unidad',
    'stock_actual'      => 5,   // Menor al mínimo
    'stock_maximo'      => 100,
    'stock_minimo'      => 20,
    'punto_reorden'     => null,
    'precio_compra'     => 800.00,
    'precio_venta'      => 1299.00,
    'precio_mayorista'  => 1150.00,
    'talla'             => '27 pulgadas',
    'color'             => 'Negro',
    'material'          => 'Plástico',
    'genero'            => 'Unisex',
    'temporada'         => 'todo_año',
    'imagen_principal'  => null,
    'codigo_barras'     => '7753333333333',
    'lote'              => 'LOT-EXITO-003',
    'fecha_vencimiento' => null,
    'observaciones'     => 'Stock bajo - debe generar alerta',
];

try {
    $id = $productoModel->create($dataExito4);
    echo "   ✅ CREADO - ID: {$id}\n";
    
    $producto = $productoModel->getById($id);
    echo "      • Estado stock: {$producto['estado_stock']} (debería ser 'critico' o 'bajo')\n";
    
    // Verificar alerta
    $stmt = $db->prepare("SELECT * FROM alertas_stock WHERE id_producto = ?");
    $stmt->execute([$id]);
    $alerta = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($alerta) {
        echo "      • 📢 Alerta generada: {$alerta['tipo_alerta']} - {$alerta['nivel_urgencia']}\n";
    } else {
        echo "      • ⚠️ No se generó alerta (revisar lógica del SP)\n";
    }
    $exitosas++;
} catch (Exception $e) {
    echo "   ❌ ERROR INESPERADO: " . $e->getMessage() . "\n";
    $erroresInesperados++;
}

// ──────────────────────────────────────────────────────────────────────────────
// 3. PRUEBAS DE ERROR (deben fallar con mensajes específicos)
// ──────────────────────────────────────────────────────────────────────────────
echo "\n\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║ 3. PRUEBAS DE ERROR (deben fallar con mensajes específicos)               ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";

// ─── Error 1: Código duplicado ─────────────────────────────────────────────────
echo "\n▶ ERROR ESPERADO 1: Código duplicado\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError1 = $dataExito1;
$dataError1['codigo'] = 'TEST-EXITO-001';  // Mismo código que ya existe

try {
    $id = $productoModel->create($dataError1);
    echo "   ❌ ERROR: El SP permitió un código duplicado (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'código de producto ya existe') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 2: Código vacío ─────────────────────────────────────────────────────
echo "\n▶ ERROR ESPERADO 2: Código vacío\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError2 = $dataExito1;
$dataError2['codigo'] = '';
$dataError2['nombre'] = 'Producto Sin Código';

try {
    $id = $productoModel->create($dataError2);
    echo "   ❌ ERROR: El SP permitió código vacío (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'código del producto es obligatorio') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 3: Nombre vacío ─────────────────────────────────────────────────────
echo "\n▶ ERROR ESPERADO 3: Nombre vacío\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError3 = $dataExito1;
$dataError3['codigo'] = 'TEST-ERROR-003';
$dataError3['nombre'] = '';

try {
    $id = $productoModel->create($dataError3);
    echo "   ❌ ERROR: El SP permitió nombre vacío (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'nombre del producto es obligatorio') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 4: Categoría inválida (ID no existe) ────────────────────────────────
echo "\n▶ ERROR ESPERADO 4: Categoría inválida (ID 9999 no existe)\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError4 = $dataExito1;
$dataError4['codigo'] = 'TEST-ERROR-004';
$dataError4['id_categoria'] = 9999;

try {
    $id = $productoModel->create($dataError4);
    echo "   ❌ ERROR: El SP permitió categoría inválida (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'categoría especificada no existe') !== false || 
        strpos($msg, 'categoría es obligatoria') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 5: Precio compra menor o igual a 0 ──────────────────────────────────
echo "\n▶ ERROR ESPERADO 5: Precio compra = 0\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError5 = $dataExito1;
$dataError5['codigo'] = 'TEST-ERROR-005';
$dataError5['precio_compra'] = 0;

try {
    $id = $productoModel->create($dataError5);
    echo "   ❌ ERROR: El SP permitió precio compra = 0 (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'precio de compra debe ser mayor a 0') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 6: Precio venta menor que precio compra ─────────────────────────────
echo "\n▶ ERROR ESPERADO 6: Precio venta menor que precio compra\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError6 = $dataExito1;
$dataError6['codigo'] = 'TEST-ERROR-006';
$dataError6['precio_compra'] = 500.00;
$dataError6['precio_venta'] = 400.00;

try {
    $id = $productoModel->create($dataError6);
    echo "   ❌ ERROR: El SP permitió venta < compra (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'precio de venta no puede ser menor') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 7: Stock actual negativo ────────────────────────────────────────────
echo "\n▶ ERROR ESPERADO 7: Stock actual negativo\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError7 = $dataExito1;
$dataError7['codigo'] = 'TEST-ERROR-007';
$dataError7['stock_actual'] = -5;

try {
    $id = $productoModel->create($dataError7);
    echo "   ❌ ERROR: El SP permitió stock negativo (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'stock actual no puede ser negativo') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 8: Stock mínimo mayor que stock máximo ──────────────────────────────
echo "\n▶ ERROR ESPERADO 8: Stock mínimo mayor que stock máximo\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError8 = $dataExito1;
$dataError8['codigo'] = 'TEST-ERROR-008';
$dataError8['stock_minimo'] = 100;
$dataError8['stock_maximo'] = 50;

try {
    $id = $productoModel->create($dataError8);
    echo "   ❌ ERROR: El SP permitió min > max (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'stock mínimo no puede ser mayor') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 9: Fecha de vencimiento pasada ──────────────────────────────────────
echo "\n▶ ERROR ESPERADO 9: Fecha de vencimiento pasada\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError9 = $dataExito1;
$dataError9['codigo'] = 'TEST-ERROR-009';
$dataError9['fecha_vencimiento'] = '2020-01-01';

try {
    $id = $productoModel->create($dataError9);
    echo "   ❌ ERROR: El SP permitió fecha pasada (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'fecha de vencimiento no puede ser una fecha pasada') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ─── Error 10: SKU duplicado (cuando se proporciona manualmente) ───────────────
echo "\n▶ ERROR ESPERADO 10: SKU duplicado (proporcionado manualmente)\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

// Primero crear un producto con SKU específico
$dataSkuBase = $dataExito1;
$dataSkuBase['codigo'] = 'TEST-SKU-BASE';
$dataSkuBase['sku'] = 'SKU-PARA-DUPLICAR';

try {
    $idBase = $productoModel->create($dataSkuBase);
    echo "   • Producto base creado con SKU: SKU-PARA-DUPLICAR (ID: {$idBase})\n";
    
    // Intentar crear otro con el mismo SKU
    $dataError10 = $dataExito1;
    $dataError10['codigo'] = 'TEST-ERROR-010';
    $dataError10['sku'] = 'SKU-PARA-DUPLICAR';
    
    try {
        $id = $productoModel->create($dataError10);
        echo "   ❌ ERROR: El SP permitió SKU duplicado (debió fallar)\n";
        $erroresInesperados++;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'SKU ya existe') !== false) {
            echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
            $erroresEsperados++;
        } else {
            echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
            $erroresInesperados++;
        }
    }
} catch (Exception $e) {
    echo "   ⚠️ No se pudo crear producto base para prueba de SKU: " . $e->getMessage() . "\n";
}

// ─── Error 11: Código de barras duplicado ──────────────────────────────────────
echo "\n▶ ERROR ESPERADO 11: Código de barras duplicado\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$dataError11 = $dataExito1;
$dataError11['codigo'] = 'TEST-ERROR-011';
$dataError11['codigo_barras'] = '7751111111111';  // Mismo que TEST-EXITO-001

try {
    $id = $productoModel->create($dataError11);
    echo "   ❌ ERROR: El SP permitió código de barras duplicado (debió fallar)\n";
    $erroresInesperados++;
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'código de barras ya está registrado') !== false) {
        echo "   ✅ ERROR CAPTURADO CORRECTAMENTE: {$msg}\n";
        $erroresEsperados++;
    } else {
        echo "   ⚠️ ERROR PERO CON MENSAJE DIFERENTE: {$msg}\n";
        $erroresInesperados++;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// 4. RESUMEN FINAL
// ──────────────────────────────────────────────────────────────────────────────
echo "\n\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║ 4. RESUMEN DE PRUEBAS                                                     ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";

echo "\n   📊 ESTADÍSTICAS:\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";
echo "   • Pruebas exitosas (productos creados):     {$exitosas}\n";
echo "   • Errores esperados capturados:             {$erroresEsperados}\n";
echo "   • Errores inesperados:                      {$erroresInesperados}\n";

// Contar productos de prueba creados
$stmt = $db->query("SELECT COUNT(*) as total FROM productos WHERE codigo LIKE 'TEST-%'");
$totalTest = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   • Total productos de prueba en BD:          {$totalTest['total']}\n";

// Contar alertas generadas
$stmt = $db->query("SELECT COUNT(*) as total FROM alertas_stock a 
                    INNER JOIN productos p ON a.id_producto = p.id_producto 
                    WHERE p.codigo LIKE 'TEST-%'");
$alertasTest = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   • Total alertas generadas:                  {$alertasTest['total']}\n";

echo "\n   📈 RESULTADO FINAL:\n";
echo "   ───────────────────────────────────────────────────────────────────────\n";

$totalPruebas = $exitosas + $erroresEsperados + $erroresInesperados;
if ($erroresInesperados === 0 && $erroresEsperados > 0 && $exitosas > 0) {
    echo "   ✅ TODAS LAS PRUEBAS SUPERADAS CORRECTAMENTE\n";
    echo "      • Las pruebas exitosas crearon productos\n";
    echo "      • Las pruebas de error capturaron las validaciones del SP\n";
} elseif ($erroresInesperados > 0) {
    echo "   ⚠️ ALGUNAS PRUEBAS FALLARON - Revisar logs\n";
} else {
    echo "   ❌ ERRORES CRÍTICOS - El SP no está funcionando correctamente\n";
}

echo "\n╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║                         FIN DE LAS PRUEBAS                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";