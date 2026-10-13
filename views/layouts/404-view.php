<?php
// Incluir configuración de sesión
require_once __DIR__ . '/../../config/SessionMiddleware.php';

SessionMiddleware::checkAuth();

// ── Información del usuario autenticado ──────────────────────────────────────
$userInfo = SessionMiddleware::getUserInfo();

// Obtener la URL que se intentó acceder
$requestedUrl = $_SERVER['REQUEST_URI'] ?? '/desconocido';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>404 — Página No Encontrada | Sistema de Inventario</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #080E1A;
      --surface:   #0F1826;
      --card:      #141F30;
      --border:    rgba(255,255,255,0.07);
      --amber:     #F5A623;
      --amber-dim: #8B691B;
      --amber-glow: rgba(245,166,35,0.18);
      --text:      #E8EDF5;
      --muted:     #6B7A92;
      --stripe:    rgba(245,166,35,0.04);
    }

    html, body {
      height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
    }

    /* ── Scanline overlay ── */
    body::before {
      content: '';
      pointer-events: none;
      position: fixed;
      inset: 0;
      background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 3px,
        var(--stripe) 3px,
        var(--stripe) 4px
      );
      z-index: 0;
    }

    /* ── Grid background ── */
    body::after {
      content: '';
      pointer-events: none;
      position: fixed;
      inset: 0;
      background-image:
        linear-gradient(var(--border) 1px, transparent 1px),
        linear-gradient(90deg, var(--border) 1px, transparent 1px);
      background-size: 48px 48px;
      z-index: 0;
    }

    /* ── Layout ── */
    .wrapper {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
    }

    /* ── Sidebar ── */
    .sidebar {
      width: 260px;
      flex-shrink: 0;
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      padding: 32px 24px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 40px;
    }

    .brand-icon {
      width: 36px;
      height: 36px;
      background: var(--amber);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .brand-icon svg { width: 20px; height: 20px; fill: #fff; }

    .brand-name {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 15px;
      letter-spacing: 0.02em;
      color: var(--text);
      line-height: 1.2;
    }

    .brand-name span {
      display: block;
      font-size: 10px;
      font-weight: 400;
      color: var(--muted);
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    .nav-section { margin-bottom: 8px; }
    .nav-label {
      font-size: 10px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--muted);
      padding: 0 8px;
      margin-bottom: 6px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-radius: 8px;
      color: var(--muted);
      font-size: 13.5px;
      cursor: default;
      margin-bottom: 2px;
    }

    .nav-item svg { width: 16px; height: 16px; opacity: 0.5; }

    .sidebar-footer {
      margin-top: auto;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }

    .user-row {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #1E2D42;
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
    }

    .user-info { flex: 1; min-width: 0; }
    .user-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-role {
      font-size: 11px;
      color: var(--muted);
    }

    .status-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--amber);
      box-shadow: 0 0 0 2px var(--amber-glow);
      animation: pulse 1.8s ease-in-out infinite;
    }

    /* ── Main content ── */
    .main {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 32px;
    }

    /* ── Top bar ── */
    .topbar {
      position: absolute;
      top: 0;
      left: 260px;
      right: 0;
      height: 56px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 32px;
      gap: 8px;
    }

    .crumb {
      font-size: 12.5px;
      color: var(--muted);
    }

    .crumb-sep { color: var(--border); margin: 0 2px; }

    .crumb.active { color: var(--amber); }

    /* ── Error card ── */
    .error-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 56px 64px;
      max-width: 600px;
      width: 100%;
      text-align: center;
      position: relative;
      overflow: hidden;
      margin-top: 56px;
    }

    .error-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 300px;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--amber), transparent);
    }

    /* ── Compass/Map Icon ── */
    .compass-wrap {
      width: 96px;
      height: 96px;
      margin: 0 auto 32px;
      position: relative;
    }

    .compass-ring {
      position: absolute;
      inset: -12px;
      border-radius: 50%;
      border: 1px solid rgba(245,166,35,0.25);
      animation: ring-expand 2.4s ease-out infinite;
    }

    .compass-ring:nth-child(2) { animation-delay: 0.8s; }
    .compass-ring:nth-child(3) { animation-delay: 1.6s; }

    .compass-circle {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: rgba(245,166,35,0.1);
      border: 1.5px solid rgba(245,166,35,0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      z-index: 1;
    }

    .compass-circle svg {
      width: 40px;
      height: 40px;
      fill: var(--amber);
    }

    /* ── Code ── */
    .error-code {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 96px;
      line-height: 1;
      letter-spacing: -0.04em;
      color: transparent;
      -webkit-text-stroke: 1px rgba(245,166,35,0.4);
      margin-bottom: 4px;
      position: relative;
      user-select: none;
    }

    .error-code::after {
      content: '404';
      position: absolute;
      inset: 0;
      color: var(--amber);
      opacity: 0.12;
      filter: blur(8px);
      -webkit-text-stroke: 0;
    }

    .error-title {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 26px;
      letter-spacing: -0.01em;
      color: var(--text);
      margin-bottom: 12px;
    }

    .error-subtitle {
      font-size: 14.5px;
      color: var(--muted);
      line-height: 1.65;
      max-width: 360px;
      margin: 0 auto 36px;
    }

    /* ── Alert strip ── */
    .alert-strip {
      background: rgba(245,166,35,0.08);
      border: 1px solid rgba(245,166,35,0.22);
      border-radius: 8px;
      padding: 12px 18px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
      text-align: left;
      margin-bottom: 36px;
    }

    .alert-icon {
      flex-shrink: 0;
      width: 18px;
      height: 18px;
      color: var(--amber);
      margin-top: 1px;
    }

    .alert-text {
      font-size: 13px;
      color: rgba(245,166,35,0.85);
      line-height: 1.55;
    }

    .alert-text strong { color: var(--amber); font-weight: 600; }

    /* ── URL Badge ── */
    .url-badge {
      background: rgba(0,0,0,0.3);
      border-radius: 6px;
      padding: 4px 8px;
      font-family: 'Courier New', monospace;
      font-size: 11px;
      color: var(--amber);
      word-break: break-all;
      margin-top: 8px;
    }

    /* ── Actions ── */
    .actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 24px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer;
      text-decoration: none;
      transition: opacity 0.15s, transform 0.12s;
      border: none;
    }

    .btn:hover { opacity: 0.85; transform: translateY(-1px); }
    .btn:active { transform: translateY(0); }

    .btn-primary {
      background: var(--amber);
      color: #080E1A;
      font-weight: 600;
    }

    .btn-ghost {
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--border);
    }

    .btn svg { width: 15px; height: 15px; }

    /* ── Metadata row ── */
    .meta-row {
      display: flex;
      justify-content: center;
      gap: 24px;
      margin-top: 36px;
      padding-top: 28px;
      border-top: 1px solid var(--border);
    }

    .meta-item {
      text-align: center;
    }

    .meta-label {
      font-size: 10px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 4px;
    }

    .meta-value {
      font-family: 'Syne', monospace;
      font-size: 12.5px;
      color: rgba(255,255,255,0.45);
    }

    /* ── Animations ── */
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.3; }
    }

    @keyframes ring-expand {
      0% { opacity: 0.6; transform: scale(0.9); }
      100% { opacity: 0; transform: scale(1.6); }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sidebar { display: none; }
      .topbar { left: 0; }
      .main { margin-top: 56px; padding: 32px 16px; }
      .error-card { padding: 40px 28px; margin-top: 0; }
      .error-code { font-size: 72px; }
    }
  </style>
