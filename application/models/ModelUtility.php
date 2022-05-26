<?php

//This model interacts with the database
class ModelUtility extends CI_Model{
	public function checkApiAuthentication($userName, $apiKey)
	{
		$query = $this->db->get_where('apiauthentication', array('userName' => $userName, 'apiKey' => $apiKey, 'isDeleted' => 0));
		$count = $query->num_rows(); //counting result from query
		if ($count === 0){
			return false;
                }else{
			return $query->result_array();
		}
	}	

	public function saveLog($userName, $input, $output)
        {
                $input = json_encode($input, JSON_UNESCAPED_UNICODE);
                $output = json_encode($output, JSON_UNESCAPED_UNICODE);

                $data = array("userName" => $userName, "input" => $input, "output" => $output);
                $this->db->insert("audit", $data);
                $lastId = $this->db->insert_id();
                if($lastId >0)
                {
                        return $lastId;
                }
                else
                {
                        return false;
                }
        }	
}

?>
