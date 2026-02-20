<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Mainpanel extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->database();
		$this->load->library(array('session'));
		$this->load->helper('url');
		$this->load->model('user_model');
		$this->load->model('total_cash');
		//$this->load->model('users_join');
		$this->load->model('member');
        // Load form validation library
        $this->load->library('form_validation');
        
        // Load file helper
        $this->load->helper('file');

		$this->load->library('grocery_CRUD');
		if (isset($_SESSION['username']) && $_SESSION['logged_in'] === true)
		{

		}
		else
		{
			redirect('Welcome/login');
		}
	}

	public function _example_output($output = null)
	{
		$this->load->view('main_view.php',(array)$output);
	}

	public function offices()
	{
		$output = $this->grocery_crud->render();

		$this->_example_output($output);
	}

	public function index()
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		$this->sells();
	}
	public function close_cash($id)
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		$total = $this->total_cash->close_cash($id);
		redirect('mainpanel/invoice');
	}
	public function _callback_image_url($value, $row)
	{
		return $value = '<img src="'.  $value.'" height="50px">';
	}
	public function _callback_file_url($value, $row)
	{
		return $value = '<a href="'.$value.'">'.$value.'</a>';
	}
	function callback_date_update($post_array, $primary_key)
	{
	  $post_array['updated_at'] = date('Y-m-d H:i:s');
	  return $post_array;
	}
	function callback_date_create($post_array, $primary_key)
	{
	  $post_array['updated_at'] = date('Y-m-d H:i:s');
	  $post_array['created_at'] = date('Y-m-d H:i:s');
	  return $post_array;
	}
	//MIXTURA-------------------
	public function invoice()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('user_cash');
			$crud->set_subject('Caja');
			$crud->set_relation('id_user', 'user', 'name');
			$crud->columns('id_user', 'floor', 'state', 'open_date', 'close_date', 'total', 'cash', 'qr', 'card', 'debt');
			$crud->display_as('id_user', 'Mesero');
			$crud->display_as('user_type', 'Zona');
			$crud->display_as('state', 'Caja');
			$crud->display_as('open_date', 'Apertura de caja');
			$crud->display_as('close_date', 'Cierre de caja');
			$crud->display_as('total', 'Total');
			$crud->display_as('cash', 'Efectivo');
			$crud->display_as('qr', 'QR');
			$crud->display_as('card', 'Tarjeta');
			$crud->display_as('debt', 'Deuda');
			$crud->callback_column('cash',array($this,'_callback_total_cash'));
			$crud->callback_column('qr',array($this,'_callback_total_qr'));
			$crud->callback_column('card',array($this,'_callback_total_card'));
			$crud->callback_column('debt',array($this,'_callback_total_debt'));
			$crud->callback_column('total',array($this,'_callback_total_total'));
			$crud->required_fields('id');
    		$crud->add_action('Ver detalle', '', '','ui-icon-grip-dotted-horizontal',array($this,'see_cash_detail'));
    		$crud->add_action('Cerrar caja', '', '','ui-icon-grip-dotted-horizontal',array($this,'_close_cash'));
            $crud->unset_add();
            $crud->unset_edit();
			$crud->order_by('open_date','desc');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function invoice_detail($user_cash)
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('invoice');
			$crud->set_subject('Ticket');
			$crud->columns('mesa', 'total', 'cash', 'qr', 'card', 'debt','created_at');
			$crud->display_as('total', 'Total');
			$crud->display_as('cash', 'Efectivo');
			$crud->display_as('qr', 'QR');
			$crud->display_as('card', 'Tarjeta');
			$crud->display_as('debt', 'Deuda');
			$crud->display_as('created_at', 'Fecha creación');
			$crud->required_fields('id');
			$crud->callback_column('mesa',array($this,'_callback_table_name_invoice'));
    		$crud->add_action('Ver más detalle', '', '','ui-icon-grip-dotted-horizontal',array($this,'see_cash_subdetail'));
            $crud->unset_add();
            $crud->unset_edit();
            $crud->unset_delete();
			$crud->where('invoice.id_user_cash', $user_cash);
			$crud->order_by('created_at','desc');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function invoice_subdetail($invoice)
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('invoice_detail');
			$crud->set_subject('Ticket');
			$crud->columns('id_product_ticket', 'quantity', 'subtotal', 'created_at');
			$crud->display_as('id_product_ticket', 'Producto');
			$crud->display_as('subtotal', 'Total');
			$crud->display_as('quantity', 'Cantidad');
			$crud->display_as('created_at', 'Fecha creación');
			$crud->callback_column('id_product_ticket',array($this,'_callback_product_name'));
			$crud->required_fields('id');
            $crud->unset_add();
            $crud->unset_edit();
            $crud->unset_delete();
			$crud->where('invoice_detail.id_invoice', $invoice);
			$crud->order_by('created_at','desc');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function sells()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('consolidado');
			$crud->set_subject('Consolidado');	
			$crud->columns('mesero', 'cobrador', 'mesa', 'categoria', 'cantidad', 'producto', 'orden', 'acomp', 'precio', 'notas', 'acomp', 'estado', 'creado', 'enviado', 'cocinado', 'entregado');
			$crud->display_as('acomp', 'Acompañamiento');
            $crud->unset_add();
            $crud->unset_edit();
            $crud->unset_delete();
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function today_sells()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('consolidado');
			$crud->set_subject('Consolidado');	
			$crud->columns('mesero', 'cobrador', 'mesa', 'categoria', 'cantidad', 'producto', 'orden', 'acomp', 'precio', 'notas', 'acomp', 'estado', 'creado', 'enviado', 'cocinado', 'entregado', 'tiempo');
			$crud->display_as('acomp', 'Acompañamiento');
			$crud->display_as('tiempo', 'Tiempo Entrega');
			$crud->callback_column('tiempo',array($this,'_callback_tiempo_preparacion'));
            $crud->unset_add();
            $crud->unset_edit();
            $crud->unset_delete();
			$crud->where('consolidado.creado >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
			
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function _callback_tiempo_preparacion($value, $row)
	{
		if($row->enviado != '') {
			$enviado = DateTime::createFromFormat('d/m/Y - H:i', $row->enviado); 
			$entregado = DateTime::createFromFormat('d/m/Y - H:i', $row->entregado); 
			$diff = $enviado->diff($entregado);
			$minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
			if($minutes < 10 && $minutes > 0) {
				$minutes = '0'.$minutes;
			}
			return $minutes." minutos";
		} else {
			return 'No entregado';
		}
	}
	public function sells_by_product()
	{
		try{
			$crud = new grocery_CRUD();
	    	$crud->set_model('Users_join');
			$crud->set_theme('bootstrap');
			$crud->set_table('product');
			$crud->set_subject('Ventas');	
			$crud->set_relation('id_category', 'category', 'name');
			$crud->columns('orden', 'id_category', 'name', 'cantidad');
			$crud->display_as('id_category', 'Categoria');
			$crud->display_as('name', 'Nombre');
            $crud->unset_add();
            $crud->unset_edit();
            $crud->unset_delete();
			//$crud->where('consolidado.creado >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
			
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function sells_by_productt()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('ventas_producto');
			$crud->set_subject('Ventas');	
			$crud->set_relation('id_categoria', 'category', 'name');
			$crud->set_relation('id_mesero', 'user', 'name');
			$crud->set_relation('id_producto', 'product', 'name');
			$crud->columns('id_mesero','orden', 'id_categoria', 'id_producto', 'cantidad', 'acomp');
			$crud->display_as('id_categoria', 'Categoria');
			$crud->display_as('id_mesero', 'Mesero');
			$crud->display_as('id_producto', 'Producto');
			$crud->display_as('acomp', 'Acompañamientos');
            $crud->unset_add();
            $crud->unset_edit();
            $crud->unset_delete();			
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function users()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('user');
			$crud->set_subject('Usuario');
			$crud->columns('name', 'role', 'change_roles', 'code', 'state', 'last_loggin', 'created_at');
			$crud->display_as('name', 'Nombre');
			$crud->display_as('role', 'Rol');
			$crud->display_as('change_roles', 'Cambia roles');
			$crud->display_as('code', 'Código');
			$crud->display_as('state', 'Estado');
			$crud->display_as('last_loggin', 'Último acceso');
			$crud->display_as('created_at', 'Fecha creación');
			$crud->required_fields('id');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function brief()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('brief');
			$crud->set_subject('Brief');
			$crud->columns('message', 'created_at');
			$crud->display_as('message', 'Mensaje');
			$crud->display_as('created_at', 'Creado');
			$crud->required_fields('id');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function categories()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('category');
			$crud->set_subject('Categoria');
			$crud->columns('name', 'active', 'created_at');
			$crud->display_as('name', 'Nombre');
			$crud->display_as('active', 'Estado');
			$crud->required_fields('id');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function zones()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('zone');
			$crud->set_subject('Areas');
			$crud->columns('name', 'active', 'created_at');
			$crud->display_as('name', 'Nombre');
			$crud->display_as('active', 'Estado');
			$crud->display_as('created_at', 'Fecha creación');
			$crud->required_fields('id');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function tables()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('tables');
			$crud->set_subject('Mesas');
			$crud->set_relation('id_zone', 'zone', 'name');
			$crud->set_relation('id_user', 'user', 'name');
			$crud->columns('name', 'id_zone', 'id_user', 'state', 'active', 'closed');
			$crud->display_as('name', 'Nombre');
			$crud->display_as('id_zone', 'Area');
			$crud->display_as('id_user', 'Mesero');
			$crud->display_as('state', 'Estado');
			$crud->display_as('closed', 'Cerrada');
			$crud->display_as('active', 'Disponible');
			$crud->required_fields('id');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function products()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('product');
			$crud->set_subject('Producto');
			$crud->set_relation('id_category', 'category', 'name');
			$crud->set_relation('id_kitchen', 'kitchen', 'name');
			$crud->columns('id_category', 'id_kitchen', 'name', 'alias', 'price', 'accompaniment','kitchen_time', 'active', 'created_at');
			$crud->display_as('id_category', 'Categoria');
			$crud->display_as('id_kitchen', 'Cocina');
			$crud->display_as('name', 'Nombre');
			$crud->display_as('alias', 'Alias');
			$crud->display_as('price', 'Precio');
			$crud->display_as('accompaniment', 'Acompañamiento');
			$crud->display_as('kitchen_time', 'Tiempo de cocina');
			$crud->display_as('active', 'Estado');
			$crud->display_as('created_at', 'Fecha creación');
			$crud->required_fields('id');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function _callback_product_name($value, $row)
	{
	  $name = $this->total_cash->get_productname_from_invoice($row->id_product_ticket);
	  return $name;
	}
	public function _callback_product_category($value, $row)
	{
	  $name = $this->total_cash->get_productcategory_from_invoice($row->id_product_ticket);
	  return $name;
	}
	public function _callback_table_name($value, $row)
	{
	  $name = $this->total_cash->get_tablename_from_invoice($row->id_invoice);
	  return $name;
	}
	public function _callback_table_name_invoice($value, $row)
	{
	  $name = $this->total_cash->get_tablename_from_invoice($row->id);
	  return $name;
	}
	public function _callback_user_name($value, $row)
	{
	  $name = $this->total_cash->get_username_from_invoice($row->id_invoice);
	  return $name;
	}
	public function _callback_total_cash($value, $row)
	{
	  $total = $this->total_cash->total_cash_type($row->id, 'cash');
	  return $total;
	}
	public function _callback_total_card($value, $row)
	{
	  $total = $this->total_cash->total_cash_type($row->id, 'card');
	  return $total;
	}
	public function _callback_total_qr($value, $row)
	{
	  $total = $this->total_cash->total_cash_type($row->id, 'qr');
	  return $total;
	}
	public function _callback_total_debt($value, $row)
	{
	  $total = $this->total_cash->total_cash_type($row->id, 'debt');
	  return $total;
	}
	public function _callback_total_total($value, $row)
	{
	  $total = $this->total_cash->total_cash_type($row->id, 'total');
	  return $total;
	}	
	function _close_cash($primary_key , $row)
	{
	  return site_url('mainpanel/close_cash/'.$row->id);
	}	
	function see_cash_detail($primary_key , $row)
	{
	    return site_url('mainpanel/invoice_detail/'.$row->id);
	}
	function see_cash_subdetail($primary_key , $row)
	{
	    return site_url('mainpanel/invoice_subdetail/'.$row->id);
	}
	
	function callback_date_update2($post_array, $primary_key)
	{
	  $post_array['updated_at'] = date('Y-m-d H:i:s');
	  return $post_array;
	}
	function callback_date_create2($post_array, $primary_key)
	{
	  $post_array['updated_at'] = date('Y-m-d H:i:s');
	  $post_array['created_at'] = date('Y-m-d H:i:s');
	  return $post_array;
	}

}
