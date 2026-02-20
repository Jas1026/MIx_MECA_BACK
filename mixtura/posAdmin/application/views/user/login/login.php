<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container">
	<div class="row">
		<?php if (validation_errors()) : ?>
			<div class="col-md-12">
				<div class="alert alert-danger" role="alert">
					<?= validation_errors() ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if (isset($error)) : ?>
			<div class="col-md-12">
				<div class="alert alert-danger" role="alert">
					<?= $error ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="col-md-12">
			<div class="page-header">
				<h1>Ingresar</h1>
			</div>
			<?= form_open() ?>
				<div class="form-group">
					<label for="username">usuario</label>
					<input type="text" class="form-control" id="username" name="username" placeholder="usuario">
				</div>
				<div class="form-group">
					<label for="password">contraseña</label>
					<input type="password" class="form-control" id="password" name="password" placeholder="contraseña">
				</div>
				<div class="form-group">
					<input type="submit" class="btn btn-default" value="Ingresar">
				</div>
			</form>
		</div>
	</div><!-- .row -->
</div><!-- .container -->