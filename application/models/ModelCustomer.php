<?php

//This model interacts with the database
class ModelCustomer extends CI_Model
{
	
	public function getCustomer($chekType, $checkValue)
	{
		$checkData = array('isDeleted' => 0, $chekType => $checkValue);
                $query = $this->db->get_where('customerv2', $checkData);
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

	public function getCustomerbyStatus($programId = 0, $status = '')
        {
		$checkStr = ' and c.isDeleted = 0';
		$dataArray = array();
		if($programId != 0){
			$checkStr.= " and c.programId = ?";	
			$dataArray[] = $programId;
		}
		if($status != ''){
			$checkStr.= " and cv.status = ?";	
			$dataArray[] = $status;	
		}
		$sql = "select c.id, c.name, c.mobile, cv.status from customer c, customerverification cv where c.id = cv.customerId $checkStr";
                $customers = $this->db->query($sql, $dataArray);
                $count = $customers->num_rows(); //counting result from query
                if ($count === 0)
                {
                        return false;
                }
                else
                {
                        return array("count" => $count, "data" => $customers->result_array());
                }
        }	

	public function getCustomerVerificationStatus($customerId, $storeId, $programId)
        {
                $checkData = array('customerId' => $customerId, "storeId" => $storeId, "programId" => $programId);
                $query = $this->db->get_where('customerverification', $checkData);
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
	public function getCustomerDocData($customerId)
        {
                $checkData = array('customerId' => $customerId, "isDeleted" => 0);
                $query = $this->db->get_where('customerdocumentdata', $checkData);
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
	
	public function addCustomerVerification($customerData)
	{
		$data = $customerData;
		try {
			$this->db->insert('customerverification', $data);
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
	
	public function addCustomerAudit($data)
        {
                try {
                        $this->db->insert('customeraudit', $data);
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
	public function updateCustomerVerificationStatus($id, $status)
        {
                $value=array('status'=>$status);
                $this->db->where('id',$id);
                $this->db->update('customerverification',$value);
                return $this->db->affected_rows();
        }	

	public function updateCustomerVerificationStatusByCustomerId($id, $status)
        {
                $value=array('status'=>$status);
                $this->db->where('customerId',$id);
                $this->db->update('customerverification',$value);
                return $this->db->affected_rows();
        }	

	public function blockCustomer($id, $days)
        {
                $sql = "update customerblacklist set blockDate = NOW() + INTERVAL $days DAY where customerId = ? order by id desc";
                $products = $this->db->query($sql, array($id));
                $count = $this->db->affected_rows(); //counting result from query
		if($count == 0){
	                $sql = "insert into customerblacklist (customerId,blockDate) values (?, NOW() + INTERVAL $days DAY)";
        	        $products = $this->db->query($sql, array($id));
			return true;
		}else{
			return true;
		}
        }	

	public function isCustomerBlock($id)
        {
                $sql = "select * from customerblacklist where customerId = ? and blockDate>now() order by id desc";
                $products = $this->db->query($sql, array($id));
                $count = $products->num_rows(); //counting result from query
                if ($count === 0)
                {
                        return false;
                }
                else
                {
                        return true;
                }
        }
	
	public function updateCustomerDocument($data, $whereData)
        {
                foreach($whereData as $k => $v)
	                $this->db->where($k,$v);
                $this->db->update('customerdocumentdata',$data);
                return $this->db->affected_rows();
        }	
	public function updateCustomer($data, $whereData)
        {
                foreach($whereData as $k => $v)
	                $this->db->where($k,$v);
                $this->db->update('customer',$data);
                return $this->db->affected_rows();
        }	

	public function addCustomer($customerData)
	{
		$data = array("name" => $customerData['name'], "mobile" => $customerData['mobileNumber'], "email" => $customerData['email'], "dob" => $customerData['dob'], "caAddress" => $customerData['caAddress'], "daAddress" => $customerData['daAddress'], "programId" => 1, "docType" => "IC", "docData" => $customerData['icNumber'], "channelPartnerStoreId" => $customerData['channelPartnerStoreId'], "docIssuedDate" => $customerData['docIssuedDate'], "occupation" => $customerData['occupation'], "telco" => $customerData['telco'], "nationality" => $customerData['nationality']);
		try {
			$this->db->insert('customer', $data);
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

	public function addCustomerDocument($data)
	{
		try {
			$sql = "update customerdocumentdata set docData = '".$data['docData']."', createdDate = now() where customerId ='".$data['customerId']."' and label = '".$data['label']."' order by id desc limit 1";
			$products = $this->db->query($sql);
			$count = $this->db->affected_rows();
			if($count == 0){
				$this->db->insert('customerdocumentdata', $data);
				$docId = $this->db->insert_id();
			
				if ($docId === 0)
				{
					return false;
				}
				else
				{
					return $docId;
				}
			}else{
				return true;
			}
		} 
		catch (Exception $e) {
		        // this will not catch DB related errors. But it will include them, because this is more general. 
		        log_message('error: ',$e->getMessage());
		        return false;
    		}	
	}

	public function getCustomerDocument($id, $label='')
        {
                $wStr = ' and customerId = ?';
                $where = array($id);
                if($label != ''){
                        $wStr .= ' and label = ?';
                        $where[] = $label;
                }
                $sql = "select * from customerdocumentdata where isDeleted = 0 $wStr order by id desc";
                $products = $this->db->query($sql, $where);
                $count = $products->num_rows(); //counting result from query
                if ($count === 0)
                {
                        return false;
                }
                else
                {
                        return $products->result_array();
                }
        }
	
}

?>
