<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment extends CI_Controller {

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
		$this->load->model('ModelPayment');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('utility');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function getDownpaymentMode()
	{
		$input = $this->input->post();
		$apiName = 'getDownpaymentMode'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$programId = $this->input->post('programId');
				$getProgram = $this->ModelProgram->getProgram($programId);
				if($getProgram)
				{
					$paymentModes = $this->ModelPayment->getDownpaymentMode($programId);
					if($paymentModes)
					{
						$data = array();
						for($i=0;$i<sizeof($paymentModes);$i++)
							$data[] = $paymentModes[$i]['paymentType'];
		                                $output = array("status" => "Success", "msg" => $data, "timeStamp" => date('Y-m-d H:i:s'));
	                                        //$this->ModelUtility->saveLog($apiName, $input, $output);
											$this->LogManager->logApi($apiName, $input, $output);
                	                        echo json_encode($output);
					}
					else
					{
		                                $output = array("status" => "Success", "msg" => "No Data Found", "timeStamp" => date('Y-m-d H:i:s'));
	                                        //$this->ModelUtility->saveLog($apiName, $input, $output);
											$this->LogManager->logApi($apiName, $input, $output);
                	                        echo json_encode($output);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Program", 'timeStamp' => date('Y-m-d H:i:s'));
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

}
