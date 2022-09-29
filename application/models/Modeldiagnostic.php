<?php

//This model interacts with the database
class Modeldiagnostic extends CI_Model
{
	
	public function checkDiagnosticTransaction($id)
	{
                $this->db->select('*');
                $this->db->from('session_register');
                $this->db->where('id', $id);
                $this->db->where('status', 'Running');
                $this->db->order_by('id', 'desc');
		$this->db->limit(1,0);
                $query = $this->db->get();
                $count = $query->num_rows(); 
                if ($count === 0)
                {
                        return false;
                }
                else
                {
                        return $query->result_array();
                }
	}

        public function getDiagnosticTransactionID($nid,$imei)
	{
		$checkData = array('nationalid' => $nid,'imei' => $imei, 'status' => 'Running');
                $query = $this->db->get_where('session_register', $checkData);
                $count = $query->num_rows(); //counting result from query
                if ($count === 0)
                {
                        return false;
                }
                else
                {
                        return $query->result_array();
                }
	}

        public function addnewphysicaldeclarationData($data)
        {
                try{
                        $this->db->insert('physical_declaration', $data);
                        $id = $this->db->insert_id();
                        if ($id === 0){
                                return false;
                        }
                        else{
                                return $id;
                        }
                }
                catch (Exception $e){
                        log_message('error: ',$e->getMessage());
                        return false;
                }
        }

        public function updatesessionregister($data, $whereData)
        {
                foreach($whereData as $k => $v){
                        $this->db->where($k,$v);
                }
                
                $this->db->update('session_register',$data);
                return $this->db->affected_rows();
        }

        public function addnewsession($data)
        {
                try{
                        $this->db->insert('session_register', $data);
                        $id = $this->db->insert_id();
                        if ($id === 0){
                                return false;
                        }
                        else{
                                return $id;
                        }
                }
                catch (Exception $e){
                        log_message('error: ',$e->getMessage());
                        return false;
                }
        }

        public function getDeviceDiagnosticHistory($id)
	{
                $sql = "select a.*,b.* from session_register a inner join physical_declaration b on a.id=b.sessionregid where a.transactionId='$id'";

                $res = $this->db->query($sql);
                $cData = $res->result_array();
                $count = $res->num_rows(); 
                if ($count === 0){
                        return false;
                }
                else{
                        return $cData;
                }
	}

        public function updatePhysicaldeclarationData($data, $whereData)
        {
                foreach($whereData as $k => $v){
                        $this->db->where($k,$v);
                }
                
                $this->db->update('physical_declaration',$data);
                return $this->db->affected_rows();
        }
	
}

?>
