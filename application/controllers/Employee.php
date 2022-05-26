<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('applicationStatus');
		$this->load->model('ModelUtility');
		$this->load->model('ModelProgram');
		$this->load->model('ModelStore');
		$this->load->model('ModelProduct');
		$this->load->model('ModelEmployee');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('utility');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function employeeLoginAll()
	{
		$input = $this->input->post();
		$apiName = 'employeeLoginAll'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("userId", "userId", "required");
		$this->form_validation->set_rules("staffName", "Staff Name", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$userId = $this->input->post('userId');
			$staffName = $this->input->post('staffName');
			
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				//$eData = array("fullName" => $staffName, "idCard" => $userId);
				$eData = array("fullName" => $staffName, "caid" => $userId);
				$res =  $this->ModelEmployee->getEmployeeByData($eData);
				if($res){
					$storeId = 0;
					$programId = $res[0]['programId'];
					$output = array("status" => "Success", "msg" => "Login successfully", "employeeId" => $res[0]['id'], "storeId" => $storeId, "programId" => $programId);
					//$this->ModelUtility->saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}else{
					$output = array("status" => "Error", "msg" => "Invalid credentials", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);	
				}
			}else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}	
		}else{
			$arr = array("Errors"=>validation_errors());
			if($arr){
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
				//print_r($arr);
			}
		}	
	}

	public function employeeLogin()
	{
		$input = $this->input->post();
		$apiName = 'employeeLogin'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("userId", "userId", "required");
		$this->form_validation->set_rules("staffName", "Staff Name", "required");
		$this->form_validation->set_rules("storeId", "Store Code", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$userId = $this->input->post('userId');
			$staffName = $this->input->post('staffName');
			$storeCode = trim(explode('-',$this->input->post('storeId'))[0]);
		
			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);
			if($isAuthenticate)
			{
				$storeId = 0;
				$getStore = $this->ModelStore->getStoreByCode($storeCode);
				if($getStore)
				{
					//$eData = array("fullName" => $staffName, "idCard" => $userId);
					$eData = array("fullName" => $staffName, "caid" => $userId);
					$res =  $this->ModelEmployee->getEmployeeByData($eData);
					if($res)
					{
						$fincoId = 1;
						$storeId = $getStore[0]['id'];
						$programData = $this->ModelProgram->getProgramByStoreFinco($storeId, $fincoId);
						//$this->ModelEmployee->setEmployeeOTP($res[0]['id'], $newOTP, $getStore[0]['id'], $fincoId, $programData[0]['id']);
						$output = array("status" => "Success", "msg" => "Login successfully1", "employeeId" => $res[0]['id'], "storeId" => $storeId, "programId" => $programData[0]['id']);
						//$this->ModelUtility->saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else
					{
						$empData = getEmployees();
						if($empData['status'] =='Success'){
							for($i=0;$i<sizeof($empData['msg']);$i++)
								$this->ModelEmployee->addEmployee(array("userId"=>$empData['msg'][$i]['userId'], "fullName"=>$empData['msg'][$i]['fullName'], "idCard"=>$empData['msg'][$i]['idCard']));
							$res =  $this->ModelEmployee->getEmployeeByData($eData);
							if($res)
							{
								$fincoId = 1;
								$storeId = $getStore[0]['id'];
								$programData = $this->ModelProgram->getProgramByStoreFinco($storeId, $fincoId);
								//$this->ModelEmployee->setEmployeeOTP($res[0]['id'], $newOTP, $getStore[0]['id'], $fincoId, $programData[0]['id']);
								$output = array("status" => "Success", "msg" => "Login successfully2", "employeeId" => $res[0]['id'], "storeId" => $storeId, "programId" => $programData[0]['id']);
								$this->LogManager->logApi($apiName, $input, $output);
								//$this->ModelUtility->saveLog($apiName, $input, $output);
								echo json_encode($output);
							}
							else
							{
								$output = array("status" => "Error", "msg" => "Invalid credentials", 'timeStamp' => date('Y-m-d H:i:s'));
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output);
							}
						}
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid credentials", 'timeStamp' => date('Y-m-d H:i:s'));
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

	
	public function employeeLoginWithOTP()
	{
		$input = $this->input->post();
		$apiName = 'employeeLogin'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("staffId", "Staff Id", "required");
		$this->form_validation->set_rules("otp", "OTP", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$staffId = $this->input->post('staffId');
			$otp = $this->input->post('otp');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$checkOTP = $this->ModelEmployee->checkStaffOTP($staffId, $otp);
				if($checkOTP)
				{
					$output = array("status" => "Success", "msg" => "Login successfully", "employeeId" => $checkOTP[0]['id'], "storeId" => $checkOTP[0]['channelPartnerId'], "programId" => $checkOTP[0]['programId']);
					//$this->ModelUtility->saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid OTP", 'timeStamp' => date('Y-m-d H:i:s'));
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
