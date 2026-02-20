<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set("default_charset", "UTF-8");
class Member extends CI_Model{
    
    function __construct() {
        // Set table name
        $this->table = 'employee';
    }
    
    /*
     * Fetch members data from the database
     * @param array filter data based on the passed parameters
     */
    function getRows($params = array()){
        $this->db->select('*');
        $this->db->from($this->table);
        
        if(array_key_exists("where", $params)){
            foreach($params['where'] as $key => $val){
                $this->db->where($key, $val);
            }
        }
        
        if(array_key_exists("returnType",$params) && $params['returnType'] == 'count'){
            $result = $this->db->count_all_results();
        }else{
            if(array_key_exists("idEmployee", $params)){

                $query = $this->db->get();
                $result = $query->row_array();
            }else{
                $this->db->order_by('idEmployee', 'desc');
                if(array_key_exists("start",$params) && array_key_exists("limit",$params)){
                    $this->db->limit($params['limit'],$params['start']);
                }elseif(!array_key_exists("start",$params) && array_key_exists("limit",$params)){
                    $this->db->limit($params['limit']);
                }
                
                $query = $this->db->get();
                $result = ($query->num_rows() > 0)?$query->result_array():FALSE;
            }
        }
        
        // Return fetched data
        return $result;
    }
    
    /*
     * Insert members data into the database
     * @param $data data to be insert based on the passed parameters
     */
    public function insert($data = array()) {
        if(!empty($data)){
            // Add created and modified date if not included
            if(!array_key_exists("created_at", $data)){
                $data['created_at'] = date("Y-m-d H:i:s");
            }
            if(!array_key_exists("modified", $data)){
                $data['updated_at'] = date("Y-m-d H:i:s");
            }
            
            // Insert member data
            $insert = $this->db->insert($this->table, $data);
            
            // Return the status
            return $insert?$this->db->insert_id():false;
        }
        return false;
    }
    
    /*
     * Update member data into the database
     * @param $data array to be update based on the passed parameters
     * @param $condition array filter data
     */
    public function update($data, $condition = array()) {
        if(!empty($data)){
            // Add modified date if not included
            if(!array_key_exists("updated_at", $data)){
                $data['updated_at'] = date("Y-m-d H:i:s");
            }
            
            // Update member data
            $update = $this->db->update($this->table, $data, $condition);
            
            // Return the status
            return $update?true:false;
        }
        return false;
    }
    public function getCompanies() {        
        $query = $this->db->get('company');
        return $query->result();        
    }
    public function getRankingKO() {        
        $query = $this->db->query('SELECT u.name, u.token, (SELECT SUM(us.points) from user_scores us WHERE us.user_id = u.id) as points FROM user u WHERE u.app_id = 200 ORDER BY points DESC');
        return $query->result();        
    }
    public function getRankingCofar() {        
        $query = $this->db->query('SELECT u.name, u.token, (SELECT SUM(us.points) from user_scores us WHERE us.user_id = u.id) as points FROM user u WHERE u.app_id = 300 ORDER BY points DESC');
        return $query->result();        
    }
    public function getIdVille($city) {
        $city = utf8_encode($city);
        $this->db->select('idCity');
        $this->db->from('city');
        $this->db->where('name', $city);
        $this->db->or_where('slug', $city);       
        $query = $this->db->get();
        $resul = $query->result();   
        return $resul[0]->idCity;

    }
}