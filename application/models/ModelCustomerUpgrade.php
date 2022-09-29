<?php

//This model interacts with the database
class ModelCustomerUpgrade extends CI_Model
{

    //Nurish - Upgrade program ---------------------------------------------------------

    public function getCustomerInfo($nationalid, $imei)
    {
        $dataArray = array();
        $sql = "select * from customerv2 where isDeleted = 0 and nationalid = '$nationalid'";
        $customers = $this->db->query($sql);
        $count = $customers->num_rows(); //counting result from query
        if ($count === 0){
            return false;
        }
        else{
            $cData = $customers->result_array();
            
            $customerId = $cData[0]['id'];
            $sql2 = "select appInfo from subscription where isDeleted = 0 and customerId = '$customerId'";
            $customers2 = $this->db->query($sql2);
    
            $res = $customers2->result_array();
            $appInfo = json_decode($res[0]['appInfo'],true);

            $sql3 = "select a.id,b.grv from product a inner join programprice b on a.id=b.productId where a.name = '".$appInfo['Device Model']."'";
            $query = $this->db->query($sql3);
            $deviceData = $query->result_array();
            //echo $sql3; die();

            $purchaseDate = "29/07/2021";
            $purchaseDateX = str_replace('/', '-', $purchaseDate);
            $dateX = date('Y-m-d', strtotime($purchaseDateX));
            $todaysdate = date('Y-m-d');
            $upgradeDate =  date('Y-m-d', strtotime("+12 months", strtotime($dateX)));

            //echo $date.'</br>';
            //echo $upgradeDate.'</br>';
            if((strtotime($todaysdate))> (strtotime($upgradeDate))){
                $eligibility = 'Eligible';
            }
            else{
                $eligibility = 'Not Eligible';
            } 

            if($imei == $appInfo['IMEI']){
                    
                
                $dataArray[] = array('purchaseDate' => $appInfo['Date of Purchase'],
                    'id' => $cData[0]['id'],
                    'imei' => $appInfo['IMEI'],
                    'programId' => $appInfo['programId'],
                    'customerName' => $cData[0]['customerName'],
                    'product' => $appInfo['Device Brand'].' - '.$appInfo['Device Model'],
                    'isEligible' => $eligibility,
                    'insurance' => 'Yes',
                    'grv' => $deviceData[0]['grv']
                    );     
            }
            else{
                $dataArray = '';
            }

            //var_dump($dataArray); die();

            return array("count" => $count, "data" => $dataArray);
        }
    }

    public function customercheking($nationalid, $imei)
    {
        $sql = "select * from customerv2 where isDeleted = 0 and nationalid = '$nationalid'";
        $customers = $this->db->query($sql);
        $count = $customers->num_rows(); //counting result from query
        if ($count == 0){
            $flag = 0;
        }
        else{
            $cData = $customers->result_array();
            
            $customerId = $cData[0]['id'];
            $sql2 = "select appInfo from subscription where isDeleted = 0 and customerId = '$customerId'";
            $customers2 = $this->db->query($sql2);
    
            $res = $customers2->result_array();
            $appInfo = json_decode($res[0]['appInfo'],true);

            if($imei == $appInfo['IMEI']){
                $flag = 1;
            }
            else{
                $flag = 0;
            }
        }

        return $flag;
    }

    public function insertCustomerUpgrade($customerData)
	{
		try {
			$this->db->insert('customerupgrade', $customerData);
            $customerId = $this->db->insert_id();
            if ($customerId === 0){
                return false;
            }    
            else{
                return $customerId;
            }
                    
		} 
		catch (Exception $e) {
            log_message('error: ',$e->getMessage());
            return false;
    	}	
	}

    public function createRecordintbldiagnosticresult($data)
	{
		try {
			$this->db->insert('diagnosticresult', $data);
            $insertedId = $this->db->insert_id();
            if ($insertedId === 0){
                return false;
            }    
            else{
                return $insertedId;
            }
                    
		} 
		catch (Exception $e) {
            log_message('error: ',$e->getMessage());
            return false;
    	}	
	}

