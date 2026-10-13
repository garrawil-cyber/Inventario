# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [Unreleased]

### Planeado
- Módulo de compras a proveedores
- Gestión de múltiples almacenes
- Códigos de barras con lector
- App móvil para inventario físico
- Integración con sistemas de facturación electrónica
- Reportes avanzados con gráficos interactivos
- Módulo de devoluciones
- Alertas por email de stock bajo

---

## [1.0.0] - 2026-10-13

### 🎉 Versión inicial estable

#### Añadido

**Autenticación y Roles**
- Login/Logout con hash bcrypt
- Roles: Administrador, Vendedor, Almacén
- Sesiones seguras
- Control de acceso por módulo

**Gestión de Productos**
- CRUD completo de productos
- Código SKU único por producto
- Categorización jerárquica
- Precio de compra y venta
- Stock actual y mínimo
- Imagen del producto
- Unidad de medida (unidad, kg, litro)

**Gestión de Categorías**
- Estructura jerárquica
- Categoría padre-hijo
- Búsqueda y filtrado
- Estado activo/inactivo

**Gestión de Ubicaciones**
- Almacenes
- Estantes/posiciones
- Capacidad por ubicación
- Asignación de productos a ubicaciones

**Control de Stock**
- Entradas de mercadería
- Salidas por venta
- Ajustes por inventario físico
- Historial de movimientos
- Alertas de stock mínimo

**Módulo de Ventas**
- Punto de venta (POS) con carrito
- Búsqueda rápida por nombre o código
- Selección de cliente
- Aplicación de descuentos
- Cálculo automático de IGV
- Impresión de comprobante

**Gestión de Clientes**
- CRUD de clientes
- Búsqueda por DNI o RUC
- Historial de compras por cliente
- Datos de contacto

**Gestión de Usuarios**
- CRUD de usuarios
- Asignación de roles
- Foto de perfil
- Estado activo/inactivo
- Cambio de contraseña

**Reportes**
- Ventas por día/semana/mes
- Productos más vendidos
- Productos con stock bajo
- Movimientos de inventario
- Exportación a Excel y PDF

**Dashboard**
- KPIs de ventas del día
- Productos con stock bajo
- Top 5 productos vendidos
- Gráfico de ventas últimos 7 días
- Accesos rápidos

**Sistema**
- Búsqueda en vivo con filtros
- Diseño responsive
- Sidebar colapsable
- Notificaciones con SweetAlert2
- Confirmaciones antes de eliminar

#### Características Técnicas
- Arquitectura MVC pura (sin frameworks)
- PDO con prepared statements
- Bootstrap 5.3
- Font Awesome 6.4
- SweetAlert2 para modales
- JavaScript vanilla para AJAX
- Compatible con XAMPP, Laragon, WAMP

#### Seguridad
- Contraseñas con `password_hash()`
- Escape HTML en vistas
- Validación de sesión
- Control de acceso por rol
- Sanitización de entradas
- Protección contra SQL injection

#### Documentación
- README.md profesional
- Banner SVG personalizado
- Logo del proyecto
- CHANGELOG.md

---

## Formato del Changelog

### Tipos de cambios

- `Añadido` para nuevas funcionalidades
- `Cambiado` para cambios en funcionalidades existentes
- `Obsoleto` para funcionalidades que serán removidas
- `Eliminado` para funcionalidades removidas
- `Corregido` para corrección de bugs
- `Seguridad` para vulnerabilidades corregidas

### Versionado Semántico

- **MAJOR** (1.x.x): Cambios incompatibles
- **MINOR** (x.1.x): Nuevas funcionalidades compatibles
- **PATCH** (x.x.1): Correcciones de bugs

---

## Enlaces

- [Repositorio](https://github.com/garrawil-cyber/Inventario)
- [Issues](https://github.com/garrawil-cyber/Inventario/issues)
- [Releases](https://github.com/garrawil-cyber/Inventario/releases)
- [README](README.md)

---

[Unreleased]: https://github.com/garrawil-cyber/Inventario/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/garrawil-cyber/Inventario/releases/tag/v1.0.0