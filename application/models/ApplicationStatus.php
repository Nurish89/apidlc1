<?php

//This model interacts with the database
class ApplicationStatus extends CI_Model
{
	public function getApplication($appId)
	{
		$applicatoinData = $this->db->query("SELECT * FROM subscription WHERE appId = ". $this->db->escape($appId) ." and isDeleted = 0");
		$query = $this->db->get_where('subscription', array('appId' => $appId));
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
	
	public function updateApplicationStatus($appId, $status, $remark)
	{
		$value=array('status'=>$status,'remarks'=>$remark, 'modifiedDate' => date('Y-m-d H:i:s'));
        	$this->db->where('appId',$appId);
	        $this->db->update('subscription',$value);
		return $this->db->affected_rows();
	}
}

?>
