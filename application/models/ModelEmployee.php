<?php

//This model interacts with the database
class ModelEmployee extends CI_Model
{
	public function getEmployeeByIdCard($idCard)
	{
		$checkData = array('isDeleted' => 0, 'idCard' => $idCard);
		$query = $this->db->get_where('employee', $checkData);
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
	
	public function getEmployeeById($id)
	{
		$checkData = array('isDeleted' => 0, 'id' => $id);
		$query = $this->db->get_where('employee', $checkData);
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
	
	public function getEmployeeByStaffName($staffName)
	{
		$checkData = array('isDeleted' => 0, 'fullName' => $staffName);
		$query = $this->db->get_where('employee', $checkData);
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
	
	public function getEmployeeByData($data)
	{
		$checkData = array_merge(array('isDeleted' => 0), $data);
		$query = $this->db->get_where('employee', $checkData);
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
	
	public function addEmployee($employeeData)
	{
		try {
				$qString = $this->db->insert_string('employee', $employeeData);
				$qString = str_replace('INSERT INTO','INSERT IGNORE INTO', $qString);
				$this->db->query($qString);
				$customerId = $this->db->insert_id();

				if ($customerId === 0)
				{
						return false;
				}
				else
				{
						return $customerId;
				}
		}
		catch (Exception $e) {
				// this will not catch DB related errors. But it will include them, because this is more general.
				log_message('error: ',$e->getMessage());
				return false;
		}
	}

	public function setEmployeeOTP($id, $newOTP, $storeId, $fincoId, $programId)
	{
		$value=array('otp'=>$newOTP, 'otpExpiry' => date('Y-m-d H:i:s', strtotime("+30 minutes")), "channelPartnerId" => $storeId, "fincoId" => $fincoId, "programId" => $programId);
		$this->db->where('id',$id);
		$this->db->update('employee',$value);
		return $this->db->affected_rows();
	}
	
	public function checkStaffOTP($staffId, $otp)
	{
		$query = $this->db->query("SELECT * FROM employee WHERE idCard = ". $this->db->escape($staffId) ." and isDeleted = 0 and otp = ". $this->db->escape($otp) ." and otpExpiry >= now()");
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

	//Nurish : CRM Multi country--------------------------------------
	public function getStaffByToken($token)
	{
		$this->db->select('id, staffId, expiryDate');
		$query = $this->db->get_where('staff_forgot_password', array('token' => $token, 'isExpired' => 0), 1, 0);
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

	public function setUserPassword($staffId, $password)
	{
		$data = array("password" => $password, "modifiedDate"=>date('Y-m-d H:i:s'));
		$this->db->where("id", $staffId);
		$this->db->order_by('id', 'desc');
		$this->db->limit(1,0);
		$query = $this->db->update('employee', $data);
		$count = $this->db->affected_rows(); 
		if ($count === 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function updateForgotToken($token)
	{
		$data = array("expiryDate"=>date('Y-m-d H:i:s'), "isExpired" => 1);
		$this->db->where("token", $token);
		$this->db->order_by('token', 'desc');
		$this->db->limit(1,0);
		$query = $this->db->update('staff_forgot_password', $data);
		$count = $this->db->affected_rows(); 
		if ($count === 0)
		{
				return false;
		}
		else
		{
				return true;
		}
	}

	public function employeeLogin($data)
	{
		$checkData = array_merge(array('isDeleted' => 0), $data);
		$query = $this->db->get_where('employee', $checkData);
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

	//----------------------------------------------------------------
}

?>
