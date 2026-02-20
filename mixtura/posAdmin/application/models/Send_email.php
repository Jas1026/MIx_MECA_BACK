<?php
class Send_email extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
		$this->load->database();   
    }
    public function Check_observations($new_observation, $embarque_id, $num_contrato) {
        $this->db->reset_query();
		$this->db->from('embarque');
		$this->db->where('id', $embarque_id);
		$observations = $this->db->get()->row('observaciones');
        if (trim(strip_tags($observations)) == trim(strip_tags($new_observation))) {
            $nmc = "test_N";
        } else {
            $nmc = $this->Send_email($new_observation, $embarque_id, $num_contrato);
        }        
        return $nmc;
    }
    public function Send_email($new_observation, $embarque_id, $num_contrato) {

         $config['protocol']    = 'smtp';
         //$config['smtp_host']    = 'relay-smtp.gmail.com:25';
         $config['smtp_host']    = 'ssl://mail.nvm-trade.com';
         $config['smtp_port']    = '465';
         $config['smtp_timeout'] = '7';
         $config['_smtp_auth'] = TRUE;
         $config['smtp_crypto']  = 'tls';
         //$config['smtp_user']    = 'update@nvm-trade.com';
         $config['smtp_user']    = 'nvmtrad1@nvm-trade.com';
         //$config['smtp_pass']    = 'NVMtrade20231399';
         $config['smtp_pass']    = '729l+ox+4UCvYU';
         $config['charset']    = 'utf-8';
         $config['newline']    = "\r\n";
         $config['mailtype'] = 'html';
         $config['validation'] = TRUE; 
		$this->load->library('email', $config);
        $this->email->set_newline("\r\n");

        //$this->email->from('update@nvm-trade.com', 'NVM TRADE SRL');
        $this->email->from('nvmtrad1@nvm-trade.com', 'NVM TRADE SRL');
        //$this->email->to('enrique.oropeza@vrealitybolivia.com');
        $this->email->to('laura@nvm-trade.com');
        $this->email->cc('faviola@nvm-trade.com');
        $this->email->bcc('quike.oropeza@gmail.com');

        $this->email->subject('There were changes in the shipment '.$num_contrato.' observations');
        $this->email->message('<h4>The following changes were made to the '.$num_contrato.' shipment:<h4>'.$new_observation);

        $this->email->send();
        return $this->email->print_debugger();




        // $email = 'quike.oropeza@gmail.com';
        // $fields = array('email' => $email, 'num_contrato' => $num_contrato, 'new_observation' => urlencode($new_observation));
        // $fields_string = http_build_query($fields);
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, "https://vrtybo.com/services/nvm/send_email.php");
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string );
        // $data = curl_exec($ch);
        // curl_close($ch);
        // return $data;


    }
}
?>