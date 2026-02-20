<?php
class Total_cash extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
		$this->load->database();   
    }
    public function total_cash_type($id, $type) {

        $this->db->reset_query();
        $this->db->select_sum($type);
		$this->db->from('invoice');
		$this->db->where('id_user_cash', $id);       
        return $this->db->get()->row($type);
    }
    public function get_productname_from_invoice($id) {
        $this->db->select('p.name');
        $this->db->from('product p');
        $this->db->join('product_ticket as pt', 'p.id = pt.id_product');
        $this->db->where('pt.id', $id);     
        return $this->db->get()->row('name');
    }
    public function get_productcategory_from_invoice($id) {
        $this->db->select('c.name');
        $this->db->from('category c');
        $this->db->join('product as p', 'p.id_category = c.id');
        $this->db->join('product_ticket as pt', 'p.id = pt.id_product');
        $this->db->where('pt.id', $id);     
        return $this->db->get()->row('name');
    }
    public function get_tablename_from_invoice($id_invoice) {
        $this->db->select('t.name');
        $this->db->from('tables t');
        $this->db->join('ticket as ti', 't.id = ti.id_table');
        $this->db->join('invoice as i', 'ti.id = i.id_ticket');
        $this->db->where('i.id', $id_invoice);     
        return $this->db->get()->row('name');
    }
    public function get_username_from_invoice($id) {
        $this->db->select('u.name');
        $this->db->from('user_cash uc');
        $this->db->join('invoice as i', 'uc.id = i.id_user_cash');
        $this->db->join('user as u', 'uc.id_user = u.id');
        $this->db->where('i.id', $id);     
        return $this->db->get()->row('name');
    }
    public function close_cash($id) {
        $this->db->set('state', 0);
        $this->db->set('close_date', 'NOW()', FALSE);
        $this->db->where('id', $id);
        $this->db->update('user_cash');
        return true;
    }
    
}
?>