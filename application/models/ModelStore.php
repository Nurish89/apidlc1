<?php

//This model interacts with the database
class ModelStore extends CI_Model
{
	public function getStore($storeId = '')
	{
		if($storeId == '')
		{
			$checkData = array('isDeleted' => 0);
		}
		else
		{
			$checkData = array('isDeleted' => 0, 'id' => $storeId);
		} 		
		$query = $this->db->get_where('channelpartnerstore', $checkData);
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

	public function getStoreByCode($storeCode)
	{
		$checkData = array('isDeleted' => 0, 'storeCode' => $storeCode);
		$query = $this->db->get_where('channelpartnerstore', $checkData);
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
	
	public function getStoreByProgram($programId){
		$where = array($programId);
		$sql = "select * from channelpartnerstore where isDeleted = 0 and channelPartnerId in (select channelPartnerId from program where id= ?)";
		$stores = $this->db->query($sql, $where);
		$count = $stores->num_rows(); //counting result from query
		if ($count === 0){
				return false;
		}else{
				return $stores->result_array();
		}	
	}

	//Nurish : Multicountry----------------------------------------

	public function getStaffProgramDetails($programId)
	{
		$where = array($programId);
		$sql = "select * from program where isDeleted = 0 and id in ($programId)";
		$stores = $this->db->query($sql, $where);
		$count = $stores->num_rows(); //counting result from query
		if ($count === 0){
				return false;
		}
		else{
				return $stores->result_array();
		}	
	}

	//--------------------------------------------------------------
}

?>