    public function createRecordintblphysicaldeclaration($data)
	{
		try {
			$this->db->insert('physicaldeclaration', $data);
            $insertedId = $this->db->insert_id();
            if ($insertedId === 0){
                return false;
            }    
            else{
                return $insertedId;
            }
                    
		} 
		catch (Exception $e) {
            log_message('error: ',$e->getMessage());
            return false;
    	}	
	}

    public function getTransactionStatusData($staffid)
	{
        $sql = "select a.*, b.customerName from customerupgrade a inner join customerV2 b on b.id = a.customerId
        where a.createdby = $staffid AND 
        DATE(a.createdDate) = CURDATE()";

        $res = $this->db->query($sql);
        $cData = $res->result_array();
        $count = $res->num_rows(); 
        if ($count === 0){
            return false;
        }
        else{
            return array("count" => $count, "data" => $cData);
        }
        //var_dump($cData); die(); 	
	}

    public function cancelcustomerupgrade($cid, $cuid, $trxid, $status)
    {
        $value=array('status'=>$status);
        $where = array('id' => $cuid, 'customerId' => $cid, 'transID' => $trxid);
        $this->db->where($where);
        $this->db->update('customerupgrade',$value);
        return $this->db->affected_rows();
    }

    public function getCustomerDiagnosticRslt($cuid)
	{
        $sql = "select a.*,b.*,c.customerName, d.* from diagnosticresult a 
        inner join customerupgrade b on a.cuid=b.id
        inner join customerv2 c on b.customerid=c.id
        inner join physicaldeclaration d on d.cuid = a.cuid
        where a.cuid=$cuid";

        $res = $this->db->query($sql);
        $cData = $res->result_array();
        $count = $res->num_rows(); 
        if ($count === 0){
            return false;
        }
        else{
            return array("count" => $count, "data" => $cData);
        }
	}

    public function getdeviceConditionType()
	{
        $sql = "select * from deviceconditiontype";

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

    public function getCustomerUpgradeInfoByTransID($transid)
	{
        $sql = "select id from customerupgrade where transID ='$transid'";
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

    public function getdiagnosticresultId($id)
	{
        $sql = "select id from diagnosticresult where cuid ='$id'";
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

    public function updatediagnosticresult($data, $whereData)
    {
        foreach($whereData as $k => $v){
            $this->db->where($k,$v);
        }
            
        $this->db->update('diagnosticresult',$data);
        return $this->db->affected_rows();
    }

    public function updatephysicaldeclaration($data, $whereData)
    {
        foreach($whereData as $k => $v){
            $this->db->where($k,$v);
        }
            
        $this->db->update('physicaldeclaration',$data);
        return $this->db->affected_rows();
    }

    public function updatecustomerupgrade($data, $whereData)
    {
        foreach($whereData as $k => $v){
            $this->db->where($k,$v);
        }
            
        $this->db->update('customerupgrade',$data);
        return $this->db->affected_rows();
    }

    public function getphysicaldeclarationdata($cuid,$dgid)
	{
        $sql = "select * from physicaldeclaration where cuid = $cuid and dgresId = $dgid";
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

    public function getAllCustomerUpgradeInfoByTransID($transid)
	{
        $sql = "select a.id as cuid,b.diagnosticResultId as dgid, b.deviceGRVvalue from customerupgrade a
                inner join physicaldeclaration b on b.cuid = a.id
                where a.transID = '$transid'";
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

    public function getCustomerUpgradeData($cuid,$transId)
    {
        $sql = "select a.* ,b.customerName from customerupgrade a 
        inner join customerV2 b on a.customerId = b.id
        where a.id = $cuid and a.transID = '$transId'";
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

    public function getcustupdata($cuid,$trxid)
	{
        $sql = "select a.*,b.*,c.customerName, d.* from diagnosticresult a 
        inner join customerupgrade b on a.cuid=b.id
        inner join customerv2 c on b.customerid=c.id
        inner join physicaldeclaration d on d.cuid = a.cuid
        where a.cuid=$cuid AND b.transID='$trxid'";

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
    //----------------------------------------------------------------------------------
	
}

?>
