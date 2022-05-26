<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mafc extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		header('Access-Control-Allow-Origin: *');
		$this->load->model('applicationStatus');
		$this->load->model('ModelUtility');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('mafchelper');
		$this->load->helper('utility');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function getProvince()
	{
		$input = $this->input->post();
		$apiName = 'getProvince'; 
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
				$provinceData = array();
                $provinceDataRow =  mafcMasterData('getCity')['msg'];
				for($i=0;$i<sizeof($provinceDataRow);$i++)
					$provinceData[] = array("id"=>$provinceDataRow[$i]['stateid'], "name" => $provinceDataRow[$i]['statedesc']);
                                
                $output = array("status" => "Success", "msg" => $provinceData);
                //$this->ModelUtility->saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
                echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}	
		}else{
			$arr = array("Errors"=>validation_errors());
			if($arr)
			{	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
		}	
	}

	public function getDistrict()
	{
		$input = $this->input->post();
		$apiName = 'getDistrict'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		//$this->form_validation->set_rules("provinceId", "Province Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			
			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$cityData = array();
                $cityDataRow =  mafcMasterData('getDistrict')['msg'];
				for($i=0;$i<sizeof($cityDataRow);$i++)
						$cityData[] = array("id"=>$cityDataRow[$i]['lmc_CITYID_C'], "name" => $cityDataRow[$i]['lmc_CITYNAME_C']);
				
                $output = array("status" => "Success", "msg" => $cityData);
                //$this->ModelUtility->saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
                echo json_encode($output, JSON_UNESCAPED_UNICODE);
            }
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}	
		}
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr){	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
		}	
	}

	public function getWard()
	{
		$input = $this->input->post();
		$apiName = 'getWard'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			
			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$wardData = array();
                $wardDataRow =  mafcMasterData('getWard')['msg'];
				for($i=0;$i<sizeof($wardDataRow);$i++)
                    $wardData[] = array("id"=>$wardDataRow[$i]['zipcode'], "name" => $wardDataRow[$i]['zipdesc']." (".$wardDataRow[$i]['zipcode'].")");			
                $output = array("status" => "Success", "msg" => $wardData);
                //$this->ModelUtility->saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
                echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}	
		}
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr){	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
		}	
	}

}
