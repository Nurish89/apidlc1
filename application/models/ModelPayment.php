<?php

//This model interacts with the database
class ModelPayment extends CI_Model
{
	public function getDownpaymentMode($programId)
	{
		$checkData = array('isDeleted' => 0, 'programId' => $programId);
		$query = $this->db->get_where('paymenttype', $checkData);
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

}

?>
