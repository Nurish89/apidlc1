<?php

//This model interacts with the database
class ModelSubscription extends CI_Model
{
	
	public function addSubscription($data)
	{
		try {
			$this->db->insert('subscription', $data);
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

	public function updateSubscription($data, $whereData)
	{
		foreach($whereData as $k => $v)
                        $this->db->where($k,$v);
			$this->db->db_debug = FALSE;                	
			$this->db->update('subscription',$data);
			// print_r($this->db->last_query()); 
	                return $this->db->affected_rows();
	}	

	public function syncDoc($id)
	{
                $this->db->where('id',$id);
		$this->db->db_debug = FALSE;                	
		$this->db->update('subscriptiondocumentdata',array("sync" => 1));
	        return $this->db->affected_rows();
	}	
	public function unsyncDoc($id, $doc)
	{
                $this->db->where('subscriptionId',$id);
                $this->db->where('label',$doc);
		$this->db->db_debug = FALSE;                	
		$this->db->update('subscriptiondocumentdata',array("sync" => 0));
	        return $this->db->affected_rows();
	}	
	
	public function checkOCBSyncDoc($id){
		$sql = "select id from subscriptiondocumentdata where subscriptionId = ? and isDeleted = 0 and sync = 0 and label in ('DOWNPAYMENT_RECEIPT', 'PRODUCT_IMAGE_ATTACH', 'LOAN_APP_CONTRACT_ATTACH', 'INSURANCE_CONTRACT_ATTACH')";
                $products = $this->db->query($sql, array($id));
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
	public function getSubscriptionByProgram($programId, $status){
		$wStr = ' ';
		$where = array();
		if(trim($status) != ''){
			$wStr .= ' and s.status = ?';
			$where = array($status);	
		}
		if($programId != 0){
			$wStr .= ' and s.programId = ?';
			$where[] = $programId;	
		}
		$sql = "select a.*, b.priceData from (select s.*, ss.status statusName, c.name customerName, c.mobile customerMobile, c.email CustomerEmail, p.name productName, pp.sku, pp.partnerSKU from programprice pp, product p, subscription s, subscriptionstatus ss, customer c  where s.isDeleted = 0 and s.status = ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id and s.appId is not NULL and s.appId != '' $wStr) as a left join (select subscriptionId, group_concat(itemType,'=>', itemValue) priceData from subscriptionpriceitem where subscriptionId in (select id from subscription s where s.appId is not null) group by subscriptionId) b on a.id = b.subscriptionId";
		$products = $this->db->query($sql, $where);
		$count = $products->num_rows(); //counting result from query
		if ($count === 0){
				return false;
		}else{
				return $products->result_array();
		}	
	}		

	public function getSubscriptionByProgram2($programId){
		$wStr = ' ';
		$where = array();
		if($programId != 0){
			$wStr .= ' and s.programId = ?';
			$where[] = $programId;	
		}
		// $sql = "select a.*, b.priceData from (select s.*, ss.status statusName, c.cData, p.name productName, pp.sku, pp.partnerSKU from programprice pp, product p, subscription s, subscriptionstatus ss, customerv2 c  where s.isDeleted = 0 and s.status = ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id and s.appId is not NULL and s.appId != '' $wStr) as a left join (select subscriptionId, group_concat(itemType,'=>', itemValue) priceData from subscriptionpriceitem where subscriptionId in (select id from subscription s where s.appId is not null) group by subscriptionId) b on a.id = b.subscriptionId";
		$sql = "select a.*, b.priceData from (select s2.*, p.name productName from (select s1.*, pp.sku, pp.partnerSKU, pp.productId from (select s.*, ss.status statusName, c.cData from subscription s, subscriptionstatus ss, customerv2 c where s.isDeleted = 0 and s.customerId=c.id and s.status=ss.statusId $wStr) s1 left join programprice pp ON s1.product = pp.sku) as s2 left join product p ON s2.productId = p.id) as a left join (select subscriptionId, group_concat(itemType,'=>', itemValue) priceData from subscriptionpriceitem where subscriptionId in (select id from subscription s where s.appId is not null) group by subscriptionId) b on a.id = b.subscriptionId";
		$products = $this->db->query($sql, $where);
		$count = $products->num_rows(); //counting result from query
		if ($count === 0){
				return false;
		}else{
				return $products->result_array();
		}	
	}

	public function useOTPAttempt($id)
	{
		$sql = "update subscription set otpAttempt = otpAttempt -1, modifiedDate = now() where id = ?";
                $products = $this->db->query($sql, array("id" => $id));
		return true;	
	}	

	public function getSubscriptionById($id){
		$wStr = ' and s.id = ?';
		$where = array($id);	
		//$sql = "select s.*, ss.status statusName, c.name customerName, c.mobile customerMobile, c.email CustomerEmail, p.name productName, pp.sku, pp.partnerSKU, p.rom, p.romUnit, p.ram, p.ramUnit, p.color, p.isRelative, p.image, si.* from programprice pp, product p, subscription s, subscriptionstatus ss, customerv2 c, subscriptionpriceitem si  where s.isDeleted = 0 and s.status = ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id and s.id=si.subscriptionId $wStr ";
		
		$sql = "select s.*, ss.status statusName, p.name productName, pp.sku, pp.partnerSKU, p.rom, p.romUnit, p.ram, p.ramUnit, p.color, p.isRelative, p.image from programprice pp, product p, subscription s, subscriptionstatus ss, customerv2 c  where s.isDeleted = 0 and s.status = ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr ";
		$products = $this->db->query($sql, $where);
		$count = $products->num_rows(); //counting result from query
		if ($count === 0){
		        return false;
		}else{
		        return $products->result_array();
		}	
	}	

	public function getStatusById($id)
	{
		$wStr = ' and statusId = ?';
		$where = array($id);	
		$sql = "select * from subscriptionstatus where isDeleted =0 $wStr ";
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

	public function getSubscriptionByAppId($id)
	{
		$wStr = ' and s.appId = ?';
		$where = array($id);	
		$sql = "select s.*, ss.status statusName, c.name customerName, c.mobile customerMobile, c.email CustomerEmail, p.name productName, pp.sku, pp.partnerSKU, p.rom, p.romUnit, p.ram, p.ramUnit, p.color, p.isRelative, p.image, pp.dmof, pp.fsv from programprice pp, product p, subscription s, subscriptionstatus ss, customer c  where s.isDeleted = 0 and s.status=ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr ";
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

	public function getSubscriptionByCustomerId($id)
	{
		$wStr = ' and s.customerId = ?';
		$where = array($id);	
		//$sql = "select s.*, ss.status statusName, c.name customerName, c.mobile customerMobile, c.email CustomerEmail, p.name productName, pp.sku, pp.partnerSKU, p.rom, p.romUnit, p.ram, p.ramUnit, p.color, p.isRelative, p.image, pp.dmof, pp.fsv from programprice pp, product p, subscription s, subscriptionstatus ss, customerv2 c  where s.isDeleted = 0 and s.status = ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr ";

		$sql = "select s.*, ss.status statusName, p.name productName, pp.sku, pp.partnerSKU, p.rom, p.romUnit, p.ram, p.ramUnit, p.color, p.isRelative, p.image, pp.dmof, pp.fsv from programprice pp, product p, subscription s, subscriptionstatus ss, customerv2 c  where s.isDeleted = 0 and s.status = ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr ";
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

	public function getSubscriptionIDByCustomerId($id){
		$wStr = ' and s.customerId = ?';
		$where = array($id);
		$sql = "select s.id, s.referenceNumber from subscription s where s.isDeleted = 0 $wStr ";
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

	public function getSubscriptionAuditData($whereData, $orderBy = '', $limit = '')
	{
		$wStr = '';
		$where = array();	
		foreach($whereData as $k => $v){
			$wStr .= ' and '.$k.' = ?';
			$where[] = $v;	
		}
		if($orderBy != '')
			$orderBy = ' order By '.$orderBy;	
		if($limit != '')
			$limit = ' limit '.$limit;	
		$sql = "select * from subscriptionaudit where isDeleted = 0 $wStr $orderBy $limit";
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

	public function getSubscriptionByStore($id, $status='')
	{
		$wStr = ' and c.storeId = ?';
		$where = array($id);	
		if($status != ''){
			$wStr .= ' and 	s.status = ?';
			$where[] = $status;
		}
		//$sql = "select b.*, e1.userId completedBy from (select a.*, e.fullName createdBy from (select s.id subscriptionId, c.name customerName, s.appId referenceNumber, c.mobile customerMobile, c.email CustomerEmail, p.name productName, pp.sku, pp.partnerSKU, s.status, ss.status statusName, s.createdDate, c.docData, s.createdStaff, s.completedStaff, s.modifiedDate, s.remarks  from programprice pp, product p, subscription s, subscriptionstatus ss, customer c  where s.appId is not NULL and s.isDeleted = 0 and s.status=ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr) as a left join employee e on a.createdStaff = e.id) as b left join employee e1 on b.completedStaff = e1.id ";
		$sql = "select b.*, e1.userId completedBy from (select a.*, e.fullName createdBy from (select s.id subscriptionId, '' customerName, s.appId referenceNumber, '' customerMobile, '' CustomerEmail, '' docData, p.name productName, pp.sku, pp.partnerSKU, s.status, ss.status statusName, s.createdDate, s.createdStaff, s.completedStaff, s.modifiedDate, s.remarks  from programprice pp, product p, subscription s, subscriptionstatus ss, customerv2 c  where s.appId is not NULL and s.isDeleted = 0 and s.status=ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr) as a left join employee e on a.createdStaff = e.id) as b left join employee e1 on b.completedStaff = e1.id ";
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
	
	public function getSubscriptionByStoreMAFC($id, $status=''){
		$wStr = ' and c.storeId = ?';
		$where = array($id);	
		if($status != ''){
			$wStr .= ' and 	s.status = ?';
			$where[] = $status;
		}
		//$sql = "select b.*, e1.userId completedBy from (select a.*, e.fullName createdBy from (select s.id subscriptionId, c.name customerName, s.appId referenceNumber, c.mobile customerMobile, c.email CustomerEmail, p.name productName, pp.sku, pp.partnerSKU, s.status, ss.status statusName, s.createdDate, c.docData, s.createdStaff, s.completedStaff, s.modifiedDate, s.remarks  from programprice pp, product p, subscription s, subscriptionstatus ss, customer c  where s.appId is not NULL and s.isDeleted = 0 and s.status=ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr) as a left join employee e on a.createdStaff = e.id) as b left join employee e1 on b.completedStaff = e1.id ";
		$sql = "select b.*, e1.userId completedBy from (select a.*, e.fullName createdBy from (select s.id subscriptionId, JSON_EXTRACT(c.cData, \"$.fullname\") customerName, s.appId referenceNumber, JSON_EXTRACT(c.cData, \"$.mobile\") customerMobile,  JSON_EXTRACT(c.cData, \"$.nationalId\") docData, '' CustomerEmail, p.name productName, pp.sku, pp.partnerSKU, s.status, ss.status statusName, s.createdDate, s.createdStaff, s.completedStaff, s.modifiedDate, s.remarks  from programprice pp, product p, subscription s, subscriptionstatus ss, customerv2 c  where s.appId is not NULL and s.isDeleted = 0 and s.status=ss.statusId and s.product = pp.sku and s.customerId = c.id and pp.productId = p.id $wStr) as a left join employee e on a.createdStaff = e.id) as b left join employee e1 on b.completedStaff = e1.id ";
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

	public function getSubscriptionDocument($id, $label='')
	{
		$wStr = ' and subscriptionId = ?';
		$where = array($id);	
		if($label != ''){
			$wStr .= ' and label = ?';
			$where[] = $label;
		}
		$sql = "select * from subscriptiondocumentdata where isDeleted = 0 $wStr order by id desc";
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

	public function addSubscriptionDocument($data)
        {
                try {
			$sql = "update subscriptiondocumentdata set docData = '".$data['docData']."', createdDate = now() where subscriptionId = '".$data['subscriptionId']."' and label = '".$data['label']."' order by id desc limit 1";
        	        $products = $this->db->query($sql);
        	        $count =  $this->db->affected_rows();
			if($count == 0){
	                        $this->db->insert('subscriptiondocumentdata', $data);
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
	
	public function addSubscriptionAudit($data)
        {
                try {
                        $this->db->insert('subscriptionaudit', $data);
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
	
	public function addSubscriptionPriceItem($data)
        {
                try {
	                for($i=0;$i<sizeof($data);$i++){
        	                $this->db->insert('subscriptionpriceitem', $data[$i]);
			}
                       return false;
                }
                catch (Exception $e) {
                        // this will not catch DB related errors. But it will include them, because this is more general.
                        log_message('error: ',$e->getMessage());
                        return false;
                }
        }

	public function getSubscriptionPriceItem($id)
	{
		$wStr = '';
		$where = array($id);	
		$sql = "select * from subscriptionpriceitem  where isDeleted = 0 and subscriptionId = ?";
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
