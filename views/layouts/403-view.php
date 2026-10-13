<?php
// Incluir configuración de permisos
//require_once __DIR__ . '/../../config/permissions.php';
require_once __DIR__ . '/../../config/SessionMiddleware.php';

SessionMiddleware::checkAuth();
// ── Información del usuario autenticado ──────────────────────────────────────
$userInfo = SessionMiddleware::getUserInfo();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>403 — Acceso Denegado | Sistema de Inventario</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #080E1A;
      --surface:   #0F1826;
      --card:      #141F30;
      --border:    rgba(255,255,255,0.07);
      --red:       #E8394A;
      --red-dim:   #8B1A24;
      --red-glow:  rgba(232,57,74,0.18);
      --amber:     #F5A623;
      --text:      #E8EDF5;
      --muted:     #6B7A92;
      --stripe:    rgba(232,57,74,0.04);
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
      background: var(--red);
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

    .nav-item.locked {
      color: var(--red);
      background: rgba(232,57,74,0.08);
      border: 1px solid rgba(232,57,74,0.2);
      cursor: not-allowed;
    }

    .nav-item.locked svg { opacity: 1; }

    .lock-badge {
      margin-left: auto;
      font-size: 10px;
      background: var(--red);
      color: #fff;
      padding: 2px 7px;
      border-radius: 4px;
      font-weight: 600;
      letter-spacing: 0.05em;
    }

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
      background: var(--red);
      box-shadow: 0 0 0 2px var(--red-glow);
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

    .crumb.active { color: var(--red); }

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
      background: linear-gradient(90deg, transparent, var(--red), transparent);
    }

    /* ── Lock Icon ── */
    .lock-wrap {
      width: 96px;
      height: 96px;
      margin: 0 auto 32px;
      position: relative;
    }

    .lock-ring {
      position: absolute;
      inset: -12px;
      border-radius: 50%;
      border: 1px solid rgba(232,57,74,0.25);
      animation: ring-expand 2.4s ease-out infinite;
    }

    .lock-ring:nth-child(2) { animation-delay: 0.8s; }
    .lock-ring:nth-child(3) { animation-delay: 1.6s; }

    .lock-circle {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: rgba(232,57,74,0.1);
      border: 1.5px solid rgba(232,57,74,0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      z-index: 1;
    }

    .lock-circle svg {
      width: 40px;
      height: 40px;
      fill: var(--red);
    }

    /* ── Code ── */
    .error-code {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 96px;
      line-height: 1;
      letter-spacing: -0.04em;
      color: transparent;
      -webkit-text-stroke: 1px rgba(232,57,74,0.4);
      margin-bottom: 4px;
      position: relative;
      user-select: none;
    }

    .error-code::after {
      content: '403';
      position: absolute;
      inset: 0;
      color: var(--red);
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
      background: rgba(232,57,74,0.08);
      border: 1px solid rgba(232,57,74,0.22);
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
      color: var(--red);
      margin-top: 1px;
    }

    .alert-text {
      font-size: 13px;
      color: rgba(232,57,74,0.85);
      line-height: 1.55;
    }

    .alert-text strong { color: var(--red); font-weight: 600; }

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
      background: var(--red);
      color: #fff;
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
        <!-- Box/Inventory icon -->
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

    <!-- Nav items (disabled context) -->
    <div class="nav-section">
      <p class="nav-label">General</p>

      <div class="nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
        Dashboard
      </div>
      <div class="nav-item">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Zm-7 14-5-5 1.41-1.41L12 14.17l7.59-7.59L21 8l-9 9Z"/></svg>
        Inventario
      </div>
    </div>

    <div class="nav-section">
      <p class="nav-label">Restringido</p>

      <div class="nav-item locked">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2Zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2Zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2Z"/></svg>
        Administración
        <span class="lock-badge">403</span>
      </div>

      <div class="nav-item locked">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2Zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2Zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2Z"/></svg>
        Reportes avanzados
        <span class="lock-badge">403</span>
      </div>

      <div class="nav-item locked">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2Zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2Zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2Z"/></svg>
        Configuración
        <span class="lock-badge">403</span>
      </div>
    </div>

    <div class="sidebar-footer">
      <div class="user-row">
        <div class="avatar">JM</div>
        <div class="user-info">
          <div class="user-name"><?= $userInfo['nombre'] ?></div>
          <div class="user-role"><?= $userInfo['rol'] ?></div>
        </div>
        <div class="status-dot"></div>
      </div>
    </div>
  </aside>

  <!-- ── Top bar ── -->
  <div class="topbar">
    <span class="crumb">Inicio</span>
    <span class="crumb crumb-sep">/</span>
    <span class="crumb active">Acceso denegado</span>
  </div>

  <!-- ── Main ── -->
  <main class="main">
    <div class="error-card">

      <!-- Pulsing lock icon -->
      <div class="lock-wrap">
        <div class="lock-ring"></div>
        <div class="lock-ring"></div>
        <div class="lock-ring"></div>
        <div class="lock-circle">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2Zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2Zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2Z"/>
          </svg>
        </div>
      </div>

      <!-- Error code -->
      <div class="error-code">403</div>

      <h1 class="error-title">Acceso Denegado</h1>
      <p class="error-subtitle">
        No tienes los permisos necesarios para acceder a este módulo.
        Contacta al administrador del sistema para solicitar acceso.
      </p>

      <!-- Alert strip -->
      <div class="alert-strip">
        <svg class="alert-icon" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"/>
        </svg>
        <p class="alert-text">
          <strong>Acceso bloqueado.</strong> Este intento ha sido registrado. Si crees que esto es un error, comunícate con el área de <strong>Sistemas</strong> o tu supervisor directo.
        </p>
      </div>

      <!-- Actions -->
      <div class="actions">
                                       
        <a href="../../logout.php" class="btn btn-primary">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
          Ir al inicio
        </a>
        <button class="btn btn-ghost" onclick="history.back()">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
          Volver atrás
        </button>
        <a href="mailto:garrawil@gmail.com" class="btn btn-ghost">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>
          Solicitar acceso
        </a>
      </div>

      <!-- Metadata row -->
      <div class="meta-row">
        <div class="meta-item">
          <p class="meta-label">Código de error</p>
          <p class="meta-value">HTTP 403</p>
        </div>
        <div class="meta-item">
          <p class="meta-label">Fecha y hora</p>
          <p class="meta-value" id="ts">—</p>
        </div>
        <div class="meta-item">
          <p class="meta-label">Usuario</p>
          <p class="meta-value"><?= $userInfo['nombre'] ?></p>
        </div>
        <div class="meta-item">
          <p class="meta-label">Módulo</p>
          <p class="meta-value">ADM-CTRL</p>
        </div>
      </div>

    </div><!-- /error-card -->
  </main>

</div><!-- /wrapper -->

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