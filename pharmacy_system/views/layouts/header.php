<?php
$u = currentUser();
$currentPage = $_GET['page'] ?? 'dashboard';

// Helper: highlight active link
$isActive = function($page) use ($currentPage) {
    if (is_array($page)) return in_array($currentPage, $page) ? 'active' : '';
    return $currentPage === $page ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<!-- Mobile-only top bar -->
<nav class="mobile-topbar d-md-none">
    <button class="btn btn-link text-white p-2" id="sidebarToggle" type="button">
        <i class="bi bi-list fs-3"></i>
    </button>
    <span class="brand-title">
        <i class="bi bi-prescription2"></i> <?= APP_NAME ?>
    </span>
    <div class="dropdown">
        <button class="btn btn-link text-white p-2" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle fs-4"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= url('index.php?page=logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <a href="<?= url('index.php?page=dashboard') ?>" class="text-white text-decoration-none d-flex align-items-center">
                <i class="bi bi-prescription2 fs-3 me-2"></i>
                <span class="fw-bold"><?= APP_NAME ?></span>
            </a>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
            <div class="user-info">
                <div class="user-name"><?= sanitize($u['full_name']) ?></div>
                <div class="user-role">
                    <span class="badge bg-info"><?= sanitize(ucfirst($u['role'])) ?></span>
                </div>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li class="nav-section-title">Main</li>
            <li><a class="sidebar-link <?= $isActive('dashboard') ?>" href="<?= url('index.php?page=dashboard') ?>"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
            <li><a class="sidebar-link <?= $isActive('pos') ?>" href="<?= url('index.php?page=pos') ?>"><i class="bi bi-cart-plus"></i> <span>POS</span></a></li>
            <li><a class="sidebar-link <?= $isActive('sales') ?>" href="<?= url('index.php?page=sales') ?>"><i class="bi bi-receipt"></i> <span>Sales</span></a></li>

            <li class="nav-section-title">Inventory</li>
            <li><a class="sidebar-link <?= $isActive('medicines') ?>" href="<?= url('index.php?page=medicines') ?>"><i class="bi bi-capsule"></i> <span>Medicines</span></a></li>
            <li><a class="sidebar-link <?= $isActive('inventory') ?>" href="<?= url('index.php?page=inventory') ?>"><i class="bi bi-box-seam"></i> <span>Stock</span></a></li>
            <?php if (hasRole(['admin','pharmacist'])): ?>
            <li><a class="sidebar-link <?= $isActive('purchases') ?>" href="<?= url('index.php?page=purchases') ?>"><i class="bi bi-truck"></i> <span>Purchases</span></a></li>
            <li><a class="sidebar-link <?= $isActive('suppliers') ?>" href="<?= url('index.php?page=suppliers') ?>"><i class="bi bi-building"></i> <span>Suppliers</span></a></li>
            <?php endif; ?>

            <li class="nav-section-title">Insights</li>
            <li><a class="sidebar-link <?= $isActive('reports') ?>" href="<?= url('index.php?page=reports') ?>"><i class="bi bi-bar-chart"></i> <span>Reports</span></a></li>

            <?php if (hasRole(['admin'])): ?>
            <li class="nav-section-title">Administration</li>
            <li><a class="sidebar-link <?= $isActive('users') ?>" href="<?= url('index.php?page=users') ?>"><i class="bi bi-people"></i> <span>Users</span></a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer">
            <a href="<?= url('index.php?page=logout') ?>" class="logout-link">
                <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main content area -->
    <div class="app-content">
        <main class="container-fluid py-4 px-md-4">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= sanitize($msg) ?>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> <?= sanitize($msg) ?>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
