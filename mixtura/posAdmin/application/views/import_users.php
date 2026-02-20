<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>NVM CRM</title>
	<meta name="description" content="">
	<meta name="keywords" content="">
	<meta name="author" content="">
<?php 
foreach($css_files as $file): ?>
	<link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
<?php endforeach; ?>
	<link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
	<link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
	<style> 
select {
  width: 100%;
  padding: 16px 20px;
  border: none;
  border-radius: 4px;
  background-color: #f1f1f1;
}
#importFrm {
	margin: 15px;
	border: 1px solid #0053a0;
	border-radius: 15px;
}
#importFrm p{
	margin: 5px;
}
.btn-submit {
	width: 100%;
	background-color: #e9441e;
}
th, td {
  padding: 5px;
}
</style>
</head>
<body>
	<header id="site-header">
		<nav class="navbar navbar-inverse" role="navigation">
			<div class="container-fluid otika-header">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="<?= site_url('mainpanel') ?>">
						<img src="https://www.mundialbolivia.com/mundialAdmin/assets/media/logo.png" height="64">
					</a>
				</div>
				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				    <ul class="nav navbar-nav">
						<li><a href='<?php echo site_url('mainpanel/userko_management')?>'>Usuarios K.O Al Dolor</a></li>
						<li><a href='<?php echo site_url('mainpanel/usercofar_management')?>'>Usuarios Cofar</a></li>
						<li><a href='<?php echo site_url('mainpanel/ranking_ko')?>'>Ranking K.O Al Dolor</a></li>
						<li><a href='<?php echo site_url('mainpanel/ranking_cofar')?>'>Ranking Cofar</a></li>
						<?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) : ?>
						<li><a href='<?php echo site_url('mainpanel/matches_management')?>'>Partidos</a></li>
						<li><a href='<?php echo site_url('mainpanel/teams_management')?>'>Equipos</a></li>
						<?php endif; ?>
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
<div class="container">	
    <div class="row">
    	<table border="1">
    		<tr>
		    <th>Nro</th>
		    <th>Nombre</th>
		    <th>Puntos</th>
		  </tr>
		<?php $i = 0; foreach ($ranking as $key => $value): $i++; ?> 
		  <tr>
		    <td><?= $i ?></td>
		    <td><?= $value->name ?></td>
		    <td><?= $value->points ?></td>
		  </tr>
		<?php endforeach; ?>
    	</table>          
    </div>
</div>

<script>
function formToggle(ID){
    var element = document.getElementById(ID);
    if(element.style.display === "none"){
        element.style.display = "block";
    }else{
        element.style.display = "none";
    }
}
</script>
</body>
</html>
