<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('applicationStatus');
		$this->load->model('ModelUtility');
		$this->load->model('ModelSubscription');
		$this->load->model('ModelCustomer');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('utility');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function index()
	{

		$data = $this->api_model->fetch_all();
		// echo json_encode($data->result_array());
		//$this->load->view('wc');
		//redirect('/api/hello');
		echo 'Welcome to CA APIs, Please pass API Sepcific URL to submit a request.';
	}

	public function hello(){
		//redirect()
		$this->load->view('wc');
	} 

	public function setApplicationStatus()
	{
		$input = $this->input->post();
		$apiName = 'setApplicationStatus'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("appId", "Application Id", "required");
		$this->form_validation->set_rules("statusCode", "Status Code", "required");
		$this->form_validation->set_rules("Remarks", "Remarks", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$appId = $this->input->post('appId');
			$status = $this->input->post('statusCode');
			$remark = $this->input->post('Remarks');	

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$allStatus = array ("AP001" => "13", "AP002" => "accepted with different down payment", "AM001" => "18", "AF001" => "12", "PN001" => "11", "AC001" => "15", "AH001" => "22", "AA001" => "24", "AA002" => "28", "AC002" => "23");
				if(array_key_exists($status, $allStatus))
				{
					//$this->load->model('applicationStatus');
					$isApplicationExits = $this->applicationStatus->getApplication($appId);
					if($isApplicationExits)
					{
						$subscriptionData = $this->ModelSubscription->getSubscriptionByAppId($appId);
						$oldStatus = $subscriptionData[0]['status'];
	
						// PRECONDITION CHECK		
						if(($status == 'AP002') or ($status =='PN001') or ($status == 'AA002') or (($status == 'AF001' or $status == "AM001" or $status == "AP001" or $status == "AC001") and $oldStatus == '11') or ($status == "AH001" and ($oldStatus == '19' or $oldStatus == '20')) or ($status == "AA001" and ($oldStatus == '22' or $oldStatus == '19' or $oldStatus == '20')) or ($status == "AC002" and ($oldStatus == '18' or $oldStatus == '19' or $oldStatus = '20')))
						{
							$statusName = $allStatus[$status];
							$statusData = $this->ModelSubscription->getStatusById($allStatus[$status]);
							if($statusData)
								$statusName = $statusData[0]['status'];
							$remarkChar = substr(trim($remark),0,1);
							$error = 0;
							if($status == 'AP001' or $status == "AP002")
							{
								if($status == 'AP001')
									$this->ModelSubscription->updateSubscription(array('disbursalDate' => date('Y-m-d H:i:s')), array("appId" => $appId));	
								$doc = json_decode(str_replace('\\','',$remark),true);
								$imgPath = './img/subscriptionDoc/';
								foreach($doc as $k => $v)
								{
									if(trim($v) != ''){
										if(file_get_contents($v))
										{
											$docName = date('YmdHis').$isApplicationExits[0]['id'].$k.'.pdf';
											file_put_contents($imgPath.$docName,file_get_contents($v));
											$file = file($imgPath.$docName);
											$endfile= $file[count($file) - 1];
											$n="%%EOF";
											if (trim($endfile) === $n) 
											{		
												$docData = array("subscriptionId" => $isApplicationExits[0]['id'], "docType" => "link", "label" => $k, "docData" =>  BASE_URL."img/subscriptionDoc/".$docName);
					                            $this -> ModelSubscription -> addSubscriptionDocument($docData);	
											}
											else{
												$output = array("status" => "Error", "msg" => "Invalid Documentss", 'timeStamp' => date('Y-m-d H:i:s'));
												//$this -> ModelUtility -> saveLog($apiName, $input, $output);
												$this->LogManager->logApi($apiName, $input, $output);
												echo json_encode($output);
												$error = 1;
												//break;
											}
										}else{
											$output = array("status" => "Error", "msg" => "Invalid Documents", 'timeStamp' => date('Y-m-d H:i:s'));
											//$this -> ModelUtility -> saveLog($apiName, $input, $output);
											$this->LogManager->logApi($apiName, $input, $output);
											echo json_encode($output);
											$error = 1;
											break;
										}
									}
								}
							}else if($status == 'AF001'){
								$this -> ModelCustomer -> blockCustomer($subscriptionData[0]['customerId'], 30);
							}else if($status == 'AM001'){
								$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($subscriptionData[0]['customerId'], 'In-Progress');
							}else if($status == 'AA001'){
								$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($subscriptionData[0]['customerId'], 'Active');
							}else if($status == 'AC001'){
								$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($subscriptionData[0]['customerId'], 'lead');
								if($remarkChar == '1')
									$this -> ModelCustomer -> blockCustomer($subscriptionData[0]['customerId'], 30);
							}else if($status == 'AC002'){
								if($remarkChar == '1')
									$this -> ModelCustomer -> blockCustomer($subscriptionData[0]['customerId'], 30);
								if($remarkChar == '2')
									$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($subscriptionData[0]['customerId'], 'lead');
							}else if($status == 'AH001'){
								$doc = explode(';', $remark);
								$newRemark = '';
								foreach($doc as $docu){
									$rem = explode(':',$docu);
									$docu = $rem[0];
									$newRemark .= $docu.'-'.$rem[1].'; ';
									$this-> ModelSubscription->unsyncDoc($isApplicationExits[0]['id'], $docu);
								}
								$remark = $newRemark;
							}
							if ($error == 0){
								$res =  $this->applicationStatus->updateApplicationStatus($appId, $allStatus[$status], $remark);
								if($res == 1)
								{
									
									addSubscriptionAudit($isApplicationExits[0]['id'], 0, 'ocb', $statusName, $remark);
									$output = array("status" => "Success",  'timeStamp' => date('Y-m-d H:i:s'));
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output);
								}
								else
								{
									$output = array("status" => "Error", "msg" => "Invalid App ID", 'timeStamp' => date('Y-m-d H:i:s'));
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output);
								}
							}
						}else{
							$output = array("status" => "Error", "msg" => "Wrong application profile", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}
					}
					else
					{
						$output = array("status" => "Error", "msg" => "Invalid App Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Status", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}	
		}
		else
		{
			$arr = array("Errors"=>validation_errors());
			if($arr)
			{	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
				//print_r($arr);
			}
		}	
	//echo true;
	}
	
}
