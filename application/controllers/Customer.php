<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		header('Access-Control-Allow-Origin: *');
		$this->load->model('applicationStatus');
		$this->load->model('ModelUtility');
		$this->load->model('ModelProgram');
		$this->load->model('ModelStore');
		$this->load->model('ModelProduct');
		$this->load->model('ModelCustomer');
		$this->load->model('ModelSubscription');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('utility_helper');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function createApp(){
		echo $appId = 100009;
		$data = $this->ModelSubscription->getSubscriptionById($appId)[0]['appInfo'];
		$d =$this->createApplicationOCB($data);
		print_r($d);
	}

	public function getBase64FromFileData($file_tmp)
	{
		$type = pathinfo($file_tmp, PATHINFO_EXTENSION);
		$data = file_get_contents($file_tmp);
		return $base64 = base64_encode($data);
		//return $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
	}

	public function test()
	{
		//print_r(getAllProvince()['msg']);	
		$provinceDataRow =  json_decode(getAllCity()['msg'],true);
		print_r($provinceDataRow);

	}

	public function addCustomerWeb()
    {
		$input = $this->input->post();
		$apiName = 'addCustomerWeb';
		$this->form_validation->set_rules("userName", "Api User Name", "required");
		$this->form_validation->set_rules("apiKey", "Api Key", "required");
		$this->form_validation->set_rules("nationality", "nationality", "required");
		$this->form_validation->set_rules("occupation", "occupation", "required");
		$this->form_validation->set_rules("operator", "operator", "required");
		if($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			//print_r($_FILES);
			if($isAuthenticate)
			{			
				$invalidOccupation = array('Sinh viên', 'Về hưu', 'Làm việc tự do', 'Nội trợ', 'Công nhân thời vụ', 'Lao động tự do', 'Lao động tại Hộ kinh doanh');
				//$customerRowData = json_decode($userData, true);
				$today = date("Y-m-d");
				$date = DateTime::createFromFormat('d/m/Y',$this->input->post('dob'));
				$dobT = $date->format('Y-m-d');
				$dob = $this->input->post('dob');
				//$dob = date('Y-m-d', strtotime(str_replace('/', '-',$this->input->post('dob')))); 
				$diff = date_diff(date_create($dobT), date_create($today));
				$age = (int)$diff->format('%y');
				if($age < 22 or $age > 56)
				{
					$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "We only allow people who are 22-55 to sign up for this program.");
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}

				if(in_array($this->input->post('occupation'), $invalidOccupation))
				{
					$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "Non-eligible occupation");
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}

				if($this->input->post('nationality') != 'Việt Nam'){
					$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "Non-eligible nationality");
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
		
				$icPictureBack = $icPictureFront = '';
				if($this->input->post('icPictureBack') != '')
				{
					$icPictureBack = $this->input->post('icPictureBack'); 
				}
				else
				{	
					if(isset($_FILES['icPictureBack'])){		
						$file_tmp = $_FILES['icPictureBack']['tmp_name'];
						$icPictureBack = $this->getBase64FromFileData($file_tmp);
					}
				}

				if($this->input->post('icPictureFront') != '')
				{
					$icPictureFront = $this->input->post('icPictureFront');
				}
				else
				{
					if(isset($_FILES['icPictureFront'])){
						$file_tmp = $_FILES['icPictureFront']['tmp_name'];
						$icPictureFront = $this->getBase64FromFileData($file_tmp);
					}
				}	

				$caAddress = json_encode(array("caHNumber" => $this->input->post('hNumber'), "caHStreet"=> $this->input->post('hStreet'), "caHregion" => $this->input->post('hRegion'), "caStreet" => $this->input->post('ward'), "caCity" => $this->input->post('district'), "caState" => $this->input->post('province'), "caCountry" => '', "caPostcode" => ''));
				$daAddress = json_encode(array("daHNumber" => $this->input->post('hNumber'), "daHStreet"=> $this->input->post('hStreet'), "daHregion" => $this->input->post('hRegion'),"daStreet" => $this->input->post('ward'), "daCity" => $this->input->post('district'), "daState" =>$this->input->post('province'), "daCountry" => '', "daPostcode" =>''));
				$cidJson = json_encode(array(
										"name" => htmlspecialchars($this->input->post('name')), 
										"mobileNumber" => $this->input->post('mobile'), 
										"dob" =>$dob, 
										"email" => $this->input->post('email'), 
										"caAddress" => htmlspecialchars($caAddress), 
										"daAddress" => htmlspecialchars($daAddress), 
										"icNumber" => $this->input->post('icNumber'), 
										"icPictureFront"=> $icPictureFront, 
										"icPictureBack" => $icPictureBack, 
										"docIssuedDate" => $this->input->post('icIssueDate'), 
										"occupation" => $this->input->post('occupation'), 
										"nationality" => $this->input->post('nationality'), 
										"telco" => $this->input->post('operator')));

				$data = "userName=".$userName."&apiKey=".$apiKey."&programId=".$this->input->post('programId')."&storeId=".$this->input->post('storeId')."&customerInfoData=".$cidJson."&pdpa=".$this->input->post('pdpa');
				//print_r($data);
				$url = BASE_URL.'Customer/onboardCustomer';
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				curl_close($ch);
				$output = json_decode($result,true);
				//$output = $result;
				//print_r($output);
				if($output['status'] == 'Success')
				{
					if($output['validationStatus'] == 'failed')
						$output['status'] = 'Fail';
				} 
				//$this->ModelUtility->saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid Api User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this->ModelUtility->saveLog($apiName, $input, $output);
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
				//$this->ModelUtility->saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
    }

	public function updateCustomerWeb()
    {
		$input = $this->input->post();
		$apiName = 'updateCustomertWeb';
		$this->form_validation->set_rules("userName", "Api User Name", "required");
		$this->form_validation->set_rules("apiKey", "Api Key", "required");
		$this->form_validation->set_rules("nationality", "nationality", "required");
		$this->form_validation->set_rules("occupation", "occupation", "required");
		$this->form_validation->set_rules("operator", "operator", "required");
		//print_r($_POST);
		if($this->form_validation->run())
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			//print_r($_FILES);
			if($isAuthenticate)
			{
				$invalidOccupation = array('Sinh viên', 'Về hưu', 'Làm việc tự do', 'Nội trợ', 'Công nhân thời vụ', 'Lao động tự do', 'Lao động tại Hộ kinh doanh');
				//$customerRowData = json_decode($userData, true);
				$today = date("Y-m-d");
				$dob = $this->input->post('dob');	
				$date = DateTime::createFromFormat('d/m/Y',$dob);
				$dobT = $date->format('Y-m-d');
				$dob = $this->input->post('dob');	
				//$dob = date('Y-m-d', strtotime(str_replace('/', '-',$this->input->post('dob')))); 
				
				$diff = date_diff(date_create($dobT), date_create($today));
				$age = (int)$diff->format('%y');
				if($age < 24 or $age > 65)
				{
					$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "We only allow people who are 24-64 to sign up for this program.");
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}

				if(in_array($this->input->post('occupation'), $invalidOccupation))
				{
					$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "Non-eligible occupation");
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}

				if($this->input->post('nationality') != 'Việt Nam')
				{
					$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "Non-eligible nationality");
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
				
				$icPictureBack = $icPictureFront = '';
				if($this->input->post('icPictureBack') != ''){
					$icPictureBack = $this->input->post('icPictureBack'); 
				}
				else
				{	
					if(isset($_FILES['icPictureBack']))
					{		
						$file_tmp = $_FILES['icPictureBack']['tmp_name'];
						$icPictureBack = $this->getBase64FromFileData($file_tmp);
					}
				}

				if($this->input->post('icPictureFront') != '')
				{
					$icPictureFront = $this->input->post('icPictureFront');
				}
				else
				{
					if(isset($_FILES['icPictureFront']))
					{
						$file_tmp = $_FILES['icPictureFront']['tmp_name'];
						$icPictureFront = $this->getBase64FromFileData($file_tmp);
					}
				}	

				$caAddress = json_encode(array("caHNumber" => $this->input->post('hNumber'), "caHStreet"=> $this->input->post('hStreet'), "caHregion" => $this->input->post('hRegion'), "caStreet" => $this->input->post('ward'), "caCity" => $this->input->post('district'), "caState" => $this->input->post('province'), "caCountry" => '', "caPostcode" => ''));
				$daAddress = json_encode(array("daHNumber" => $this->input->post('hNumber'), "daHStreet"=> $this->input->post('hStreet'), "daHregion" => $this->input->post('hRegion'),"daStreet" => $this->input->post('ward'), "daCity" => $this->input->post('district'), "daState" =>$this->input->post('province'), "daCountry" => '', "daPostcode" =>''));
				
				$cidJson = json_encode(array(
					"name" => htmlspecialchars($this->input->post('name')), 
					"mobileNumber" => $this->input->post('mobile'), 
					"dob" =>$dob, "email" => $this->input->post('email'), 
					"caAddress" => htmlspecialchars($caAddress), 
					"daAddress" => htmlspecialchars($daAddress), 
					"icNumber" => $this->input->post('icNumber'), 
					"icPictureFront"=> $icPictureFront, 
					"icPictureBack" => $icPictureBack, 
					"docIssuedDate" => $this->input->post('icIssueDate'), 
					"occupation" => $this->input->post('occupation'), 
					"nationality" => $this->input->post('nationality'), 
					"telco" => $this->input->post('operator')));

				$data = "userName=".$userName."&apiKey=".$apiKey."&programId=".$this->input->post('programId')."&storeId=".$this->input->post('storeId')."&customerInfoData=".$cidJson."&pdpa=".$this->input->post('pdpa');
                //print_r($data);

				$url = BASE_URL.'Customer/updateCustomer';
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				curl_close($ch);
				$output = json_decode($result,true);
				//$output = $result;
				//print_r($output);
				/*if($output['status'] == 'Success'){
					if($output['validationStatus'] == 'failed')
						$output['status'] = 'Fail';
				} */
				//$this->ModelUtility->saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}	       
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid Api User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this->ModelUtility->saveLog($apiName, $input, $output);
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
				//$this->ModelUtility->saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
    }

	public function onboardCustomer()
	{
		$input = $this->input->post();
		$apiName = 'onboardCustomer'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("storeId", "Store Id", "required");
		$this->form_validation->set_rules("customerInfoData", "Customer Info Data", "required");
		$this->form_validation->set_rules("pdpa", "PDPA", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$storeId = $this->input->post('storeId');
			$pdpa = $this->input->post('pdpa');
			$userData = $this->input->post('customerInfoData');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){

				//Check PDPA Value
				if(strtolower($pdpa) == 'accepted')
				{
					//Check Store validity
					$getStore = $this->ModelStore->getStore($storeId);
					if($getStore)
					{
						$baseicFormData = '';
						if($bfd = $this->ModelProgram->getProgramForm('1', 'basic'))
							$basicFormData = json_decode($bfd['data'][0]['formData'],true);
						
						$isValidFormData = $this ->validateFormData($userData, $basicFormData);
						if($isValidFormData['status'] == 'Success')
						{
							$subscriptionId = 0;
							$customerRowData = json_decode($userData, true);
							$isCustomerExist =$this -> ModelCustomer -> getCustomer('docData', $customerRowData['icNumber']);
							if($isCustomerExist)
							{
								$customerId = $isCustomerExist['data']['0']['id'];
								if($this->ModelCustomer->isCustomerBlock($customerId))
								{
									$output = array("status" => "Success",  'timeStamp' => date('Y-m-d H:i:s'), "customerId" => $customerId, "validationStatus" => "failed", "newCustomer" => 0);
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output, JSON_UNESCAPED_UNICODE);
								}
								else
								{
									$subscriptionId = 0;
									$verificationCustomer = $this -> ModelCustomer ->getCustomerVerificationStatus($customerId, $storeId, 1);
									//$subData = $this->ModelSubscription->getSubscriptionByCustomerId($customerId);
									//if($subData){
										//print_r($subData);
										if($verificationCustomer){
											$verificationData = $verificationCustomer['data'][0];
											$customerVerificationStatus = $verificationData['status'];
											if(strtolower($customerVerificationStatus) != 'lead' and strtolower($customerVerificationStatus) != 'inactive' and strtolower($customerVerificationStatus) != 'failed')
												$subscriptionId = 1;
												//$subscriptionId = $subData[0]['id'];
										}
									/*	}else{
											$subscriptionId = $subData[0]['id'];
										}	
									}*/
									$status = 'failed';
									if($subscriptionId == 0){
										$verificationStatus = $this -> getCustomerVerificationStatusOCB($customerRowData['mobileNumber'], $customerRowData['icNumber']);	
										//$this -> ModelUtility -> saveLog('getCustomerVerificationStatusOCB', array($customerRowData['mobileNumber'], $customerRowData['icNumber']), $verificationStatus);
										$this->LogManager->logApi('getCustomerVerificationStatusOCB', array($customerRowData['mobileNumber'], $customerRowData['icNumber']), $verificationStatus);
										if($verificationStatus['status'] == 'Success')
										{
                	                        if($verificationStatus['msg'])
                        	                    $status = 'lead';
											$this -> ModelCustomer ->updateCustomerVerificationStatusByCustomerId($customerId, $status);
										}
										else
										{
											$this -> ModelCustomer ->updateCustomerVerificationStatusByCustomerId($customerId, $status);
										}
									}
									else
									{
										$status = 'lead';
									}	
									$output = array("status" => "Success",  'timeStamp' => date('Y-m-d H:i:s'),"subscriptionId"=>$subscriptionId, "customerId" => $customerId, "validationStatus" => $status, "subscriptionId"=> $subscriptionId, "newCustomer" => 0);
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output, JSON_UNESCAPED_UNICODE);
								}
							}
							else
							{
								$customerData =  array("channelPartnerStoreId" => $storeId, "name" => $customerRowData['name'], "mobileNumber" => $customerRowData['mobileNumber'], "email" => $customerRowData['email'], "dob" => $customerRowData['dob'], "caAddress" => $customerRowData['caAddress'], "daAddress" => $customerRowData['daAddress'], "icNumber" => $customerRowData['icNumber'], "nationality" => $customerRowData['nationality'], "occupation" => $customerRowData['occupation'], "docIssuedDate" => $customerRowData['docIssuedDate'], "telco" => $customerRowData['telco']);
								$customerId = $this -> ModelCustomer -> addCustomer($customerData);
								
								addCustomerAudit($customerId, 'customer', $customerId, 'New Customer', '');

								$imgPath = './img/customerDoc/';
								$fileName = date('YmdHis').$customerId.'icPictureFront.jpg';
								file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$customerRowData['icPictureFront'])));
								$docData = array("customerId" => $customerId, "docType" => "file", "label" => "icPictureFront", "docData" => BASE_URL."img/customerDoc/".$fileName);
								$this -> ModelCustomer -> addCustomerDocument($docData);

								$fileName = date('YmdHis').$customerId.'icPictureBack.jpg';
								file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$customerRowData['icPictureBack'])));
								$docData = array("customerId" => $customerId, "docType" => "file", "label" => "icPictureBack", "docData" => BASE_URL."img/customerDoc/".$fileName);
								$this -> ModelCustomer -> addCustomerDocument($docData);

								$verificationCustomer = $this -> ModelCustomer ->getCustomerVerificationStatus($customerId, $storeId, 1);
								$verificationStatus = $this -> getCustomerVerificationStatusOCB($customerRowData['mobileNumber'], $customerRowData['icNumber']);
								$status = 'failed';
								if($verificationStatus['status'] == 'Success')
								{
                                    if($verificationStatus['msg'])
                                        $status = 'lead';
								}
								else
								{
									$this -> ModelCustomer -> blockCustomer($customerId, 30);
								}
								$data = array("customerId" => $customerId, "storeId" => $storeId, "programId" => 1, "status" => $status);
								$customerVerificationId = $this -> ModelCustomer -> addCustomerVerification($data);
								if($customerVerificationId)
								{
									$output = array("status" => "Success",  'timeStamp' => date('Y-m-d H:i:s'), "customerId" => $customerId, "validationStatus" => $status, "subscriptionId" => $subscriptionId, "newCustomer" => 1);
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output, JSON_UNESCAPED_UNICODE);
								}
								else
								{
									$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "something went wrong");
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output, JSON_UNESCAPED_UNICODE);
										
								}		
							}
							
						}
						else
						{
							$output = $isValidFormData;
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
					}
					else
					{
						$output = array("status" => "Error", "msg" => "Invalid Store Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid PDPA value", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
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
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
				//print_r($arr);
			}
		}	
	//echo true;
	}

	public function updateCustomer()
	{
		$input = $this->input->post();
		$apiName = 'updateustomer'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("storeId", "Store Id", "required");
		$this->form_validation->set_rules("customerInfoData", "Customer Info Data", "required");
		$this->form_validation->set_rules("pdpa", "PDPA", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$storeId = $this->input->post('storeId');
			$pdpa = $this->input->post('pdpa');
			$userData = $this->input->post('customerInfoData');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				//Check PDPA Value
				if(strtolower($pdpa) == 'accepted')
				{
					//Check Store validity
					$getStore = $this->ModelStore->getStore($storeId);
					if($getStore)
					{
						$baseicFormData = '';
						if($bfd = $this->ModelProgram->getProgramForm('1', 'basic'))
							$basicFormData = json_decode($bfd['data'][0]['formData'],true);
						
						$isValidFormData = $this ->validateFormData($userData, $basicFormData);
						if($isValidFormData['status'] == 'Success')
						{
							$subscriptionId = 0;
							$customerRowData = json_decode($userData, true);
							$isCustomerExist =$this -> ModelCustomer -> getCustomer('docData', $customerRowData['icNumber']);
							if($isCustomerExist)
							{
								$customerId = $isCustomerExist['data']['0']['id'];
								$customerData =  array("channelPartnerStoreId" => $storeId, "name" => $customerRowData['name'], "email" => $customerRowData['email'], "DOB" => $customerRowData['dob'], "caAddress" => $customerRowData['caAddress'], "daAddress" => $customerRowData['daAddress'], "nationality" => $customerRowData['nationality'], "occupation" => $customerRowData['occupation'], "docIssuedDate" => $customerRowData['docIssuedDate'], "telco" => $customerRowData['telco']);
								//$whereData = array("docData" => $customerRowData['icNumber'], "mobile" => $customerRowData['mobileNumber']);
								$whereData = array("docData" => $customerRowData['icNumber']);
								$this -> ModelCustomer -> updateCustomer($customerData, $whereData);
								
								addCustomerAudit($customerId, 'customer', $customerId, 'Update Customer', '');

								$imgPath = './img/customerDoc/';
								$fileName = date('YmdHis').$customerId.'icPictureFront.jpg';
								file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$customerRowData['icPictureFront'])));
								$docData = array("customerId" => $customerId, "docType" => "file", "label" => "icPictureFront", "docData" => BASE_URL."img/customerDoc/".$fileName);
								$this -> ModelCustomer -> addCustomerDocument($docData);

								$fileName = date('YmdHis').$customerId.'icPictureBack.jpg';
								file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$customerRowData['icPictureBack'])));
								$docData = array("customerId" => $customerId, "docType" => "file", "label" => "icPictureBack", "docData" => BASE_URL."img/customerDoc/".$fileName);
								$this -> ModelCustomer -> addCustomerDocument($docData);

								$output = array("status" => "Suceess",  'timeStamp' => date('Y-m-d H:i:s'), 'customerId' => $customerId);
								$this -> ModelUtility -> saveLog($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
							else
							{
								$output = array("status" => "Error",  'timeStamp' => date('Y-m-d H:i:s'), "msg" => "something went wrong");
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
							
						}
						else
						{
							$output = $isValidFormData;
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
					}
					else
					{
						$output = array("status" => "Error", "msg" => "Invalid Store Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid PDPA value", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
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
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
				//print_r($arr);
			}
		}	
	}

	public function getAdvanceFormByProgram()
	{
		$input = $this->input->post();
		$apiName = 'getAdvanceFormByProgram'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$tDate = date('y-m-d');
				if($bfd = $this->ModelProgram->getProgramForm('1', 'advance'))
					$formData = json_decode($bfd['data'][0]['formData'],true);

				//$formData = '[{"name":"traceCode", "type":"hidden", "placeholder":"", "value":"COMPASIA.005", "isRequired":"1"},{"name":"applicationDate", "type":"hidden", "placeholder":"", "value":"'.$tDate.'", "isRequired":"1"},{"name":"sellerNote", "type":"text", "placeholder":"Seller Note", "isRequired":"1"},{"name":"loanAmount", "type":"text", "placeholder":"Loan Amount", "isRequired":"1"},{"name":"loanPurpose", "type":"select", "placeholder":"Loan Purpose", "isRequired":"1"},{"name":"loanTerm", "type":"number", "placeholder":"Loan term in Months", "isRequired":"1"},{"name":"downpayment", "type":"number", "placeholder":"Down Payment", "isRequired":"1"},{"name":"downpaymentPercent", "type":"number", "placeholder":"Down Payment %", "isRequired":"1"},{"name":"commodityName", "type":"select", "placeholder":"Device Name", "isRequired":"1"},{"name":"productId", "type":"hidden", "placeholder":"Product Code", "isRequired":"1"},{"name":"shopId", "type":"text", "placeholder":"Shop ID (ex. SL700000010001)", "isRequired":"1"},{"name":"sellerId", "type":"text", "placeholder":"Seller ID (ex. SM79504471)", "isRequired":"1"},{"name":"requestLoanAmount", "type":"number", "placeholder":"Request Loan Amount", "isRequired":"1"},{"name":"requestLoanTerm", "type":"number", "placeholder":"Request Loan Term", "isRequired":"1"},{"name":"familyOwnerFullname", "type":"text", "placeholder":"Family Owner Full Name", "isRequired":"1"},{"name":"fullName", "type":"text", "placeholder":"Full Name", "isRequired":"1"},{"name":"gender", "type":"select", "placeholder":"Gender", "isRequired":"1"},{"name":"idCard", "type":"text", "placeholder":"ID Card Number", "isRequired":"1"},{"name":"idIssueDate", "type":"date", "placeholder":"ID Issued Date", "isRequired":"1"},{"name":"idIssuePlaceId", "type":"text", "placeholder":"ID Issued Place", "isRequired":"1"},{"name":"marriedId", "type":"select", "placeholder":"Married ID", "isRequired":"1"},{"name":"birthday", "type":"date", "placeholder":"Birth Day", "isRequired":"1"},{"name":"educationId", "type":"select", "placeholder":"Education ID", "isRequired":"1"},{"name":"mobilephone", "type":"text", "placeholder":"Mobile Number", "isRequired":"1"},{"name":"regAddressWardId", "type":"select", "placeholder":"Commune / Ward / Town Permanent address", "isRequired":"1"},{"name":"regAddressDistId", "type":"text", "placeholder":"District Permanent Address", "isRequired":"1"},{"name":"regAddressProvinceId", "type":"select", "placeholder":"Province / City Permanent address", "isRequired":"1"},{"name":"regAddressStatus", "type":"select", "placeholder":"Status of permanent residence", "isRequired":"1"},{"name":"actAddressWardId", "type":"select", "placeholder":"Commune / Ward / Town Temporary address", "isRequired":"1"},{"name":"actAddressDistId", "type":"text", "placeholder":"District Temporary residence address", "isRequired":"1"},{"name":"actAddressProvinceId", "type":"select", "placeholder":"Province / City Temporary address", "isRequired":"1"},{"name":"curAddressWardId", "type":"select", "placeholder":"Commune / Ward / Town Current Address", "isRequired":"1"},{"name":"curAddressDistId", "type":"text", "placeholder":"District Current address", "isRequired":"1"},{"name":"curAddressProvinceId", "type":"select", "placeholder":"Province / City Current address", "isRequired":"1"},{"name":"worAddressWardId", "type":"select", "placeholder":"Commune / Ward / Town Workplace address", "isRequired":"1"},{"name":"worAddressDistId", "type":"text", "placeholder":"District Address work", "isRequired":"1"},{"name":"worAddressProvinceId", "type":"select", "placeholder":"Province / City Workplace address", "isRequired":"1"},{"name":"livingYear", "type":"number", "placeholder":"Year of living at current address", "isRequired":"1"},{"name":"livingMonth", "type":"number", "placeholder":"Months of living at current address", "isRequired":"1"},{"name":"phone", "type":"text", "placeholder":"Lnadline Number", "isRequired":"1"},{"name":"accommodationsId", "type":"select", "placeholder":"Accommodation ID", "isRequired":"1"},{"name":"workingTypeId", "type":"select", "placeholder":"Working Type ID", "isRequired":"1"},{"name":"companyName", "type":"text", "placeholder":"Company Name", "isRequired":"1"},{"name":"workingYear", "type":"number", "placeholder":"Years of work at current address", "isRequired":"1"},{"name":"workingMonth", "type":"number", "placeholder":"Months of work at current address", "isRequired":"1"},{"name":"companyAddressWardId", "type":"select", "placeholder":"Company Ward", "isRequired":"1"},{"name":"companyAddressDistId", "type":"select", "placeholder":"Company District ", "isRequired":"1"},{"name":"companyAddressProvinceId", "type":"select", "placeholder":"Company Province", "isRequired":"1"},{"name":"companyPhone", "type":"text", "placeholder":"Company Phone Number", "isRequired":"1"},{"name":"companyTypeId", "type":"select", "placeholder":"Company Type", "isRequired":"1"},{"name":"careerId", "type":"select", "placeholder":"Career ", "isRequired":"1"},{"name":"positionId", "type":"select", "placeholder":"Position ", "isRequired":"1"},{"name":"income", "type":"number", "placeholder":"Personal income", "isRequired":"1"},{"name":"familyIncome", "type":"number", "placeholder":"Family income", "isRequired":"1"},{"name":"expense", "type":"number", "placeholder":"Personal Expense", "isRequired":"1"},{"name":"familyExpense", "type":"number", "placeholder":"Family Expense", "isRequired":"1"},{"name":"numerOfFamilyExpense", "type":"number", "placeholder":"Number of dependents", "isRequired":"1"},{"name":"spouseFullName", "type":"text", "placeholder":"Name of wife / husband", "isRequired":"1"},{"name":"spouseIdCard", "type":"text", "placeholder":"ID card number of husband / wife", "isRequired":"1"},{"name":"spousePhone", "type":"text", "placeholder":"Wife / husband phone number", "isRequired":"1"},{"name":"referenceFullName1", "type":"text", "placeholder":"Name of reference 1", "isRequired":"1"},{"name":"referenceRelationship1", "type":"select", "placeholder":"Relationship of reference 1", "isRequired":"1"},{"name":"referencePhone1", "type":"text", "placeholder":"Phone reference 1", "isRequired":"1"},{"name":"reference1Gender", "type":"select", "placeholder":"Gender of reference 1", "isRequired":"1"},{"name":"referenceFullName2", "type":"text", "placeholder":"Name of reference 2", "isRequired":"1"},{"name":"referenceRelationship2", "type":"select", "placeholder":"Relationship of reference 2", "isRequired":"1"},{"name":"referencePhone2", "type":"text", "placeholder":"Phone reference 2", "isRequired":"1"},{"name":"reference2Gender", "type":"select", "placeholder":"Gender of reference 2", "isRequired":"1"},{"name":"referenceFullName3", "type":"text", "placeholder":"Name of reference 3", "isRequired":"1"},{"name":"referenceRelationship3", "type":"select", "placeholder":"Relationship of reference 3", "isRequired":"1"},{"name":"referencePhone3", "type":"text", "placeholder":"Phone reference 3", "isRequired":"1"},{"name":"reference3Gender", "type":"select", "placeholder":"Gender of reference 3", "isRequired":"1"},{"name":"numerOfFamily", "type":"number", "placeholder":"Number of family members", "isRequired":"1"},{"name":"hasOcbAccount", "type":"select", "placeholder":"Already have an OCB account", "isRequired":"1"},{"name":"hasOtherAccount", "type":"select", "placeholder":"Choose a different TK Account OCB", "isRequired":"1"},{"name":"supplyAccountLater", "type":"select", "placeholder":"Supply Account", "isRequired":"1"},{"name":"customerAccount", "type":"text", "placeholder":"Customer Account Number", "isRequired":"1"},{"name":"customerBankProvince", "type":"text", "placeholder":"Customer Bank Province", "isRequired":"1"},{"name":"customerBank", "type":"text", "placeholder":"Customer Bank Name", "isRequired":"1"},{"name":"customerBankBrand", "type":"text", "placeholder":"Customer Bank Branch", "isRequired":"1"},{"name":"tradeId", "type":"text", "placeholder":"TradeId", "isRequired":"1"},{"name":"disbursementMethod", "type":"select", "placeholder":"Disbursement Method ", "isRequired":"1"},{"name":"mailAddressId", "type":"select", "placeholder":"Mail Address", "isRequired":"1"},{"name":"metaData", "type":"textarea", "placeholder":"Other information", "isRequired":"1"},{"name":"RegAddressNumber", "type":"text", "placeholder":"House number for permanent residence", "isRequired":"0"},{"name":"RegAddressStreet", "type":"text", "placeholder":"Street of permanent address", "isRequired":"0"},{"name":"RegAddressRegion", "type":"text", "placeholder":"Village / hamlet / residential quarter permanent address", "isRequired":"0"},{"name":"CurAddressNumber", "type":"text", "placeholder":"Current home address", "isRequired":"0"},{"name":"CurAddressStreet", "type":"text", "placeholder":"Current address line", "isRequired":"0"},{"name":"CurAddressRegion", "type":"text", "placeholder":"The current village / hamlet", "isRequired":"0"},{"name":"ComAddressNumber", "type":"text", "placeholder":"Address of the company address", "isRequired":"0"},{"name":"ComAddressStreet", "type":"text", "placeholder":"Street company address", "isRequired":"0"},{"name":"ComAddressRegion", "type":"text", "placeholder":"Village / hamlet / residential area company address", "isRequired":"1"},{"name":"WorAddressNumber", "type":"text", "placeholder":"House number working address", "isRequired":"1"},{"name":"WorAddressStreet", "type":"text", "placeholder":"Street work address", "isRequired":"1"},{"name":"WorAddressRegion", "type":"text", "placeholder":"Village / hamlet / residential area work address", "isRequired":"1"},{"name":"OldIdCard", "type":"text", "placeholder":"Old ID card", "isRequired":"1"},{"name":"Ethnic", "type":"select", "placeholder":"Nation", "isRequired":"1"},{"name":"IdExpireDate", "type":"date", "placeholder":"Id expiry date", "isRequired":"1"},{"name":"BeneficiaryName", "type":"text", "placeholder":"Beneficiary name", "isRequired":"1"},{"name":"BeneficiaryAccount", "type":"text", "placeholder":"Beneficiary Account Number", "isRequired":"1"},{"name":"BeneficiaryProvinceId", "type":"text", "placeholder":"Province / City The beneficiary bank", "isRequired":"1"},{"name":"BeneficiaryBankId", "type":"text", "placeholder":"Beneficiary bank name", "isRequired":"1"},{"name":"BeneficiaryBankBranchId", "type":"text", "placeholder":"Beneficiary branch", "isRequired":"1"}]';
				$dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
				$dictionaryData = array();
				foreach($dictionaryDataRow as $d)
				{
					$dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);

				}
				$productDataRow = $this -> ModelProduct -> getProductbyProgram($programId);
				for($i=0;$i<sizeof($productDataRow);$i++)
					$productData[] = array("id" => $productDataRow[$i]['partnerSKU'], "name" => $productDataRow[$i]['name']." - ".$productDataRow[$i]['partnerSKU']); 
				//print_r($dictionaryData);
				$bool = array(array("id" => 1, "name" => "Yes"), array("id" =>0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"VIETTEL"),array("id"=>"mb", "name"=>"MOBIPHONE"),array("id"=>"vn", "name"=>"VINAPHONE"),array("id"=>"other", "name"=>"khác"));
				$disbursementMethod = array(array("id" => "BANK", "name" => "BANK"),array("id" => "CARD", "name" => "CARD"));
				$provinceDataRow =  json_decode(getAllProvince()['msg'],true);
				for($i=0;$i<sizeof($provinceDataRow);$i++)
					$provinceData[] = array("id"=>$provinceDataRow[$i]['ProvinceId'], "name" => $provinceDataRow[$i]['ProvinceName']);
				$cityDataRow =  json_decode(getAllCity()['msg'],true);
				for($i=0;$i<sizeof($cityDataRow);$i++)
					$cityData[] = array("id"=>$cityDataRow[$i]['CityId'], "name" => $cityDataRow[$i]['CityName']);
				$wardDataRow =  json_decode(getAllWard()['msg'],true);
				for($i=0;$i<sizeof($wardDataRow);$i++)
					$wardData[] = array("id"=>$wardDataRow[$i]['WardId'], "name" => $wardDataRow[$i]['WardName']);
				$bankDataRow =  json_decode(getOCBBank()['msg'],true);
				for($i=0;$i<sizeof($bankDataRow);$i++)
					$bankData[] = array("id"=>$bankDataRow[$i]['BankBranchId'], "name" => $bankDataRow[$i]['BankBranchName']);
				//$formData = json_decode($formData,true);
				//print_r(getAllCity());
				$gender  =$dictionaryData['GENDER'];
				$marridId = $dictionaryData['MARRIED_ID'];
				$educationId = $dictionaryData['EDUCATION_ID'];
				$accommodationsId = $dictionaryData['ACCOMMODATIONS_ID'];
				$careerId = $dictionaryData['CAREER_ID'];
				$companyTypeId = $dictionaryData['COMPANY_TYPE_ID'];
				$loanPurpose = $dictionaryData['LOAN_PURPOSE_ID'];
				$mailAddressId = $dictionaryData['MAIL_ADDRESS_ID'];
				$positionId = $dictionaryData['POSITION_ID'];
				$regAddressStatus = $dictionaryData['REG_ADDRESS_STATUS'];
				$relationship = $dictionaryData['RELATIONSHIP'];
				$workingTypeId = $dictionaryData['WORKING_TYPE_ID'];
				$nation = array("id" => 1, "name" => "Việt Nam");
					
				for($i=0;$i<sizeof($formData); $i++)
				{
					if($formData[$i]['name'] == 'gender' or $formData[$i]['name'] == 'reference1Gender' or $formData[$i]['name'] == 'reference2Gender' or $formData[$i]['name'] == 'reference3Gender')
						$formData[$i]['data'] = $gender;
					if($formData[$i]['name'] == 'marriedId')
						$formData[$i]['data'] = $marridId;
					if($formData[$i]['name'] == 'educationId')
						$formData[$i]['data'] = $educationId;
					if($formData[$i]['name'] == 'accommodationsId')
						$formData[$i]['data'] = $accommodationsId;	
					if($formData[$i]['name'] == 'careerId')
						$formData[$i]['data'] = $careerId;	
					if($formData[$i]['name'] == 'companyTypeId')
						$formData[$i]['data'] = $companyTypeId;	
					if($formData[$i]['name'] == 'loanPurpose')
						$formData[$i]['data'] = $loanPurpose;	
					if($formData[$i]['name'] == 'mailAddressId')
						$formData[$i]['data'] = $mailAddressId;	
					if($formData[$i]['name'] == 'positionId')
						$formData[$i]['data'] = $positionId;	
					if($formData[$i]['name'] == 'regAddressStatus')
						$formData[$i]['data'] = $regAddressStatus;	
					if($formData[$i]['name'] == 'referenceRelationship1' or $formData[$i]['name'] == 'referenceRelationship2' or $formData[$i]['name'] == 'referenceRelationship3')
						$formData[$i]['data'] = $relationship;	
					if($formData[$i]['name'] == 'workingTypeId')
						$formData[$i]['data'] = $workingTypeId;
					if($formData[$i]['name'] == 'regAddressWardId' or $formData[$i]['name'] == 'actAddressWardId' or $formData[$i]['name'] == 'curAddressWardId' or $formData[$i]['name'] == 'worAddressWardId' or $formData[$i]['name'] == 'companyAddressWardId')
						$formData[$i]['data'] = $wardData;
					if($formData[$i]['name'] == 'regAddressProvinceId' or $formData[$i]['name'] == 'actAddressProvinceId' or $formData[$i]['name'] == 'curAddressProvinceId' or $formData[$i]['name'] == 'worAddressProvinceId' or $formData[$i]['name'] == 'companyAddressProvinceId' or $formData[$i]['name'] == 'BeneficiaryProvinceId')
						$formData[$i]['data'] = $provinceData;
					if($formData[$i]['name'] == 'regAddressDistId' or $formData[$i]['name'] == 'actAddressDistId' or $formData[$i]['name'] == 'curAddressDistId' or $formData[$i]['name'] == 'worAddressDistId' or $formData[$i]['name'] == 'companyAddressDistId')
						$formData[$i]['data'] = $cityData;
					if($formData[$i]['name'] == 'hasOcbAccount' or $formData[$i]['name'] == 'supplyAccountLater')
						$formData[$i]['data'] = $bool;
					if($formData[$i]['name'] == 'disbursementMethod')
						$formData[$i]['data'] = $disbursementMethod;
					if($formData[$i]['name'] == 'Ethnic')
						$formData[$i]['data'] = array($nation);
					if($formData[$i]['name'] == 'commodityName')
						$formData[$i]['data'] = $productData;
					if($formData[$i]['name'] == 'hasOtherAccount')
						$formData[$i]['data'] = $bankData;
					if($formData[$i]['name'] == 'Telco')
						$formData[$i]['data'] = $telco;
					
				}
				$output = array("status" => "Success", "msg" => array("formData" => $formData), 'timeStamp' => date('Y-m-d H:i:s'));
                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
                                echo json_encode($output, JSON_UNESCAPED_UNICODE);
				
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
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
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
				//print_r($arr);
			}
		}	
	//echo true;
	}

	public function createApplication()
	{
		$input = $this->input->post();
		$apiName = 'createApplication'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				if($bfd = $this->ModelProgram->getProgramForm('1', 'advance'))
					$formData = json_decode($bfd['data'][0]['formData'],true);

				$isValidFormData = $this ->validateFormData(json_encode($this->input->post()), $formData);
				if($isValidFormData['status'] == 'Success')
				{
					$appInfo = array();

					$partnerSKU = $this->input->post('productId');
					$sku = $this->input->post('sku');
					$data = $this->ModelProduct->getProductDetailBySKU($sku);
					for($i=0; $i<sizeof($formData); $i++){
						$n = $formData[$i]['name']; 
						$sCheck  = array('referencePhone1', 'referencePhone2', 'referencePhone3', 'mobilephone', 'idCard', 'OldIdCard', 'spouseIdCard', 'companyAddressDistId', 'companyAddressProvinceId', 'companyPhone', 'phone', 'worAddressProvinceId', 'worAddressDistId', 'worAddressWardId', 'curAddressProvinceId', 'curAddressDistId', 'curAddressWardId', 'actAddressProvinceId', 'actAddressDistId', 'actAddressWardId', 'regAddressProvinceId', 'regAddressDistId', 'regAddressWardId', 'idIssuePlaceId', 'sellerId', 'shopId', 'productId', 'spousePhone', 'customerAccount', 'customerBankProvince', 'customerBank', 'customerBankBrand', 'BeneficiaryProvinceId', 'BeneficiaryBankId', 'BeneficiaryBankBranchId', 'BeneficiaryAccount');
						if(in_array($n, $sCheck))
							$appInfo[$formData[$i]['name']] = $this->input->post($formData[$i]['name']);
						else if($formData[$i]['name'] == 'birthday' or $formData[$i]['name'] == 'idIssueDate' or $formData[$i]['name'] == 'IdExpireDate')
							if(trim($this->input->post($formData[$i]['name'])) != '')
								$appInfo[$formData[$i]['name']] = date('Y-m-d', strtotime(str_replace('/', '-',$this->input->post($formData[$i]['name'])))); 
							else
								$appInfo[$formData[$i]['name']] = '';	
						else
							$appInfo[$formData[$i]['name']] = is_numeric($this->input->post($formData[$i]['name'])) ? intval($this->input->post($formData[$i]['name'])) : $this->input->post($formData[$i]['name']); 
					}
					//$appInfo['productId'] = $partnerSKU;	
					//$appInfo['commodityName'] = $data[0]['name'];
					$meta = array("OldIdCard","Ethnic", "IdExpireDate", "BeneficiaryName", "BeneficiaryAccount", "BeneficiaryProvinceId", "BeneficiaryBankId", "BeneficiaryBankBranchId", "RegAddressNumber", "RegAddressStreet", "RegAddressRegion", "CurAddressNumber", "CurAddressStreet", "CurAddressRegion", "ComAddressNumber", "ComAddressStreet", "ComAddressRegion", "WorAddressNumber", "WorAddressStreet", "WorAddressRegion", "Email", "Telco"); 
					$metaArray = array();
					for($i=0;$i<sizeof($meta);$i++)
					{
						$metaArray[$meta[$i]] = isset($appInfo[$meta[$i]]) ? $appInfo[$meta[$i]] : "";
						unset($appInfo[$meta[$i]]);
					}
					$appInfo['metaData'] = json_encode($metaArray, JSON_UNESCAPED_UNICODE);
					//$data = $this->ModelSubscription->getSubscriptionById($appId)[0]appInfo'];
					$appInfo['traceCode'] = 'COMPASIA.'.date('ymdHis');
					//$d =$this->createApplicationOCB(json_encode($appInfo));
					$customerData = $this->ModelCustomer->getCustomer('id', $this->input->post('customerId'));
					$storeId = isset($customerData['data'][0]['channelPartnerStoreId']) ? $customerData['data'][0]['channelPartnerStoreId'] : 0;
					//if($d['status'] == 'Success')
					{	
						$newData = array("programId" => $this->input->post('programId'), "channelPartnerStoreId" => $storeId, "createdStaff" => $this->input->post('employeeId'), "customerId" => $this->input->post('customerId'), "orderValue" => $data[0]['drp'], "orderUpfront"=>$data[0]['dpv'], "loanAmount"=>$this->input->post('loanAmount'), "monthlyFee" => $data[0]['dmof'], "product" => $data[0]['sku'], "tenure" => $data[0]['tenure'], "rrp" => $data[0]['drp'], "caFee" => "", "status" => "11", "appInfo" => json_encode($appInfo, JSON_UNESCAPED_UNICODE));
						$res = $this -> ModelSubscription -> addSubscription($newData);
						if($res)
						{
							$priceData = $this->ModelProduct->getProductDetailBySKU($data[0]['sku']);
							if($priceData){
								$pd = $priceData[0];	
								$pData = array(array('subscriptionId' => $res, 'itemType' => 'tenure', 'itemValue' => $pd['tenure']), array('subscriptionId' => $res, 'itemType' => 'suw', 'itemValue' => $pd['suw']), array('subscriptionId' => $res, 'itemType' => 'suwUnit', 'itemValue' => $pd['suwUnit']), array('subscriptionId' => $res, 'itemType' => 'euw', 'itemValue' => $pd['euw']), array('subscriptionId' => $res, 'itemType' => 'euwUnit', 'itemValue' => $pd['euwUnit']), array('subscriptionId' => $res, 'itemType' => 'drp', 'itemValue' => $pd['drp']), array('subscriptionId' => $res, 'itemType' => 'cadc', 'itemValue' => $pd['cadc']), array('subscriptionId' => $res, 'itemType' => 'capf', 'itemValue' => $pd['capf']), array('subscriptionId' => $res, 'itemType' => 'camrf', 'itemValue' => $pd['camrf']), array('subscriptionId' => $res, 'itemType' => 'dt', 'itemValue' => $pd['dt']), array('subscriptionId' => $res, 'itemType' => 'cadm', 'itemValue' => $pd['cadm']), array('subscriptionId' => $res, 'itemType' => 'dmf', 'itemValue' => $pd['dmf']), array('subscriptionId' => $res, 'itemType' => 'cpf', 'itemValue' => $pd['cpf']), array('subscriptionId' => $res, 'itemType' => 'fdc', 'itemValue' => $pd['fdc']), array('subscriptionId' => $res, 'itemType' => 'dpv', 'itemValue' => $pd['dpv']), array('subscriptionId' => $res, 'itemType' => 'dpvt', 'itemValue' => $pd['dpvt']), array('subscriptionId' => $res, 'itemType' => 'drv', 'itemValue' => $pd['drv']), array('subscriptionId' => $res, 'itemType' => 'fsv', 'itemValue' => $pd['fsv']), array('subscriptionId' => $res, 'itemType' => 'dmof', 'itemValue' => $pd['dmof']));
								$this->ModelSubscription->addSubscriptionPriceItem($pData);
							}
							$newRef = 'FPOC'.$res;
							$uData = array("referenceNumber" => $newRef);
							$whereData = array("id"=>$res);
							$this->ModelSubscription->updateSubscription($uData, $whereData);
						
							$output = array("status" => "Success", "msg" => $newRef, "subscriptionId" => $res, 'timeStamp' => date('Y-m-d H:i:s'));
                	                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
                        	                        $this->LogManager->logApi($apiName, $input, $output);
													echo json_encode($output, JSON_UNESCAPED_UNICODE);						
						}
						else
						{
							$output = array("status" => "Error", "msg" => "Something went wrong", 'timeStamp' => date('Y-m-d H:i:s'));
        		        	                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
	        		                        $this->LogManager->logApi($apiName, $input, $output);
											echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
					}
					//else
					//{
					//	$this -> ModelUtility -> saveLog($apiName, $input, $d);
                                        //        echo json_encode($d);
					//}
				}
				else
				{
					$output = $isValidFormData;
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
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
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
				//print_r($arr);
			}
		}	
	//echo true;
	}

	public function updateCustomerDocument()
	{
		$input = $this->input->post();
		$apiName = 'updateCustomerDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("customerId", "customerId", "required");
		$this->form_validation->set_rules("imageData", "imageData", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$customerId = $this->input->post('customerId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$imageData = json_decode($this->input->post('imageData'),true);
				$imgPath = './img/customerDoc/';
				
				if(isset($imageData['icPictureFront'])){
					$fileName = date('YmdHis').$customerId.'icPictureFront.jpg';
					file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$imageData['icPictureFront'])));
					$whereData = array("customerId" => $customerId, "docType" => "file", "label" => "icPictureFront");
					$data = array( "docData" => BASE_URL."img/customerDoc/".$fileName, "createdDate" => date('Y-m-d H:i:s'));
					$this -> ModelCustomer -> updateCustomerDocument($data, $whereData);
				}
				if(isset($imageData['icPictureBack'])){
					$fileName = date('YmdHis').$customerId.'icPictureBack.jpg';
					file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$imageData['icPictureBack'])));
					$whereData = array("customerId" => $customerId, "docType" => "file", "label" => "icPictureBack");
					$data = array( "docData" => BASE_URL."img/customerDoc/".$fileName, "createdDate" => date('Y-m-d H:i:s'));
					$this -> ModelCustomer -> updateCustomerDocument($data, $whereData);
				}
				$output = array("status" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
		
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
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
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
				//print_r($arr);
			}
		}	
	}
	
	public function validateFormData($userForm, $metaForm)
	{
		//echo $userForm;
		$userFormData = json_decode($userForm, true);
		if(is_array($userFormData)){
			foreach($metaForm as $d)
			{
				//print_r($d);
				if($d['isRequired'] == 1){
					if(array_key_exists($d['name'],$userFormData))
					{
						if(trim($userFormData[$d['name']] != ''))
						{
							if(array_key_exists('minLength',$d) or array_key_exists('maxLength',$d)){
								if($d['type'] == 'text' and strlen(trim($userFormData[$d['name']])) < $d['minLength'])
								{
									return array("status" => "Error", "fieldName" =>$d['name'], "msg"=> "Minimum ".$d['minLength']." characters required ".$d['name'], 'timeStamp' => date('Y-m-d H:i:s'));
								}
								else if($d['type'] == 'text' and strlen(trim($userFormData[$d['name']])) > $d['maxLength'])
								{
									return array("status" => "Error", "fieldName" =>$d['name'], "msg"=> "Maximum  ".$d['maxLength']." characters limit for ".$d['name'], 'timeStamp' => date('Y-m-d H:i:s'));
								}
							}else if(array_key_exists('min',$d) or array_key_exists('max',$d)){
								if($d['type'] == 'number' and trim($userFormData[$d['name']]) < $d['min'])
								{
									return array("status" => "Error", "fieldName" =>$d['name'], "msg"=> "Minimum ".$d['min']." value required ".$d['name'], 'timeStamp' => date('Y-m-d H:i:s'));
								}
								else if($d['type'] == 'number' and trim($userFormData[$d['name']]) > $d['max'])
								{
									return array("status" => "Error", "fieldName" =>$d['name'], "msg"=> "Maximum  ".$d['max']." value limit for ".$d['name'], 'timeStamp' => date('Y-m-d H:i:s'));
								}
							}
						}
						else
						{
							return array("status" => "Error", "msg"=> "requied field ".$d['name']." not to be empty", "fieldName" =>$d['name'],  'timeStamp' => date('Y-m-d H:i:s'));
						}
					}
					else
					{
						return array("status" => "Error", "msg"=> "requied field ".$d['name']." not found", "fieldName" =>$d['name'], 'timeStamp' => date('Y-m-d H:i:s'));
					}
				}
			}
			return array("status" => "Success");	
			//print_r($metaForm);	
		}else
		{
			return array("status" => "Error", "msg"=> "Customer info data is not valid", "fieldName" =>'', 'timeStamp' => date('Y-m-d H:i:s'));
		}		
	}	

	public function getVerificationDropdown()
	{
		$input = $this->input->post();
		$apiName = 'getVerificationDropdown'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
				$dictionaryData = array();
				foreach($dictionaryDataRow as $d)
				{
					$dictionaryData[$d['GroupId']][] = array("value" => $d['DisplayName'], "name" => $d['DisplayName']);

				}
				$bool = array(array("id" => 1, "name" => "Yes"), array("id" =>0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"VIETTEL"),array("id"=>"mb", "name"=>"MOBIPHONE"),array("id"=>"vn", "name"=>"VINAPHONE"),array("id"=>"other", "name"=>"khác"));
				$disbursementMethod = array(array("id" => "BANK", "name" => "BANK"),array("id" => "CARD", "name" => "CARD"));
				$provinceDataRow =  json_decode(getAllProvince()['msg'],true);
				for($i=0;$i<sizeof($provinceDataRow);$i++)
					$provinceData[] = array("id"=>$provinceDataRow[$i]['ProvinceId'], "value"=>$provinceDataRow[$i]['ProvinceName'], "name" => $provinceDataRow[$i]['ProvinceName']);
				$cityDataRow =  json_decode(getAllCity()['msg'],true);
				for($i=0;$i<sizeof($cityDataRow);$i++)
					$cityData[] = array("id"=>$cityDataRow[$i]['CityId'], "value"=>$cityDataRow[$i]['CityName'], "name" => $cityDataRow[$i]['CityName']);
				$wardDataRow =  json_decode(getAllWard()['msg'],true);
				for($i=0;$i<sizeof($wardDataRow);$i++)
					$wardData[] = array("id"=> $wardDataRow[$i]['WardId'], "value"=>$wardDataRow[$i]['WardName'], "name" => $wardDataRow[$i]['WardName']);
				//$formData = json_decode($formData,true);
				$workingTypeId = $dictionaryData['WORKING_TYPE_ID'];
				$nation = array(array("value" => "Việt Nam", "name" => "Việt Nam"), array("value" => "Other", "name" => "Other"));
									
				$data = array("nationality" => $nation, "occupation" => $workingTypeId, "operator" => $telco, "city" => $wardData, "district" => $cityData, "province" => $provinceData);
				$output = array("status" => "Success", "msg" => $data, 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
		
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
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
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
				//print_r($arr);
			}
		}	
	}
	

	public function getCustomerVerificationStatusOCB($mobile, $idNo){
		$url = OCBLINK."api/compasia/CheckValidData";
	      // append the header putting the secret key and hash
		$token =  getOCBToken();	
		$request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
		$data = "mobilePhone=".$mobile."&idNo=".$idNo;
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	        $data = curl_exec($ch);
		if (curl_errno($ch))
        	{	        
			return array("status" => "Error", "msg" => curl_error($ch));
        	}
	        else
        	{
	        	$transaction = json_decode($data, TRUE);
			//print_r($transaction);
			if(isset($transaction['status']))
			{
				if($transaction['status'] == 200)
					return array("status" => "Success", "msg" => $transaction['data']['validData']);
				else	
					return array("status" => "Error", "msg" => $data);
			}
			else
			{
				return array("status" => "Error", "msg" => $data);
			}
		        curl_close($ch);
      		}
	}


	public function createApplicationOCB1($data){
		$url = OCBLINK."api/CompAsia/CreateNewApp";
		/*$data = json_decode($data,true);
		$postData = '';
		foreach($data as $k => $v)
		      $postData .= $k . '='.$v.'&';
		$postData = rtrim($postData, '&');
		*/
	      // append the header putting the secret key and hash
		$token =  getOCBToken();	
		$request_headers = array('Content-Type:application/json', 'Authorization:'.$token);
		//$data = $postData;
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	        $data = curl_exec($ch);
		if (curl_errno($ch))
        	{	        
			return array("status" => "Error", "msg" => curl_error($ch));
        	}
	        else
        	{
	        	$transaction = json_decode($data, TRUE);
			if(isset($transaction['status']))
			{
				if($transaction['status'] == 200)
					return array("status" => "Success", "msg" => $transaction['data']['appId'], "contractId" => $transaction['data']['contractId']);
				else if ($transaction['status'] == 500) 
				{
					return array("status" => "Error500", "msg" => $transaction['message'], "fieldName"=>'');
				}
				else 
				{
					$data = $transaction['data'];
					foreach($data as $k => $v){
						$msg= trim($v) == ''? "There is an error in ".$k:$v;
						$fieldName = $k;
						break;	
					}
					return array("status" => "Error", "msg" =>$msg, "fieldName"=>$fieldName );
				}
							
			}
			else
			{
				$data = $transaction['data'];
				foreach($data as $k => $v){
					$msg= trim($v) == ''? "There is an error in ".$k:$v;
					$fieldName = $k;
					break;	
				}
				return array("status" => "Error", "msg" =>$msg, "fieldName"=>$fieldName );
			}
		        curl_close($ch);
      		}
	}

}
