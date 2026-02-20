<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Mainpanel_en extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->database();
		$this->load->library(array('session'));
		$this->load->helper('url');
		$this->load->model('user_model');
		$this->load->model('send_email');
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
		$this->load->view('main_view_en.php',(array)$output);
	}

	public function offices()
	{
		$output = $this->grocery_crud->render();

		$this->_example_output($output);
	}

	public function index()
	{
		$this->embarques();
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

	public function _callback_translate($value, $row)
	{
		switch ($value) {
			case 'Enero':
				return 'January';
				break;
			case 'Febrero':
				return 'February';
				break;
			case 'Marzo':
				return 'March';
				break;
			case 'Abril':
				return 'April';
				break;
			case 'Mayo':
				return 'May';
				break;
			case 'Junio':
				return 'June';
				break;
			case 'Julio':
				return 'July';
				break;
			case 'Agosto':
				return 'August';
				break;
			case 'Septiembre':
				return 'September';
				break;
			case 'Octubre':
				return 'January';
				break;
			case 'Noviembre':
				return 'November';
				break;
			case 'Diciembre':
				return 'December';
				break;
			case 'Embarque finalizado':
				return 'Shipment completed';
				break;
			case 'Zarpó la nave':
				return 'The ship sailed';
				break;
			case 'Marejada':
				return 'Surge';
				break;
			case 'Alta':
				return 'High';
				break;
			case 'Mediana':
				return 'Medium';
				break;
			case 'Baja':
				return 'Low';
				break;
			case 'Aprobada':
				return 'Approved';
				break;
			case 'Rechazada':
				return 'Rejected';
				break;
			case 'En revision':
				return 'In Review';
				break;
			case 'No aplica':
				return 'Does not apply';
				break;
			case 'Solicitud de iniciación de documentos':
				return 'Document initiation request';
				break;
			case 'Solicitud de reporte LABCAR':
				return 'LABCAR report request';
				break;
			case 'Actualización de permisos de exportación en instituciones publicas':
				return 'Update of export permits in public institutions';
				break;
			case 'Papeles enviados a La Paz':
				return 'Papers sent to La Paz';
				break;
			case 'Retraso de producto terminado':
				return 'Finished product delay';
				break;
			case 'Papeles terminados':
				return 'Finished papers';
				break;
			case 'Camino a puerto':
				return 'It\'s on the way to port';
				break;
			case 'Paso Frontera':
				return 'Crossed the Border';
				break;
			case 'Bloqueos socio-político en carreteras':
				return 'Socio-political roadblocks';
				break;
			case 'Detenido por inconveniente técnico':
				return 'Stopped due to technical problem';
				break;
			default: 
			return $value;
		}
	}


	//NVM-------------------
		
	public function embarques()
	{
		try{
			$crud = new grocery_CRUD();
			$crud->set_language("english");
			$crud->set_lang_string('ZARPO LA NAVE','Sailed the ship');
			$crud->set_theme('bootstrap');
			$crud->set_table('embarque');
			$crud->set_subject('Shipment');
			$crud->set_relation('id_embarcador', 'embarcador', 'name');
			$crud->set_relation('id_broker', 'broker', 'name');
			$crud->set_relation('id_cliente', 'cliente', 'name');
			$crud->set_relation('id_linea_naviera', 'linea_naviera', 'name');
			$crud->set_relation('id_destino', 'destino', 'name');
			$crud->callback_column('mes', array($this,'_callback_translate'));
			$crud->callback_column('status_rd', array($this,'_callback_translate'));
			$crud->callback_read_field('mes', array($this,'_callback_translate'));
			$crud->callback_read_field('status_rd', array($this,'_callback_translate'));
			$crud->callback_read_field('doc_state', array($this,'_callback_translate'));
			$crud->callback_read_field('estado_frontera', array($this,'_callback_translate'));
			$crud->callback_read_field('estado_etiqueta', array($this,'_callback_translate'));
			$crud->callback_read_field('priority', array($this,'_callback_translate'));
			$crud->columns('id_embarcador', 'id_broker', 'id_cliente', 'mes', 'gestion', 'num_contrato', 'factura', 'lote', 'id_destino', 'contenedor', 'tipo', 'updated_at', 'status_rd');
			$crud->add_fields('mes', 'gestion', 'id_embarcador', 'id_broker', 'id_cliente', 'num_contrato', 'doc_contrato', 'prioridad', 'tipo', 'organico', 'cantidad_tipo', 'observaciones', 'lote', 'factura', 'etiqueta', 'estado_etiqueta', 'solicitud', 'doc_state', 'id_destino', 'fecha_envio', 'numero_reserva', 'id_linea_naviera', 'contenedor', 'tamano_contenedor', 'bill_of_lading', 'estado_frontera', 'fecha_confirmacion', 'status_rd', 'estado');
			$crud->display_as('mes', 'Shipment month');
			$crud->display_as('gestion', 'Year');
			$crud->display_as('id_embarcador', 'Seller');
			$crud->display_as('id_broker', 'Broker');
			$crud->display_as('id_cliente', 'Client / Buyer');
			$crud->display_as('consignatario', 'Buyer');
			$crud->display_as('num_contrato', 'Number of contracts');
			$crud->display_as('doc_contrato', 'Document attached to the contract');
			$crud->display_as('prioridad', 'Priority');
			$crud->display_as('tipo', 'Commodity');
			$crud->display_as('organico', 'Organic ');
			$crud->display_as('cantidad_tipo', 'Commodity Amount');
			$crud->display_as('observaciones', 'Note');
			$crud->display_as('lote', 'Lot');
			$crud->display_as('factura', 'Invoice');
			$crud->display_as('etiqueta', 'Label');
			$crud->display_as('estado_etiqueta', 'Status of the label');
			$crud->display_as('solicitud', 'Container request');
			$crud->display_as('doc_state', 'Status of documentation');
			$crud->display_as('id_destino', 'Destination');
			$crud->display_as('fecha_envio', 'Shipment date to port');
			$crud->display_as('numero_reserva', 'Booking number');
			$crud->display_as('id_linea_naviera', 'Line of shipment');
			$crud->display_as('contenedor', 'ID of container');
			$crud->display_as('tamano_contenedor', 'Size of container');
			$crud->display_as('bill_of_lading', 'Bill of lading');
			$crud->display_as('estado_frontera', 'State on the border');
			$crud->display_as('fecha_confirmacion', 'Date confirmed by shipping company');
			$crud->display_as('status_rd', 'Status of shipment');
			$crud->display_as('updated_at', 'Last Update');
			$crud->display_as('estado', 'State');
			if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {	        	
	            $crud->unset_add();
	            //$crud->unset_edit();
	            $crud->unset_delete();
				$crud->edit_fields('observaciones');
				if ($_SESSION['id_cliente'] != NULL) {				
					$crud->where('embarque.id_cliente', $_SESSION['id_cliente']);
				}
				if ($_SESSION['id_broker'] != NULL) {				
					$crud->where('embarque.id_broker', $_SESSION['id_broker']);
				}
					$crud->where('embarque.estado', 1);
			} else {				
				$crud->edit_fields('mes', 'gestion', 'id_embarcador', 'id_broker', 'id_cliente', 'num_contrato', 'doc_contrato', 'prioridad', 'tipo', 'organico', 'cantidad_tipo', 'observaciones', 'lote', 'factura', 'etiqueta', 'estado_etiqueta', 'solicitud', 'doc_state', 'id_destino', 'fecha_envio', 'numero_reserva', 'id_linea_naviera', 'contenedor', 'tamano_contenedor', 'bill_of_lading', 'estado_frontera', 'fecha_confirmacion', 'status_rd', 'estado');
			}
			$crud->where('embarque.gestion', date("Y"));
			$crud->change_field_type('created_at','hidden');
			$crud->change_field_type('updated_at','hidden');
			$crud->set_field_upload('doc_contrato','assets/files/contratos');
			$crud->set_field_upload('etiqueta','assets/media/etiquetas');
			$crud->callback_before_update(array($this,'send_mail_if_change'));
			$crud->required_fields('id');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	public function historico()
	{		
		try{
			$crud = new grocery_CRUD();
			$crud->set_theme('bootstrap');
			$crud->set_table('embarque');
			$crud->set_subject('embarque');
			$crud->set_relation('id_embarcador', 'embarcador', 'name');
			$crud->set_relation('id_broker', 'broker', 'name');
			$crud->set_relation('id_cliente', 'cliente', 'name');
			$crud->set_relation('id_linea_naviera', 'linea_naviera', 'name');
			$crud->set_relation('id_destino', 'destino', 'name');
			$crud->columns('id_embarcador', 'id_broker', 'id_cliente', 'mes', 'gestion', 'num_contrato', 'factura', 'lote', 'id_destino', 'contenedor', 'tipo', 'updated_at', 'status_rd');
			$crud->add_fields('mes', 'gestion', 'id_embarcador', 'id_broker', 'id_cliente', 'num_contrato', 'doc_contrato', 'prioridad', 'tipo', 'organico', 'cantidad_tipo', 'observaciones', 'lote', 'factura', 'etiqueta', 'estado_etiqueta', 'solicitud', 'doc_state', 'id_destino', 'fecha_envio', 'numero_reserva', 'id_linea_naviera', 'contenedor', 'tamano_contenedor', 'bill_of_lading', 'estado_frontera', 'fecha_confirmacion', 'status_rd', 'estado');
			$crud->display_as('mes', 'Mes de embarque');
			$crud->display_as('gestion', 'Gestión');
			$crud->display_as('id_embarcador', 'ID de embarcador');
			$crud->display_as('id_broker', 'Broker');
			$crud->display_as('id_cliente', 'Cliente / Consignatario');
			$crud->display_as('num_contrato', 'Número de contrato');
			$crud->display_as('doc_contrato', 'Documento de contrato');
			$crud->display_as('prioridad', 'Prioridad');
			$crud->display_as('tipo', 'Grado');
			$crud->display_as('organico', 'Orgánico ');
			$crud->display_as('cantidad_tipo', 'Cantidad Grado');
			$crud->display_as('observaciones', 'Observaciones');
			$crud->display_as('lote', 'Lote');
			$crud->display_as('factura', 'Factura');
			$crud->display_as('etiqueta', 'Etiqueta');
			$crud->display_as('estado_etiqueta', 'Estado de etiqueta');
			$crud->display_as('solicitud', 'Solicitud de contenedor');
			$crud->display_as('doc_state', 'Estado de documentación');
			$crud->display_as('id_destino', 'Destino');
			$crud->display_as('fecha_envio', 'Fecha de envío a puerto');
			$crud->display_as('numero_reserva', 'Número de Reserva');
			$crud->display_as('id_linea_naviera', 'Línea Naviera');
			$crud->display_as('contenedor', 'ID de Contenedor');
			$crud->display_as('tamano_contenedor', 'Tamaño de contenedor');
			$crud->display_as('bill_of_lading', 'Bill of lading');
			$crud->display_as('estado_frontera', 'Estado en Frontera');
			$crud->display_as('fecha_confirmacion', 'Fecha confirmación por naviera');
			$crud->display_as('status_rd', 'Estado de envío');
			$crud->display_as('updated_at', 'Última actualización');
			$crud->display_as('estado', 'Estado');
			$crud->change_field_type('created_at','hidden');
			$crud->change_field_type('updated_at','hidden');
			$crud->field_type('observaciones', 'text');
			$crud->set_field_upload('doc_contrato','assets/files/contratos');
			$crud->set_field_upload('etiqueta','assets/media/etiquetas');
			if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {	        	
	            $crud->unset_add();
	            $crud->unset_edit();
	            $crud->unset_delete();
				$crud->edit_fields('observaciones');
				if ($_SESSION['id_cliente'] != NULL) {				
					$crud->where('embarque.id_cliente', $_SESSION['id_cliente']);
				}
				if ($_SESSION['id_broker'] != NULL) {				
					$crud->where('embarque.id_broker', $_SESSION['id_broker']);
				}
					$crud->where('embarque.estado', 1);
			} else {				
				$crud->edit_fields('mes', 'gestion', 'id_embarcador', 'id_broker', 'id_cliente', 'num_contrato', 'doc_contrato', 'prioridad', 'tipo', 'organico', 'cantidad_tipo', 'observaciones', 'lote', 'factura', 'etiqueta', 'estado_etiqueta', 'solicitud', 'doc_state', 'id_destino', 'fecha_envio', 'numero_reserva', 'id_linea_naviera', 'contenedor', 'tamano_contenedor', 'bill_of_lading', 'estado_frontera', 'fecha_confirmacion', 'status_rd', 'estado');
			}
			$crud->where('embarque.gestion <', date("Y"));
			$crud->callback_before_update(array($this,'send_mail_if_change'));
			$crud->required_fields('id ');		
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	function send_mail_if_change($post_array, $primary_key) {
		
	    $changed = $this->send_email->Check_observations($post_array["observaciones"], $primary_key, $post_array["num_contrato"]);
		//$post_array["observaciones"] = $changed;
		return $post_array;
	}
	public function brokers()
	{
		if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {			
        	redirect('mainpanel/embarques');
		}
		try{
			$crud = new grocery_CRUD();
			$crud->set_language("english"); 
			$crud->set_theme('bootstrap');
			$crud->set_table('broker');
			$crud->set_subject('broker');
			$crud->columns('name', 'country', 'city', 'email', 'phone');
			$crud->display_as('name', 'Broker');
			$crud->display_as('country', 'Country');
			$crud->display_as('city', 'City');
			$crud->display_as('email', 'Email');
			$crud->display_as('phone', 'Phone');
			$crud->display_as('state', 'State');
			$crud->add_fields('name', 'country', 'city', 'email', 'phone', 'state');
			$crud->edit_fields('name', 'country', 'city', 'email', 'phone', 'state');
			$crud->required_fields('id ');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}		
	public function clientes()
	{
		if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {			
        	redirect('mainpanel/embarques');
		}
		try{
			$crud = new grocery_CRUD();
			$crud->set_language("english"); 
			$crud->set_theme('bootstrap');
			$crud->set_table('cliente');
			$crud->set_subject('Client');
			$crud->columns('name', 'country', 'city', 'email', 'phone');
			$crud->display_as('name', 'Client');
			$crud->display_as('country', 'Country');
			$crud->display_as('city', 'City');
			$crud->display_as('email', 'Email');
			$crud->display_as('phone', 'Phone');
			$crud->display_as('state', 'State');
			$crud->display_as('code', 'Code');
			$crud->display_as('password', 'Password');
			$crud->add_fields('name', 'country', 'city', 'email', 'phone', 'state');
			$crud->edit_fields('name', 'country', 'city', 'email', 'phone', 'state');
    		//$crud->add_action('Generate Password', '', '','ui-icon-grip-dotted-horizontal',array($this,'generate_code'));
			$crud->required_fields('id ');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}	
	public function destinos()
	{
		if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {			
        	redirect('mainpanel/embarques');
		}
		try{
			$crud = new grocery_CRUD();
			$crud->set_language("english"); 
			$crud->set_theme('bootstrap');
			$crud->set_table('destino');
			$crud->set_subject('Destination');
			$crud->columns('name');
			$crud->display_as('name', 'Destination');
			$crud->display_as('state', 'State');
			$crud->add_fields('name', 'state');
			$crud->edit_fields('name', 'state');
			$crud->required_fields('id ');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}	
	public function embarcadores()
	{
		if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {			
        	redirect('mainpanel/embarques');
		}
		try{
			$crud = new grocery_CRUD();
			$crud->set_language("english"); 
			$crud->set_theme('bootstrap');
			$crud->set_table('embarcador');
			$crud->set_subject('Shipper');
			$crud->columns('name', 'contact_name', 'contact_phone', 'contact_email');
			$crud->display_as('name', 'Shipper');
			$crud->display_as('state', 'State');
			$crud->display_as('contact_name', 'Contact mame');
			$crud->display_as('contact_phone', 'Contact phone');
			$crud->display_as('contact_email', 'Contact email');
			$crud->add_fields('name', 'state');
			$crud->edit_fields('name', 'state');
			$crud->required_fields('id ');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}	
	public function lineas_navieras()
	{
		if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {			
        	redirect('mainpanel/embarques');
		}
		try{
			$crud = new grocery_CRUD();
			$crud->set_language("english"); 
			$crud->set_theme('bootstrap');
			$crud->set_table('linea_naviera');
			$crud->set_subject('Line of shipment');
			$crud->columns('name', 'contact_name', 'contact_phone', 'contact_email');
			$crud->display_as('name', 'Line of shipment');
			$crud->display_as('state', 'State');
			$crud->display_as('contact_name', 'Contact mame');
			$crud->display_as('contact_phone', 'Contact phone');
			$crud->display_as('contact_email', 'Contact email');
			$crud->edit_fields('name', 'state');
			$crud->add_fields('name', 'state');
			$crud->required_fields('id ');
			$output = $crud->render();

			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	
	public function admin_management()
	{
		if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== true) {			
        	redirect('mainpanel/embarques');
		}
		try{
			$crud = new grocery_CRUD();
			$crud->set_language("english"); 
			$crud->set_theme('bootstrap');
			$crud->set_table('usersadmin');
			$crud->set_subject('User access');
			$crud->set_relation('id_cliente', 'cliente', 'name');
			$crud->set_relation('id_broker', 'broker', 'name');
			$crud->columns('id_cliente', 'id_broker', 'username');
			$crud->columns('id_cliente', 'id_broker', 'username', 'email', 'is_admin');
			$crud->add_fields('id_cliente', 'id_broker', 'username', 'email', 'password', 'is_admin');
			$crud->edit_fields('id_cliente', 'id_broker', 'username', 'email', 'password', 'is_admin');			
			$crud->fields('id_cliente', 'id_broker', 'username', 'email', 'password', 'is_admin');
			$crud->display_as('id_cliente', 'Client/Buyer');
			$crud->display_as('id_broker', 'Broker');
			$crud->display_as('username', 'Username');
			$crud->display_as('password', 'Password');
			$crud->callback_before_update(array($this,'create_password'));
			$crud->callback_before_insert(array($this,'create_password'));
			$output = $crud->render();
			
			$this->_example_output($output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	
	function generate_code($primary_key , $row)
	{
	    return site_url('generatecode/generate_code/'.$row->code.'/'.$row->id);
	}	
	function create_password($post_array)
	{
	  $post_array['password'] = $this->hash_password($post_array['password']);
	  return $post_array;
	}
	private function hash_password($password) {		
		return password_hash($password, PASSWORD_BCRYPT);		
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
