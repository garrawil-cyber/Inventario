![Sistema de Inventario](docs/banner.svg)
# 📦 Sistema de Inventario

Sistema web completo para la gestión de inventario, productos, ventas y ubicaciones. Desarrollado en **PHP puro (MVC)** con **MySQL** y **Bootstrap 5**, con autenticación por roles, control de stock y reportes.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.0-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-%3E%3D8.0-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Módulos del Sistema](#-módulos-del-sistema)
- [Stack Tecnológico](#-stack-tecnológico)
- [Requisitos del Sistema](#-requisitos-del-sistema)
- [Instalación](#-instalación)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Base de Datos](#-base-de-datos)
- [Configuración](#-configuración)
- [Credenciales por Defecto](#-credenciales-por-defecto)
- [Uso del Sistema](#-uso-del-sistema)
- [Seguridad](#-seguridad)
- [Contribución](#-contribución)
- [Changelog](#-changelog)
- [Licencia](#-licencia)

---

## ✨ Características

- 🔐 **Autenticación con roles** (Administrador, Vendedor, Almacén)
- 📦 **Gestión completa de productos** con categorías y códigos únicos
- 📊 **Control de stock** en tiempo real con alertas de mínimos
- 🏪 **Gestión de ubicaciones** (almacenes, estantes, posiciones)
- 🛒 **Módulo de ventas** con carrito, descuentos e impuestos
- 👥 **Administración de usuarios** con permisos granulares
- 📈 **Dashboard con KPIs** (ventas del día, productos bajos, top productos)
- 📄 **Reportes** exportables a Excel y PDF
- 🎨 **Diseño responsive** con sidebar colapsable y modo móvil
- 🌙 **Interfaz moderna** con notificaciones SweetAlert2
- 🔍 **Búsqueda avanzada** con filtros por categoría, estado y fecha
- 💾 **Backups manuales** de la base de datos

---

## 🧩 Módulos del Sistema

| Módulo | Descripción | Estado |
|---|---|---|
| **Auth** | Login/Logout con hash bcrypt y sesiones seguras | ✅ |
| **Dashboard** | KPIs generales y accesos rápidos | ✅ |
| **Productos** | CRUD completo con imágenes y códigos SKU | ✅ |
| **Categorías** | Organización jerárquica de productos | ✅ |
| **Ubicaciones** | Almacenes, estantes y posiciones | ✅ |
| **Stock** | Entradas, salidas y ajustes de inventario | ✅ |
| **Ventas** | Punto de venta con carrito y comprobantes | ✅ |
| **Usuarios** | Gestión de cuentas con roles y permisos | ✅ |
| **Reportes** | Exportación a Excel/PDF con filtros | ✅ |
| **Configuración** | Parámetros del sistema | ✅ |

---

## 🛠 Stack Tecnológico

### Backend
- **PHP 8.0+** (MVC puro, sin frameworks)
- **MySQL 8.0+** (InnoDB, UTF-8)
- **Composer** (gestión de dependencias)

### Frontend
- **Bootstrap 5.3** (UI responsive)
- **Font Awesome 6.4** (iconografía)
- **SweetAlert2** (modales y notificaciones)
- **JavaScript vanilla** (validaciones y AJAX)

### Herramientas
- **XAMPP** / **Laragon** / **WAMP** (entorno local)
- **Git** (control de versiones)
- **MySQL Workbench** / **phpMyAdmin** (administración de BD)

---

## 💻 Requisitos del Sistema

### Servidor
- PHP >= 8.0
- MySQL >= 8.0 o MariaDB >= 10.4
- Apache/Nginx con `mod_rewrite` habilitado
- Extensiones PHP: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `xml`, `json`

### Cliente
- Navegador moderno (Chrome, Firefox, Edge, Safari)
- JavaScript habilitado
- Resolución mínima: 360x640 (móvil) / 1366x768 (desktop)

---

## 🚀 Instalación

### Paso 1: Clonar el repositorio

```bash
git clone https://github.com/garrawil-cyber/Inventario.git
cd Inventario
Inventario/
│
├── app/
│   ├── config/              # Configuración y conexión a BD
│   │   ├── config.php
│   │   └── Database.php
│   │
│   ├── controllers/         # Controladores MVC
│   │   ├── AuthController.php
│   │   ├── ProductosController.php
│   │   ├── VentasController.php
│   │   ├── UbicacionesController.php
│   │   └── UsuariosController.php
│   │
│   ├── models/              # Modelos de datos
│   │   ├── Model.php        # Clase base
│   │   ├── Producto.php
│   │   ├── Venta.php
│   │   ├── Ubicacion.php
│   │   └── Usuario.php
│   │
│   └── helpers/             # Funciones auxiliares
│       ├── Validator.php
│       └── Response.php
│
├── views/                   # Vistas HTML
│   ├── layouts/
│   │   ├── inventario_layout.php
│   │   ├── footer.php
│   │   └── constants.php
│   │
│   └── inventario/          # Vistas por módulo
│       ├── productos/
│       ├── ventas/
│       ├── ubicaciones/
│       └── usuarios/
│
├── assets/                  # Recursos estáticos
│   ├── css/
│   ├── js/
│   └── img/
│
├── files/                   # Archivos subidos (imágenes de productos)
│
├── Base_datos/              # Scripts SQL
│   └── inventario.sql
│
├── public/
│   └── uploads/             # Uploads públicos
│
├── .env.example             # Plantilla de configuración
├── .gitignore
├── composer.json
├── index.php                # Front Controller
├── login.php
├── logout.php
├── dashboard.php
└── README.md
👥 Autor
Wilmer Garcia Ramirez — Desarrollo inicial

## 📚 Documentación

- [README.md](README.md) — Documentación principal
- [CHANGELOG.md](CHANGELOG.md) — Historial de cambios
- [LICENSE](LICENSE) — Licencia del proyecto

## 🔗 Enlaces

- [Repositorio](https://github.com/garrawil-cyber/Inventario)
- [Issues](https://github.com/garrawil-cyber/Inventario/issues)
- [Releases](https://github.com/garrawil-cyber/Inventario/releases)

🙏 Agradecimientos
Bootstrap

Font Awesome

SweetAlert2

📞 Soporte
📧 Email: soporte@inventario.com

🐛 Issues: GitHub Issues

