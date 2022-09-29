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
		$this->form_validation->set_rules("staffEmail", "Email", "required");
		$this->form_validation->set_rules("staffPassword", "Password", "required");
		
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$staffEmail = $this->input->post('staffEmail');
			$staffPassword = $this->input->post('staffPassword');
		
			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			if($isAuthenticate)
			{			
				$eData = array("email" => $staffEmail);
				$res =  $this->ModelEmployee->getEmployeeByData($eData);
				//print_r($res); exit();
				if($res)
				{
					if (password_verify($staffPassword, $res[0]['password']))
					{
						$output = array("status" => "Success", "msg" => "Login successfully", "employeeId" => $res[0]['id'], "programId" => $res[0]['programId'], "storeId" => $res[0]['channelStoreId']);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else{
						$output = array("status" => "Error", "msg" => "Invalid Password", 'timeStamp' => date('Y-m-d H:i:s'));			
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid credentials", 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}	
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
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
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}	
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

	//Nurish : Multi Country ---------------------------------------------------------------------
	public function employeeSetPassword()
	{
		$input = $this->input->post();
		$apiName = 'Employee Set Password'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("password", "Password", "required");
		$this->form_validation->set_rules("cpassword", "Confirm Password", "required");
		$this->form_validation->set_rules("token", "Unique token", "required");

		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$password = $this->input->post('password');
			$cpassword = $this->input->post('cpassword');

			if($password != $cpassword){
				$output = array("status" => "Error", "msg" => "The password confirmation deos not match", 'timeStamp' => date('Y-m-d H:i:s'));
				echo json_encode($output);
			}
			else{
				$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);
				if($isAuthenticate)
				{
					$token = $this->input->post('token');
					$getForgotData = $this->ModelEmployee->getStaffByToken($token);
					if($getForgotData)
					{
						$eDate = $getForgotData[0]['expiryDate'];
						if($eDate > date('Y-m-d H:i:s'))
						{
							$staffId = $getForgotData[0]['staffId'];
							$password = $this->input->post('password');
							$passwordData = $this->encrypt($password);
							$isUpdated= $this->ModelEmployee->setUserPassword($staffId, $passwordData['encrypted'], $passwordData['salt']);
							if($isUpdated)
							{
								$this->ModelEmployee->updateForgotToken($token);
								$output = array("status" => "Success", "msg" => "Password updated successfully.", 'timeStamp' => date('Y-m-d H:i:s'));
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output);
							}
							else
							{
								$output = array("status" => "Error", "msg" => "Somthing went wrong when updating data", 'timeStamp' => date('Y-m-d H:i:s'));
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output);
							}	
						}
						else{
							$output = array("status" => "Error", "msg" => "Token expired", 'timeStamp' => date('Y-m-d H:i:s'));
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}	
					}
					else{
						$output = array("status" => "Error", "msg" => "Invalid token", 'timeStamp' => date('Y-m-d H:i:s'));
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
			}
		}
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr)
			{	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
	}

	public function encrypt($password)
	{
		$encrypted = password_hash($password, PASSWORD_DEFAULT);
		$hash = array("salt" => '', "encrypted" => $encrypted);
		return $hash;
	}

	public function getIsUpgradeProgram()
	{
		$input = $this->input->post();
		$apiName = 'getIsUpgradeProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");

		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');

			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			if($isAuthenticate)
			{
				$getProgram = $this->ModelProgram->getProgram($programId);
				//print_r($getProgram); die();
				if($getProgram)
				{					
					$isUpgradeProgram = $getProgram[0]['isUpgradeProgram'];
					$output = array("status" => "Success", "flag" => $isUpgradeProgram, 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);					
				}
				else{
					$output = array("status" => "Error", "msg" => "Token expired", 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}	
			}
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
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
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
	}

	//--------------------------------------------------------------------------------------
}
