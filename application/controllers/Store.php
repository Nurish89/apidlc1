<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		header('Access-Control-Allow-Origin: *');
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

	public function getStoreCode()
	{
		$input = $this->input->post();
		$apiName = 'getStoreCode'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$getStore = $this->ModelStore->getStore();
				if($getStore)
				{
					$codeArray = array();
					for($i=0;$i<sizeof($getStore);$i++)
						$codeArray[] = $getStore[$i]['storeCode'].' - '.$getStore[$i]['name'];
	                                $output = array("status" => "Success", "msg" => $codeArray);
                                    //$this->ModelUtility->saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
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
			}
		}	
	}

	public function getStoreByProgram()
	{
		$input = $this->input->post();
		$apiName = 'getStoreByProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "programId", "required");
		if ($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$programId = $this->input->post('programId');
				$getStore = $this->ModelStore->getStoreByProgram($programId);
				if($getStore)
				{
					$codeArray = array();
					for($i=0;$i<sizeof($getStore);$i++)
						$codeArray[] = $getStore[$i]['storeCode'].' - '.$getStore[$i]['name'];
					$output = array("status" => "Success", "msg" => $codeArray);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid credentials", 'timeStamp' => date('Y-m-d H:i:s'));
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
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr){
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}	
	}

	//Nurish : Multicountry-------------------------------------------------------

	public function getStaffProgramDetails()
	{
		$input = $this->input->post();
		$apiName = 'getStoreByProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "programId", "required");
		if ($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$programId = $this->input->post('programId');
				$getStore = $this->ModelStore->getStaffProgramDetails($programId);
				if($getStore)
				{
					$output = array("status" => "Success", "msg" => $getStore);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid credentials", 'timeStamp' => date('Y-m-d H:i:s'));
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
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr){
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}	
	}

	//--------------------------------------------------------------------------------

}
