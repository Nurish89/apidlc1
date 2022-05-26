<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('applicationStatus');
		$this->load->model('ModelUtility');
		$this->load->model('ModelProgram');
		$this->load->model('ModelStore');
		$this->load->model('ModelProduct');
		$this->load->model('ModelProgram');
		$this->load->library('form_validation');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}
	
	public function getFormDataByProgram()
	{
		$input = $this->input->post();
		$apiName = 'getFormDataByProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program Id", "required");
		$this->form_validation->set_rules("phase", "Phase", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');
			$phase = $this->input->post('phase');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$getProgram = $this -> ModelProgram->getProgram($programId);

				if($getProgram){
					$getFormData = $this->ModelProgram->getFormData(array("programId" => $programId, "phase" => $phase, "isDeleted" => 0));
					if($getFormData){
						$basicFormData = json_decode($getFormData['data'][0]['data'],true);
						$output = array("status" => "Success",  'timeStamp' => date('Y-m-d H:i:s'), "form" => $basicFormData);
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else{
						$output = array("status" => "Error", "msg" => "Invalid Store Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid Program Id", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
			}
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}	
		}
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr){
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
				//print_r($arr);
			}
		}	
	//echo true;
	}

	public function getFAQByProgram()
	{
		$input = $this->input->post();
		$apiName = 'getFAQByProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "programId", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$programId = $this->input->post('programId');
				$programData = $this->ModelProgram->getDocuments(array("programId" => $programId, "visibleName" => "faq", "isDeleted" => "0"));
				if($programData){
					if($programData['count'] > 0){
						$vd = $programData['data'];
						$output = array("status" => "Success", "msg" => $vd);
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else{
						$output = array("status" => "Error", "msg" => "No Data", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else{
					$output = array("status" => "Error", "msg" => "No Data", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
			}
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}	
		}
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr){	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}	
	}

}