</head>
<body>

<div class="wrapper">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM9 19v-5h6v5H9Zm12 0h-4v-6H7v6H3V9h18v10Z"/>
          <path d="M15 4H9L7.5 7h9L15 4Z"/>
        </svg>
      </div>
      <div class="brand-name">
        InvControl
        <span>Sistema de Inventario</span>
      </div>
    </div>

    <div class="nav-section">
      <p class="nav-label">Navegación</p>
      <div class="nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
        Dashboard
      </div>
    </div>

    <div class="sidebar-footer">
      <div class="user-row">
        <div class="avatar"><?= strtoupper(substr($userInfo['nombre'] ?? 'U', 0, 2)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($userInfo['nombre'] ?? 'Usuario') ?></div>
          <div class="user-role"><?= htmlspecialchars($userInfo['rol'] ?? 'vendedor') ?></div>
        </div>
        <div class="status-dot"></div>
      </div>
    </div>
  </aside>

  <!-- ── Top bar ── -->
  <div class="topbar">
    <span class="crumb">Inicio</span>
    <span class="crumb crumb-sep">/</span>
    <span class="crumb active">Página no encontrada</span>
  </div>

  <!-- ── Main ── -->
  <main class="main">
    <div class="error-card">

      <!-- Compass/Map icon -->
      <div class="compass-wrap">
        <div class="compass-ring"></div>
        <div class="compass-ring"></div>
        <div class="compass-ring"></div>
        <div class="compass-circle">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"/>
          </svg>
        </div>
      </div>

      <!-- Error code -->
      <div class="error-code">404</div>

      <h1 class="error-title">Página no encontrada</h1>
      <p class="error-subtitle">
        La página que estás buscando no existe o ha sido movida.
        Verifica la URL o regresa al inicio del sistema.
      </p>

      <!-- Alert strip -->
      <div class="alert-strip">
        <svg class="alert-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"/>
        </svg>
        <p class="alert-text">
          <strong>Recurso no disponible.</strong> La URL solicitada <strong><span class="url-badge"><?= htmlspecialchars($requestedUrl) ?></span></strong> no coincide con ninguna ruta válida del sistema.
        </p>
      </div>

      <!-- Actions -->
      <div class="actions">
        <a href="../../dashboard.php" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
          Ir al Dashboard
        </a>
        <button class="btn btn-ghost" onclick="history.back()">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
          Volver atrás
        </button>
      </div>

      <!-- Metadata row -->
      <div class="meta-row">
        <div class="meta-item">
          <p class="meta-label">Código de error</p>
          <p class="meta-value">HTTP 404</p>
        </div>
        <div class="meta-item">
          <p class="meta-label">Fecha y hora</p>
          <p class="meta-value" id="ts">—</p>
        </div>
        <div class="meta-item">
          <p class="meta-label">Usuario</p>
          <p class="meta-value"><?= htmlspecialchars($userInfo['nombre'] ?? 'Desconocido') ?></p>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  const el = document.getElementById('ts');
  function tick() {
    const now = new Date();
    el.textContent = now.toLocaleString('es-PE', {
      day:'2-digit', month:'2-digit', year:'numeric',
      hour:'2-digit', minute:'2-digit', second:'2-digit'
    });
  }
  tick();
  setInterval(tick, 1000);
</script>

</body>
</html>