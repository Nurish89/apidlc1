<?php

//This model interacts with the database
class ModelFinco extends CI_Model
{
	public function getFinco($whereData){
		$checkData = array();
		if(sizeof($whereData)>0){
			foreach($whereData as $k => $v)
				$checkData = array($k => $v);
		}
		$query = $this->db->get_where('finco', $checkData);
		$count = $query->num_rows(); //counting result from query
		if ($count === 0){
			return false;
		}
		else{
			return $query->result_array();
		}
	}
        
        public function getFincoByProgram($programId){
		$query = $this->db->query("select * from finco where id in (select fincoId from program where id = $programId)");
                $count = $query->num_rows(); //counting result from query
                if ($count === 0){
                        return false;
                }else{
                        return $query->result_array();
                }
	}

	public function updateFinco($data, $whereData){
                foreach($whereData as $k => $v)
                        $this->db->where($k,$v);
                        $this->db->db_debug = FALSE;
                        $this->db->update('finco',$data);
                        return $this->db->affected_rows();
        }

	public function addFinco($fincoData)
        {
                $data = array("name" => $fincoData['name'], "registeredAddress" => $fincoData['address'], "country" => $fincoData['country'], "language" => $fincoData['language'], "currency" => $fincoData['currency'], "logo" => $fincoData['logo'], "createdBy" => $fincoData['userId']);
                try{
                        $this->db->insert('finco', $data);
                                $fincoId = $this->db->insert_id();
                                if ($fincoId === 0){
                                        return false;
                                }else{
                                        return $fincoId;
                                }
                }catch (Exception $e){
                        // this will not catch DB related errors. But it will include them, because this is more general.
                        log_message('error: ',$e->getMessage());
                        return false;
                }
        }

}

?>
