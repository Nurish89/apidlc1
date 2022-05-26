<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('applicationStatus');
		$this->load->model('ModelUtility');
		$this->load->model('ModelProgram');
		$this->load->model('ModelStore');
		$this->load->model('ModelProduct');
		$this->load->library('form_validation');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}
	
	public function getProductList()
	{
		$input = $this->input->post();
		$apiName = 'getProductList'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program Id", "required");
		$this->form_validation->set_rules("storeId", "Store Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');
			$storeId = $this->input->post('storeId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$getProgram = $this -> ModelProgram->getProgram($programId);

				if($getProgram)
				{
					$getStore = $this->ModelStore->getStore($storeId);
					if($getStore)
					{
						$res =  $this->ModelProduct->getProductbyProgram($programId);
						if($res)
						{
							$baseicFormData = '';
							if($bfd = $this->ModelProgram->getProgramForm($programId, 'basic'))
								$basicFormData = json_decode($bfd['data'][0]['formData'],true);
							
							$output = array("status" => "Success",  'timeStamp' => date('Y-m-d H:i:s'), "deviceList" => $res, "validationForm" => $basicFormData, "pdpa" => "This is an example for customer to agree to personal data protection agreement.");
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}
						else
						{
							$output = array("status" => "Error", "msg" => "No data found", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output);
						}
					}
					else
					{
						$output = array("status" => "Error", "msg" => "Invalid Store Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Program Id", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getProductDescription()
	{
		$input = $this->input->post();
		$apiName = 'getProductList'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("sku", "sku", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$sku = $this->input->post('sku');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$getProduct = $this -> ModelProduct->getProductDetailBySKU($sku);
				if($getProduct)
				{
					$image = $getProduct[0]['image'];
					if($getProduct[0]['isRelative'] == 1)
						$image = CRM_URL.$image;
					$p = $getProduct[0];
					$data = array("productName" => $p['name'], "image" => $image, "capacity" => $p['rom'].$p['romUnit'].'/'.$p['ram'].$p['ramUnit'], "color" => $p['color'], "tenure" => $p['tenure'], "devicePrice"=> $p['drp'], "downpayment" => $p['dpv'], "monthlyPayment" => $p['dmof'], "fincoResidualValue" => $p['drv']);
					$output = array("status" => "Success",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => $data);
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid SKU", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getModelByProgram()
	{
		$input = $this->input->post();
		$apiName = 'getModelByProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$getProgram = $this -> ModelProgram->getProgram($programId);
				if($getProgram)
				{
					$getModel = $this->ModelProduct->getModelByProgram($programId);
					if($getModel)
					{
						for($i=0;$i<sizeof($getModel);$i++){
							if($getModel[$i]['isRelative'] == 1)
								$getModel[$i]['image'] = CRM_URL.$getModel[$i]['image'];
							if($getModel[$i]['brandisRelative'] == 1)
								$getModel[$i]['brandImage'] = CRM_URL.$getModel[$i]['brandImage'];
						}	
						$output = array("status" => "Suceess", "msg" => $getModel, 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else
					{
						$output = array("status" => "Error", "msg" => "No Data found", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Program Id", 'timeStamp' => date('Y-m-d H:i:s'));
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
	}

	public function getModelDetailByProgram()
	{
		$input = $this->input->post();
		$apiName = 'getModelDetailByProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program Id", "required");
		$this->form_validation->set_rules("model", "Model", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');
			$model = $this->input->post('model');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$getProgram = $this -> ModelProgram->getProgram($programId);
				if($getProgram)
				{
					$getModel = $this->ModelProduct->getModelDetailByProgram($programId, $model);
					if($getModel)
					{	
						$capacity = array();
						//print_r($getModel);
						for($i=0;$i<sizeof($getModel);$i++){
							$d = $getModel[$i];
							$cpStr =  trim($d['ram']).trim($d['ramUnit']).'/'.trim($d['rom']).trim($d['romUnit']);
							$kc = trim($d['ram']).trim($d['ramUnit']).trim($d['rom']).trim($d['romUnit']);
							$getModel[$i]['capacity'] = $cpStr;
							$getModel[$i]['capacityKey'] = $kc;
							if($getModel[$i]['isRelative'] == 1)
								$getModel[$i]['image'] = CRM_URL.$getModel[$i]['image'];
							$capacity[$kc][] = $getModel[$i];
						}
					
						foreach($capacity as $k => $v){
							for($j=0;$j<sizeof($v);$j++){
								$cc[$k][$v[$j]['color']][] = $v[$j];
							}
						}
		
						$capacity = array();
						foreach($cc as $k => $v){
							$color = array();
							foreach($v as $vk => $vv){
							$tenure = array();
								for($i=0;$i<sizeof($vv);$i++){
									$tenure[]  = array("name" => $vv[$i]['tenure'].' Months', "sku" => $vv[$i]["sku"], "partnerSKU" => $vv[$i]["partnerSKU"], "productId" => $vv[$i]["productId"], "deviceImage" => $vv[$i]['image'], "tenure" => $vv[$i]["tenure"], "suw" => $vv[$i]["suw"], "suwUnit" => $vv[$i]["suwUnit"], "euw" => $vv[$i]["euw"], "euwUnit" => $vv[$i]["euwUnit"], "drp" => $vv[$i]["drp"], "cadc" => $vv[$i]["cadc"], "capf" => $vv[$i]["capf"], "camrf" => $vv[$i]["camrf"], "dt" => $vv[$i]["dt"], "cadm" => $vv[$i]["cadm"], "dmf" => $vv[$i]["dmf"], "cpf" => $vv[$i]["cpf"], "fdc" => $vv[$i]["fdc"], "dpv" => $vv[$i]["dpv"], "dpvt" => $vv[$i]["dpvt"], "drv" => $vv[$i]["drv"], "fsv" => $vv[$i]["fsv"], "dmof" => $vv[$i]["dmof"]);
								}
								$color[] = array("name" =>$vv[$i-1]['color'], "tenure"=>$tenure);	
							}
							$capacity[] = array("name"=>$vv[$i-1]['capacity'], "color"=>$color);  
						}
						$data = array("model"=>$model, "features" => array("capacity"=> $capacity));
						$output = array("status" => "success", "msg" => $data, 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
					else
					{
						$output = array("status" => "Error", "msg" => "No Data found", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Program Id", 'timeStamp' => date('Y-m-d H:i:s'));
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

