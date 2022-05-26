<?php

//This model interacts with the database
class ModelProgram extends CI_Model
{
	public function getProgram($programId = '')
	{
		if($programId == '')
		{
			$checkData = array('isDeleted' => 0);
		}
		else
		{
			$checkData = array('isDeleted' => 0, 'id' => $programId);
		} 		
		$query = $this->db->get_where('program', $checkData);
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
	
	public function getProgramForm($programId, $formType)
	{
		$checkData = array('isDeleted' => 0, "formType" => $formType, "programId" => $programId);
		$query = $this->db->get_where('programinfo', $checkData);
			
		$count = $query->num_rows(); //counting result from query
		if ($count === 0)
		{
			return false;
		}
		else
		{
			return array("count" => $count, "data" => $query->result_array());
		}
	}

	public function getFormData($checkData)
	{
		$this->db->order_by('modifiedDate', 'DESC');
		$query = $this->db->get_where('formdata', $checkData);
		// print_r($this->db->last_query());
		$count = $query->num_rows(); //counting result from query
		if ($count === 0){
			return false;
		}
		else{
			return array("count" => $count, "data" => $query->result_array());
		}
	}	

	public function getProgramByStoreFinco($storeId, $fincoId)
	{
		$query = $this->db->query("select id from program where channelPartnerId in (select channelPartnerId from channelpartnerstore where id = $storeId) and fincoId = $fincoId and isDeleted = 0");
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

	public function getDocuments($whereData){
		$this->db->select('f.*, p.name programName, u1.name createrName, u2.name modifierName');
        	$this->db->from('programdata as f');
	        $this->db->join('program as p', 'p.id = f.programId');
        	$this->db->join('users as u1', 'u1.id = f.createdBy');
	        $this->db->join('users as u2', 'u2.id = f.modifiedBy');
        	        if(sizeof($whereData)>0){
                	        foreach($whereData as $k => $v)
                        	        $this->db->where('f.'.$k,$v);
                                	//$checkData = array($k => $v);
	                }
		$this->db->order_by("f.isDeleted", "asc");
	        $query = $this->db->get();
       	        $count = $query->num_rows(); //counting result from query
	
       	        if ($count === 0){
               	        return false;
               	}else{
                       	return array("count" => $count, "data" => $query->result_array());
                }
	}
}

?>
