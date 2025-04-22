<!-- application/views/templates/header.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Monitor de Dólares' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/styles.css') ?>">
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url() ?>">
                <i class="bi bi-currency-dollar"></i> Monitor de Dólares
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $this->router->fetch_class() == 'dashboard' ? 'active' : '' ?>" href="<?= base_url() ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $this->router->fetch_class() == 'configuracion' ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Configuración
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?= site_url('configuracion') ?>">
                                    <i class="bi bi-sliders"></i> General
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= site_url('configuracion/dolares') ?>">
                                    <i class="bi bi-currency-exchange"></i> Dólares
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $this->router->fetch_class() == 'test' ? 'active' : '' ?>" href="<?= site_url('test') ?>">
                            <i class="bi bi-wrench-adjustable"></i> Pruebas
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="<?= site_url('consultar-ahora') ?>" class="btn btn-success">
                        <i class="bi bi-arrow-repeat"></i> Consultar Ahora
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($this->session->flashdata('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('warning') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>