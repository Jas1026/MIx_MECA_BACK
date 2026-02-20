<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Generatecode extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library(array('session'));
		$this->load->helper('url');
		$this->load->model('employee_model');

		$this->load->library('grocery_CRUD');
		if (isset($_SESSION['username']) && $_SESSION['logged_in'] === true)
		{

		}
		else
		{
			redirect('Welcome/login');
		}
	}

	

	public function index()
	{
		echo 1;
	}
	
	public function generate_code($code, $id) {
		//ini_set('display_errors', 1);
		//ini_set('display_startup_errors', 1);
        /* Load QR Code Library */
            
		$this->load->model('user_model');
		$code = $code .'-'. rand(10000, 99999);
	    $this->user_model->update_password($id, $code);
        redirect('mainpanel/clientes');
	}
}
