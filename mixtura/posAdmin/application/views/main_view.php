<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>ADMINISTRADOR MIXTURA</title>
	<meta name="description" content="">
	<meta name="keywords" content="">
	<meta name="author" content="">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
	<script src="<?= base_url('assets/js/bootstrap.min.js') ?>"></script>
	<script src="<?= base_url('assets/js/script.js') ?>"></script>
<?php 
foreach($css_files as $file): ?>
	<link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
	<?php endforeach; ?>
	<link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
	<link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
	<header id="site-header">
		<nav class="navbar navbar-inverse" role="navigation">
			<div class="container-fluid nvm-header">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="<?= site_url('mainpanel') ?>">
						<img src="<?= base_url() ?>/assets/media/logo.png" height="64">
					</a>
				</div>
				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				    <ul class="nav navbar-nav">
						<li><a href='<?php echo site_url('mainpanel/users')?>'>Usuarios</a></li>
						<li><a href='<?php echo site_url('mainpanel/categories')?>'>Categorias</a></li>
						<li><a href='<?php echo site_url('mainpanel/products')?>'>Productos</a></li>
						<li><a href='<?php echo site_url('mainpanel/brief')?>'>Brief</a></li>
						<li><a href='<?php echo site_url('mainpanel/invoice')?>'>Cajas</a></li>
						<li><a href='<?php echo site_url('mainpanel/zones')?>'>Areas</a></li>
						<li><a href='<?php echo site_url('mainpanel/tables')?>'>Mesas</a></li>
						<li><a href='<?php echo site_url('mainpanel/sells')?>'>Consolidado de Ventas</a></li>
						<li><a href='http://192.168.88.204/mecapos/consolidado.php'>Ventas de ayer</a></li>
						<li><a href='<?php echo site_url('mainpanel/sells_by_product')?>'>Ventas por Producto</a></li>
						<li><a href='http://192.168.88.204/mecapos/ventas_productos.php'>Ventas por Mesero</a></li>
				    </ul>
					<ul class="nav navbar-nav navbar-right">
						<?php if (isset($_SESSION['username']) && $_SESSION['logged_in'] === true) : ?>
							<li><a href="<?= base_url('index.php/welcome/logout') ?>">Desconectarse</a></li>
						<?php else : ?>
							<li><a href="<?= base_url('index.php/welcome/login') ?>">Conectarse</a></li>
						<?php endif; ?>
					</ul>
				</div><!-- .navbar-collapse -->
			</div><!-- .container-fluid -->
		</nav><!-- .navbar -->
	</header><!-- #site-header -->
    <div style="padding: 10px">
		<?php echo $output; ?>
    </div>
    <?php foreach($js_files as $file): ?>
        <script src="<?php echo $file; ?>"></script>
    <?php endforeach; ?><!-- js -->
        <script type="text/javascript">
        		$('.navbar .dropdown').hover(function() {
		  $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
		}, function() {
		  $(this).find('.dropdown-menu').first().stop(true, true).slideUp(105)
		});
        </script>
</body>
</html>
