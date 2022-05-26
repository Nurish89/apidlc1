<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Finco extends CI_Controller {

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
		$this->load->model('ModelFinco');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('utility');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function getFincoList()
	{
		$input = $this->input->post();
		$apiName = 'getFincoList'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		if ($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$fincoData = $this->ModelFinco->getFinco(array());
				if($fincoData)
				{
					$output = array("status" => "Success", "msg" => $fincoData);
					//$this->ModelUtility->saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$output = array("status" => "Error", "msg" => "No Data Found", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function deleteFinco()
	{
		$input = $this->input->post();
		$apiName = 'getFincoList'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("userId", "userId", "required");
		$this->form_validation->set_rules("fincoId", "fincoId", "required");
		if ($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$fincoId = $this->input->post('fincoId');
				$userId = $this->input->post('userId');
				$fincoData = $this->ModelFinco->getFinco(array("id" => $fincoId));
				if($fincoData)
				{
					$updateResponse = $this->ModelFinco->updateFinco(array("isDeleted" => "1", "modifiedBy" => $userId, "modifiedDate" => date('Y-m-d H:i:s')), array("id" => $fincoId));
					$output = array("status" => "Success", "msg" => $updateResponse);
					//$this->ModelUtility->saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid Finco Id", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function activeFinco()
	{
		$input = $this->input->post();
		$apiName = 'getFincoList'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("userId", "userId", "required");
		$this->form_validation->set_rules("fincoId", "fincoId", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$fincoId = $this->input->post('fincoId');
				$userId = $this->input->post('userId');
				$fincoData = $this->ModelFinco->getFinco(array("id" => $fincoId));
				if($fincoData)
				{
					$updateResponse = $this->ModelFinco->updateFinco(array("isDeleted" => "0", "modifiedBy" => $userId, "modifiedDate" => date('Y-m-d H:i:s')), array("id" => $fincoId));
					$output = array("status" => "Success", "msg" => $updateResponse);
					//$this->ModelUtility->saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid Finco Id", 'timeStamp' => date('Y-m-d H:i:s'));
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
		else
		{
			$arr = array("Errors"=>validation_errors());
			if($arr){	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}	
	}

	public function addFinco()
	{
		$input = $this->input->post();
		$apiName = 'addFinco'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("userId", "userId", "required");
		$this->form_validation->set_rules("name", "Finco Name", "required");
		$this->form_validation->set_rules("address", "Registered Address", "required");
		$this->form_validation->set_rules("language", "Language", "required");
		$this->form_validation->set_rules("currency", "Currency", "required");
		$this->form_validation->set_rules("country", "Country", "required");
		if ($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$userId = $this->input->post('userId');
				$name = $this->input->post('name');
				$fincoData = $this->ModelFinco->getFinco(array("name" => $name));
				if($fincoData)
				{
					$output = array("status" => "Error", "msg" => "Finco already exist");
					//$this->ModelUtility->saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$country = $this->input->post('country');
					$language = $this->input->post('language');
					$currency = $this->input->post('currency');
					$address = $this->input->post('address');
					if(!isset($_FILES['logo']))
					{
						$output = array("status" => "Error", "msg" => "Logo Not found");
						//$this->ModelUtility->saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else{ 
						$imgPath = './img/fincoLogo/';
						$file_tmp = $_FILES['logo']['tmp_name'];
						$base64Data = $this->getBase64FromFileData($file_tmp);
						$fileName = date('YmdHis').$name.'.jpg';
						file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$logo = $fileName;
					
						$addResponse = $this->ModelFinco->addFinco(array("name" => $name, "address" => $address, "country" => $country, "language" => $language, "currency" => $currency, "userId" => $userId, "logo" =>$logo));
						if($addResponse){
							$output = array("status" => "Success", "msg" => "Finco added successfully", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}
						else{
							$output = array("status" => "Error", "msg" => "Something went wrong", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}
					}
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

	public function editFinco()
	{
		$input = $this->input->post();
		$apiName = 'editFinco'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("userId", "userId", "required");
		$this->form_validation->set_rules("fincoId", "Finco Id", "required");
		$this->form_validation->set_rules("name", "Finco Name", "required");
		$this->form_validation->set_rules("address", "Registered Address", "required");
		$this->form_validation->set_rules("language", "Language", "required");
		$this->form_validation->set_rules("currency", "Currency", "required");
		$this->form_validation->set_rules("country", "Country", "required");
		if ($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$userId = $this->input->post('userId');
				$fincoId = $this->input->post('fincoId');
				$fincoData = $this->ModelFinco->getFinco(array("id" => $fincoId));
				if(!$fincoData)
				{
					$output = array("status" => "Error", "msg" => "Invalid Finco");
					//$this->ModelUtility->saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$name = $this->input->post('name');
					$country = $this->input->post('country');
					$language = $this->input->post('language');
					$currency = $this->input->post('currency');
					$address = $this->input->post('address');
					if(!isset($_FILES['logo']))
					{
						$output = array("status" => "Error", "msg" => "Logo Not found");
						//$this->ModelUtility->saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else{ 
						$imgPath = './img/fincoLogo/';
						$file_tmp = $_FILES['logo']['tmp_name'];
						$base64Data = $this->getBase64FromFileData($file_tmp);
						$fileName = date('YmdHis').$name.'.jpg';
						file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$logo = $fileName;
						$updateResponse = $this->ModelFinco->updateFinco(array("name" => $name, "registeredAddress" => $address, "country" => $country, "language" => $language, "currency" => $currency, "logo" =>$logo, "modifiedBy" => $userId, "modifiedDate" => date('Y-m-d H:i:s')), array("id" => $fincoId));
						if($updateResponse)
						{
							$output = array("status" => "Success", "msg" => "Finco updated successfully", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}
						else
						{
							$output = array("status" => "Error", "msg" => "Something went wrong", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}
					}
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

	public function getBase64FromFileData($file_tmp)
	{
		$type = pathinfo($file_tmp, PATHINFO_EXTENSION);
		$data = file_get_contents($file_tmp);
		return $base64 = base64_encode($data);
		//return $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
