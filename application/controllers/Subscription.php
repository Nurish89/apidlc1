<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends CI_Controller {

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
		$this->load->model('ModelEmployee');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('mafchelper');
		$this->load->helper('utility');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function mImage($images){
		$img = imagecreatetruecolor(58, 75);
		// Make alpha channels work
		imagealphablending($img, true);
		imagesavealpha($img, true);

		foreach($images as $fn) {
			// Load image
			$cur = imagecreatefrompng($fn);
			imagealphablending($cur, true);
			imagesavealpha($cur, true);

			// Copy over image
			imagecopy($img, $cur, 0, 0, 0, 0, 58, 75);

			// Free memory
			imagedestroy($cur);
		}   

		// header('Content-Type: image/png');  // Comment out this line to see PHP errors
		imagepng($img);
		return $img;
	}	
	public function convertpdf($html, $filePath)
	{
		$this->load->library('pdf');
		// Load HTML content
		$this->pdf->loadHtml($html);

		// (Optional) Setup the paper size and orientation
		$this->pdf->setPaper('A4', 'portrait');

		// Render the HTML as PDF
		$this->pdf->render();

		// Output the generated PDF (1 = download and 0 = preview)
		file_put_contents($filePath, $this->pdf->output());
		//      $this->pdf->stream($filePath, array("Attachment"=>0));
		unset($this->pdf);
	}

	public function test()
	{
		echo $this->createOCBDocument('100451', 'invoice');	
	}

	public function createOCBDocument($subscriptionId, $doc)
	{
		$imgPath = './img/subscriptionDoc/';
		//$subscriptionId = '100016';	
		//$doc = 'cp';
		$subData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
		$subData[0]['appInfo'] = json_decode($subData[0]['appInfo'],true);
		//print_r($subData);
		$priceData = $this->ModelSubscription->getSubscriptionPriceItem($subscriptionId);
		$priceA = array();
		for($i=0;$i<sizeof($priceData);$i++)
			$priceA[$priceData[$i]['itemType']] = $priceData[$i]['itemValue'];
		$storeData = $this->ModelStore->getStore($subData[0]['channelPartnerStoreId']);
		$employeeData = $this->ModelEmployee->getEmployeeById($subData[0]['createdStaff']);
		if($doc == 'invoice')
		{
			include('caInvoice1.php');			
			$fileName = date('YmdHis').$subscriptionId.'devicePaymentNote.pdf';
		        $this->convertpdf($html, $imgPath.$fileName);
			$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => 'devicePaymentNote', "displayName" => "Device Payment Note", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
			$base64Data = $this->getBase64FromFileData($imgPath.$fileName);
	  	        $this -> ModelSubscription -> addSubscriptionDocument($docData);
		}
		elseif($doc == 'tnc')
		{
			include('cp1.php');	
			//echo $html;		
			$fileName = date('YmdHis').$subscriptionId.'caCustomertnc.pdf';
	        	$this->convertpdf($html, $imgPath.$fileName);
			$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => 'caCustomertnc', "displayName" => "Compasia T&C", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
			$base64Data = $this->getBase64FromFileData($imgPath.$fileName);
  	        	$this -> ModelSubscription -> addSubscriptionDocument($docData);
		}	
	}	

	public function getCustomerICDocument()
	{
		$input = $this->input->post();
		$apiName = 'getCustomerICDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					//print_r($subscriptionData);
					$requiredFile = array("icPictureFront" => '', "icPictureBack" => "");
					$dData = $this->ModelCustomer->getCustomerDocument($subscriptionData[0]['customerId']);
					//print_r($dData);
					foreach($requiredFile as $k => $v){
						for($i=0;$i<sizeof($dData);$i++){
							if($k == $dData[$i]['label']){
								$requiredFile[$k] = $dData[$i]['docData'];
								break;
							}
						}	
					}
					//print_r($requiredFile);
					$output = array("status" => "Success", "timeStamp" => date('Y-m-d H:i:s'), "msg"=>$requiredFile);
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}	
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function checkRequestOTPByTelco()
	{
		$input = $this->input->post();
		$apiName = 'checkRequestOTPByTelco'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$subscriptionData[0]['appInfo'] = json_decode($subscriptionData[0]['appInfo'],true);
					$metaData = json_decode($subscriptionData[0]['appInfo']['metaData'],true);
					if($metaData['Telco'] != 'vn')
					{
						$otpAttempt = $subscriptionData[0]['otpAttempt'];
						if($otpAttempt > 0)
						{
							$data = array("appId"=>$subscriptionData[0]['appId']);
							//$this->ModelSubscription->useOTPAttempt($subscriptionId);
							//$output =  array("status"=>"Success", "attemptLeft" => $otpAttempt - 1, "otpRequired"=> 1);
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
                                                        //echo json_encode($output, JSON_UNESCAPED_UNICODE);
							$d = requestOTPOCB(json_encode($data));
							if($d['status'] == 'Success')
							{
								$this->ModelSubscription->useOTPAttempt($subscriptionId);
								$d['attemptLeft'] = $otpAttempt - 1; 
								$d['otpRequired'] = 1; 
								$output = $d;
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
			                    echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
							else
							{
								$d['attemptLeft'] = 0; 
								$d['otpRequired'] = 0; 
								$output = $d;
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
						}
						else
						{
							$output = array("status" => "Success", "otpRequired" => 1, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}	
			
					}
					else
					{
						$output = array("status" => "Success", "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}		
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function checkRequestOTPByTelco1($subscriptionId)
	{
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$subscriptionData[0]['appInfo'] = json_decode($subscriptionData[0]['appInfo'],true);
					$metaData = json_decode($subscriptionData[0]['appInfo']['metaData'],true);
					if($metaData['Telco'] != 'vt')
					{
						$otpAttempt = $subscriptionData[0]['otpAttempt'];
						if($otpAttempt > 0)
						{
							$data = array("appId"=>$subscriptionData[0]['appId']);
							//$this->ModelSubscription->useOTPAttempt($subscriptionId);
							//return array("status"=>"Success", "attemptLeft" => $otpAttempt - 1, "otpRequired"=> 1);
							$d = requestOTPOCB(json_encode($data));
							//	print_r($d);
							if($d['status'] == 'Success')
							{
								$this->ModelSubscription->useOTPAttempt($subscriptionId);
								$d['attemptLeft'] = $otpAttempt - 1; 
								$d['otpRequired'] = 1; 
								return $output = $d;
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
			                                        //echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
							else
							{
								return $output = $d;
                                        	                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
                                                	        //echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
						}
						else
						{
							return $output = array("status" => "Success", "otpRequired" => 1, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
        	                                        //$this -> ModelUtility -> saveLog($apiName, $input, $output);
	                                                //echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}	
			
					}
					else
					{
						$data = array("appId" => $subscriptionData[0]['appId']);	
						$d = getTelcoScoreOCB(json_encode($data));
						$d['otpRequired'] = 0;
						//$this -> ModelUtility -> saveLog('getTelcoScoreOCB', $data, $d);
						$this->LogManager->logApi('getTelcoScoreOCB', $data, $d);
						return $d;
					}		
				}
	}

	public function getTelcoScore()
	{
		$input = $this->input->post();
		$apiName = 'getTelcoScore'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		$this->form_validation->set_rules("otp", "OTP", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$subscriptionData[0]['appInfo'] = json_decode($subscriptionData[0]['appInfo'],true);
					$metaData = json_decode($subscriptionData[0]['appInfo']['metaData'],true);
					if($metaData['Telco'] != 'vn')
					{
						$otpAttempt = $subscriptionData[0]['otpAttempt'];
						$data = array("appId"=>$subscriptionData[0]['appId'], "otp" => $this->input->post('otp'));
						$d = getTelcoScoreOCB(json_encode($data));
						if($d['status'] == 'Success')
						{
							$output = $d;
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
		                    echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
						else
						{
							$output = $d;
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
					}
					else
					{
						$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}		
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}
	
	public function syncOCBDocument()
	{
		$input = $this->input->post();
		$apiName = 'syncOCBDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		$this->form_validation->set_rules("docType", "Document Type", "required");
		$this->form_validation->set_rules("userId", "User Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$docType = $this->input->post('docType');
			$userId = $this->input->post('userId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$docData = $this->ModelSubscription->getSubscriptionDocument($subscriptionId,$docType);
				if($docData)
				{
					$subData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
					$appId = $subData[0]['appId'];
					$path = $docData[0]['docData'];
					$base64Data = $this->getBase64FromFileData($path);
					$data = array("appId" => $appId, "fieldName" => $docType, "fileType" => "pdf", "traceCode" => 'COMPASIA.'.date('YmdHis').'1', "fileContent" => $base64Data);
					$d = $this->addAppDocument($data);
					//$this -> ModelUtility -> saveLog('sendImageOCB', $data, $d);
					$this->LogManager->logApi('sendImageOCB', $data, $d);
					if($d['status'] == 'Success'){
						$this->ModelSubscription->syncDoc($docData[0]['id']);								
						$cs = $this -> ModelSubscription->checkOCBSyncDoc($subscriptionId);
						if(!$cs){
							$this -> updateSubscriptionStatus($subscriptionId, '19');
							addSubscriptionAudit($subscriptionId, $userId, 'admin', 'Softcopy Sent to OCB', '');	
						}
						$output = array("status" => "Success", "msg" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else
					{
						$output = array("status" => "Error", "msg" => $d['msg'], 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
						
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Document", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function editSubscriptionDocument()
	{
		$input = $this->input->post();
		$apiName = 'editSubscriptionDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$docName = $this->input->post('docName');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$appId = $subscriptionData[0]['appId'];
					$customerId = $subscriptionData[0]['customerId'];
					$imgPath = './img/subscriptionDoc/';
					$htmlPath = date('YmdHis').'html.html';
					$fileName = '';
					$isPdf = 0;
					if($_FILES[$docName]['name'][0] != ''){
						for($j=0;$j<sizeof($_FILES[$docName]['name']);$j++){
							$file_tmp = $_FILES[$docName]['tmp_name'][$j];
							if($_FILES[$docName]['type'][$j] == 'application/pdf')
								$isPdf = 1;
				                     	$base64Data = $this->getBase64FromFileData($file_tmp);
							if($isPdf == 1){
        			        		        $fileName = date('YmdHis').$subscriptionId.$docName.'.pdf';
	        	        		        	file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
							}else{
	        		        		        $fileName = date('YmdHis').$subscriptionId.$docName.'.jpg';
	        	        		        	file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
								$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
								$myfile = fopen($imgPath.$htmlPath, "a");
								fwrite($myfile, $html);
								fclose($myfile);
							}
						}
					}
					if($isPdf == 1){
						$pdfFilePath = $fileName;
					}else{
						$pdfFilePath = date('YmdHis').$subscriptionId.$docName.'.pdf';
						$this->convertpdf(file_get_contents($imgPath.$htmlPath), $imgPath.$pdfFilePath);
					}
					$base64Data = $this->getBase64FromFileData($imgPath.$pdfFilePath);
               		       	        file_put_contents($imgPath.$htmlPath, base64_decode(str_replace(" ","+",$base64Data)));
					unlink($imgPath.$htmlPath);
					$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => $docName, "displayName" => $docName, "docData" => BASE_URL."img/subscriptionDoc/".$pdfFilePath);
	               			$this -> ModelSubscription -> addSubscriptionDocument($docData);

					$output = array("status" => "Success", "msg" => BASE_URL."img/subscriptionDoc/".$pdfFilePath, 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);

				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function uploadCAOCBSignedDocument()
	{
		$input = $this->input->post();
		$apiName = 'uploadCASignedDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$allDoc = 1;
					$appId = $subscriptionData[0]['appId'];
					$customerId = $subscriptionData[0]['customerId'];
					$uploadDoc = array(array("type"=>"file", "name"=>"signedDevicePaymentNote", "placeHolder"=>"Upload Signed Device delivery and Payment Note", "isRequired"=>1, "fileName" => "DOWNPAYMENT_RECEIPT", "isForOCB" => 1), array("type"=>"file", "name"=>"signedCACustomertnc", "placeHolder"=>"Upload CompAsia Customer T&C", "isRequired"=>1, "fileName" => "signedCACustomertnc", "isForOCB" => 0), array("type"=>"file", "name"=>"customerSelfieWithStaff", "placeHolder"=>"Product Image", "isRequired"=>1, "fileName" => "PRODUCT_IMAGE_ATTACH", "isForOCB" => 1), array("type"=>"file", "name"=>"signedOcbContract", "placeHolder"=>"Upload Signed Contract", "isRequired"=>1, "fileName" => "LOAN_APP_CONTRACT_ATTACH", "isForOCB" => 1), array("type"=>"file", "name"=>"signedCreditInsurance", "placeHolder"=>"Upload Signed Credit Insurance", "isRequired"=>1, "fileName" => "INSURANCE_CONTRACT_ATTACH", "isForOCB" => 1));
					for($i=0;$i<sizeof($uploadDoc); $i++)
					{
						if(!isset($_FILES[$uploadDoc[$i]['name']]))
						{
							$allDoc = 0;	
							break;
						}
						else if($_FILES[$uploadDoc[$i]['name']]['name'][0] == '')
						{
							$allDoc = 0;	
							break;
						}	
					}
					if($allDoc == 1)
					{
						$imgPath = './img/subscriptionDoc/';
						for($i=0;$i<sizeof($uploadDoc); $i++)
						{
							$ud = $uploadDoc[$i];
							$htmlPath = date('YmdHis').'html.html';
							if($_FILES[$ud['name']]['name'][0] != ''){
								for($j=0;$j<sizeof($_FILES[$ud['name']]['name']);$j++){
									$file_tmp = $_FILES[$ud['name']]['tmp_name'][$j];
				                        	        $base64Data = $this->getBase64FromFileData($file_tmp);
        		        		                	$fileName = date('YmdHis').$subscriptionId.$ud['name'].'.jpg';
	                		        		        file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
									$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
									$myfile = fopen($imgPath.$htmlPath, "a");
									fwrite($myfile, $html);
									fclose($myfile);
								}
							}
							$pdfFilePath = date('YmdHis').$subscriptionId.$ud['name'].'.pdf';
					                $this->convertpdf(file_get_contents($imgPath.$htmlPath), $imgPath.$pdfFilePath);
							$base64Data = $this->getBase64FromFileData($imgPath.$pdfFilePath);
               		        		        file_put_contents($imgPath.$htmlPath, base64_decode(str_replace(" ","+",$base64Data)));
							unlink($imgPath.$htmlPath);
							$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => $ud['fileName'], "displayName" => $ud['placeHolder'], "docData" => BASE_URL."img/subscriptionDoc/".$pdfFilePath);
	                               			$this -> ModelSubscription -> addSubscriptionDocument($docData);
							if($ud['isForOCB'] == 1){
								$data = array("appId" => $appId, "fieldName" => $ud['fileName'], "fileType" => "pdf", "traceCode" => 'COMPASIA.'.date('YmdHis').'1', "fileContent" => $base64Data);
								//$d = $this->addAppDocument($data);
							}							
						}
						$userId = $this->input->post('userId');
						$userId = trim($userId) == '' ? 0 : $userId;
						$output = array("status" => "Success", "msg" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);

						$this -> updateSubscriptionStatus($subscriptionId, '16');
						$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($customerId, 'In-Progress');
						if($userId > 0)
							$this -> ModelSubscription -> updateSubscription(array('completedStaff' => $userId), array('id' => $subscriptionId));
						addSubscriptionAudit($subscriptionId, 0, 'OCB', 'Pending Hardcopy', '');	
					}
					else
					{
						$output = array("status" => "Error", "msg" => "Please ".$uploadDoc[$i]['placeHolder'], 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}	
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function uploadOCBSignedDocument()
	{
		$input = $this->input->post();
		$apiName = 'uploadOCBSignedDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$allDoc = 1;
					//$uploadDoc = array(array("type"=>"file", "name"=>"signedApplicationForm", "placeHolder"=>"Upload Signed Application Form", "isRequired"=>1), array("type"=>"file", "name"=>"signedOcbContract", "placeHolder"=>"Upload Signed Contract", "isRequired"=>1));
					//$uploadDoc = array(array("type"=>"file", "name"=>"signedOcbContract", "placeHolder"=>"Upload Signed Contract", "isRequired"=>1));
					$uploadDoc = array(array("type"=>"file", "name"=>"signedOcbContract", "placeHolder"=>"Upload Signed Contract", "isRequired"=>1), array("type"=>"file", "name"=>"signedCreditInsurance", "placeHolder"=>"Upload Signed Credit Insurance", "isRequired"=>1));
						for($i=0;$i<sizeof($uploadDoc); $i++){
						if(!isset($_FILES[$uploadDoc[$i]['name']]))
						{
							$allDoc = 0;	
							break;
						}	
					}
					if($allDoc == 1)
					{
						$imgPath = './img/subscriptionDoc/';
						for($i=0;$i<sizeof($uploadDoc); $i++)
						{
							$ud = $uploadDoc[$i];
							$file_tmp = $_FILES[$ud['name']]['tmp_name'];
		                        	        $base64Data = $this->getBase64FromFileData($file_tmp);
        		                        	$fileName = date('YmdHis').$subscriptionId.$ud['name'].'.jpg';
	                		                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
							$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => $ud['name'], "displayName" => $ud['palceHolder'], "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
	                               			$this -> ModelSubscription -> addSubscriptionDocument($docData);
						
						}
						$output = array("status" => "Success", "msg" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else{
						$output = array("status" => "Error", "msg" => "Please ".$uploadDoc[$i]['placeHolder'], 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}	
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function getCAOCBContractDocument()
	{
		$input = $this->input->post();
		$apiName = 'getCAContractDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$this->createOCBDocument($subscriptionId, 'tnc');
					$downloadDoc = array(array("name" => "Download FINCO Contract", "id"=>"OCBContract", "link"=>''), array("name"=>"Credit Insurance", "id"=>"creditInsurance", "link"=> ''), array("name" => "Device Delivery & Payment Note", "id" => 'devicePaymentNote', "link" => ""), array("name"=> 'CompAsia Customer T&C', "id" => 'caCustomertnc', "link" => ''));
					$subDocData = $this->ModelSubscription->getSubscriptionDocument($subscriptionId);
					for($i=0;$i<sizeof($downloadDoc); $i++){
						for($j=0; $j<sizeof($subDocData); $j++){
							if(trim($subDocData[$j]['label']) == trim($downloadDoc[$i]['id'])){
								$downloadDoc[$i]['link'] = $subDocData[$j]['docData'];
								break;	
							}
						}	
					}
					$uploadDoc = array(array("type"=>"file", "name"=>"signedOcbContract", "placeHolder"=>"Upload Signed Contract", "isRequired"=>1), array("type"=>"file", "name"=>"signedCreditInsurance", "placeHolder"=>"Upload Signed Credit Insurance", "isRequired"=>1), array("type"=>"file", "name"=>"signedDevicePaymentNote", "placeHolder"=>"Upload Signed Device delivery and Payment Note", "isRequired"=>1), array("type"=>"file", "name"=>"signedCACustomertnc", "placeHolder"=>"Upload CompAsia Customer T&C", "isRequired"=>1), array("type"=>"file", "name"=>"customerSelfieWithStaff", "placeHolder"=>"Product Image", "isRequired"=>1));
					$output = array("status" => "Success", "msg" => array("download"=>$downloadDoc, "upload" => $uploadDoc), 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);	
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function getCAContractDocument()
	{
		$input = $this->input->post();
		$apiName = 'getCAContractDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$downloadDoc = array('devicePaymentNote' => '', 'caCustomertnc' => '');
					$subDocData = $this->ModelSubscription->getSubscriptionDocument($subscriptionId);
					for($i=1; $i<sizeof($subDocData); $i++)
					{
						if($subDocData[$i]['label'] == 'devicePaymentNote' and $downloadDoc['devicePaymentNote']=='')
							$downloadDoc['devicePaymentNote'] = $subDocData[$i]['docData']; 
						if($subDocData[$i]['label'] == 'caCustomertnc' and $downloadDoc['caCustomertnc']=='')
							$downloadDoc['caCustomertnc'] = $subDocData[$i]['docData']; 
						if($downloadDoc['caCustomertnc'] != '' and $downloadDoc['devicePaymentNote']!='')
							break;
					}
					$uploadDoc = array(array("type"=>"file", "name"=>"signedDevicePaymentNote", "placeHolder"=>"Upload Signed Device delivery and Payment Note", "isRequired"=>1), array("type"=>"file", "name"=>"signedCACustomertnc", "placeHolder"=>"Upload CompAsia Customer T&C", "isRequired"=>1), array("type"=>"file", "name"=>"customerSelfieWithStaff", "placeHolder"=>"Product Image", "isRequired"=>1));
					$output = array("status" => "Success", "msg" => array("download"=>$downloadDoc, "upload" => $uploadDoc), 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);	
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function getOCBContractDocument()
	{
		$input = $this->input->post();
		$apiName = 'getOCBContractDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$downloadDoc = array(array("name" => "Download FINCO Contract", "id"=>"OCBContract", "link"=>''), array("name"=>"Credit Insurance", "id"=>"creditInsurance", "link"=> ''));
					//$downloadDoc = array('ocbContract' => '', 'creditInsurance' => '');
					$subDocData = $this->ModelSubscription->getSubscriptionDocument($subscriptionId);
					//print_r($subDocData);	
					if(sizeof($subDocData)>0)
					{	
						for($j=0;$j<sizeof($downloadDoc); $j++)
						{
							//echo $downloadDoc[$j]['name'];
							if($downloadDoc[$j]['link'] == '')
							{
								for($i=1; $i<sizeof($subDocData); $i++)
								{
									if($subDocData[$i]['label'] == $downloadDoc[$j]['id'])
									{
										$downloadDoc[$j]['link'] = $subDocData[$i]['docData']; 
										//break;
									}
								}
							}
						}
					}
					$uploadDoc = array( array("type"=>"file", "name"=>"signedOcbContract", "placeHolder"=>"Upload Signed Contract", "isRequired"=>1), array("type"=>"file", "name"=>"signedCreditInsurance", "placeHolder"=>"Upload Signed Credit Insurance", "isRequired"=>1));
					$this->createOCBDocument($subscriptionId, 'tnc');
					$output = array("status" => "Success", "msg" => array("download"=>$downloadDoc, "upload" => $uploadDoc), 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);	
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function updateSubscriptionIMEI()
	{
		$input = $this->input->post();
		$apiName = 'updateSubscriptionIMEI'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		$this->form_validation->set_rules("imei", "IMEI", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$imei = $this->input->post('imei');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$isUpdate = $this->ModelSubscription->updateSubscription(array("imei" => $imei, "modifiedDate"=>date('Y-m-d H:i:s')), array("id" => $subscriptionId));
					if($isUpdate > 0){
						$this->createOCBDocument($subscriptionId, 'invoice');
						$output = array("status" => "Success", "msg" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);	
					}
					else
					{
						$output = array("status" => "Error", "msg" => "IMEI already used", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function updateSubscriptionIMEIMAFC(){
		$input = $this->input->post();
		$apiName = 'updateSubscriptionIMEIMAFC'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		$this->form_validation->set_rules("imei", "IMEI", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$imei = $this->input->post('imei');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData){
					$isUpdate = $this->ModelSubscription->updateSubscription(array("imei" => $imei, "modifiedDate"=>date('Y-m-d H:i:s')), array("id" => $subscriptionId));
					if($isUpdate > 0){
						$output = array("status" => "Success", "msg" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);	
					}
					else{
						$output = array("status" => "Error", "msg" => "IMEI already used", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
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

	public function updateSubscriptionDownpaymentMode()
	{
		$input = $this->input->post();
		$apiName = 'updateSubscriptionDownpaymentMode'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		$this->form_validation->set_rules("paymentMode", "paymentMode", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$paymentMode = $this->input->post('paymentMode');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$this->ModelSubscription->updateSubscription(array("downpaymentMode" => $paymentMode, "modifiedDate"=>date('Y-m-d H:i:s')), array("id" => $subscriptionId));
					//$this->createOCBDocument($subscriptionId, 'invoice');
					$output = array("status" => "Success", "msg" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);	
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription", 'timeStamp' => date('Y-m-d H:i:s'));
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
			}
		}	
	}

	public function getSubscriptionData()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptiondata'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');
			$status = $this->input->post('status');
			$programData = $this -> ModelProgram -> getProgram();
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
					/*$pd = json_decode(getAllProvince()['msg'],true);	
					$provinceData = array();
        			        foreach($pd as $k)
	                        		$provinceData[$k['ProvinceId']] = $k['ProvinceName'];
					$pd = json_decode(getAllCity()['msg'],true);	
					$cityData = array();
        			        foreach($pd as $k)
	                        		$cityData[$k['CityId']] = $k['CityName'];
					$pd = json_decode(getAllWard()['msg'],true);	
					$wardData = array();
        			        foreach($pd as $k)
	                        		$wardData[$k['WardId']] = $k['WardName'];
					*/

				if($bfd = $this->ModelProgram->getProgramForm($programId, 'advance'))
                                        $formData = json_decode($bfd['data'][0]['formData'],true);

                                $dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
                                $dictionaryData = array();
                                foreach($dictionaryDataRow as $d)
                                {
                                        $dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);

                                }
				// $disabled = array('idCard', 'idIssueDate', 'fullName', 'birthday', 'mobilephone');
				// $hidden = array("applicationDate","sellerNote","loanAmount","loanPurpose","loanTerm","downpayment","downpaymentPercent","commodityName","productId","shopId","sellerId","requestLoanAmount","requestLoanTerm","hasOcbAccount","hasOtherAccount","supplyAccountLater","customerAccount","customerBankProvince","customerBank","customerBankBranch","tradeId","disbursementMethod","BeneficiaryName","BeneficiaryAccount","BeneficiaryProvinceId","BeneficiaryBankId","BeneficiaryBankBranchId","customerBankBrand");
                //                $productDataRow = $this -> ModelProduct -> getProductbyProgram(1);
                //                for($i=0;$i<sizeof($productDataRow);$i++)
                //                        $productData[] = array("id" => $productDataRow[$i]['partnerSKU'], "name" => $productDataRow[$i]['name']." - ".$productDataRow[$i]['partnerSKU']);
                                $bool = array(array("id" => 1, "name" => "Yes"), array("id" => 0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"Viettel"),array("id"=>"mb", "name"=>"Mobiphone"),array("id"=>"vn", "name"=>"Vinaphone"),array("id"=>"other", "name"=>"Khác"));
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
				$wtid = array();
				for($i=0;$i<sizeof($workingTypeId); $i++){
					if(!in_array($workingTypeId[$i]['name'], array('Nội trợ','Sinh viên','Hợp đồng thời vụ','Lao động tự do','Hưu trí','Lao động tại Hộ kinh doanh')))
						$wtid[] = $workingTypeId[$i];
				}
				$workingTypeId = $wtid;
                                $nation = array(array("id"=> 1,"name"=> "Kinh"),array("id"=> 2,"name"=> "Tày"),array("id"=> 3,"name"=> "Thái"),array("id"=> 4,"name"=> "Mường"),array("id"=> 5,"name"=> "Khơ Me"),array("id"=> 6,"name"=> "H'Mông"),array("id"=> 7,"name"=> "Nùng"),array("id"=> 8,"name"=> "Hoa"),array("id"=> 9,"name"=> "Dao"),array("id"=> 10,"name"=> "Gia Rai"),array("id"=> 11,"name"=> "Ê Đê"),array("id"=> 12,"name"=> "Ba Na"),array("id"=> 13,"name"=> "Xơ Đăng"),array("id"=> 14,"name"=> "Sán Chay"),array("id"=> 15,"name"=> "Cơ Ho"),array("id"=> 16,"name"=> "Chăm"),array("id"=> 17,"name"=> "Sán Dìu"),array("id"=> 18,"name"=> "Hrê"),array("id"=> 19,"name"=> "Ra Glai"),array("id"=> 20,"name"=> "M'Nông"),array("id"=> 21,"name"=> "X’Tiêng"),array("id"=> 22,"name"=> "Bru-Vân Kiều"),array("id"=> 23,"name"=> "Thổ"),array("id"=> 24,"name"=> "Khơ Mú"),array("id"=> 25,"name"=> "Cơ Tu"),array("id"=> 26,"name"=> "Giáy"),array("id"=> 27,"name"=> "Giẻ Triêng"),array("id"=> 28,"name"=> "Tà Ôi"),array("id"=> 29,"name"=> "Mạ"),array("id"=> 30,"name"=> "Co"),array("id"=> 31,"name"=> "Chơ Ro"),array("id"=> 32,"name"=> "Xinh Mun"),array("id"=> 33,"name"=> "Hà Nhì"),array("id"=> 34,"name"=> "Chu Ru"),array("id"=> 35,"name"=> "Lào"),array("id"=> 36,"name"=> "Kháng"),array("id"=> 37,"name"=> "La Chí"),array("id"=> 38,"name"=> "Phù Lá"),array("id"=> 39,"name"=> "La Hủ"),array("id"=> 40,"name"=> "La Ha"),array("id"=> 41,"name"=> "Pà Thẻn"),array("id"=> 42,"name"=> "Chứt"),array("id"=> 43,"name"=> "Lự"),array("id"=> 44,"name"=> "Lô Lô"),array("id"=> 45,"name"=> "Mảng"),array("id"=> 46,"name"=> "Cờ Lao"),array("id"=> 47,"name"=> "Bố Y"),array("id"=> 48,"name"=> "Cống"),array("id"=> 49,"name"=> "Ngái"),array("id"=> 50,"name"=> "Si La"),array("id"=> 51,"name"=> "Pu Péo"),array("id"=> 52,"name"=> "Rơ măm"),array("id"=> 53,"name"=> "Brâu"),array("id"=> 54,"name"=> "Ơ Đu"),array("id"=> 55,"name"=> "người nước ngoài"),array("id"=> 56,"name"=> "Không xác định"));
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
                                        if($formData[$i]['name'] == 'referenceRelationship1' or $formData[$i]['name'] == 'referenceRelationship2' or $formData[$i]['name'] ==  'referenceRelationship3')
                                                $formData[$i]['data'] = $relationship;
                                        if($formData[$i]['name'] == 'workingTypeId')
                                                $formData[$i]['data'] = $workingTypeId;
                                        if($formData[$i]['name'] == 'regAddressWardId' or $formData[$i]['name'] == 'actAddressWardId' or $formData[$i]['name'] == 'curAddressWardId' or $formData[$i]['name'] == 'worAddressWardId' or $formData[$i]['name'] == 'companyAddressWardId')
                                                $formData[$i]['data'] = $wardData;
                                        if($formData[$i]['name'] == 'regAddressProvinceId' or $formData[$i]['name'] == 'actAddressProvinceId' or $formData[$i]['name'] == 'curAddressProvinceId' or $formData[$i]['name'] == 'worAddressProvinceId' or $formData[$i]['name'] == 'companyAddressProvinceId' or $formData[$i]['name'] == 'idIssuePlaceId')
                                                $formData[$i]['data'] = $provinceData;			
					if($formData[$i]['name'] == 'regAddressDistId' or $formData[$i]['name'] == 'actAddressDistId' or $formData[$i]['name'] == 'curAddressDistId' or $formData[$i]['name'] == 'worAddressDistId' or $formData[$i]['name'] == 'companyAddressDistId')
                                                $formData[$i]['data'] = $cityData;
                                        if($formData[$i]['name'] == 'hasOcbAccount' or $formData[$i]['name'] == 'supplyAccountLater')
                                                $formData[$i]['data'] = $bool;
                                        if($formData[$i]['name'] == 'disbursementMethod')
                                                $formData[$i]['data'] = $disbursementMethod;
                                        if($formData[$i]['name'] == 'Ethnic')
                                                $formData[$i]['data'] = ($nation);
                                        //if($formData[$i]['name'] == 'commodityName')
                                        //        $formData[$i]['data'] = $productData;
                                        //if($formData[$i]['name'] == 'hasOtherAccount')
                                        //        $formData[$i]['data'] = $bankData;		
                                        if($formData[$i]['name'] == 'Telco')
                                                $formData[$i]['data'] = $telco;		
					/*if(in_array($formData[$i]['name'], $disabled))
						$formData[$i]['editable'] = 'disabled';
					else
						$formData[$i]['editable'] = '';	
					if(in_array($formData[$i]['name'], $hidden))
						$formData[$i]['type'] = 'hidden';
					if($formData[$i]['name'] == 'metaData')
						$formData[$i]['type'] = 'nothing';
			*/	}





				$data = array();
				$data = $this -> ModelSubscription -> getSubscriptionByProgram($programId, $status);	

				$newData = array();
				if($data){
				for($i=0;$i<sizeof($data);$i++){
					$lappInfo = $data[$i]['appInfo'];
					$lappInfo = $this->getAllItem(json_decode($data[$i]['appInfo'],true));
					unset($data[$i]['appInfo']);
					$priceItem = array();
					$priceItemData = explode(',',$data[$i]['priceData']);
					foreach($priceItemData as $a){
						$idata = explode('=>',$a);
						$priceItem[$idata[0]] = $idata[1]; 
					}
					unset($data[$i]['priceData']);
					$nd  = array_merge($data[$i],$lappInfo, $priceItem);
					$nd['orderValue'] = $priceItem['drp'] + $priceItem['cpf'];
					for($k=0;$k<sizeof($formData);$k++){
						if($formData[$k]['type'] == 'select'){
							$tmpValue = isset($nd[$formData[$k]['name']])?$nd[$formData[$k]['name']]:'';
							if(isset($formData[$k]['data'])){
								for($j=0;$j<sizeof($formData[$k]['data']);$j++){
									if($formData[$k]['data'][$j]['id'] == $tmpValue){
										$nd[$formData[$k]['name']] = $formData[$k]['data'][$j]['name'];
										break;
									}
								}
							}
						}
					}
					for($k=0;$k<sizeof($programData);$k++){
						if($programData[$k]['id'] == $nd['programId'])
							$nd['programId'] = $programData[$k]['name'];
					}
					/*$nd['companyAddress'] = (isset($nd['ComAddressNumber']) ? $nd['ComAddressNumber'] : '').',<br>'.(isset($nd['ComAddressStreet']) ? $nd['ComAddressStreet'] : '').',<br>'.(isset($nd['ComAddressRegion']) ? $nd['ComAddressRegion'] : '').',<br>'.(isset($nd['comAddressWardId']) ? $wardData[$nd['comAddressWardId']] : '').',<br>'.(isset($nd['comAddressDistId']) ? $cityData[$nd['comAddressDistId']] : '').',<br>'. (isset($nd['comAddressProvinceId']) ? $provinceData[$nd['companyAddressProvinceId']] : '');
					$nd['registeredAddress'] = (isset($nd['RegAddressNumber']) ? $nd['RegAddressNumber'] : '').',<br>'.(isset($nd['RegAddressStreet']) ? $nd['RegAddressStreet'] : '').',<br>'.(isset($nd['RegAddressRegion']) ? $nd['RegAddressRegion'] : '').',<br>'.(isset($nd['regAddressWardId'])? ($wardData[$nd['regAddressWardId']]) : '').',<br>'.(isset($nd['regAddressDistId']) ? $cityData[$nd['regAddressDistId']] : '').',<br>'. (isset($nd['regAddressProvinceId']) ? $provinceData[$nd['regAddressProvinceId']] : '');
					$nd['currentAddress'] = (isset($nd['CurAddressNumber']) ? $nd['CurAddressNumber'] : '').',<br>'.(isset($nd['CurAddressStreet']) ? $nd['CurAddressStreet'] : '').',<br>'.(isset($nd['CurAddressRegion']) ? $nd['CurAddressRegion'] : '').',<br>'.(isset($nd['curAddressWardId']) ? $wardData[$nd['curAddressWardId']] : '').',<br>'.(isset($nd['curAddressDistId']) ? (isset($cityData[$nd['curAddressDistId']])? $cityData[$nd['curAddressDistId']]:'') : '').',<br>'. (isset($nd['curAddressProvinceId']) ? $provinceData[$nd['curAddressProvinceId']] : '');
					$nd['activeAddress'] = (isset($nd['ActAddressNumber']) ? $nd['ActAddressNumber'] : '').',<br>'.(isset($nd['ActAddressStreet']) ? $nd['ActAddressStreet'] : '').',<br>'.(isset($nd['ActAddressRegion']) ? $nd['ActAddressRegion'] : '').',<br>'.(isset($nd['actAddressWardId']) ? $wardData[$nd['actAddressWardId']] : '').',<br>'.(isset($nd['actAddressDistId']) ? (isset($cityData[$nd['actAddressDistId']])? $cityData[$nd['actAddressDistId']] : '') : '').',<br>'. (isset($nd['actAddressProvinceId']) ? $provinceData[$nd['actAddressProvinceId']] : '');
					$nd['workAddress'] = (isset($nd['WorAddressNumber']) ? $nd['WorAddressNumber'] : '').',<br>'.(isset($nd['WorAddressStreet']) ? $nd['WorAddressStreet'] : '').',<br>'.(isset($nd['WorAddressRegion']) ? $nd['WorAddressRegion'] : '').',<br>'.(isset($nd['warAddressWardId']) ? $nd['warAddressWardId'] : '').',<br>'.(isset($nd['warAddressDistId']) ? $cityData[$nd['warAddressDistId']] : '').',<br>'. (isset($nd['warAddressProvinceId']) ? $provinceData[$nd['warAddressProvinceId']] : '');
					*/
					$nd['companyAddress'] = $nd['ComAddressNumber'].',<br>'.$nd['ComAddressStreet'].',<br>'.$nd['ComAddressRegion'].',<br>'.$nd['companyAddressDistId'].',<br>'. $nd['companyAddressProvinceId'];
					$nd['registeredAddress'] = $nd['RegAddressNumber'].',<br>'.$nd['RegAddressStreet'].',<br>'.($nd['RegAddressRegion']).',<br>'.($nd['regAddressWardId']).',<br>'.($nd['regAddressDistId']).',<br>'. ($nd['regAddressProvinceId']);
					$nd['currentAddress'] = ($nd['CurAddressNumber']).',<br>'.($nd['CurAddressStreet']).',<br>'.($nd['CurAddressRegion']).',<br>'.($nd['curAddressWardId']).',<br>'.($nd['curAddressDistId']).',<br>'. ($nd['curAddressProvinceId']);
					//$nd['activeAddress'] = ($nd['ActAddressNumber']).',<br>'.($nd['ActAddressStreet']).',<br>'.($nd['ActAddressRegion']).',<br>'.($nd['actAddressWardId']).',<br>'.($nd['actAddressDistId']).',<br>'. ($nd['actAddressProvinceId']);
					$nd['activeAddress'] = ($nd['actAddressWardId']).',<br>'.($nd['actAddressDistId']).',<br>'. ($nd['actAddressProvinceId']);
					$nd['workAddress'] = ($nd['WorAddressNumber']).',<br>'.($nd['WorAddressStreet']).',<br>'.($nd['WorAddressRegion']).',<br>'.($nd['worAddressWardId']).',<br>'.($nd['worAddressDistId']).',<br>'. ($nd['worAddressProvinceId']);
					$newData[] = $nd;
				}
				}
				$output = array("status" => "Success", "msg" => $newData, 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getSubscriptionData2(){
		$input = $this->input->post();
		$apiName = 'getSubscriptiondata2'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("programId", "Program Id", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$programId = $this->input->post('programId');
			$status = $this->input->post('status');
			$programData = $this -> ModelProgram -> getProgram();
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$programData = $this -> ModelProgram -> getProgram($programId);
				$programName = $programData[0]['name'];
				$data = array();
				$data = $this -> ModelSubscription -> getSubscriptionByProgram2($programId);	
				$newData = array();
				if($data){
					for($i=0;$i<sizeof($data);$i++){
						$cData = json_decode($data[$i]['cData'],true);
						$appInfo = json_decode($data[$i]['appInfo'], true);
						unset($data[$i]['cData']);
						$priceItem = array();
						$nd['id'] = $data[$i]['id'];
						$nd['appId'] = $data[$i]['appId'];
						$nd['programName'] = $programName;
						$nd['customerId'] = $data[$i]['customerId'];
						$nd['customerName'] = isset($cData['fullname']) ? $cData['fullname'] : (isset($cData['fName']) ? $cData['fName'] : '');
						$nd['productName'] = $data[$i]['productName'] != '' ? $data[$i]['productName'] : (isset($appInfo['Device Model']) ? $appInfo['Device Model'] : '');
						$nd['orderValue'] = $data[$i]['orderValue'] != '' ? $data[$i]['orderValue'] : (isset($appInfo['loanAmount']) ? ($appInfo['loanAmount']+$appInfo['downPayment']) : (isset($appInfo['Device Total Price']) ? $appInfo['Device Total Price'] : ''));
						$nd['tenure'] = isset($cData['tenure']) ? $cData['tenure'] : (isset($data[$i]['tenure']) ? $data[$i]['tenure'] : (isset($appInfo['Loan Tenure']) ? $appInfo['Loan Tenure'] : (isset($appInfo['loanTerm']) ? $appInfo['loanTerm'] : '')));
						$nd['statusName'] = $data[$i]['statusName'];
						$nd['capf'] = 0;
						$newData[] = $nd;
					}
				}
				$output = array("status" => "Success", "msg" => $newData, 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getSubscriptionFormData()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionFormData'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$programData = $this -> ModelProgram -> getProgram();
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				if($bfd = $this->ModelProgram->getProgramForm('1', 'advance'))
                                        $formData = json_decode($bfd['data'][0]['formData'],true);

                                $dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
                                $dictionaryData = array();
                                foreach($dictionaryDataRow as $d)
                                {
                                        $dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);

                                }
				$disabled = array('idCard', 'idIssueDate', 'fullName', 'birthday', 'mobilephone');
				$hidden = array("applicationDate","sellerNote","loanAmount","loanPurpose","loanTerm","downpayment","downpaymentPercent","commodityName","productId","shopId","sellerId","requestLoanAmount","requestLoanTerm","hasOcbAccount","hasOtherAccount","supplyAccountLater","customerAccount","customerBankProvince","customerBank","customerBankBranch","tradeId","disbursementMethod","BeneficiaryName","BeneficiaryAccount","BeneficiaryProvinceId","BeneficiaryBankId","BeneficiaryBankBranchId","customerBankBrand");
                                $productDataRow = $this -> ModelProduct -> getProductbyProgram(1);
                                for($i=0;$i<sizeof($productDataRow);$i++)
                                        $productData[] = array("id" => $productDataRow[$i]['partnerSKU'], "name" => $productDataRow[$i]['name']." - ".$productDataRow[$i]['partnerSKU']);
                                //print_r($dictionaryData);
                                $bool = array(array("id" => 1, "name" => "Yes"), array("id" => 0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"Viettel"),array("id"=>"mb", "name"=>"Mobiphone"),array("id"=>"vn", "name"=>"Vinaphone"),array("id"=>"other", "name"=>"Khác"));
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
				$wtid = array();
				for($i=0;$i<sizeof($workingTypeId); $i++){
					if(!in_array($workingTypeId[$i]['name'], array('Nội trợ','Sinh viên','Hợp đồng thời vụ','Lao động tự do','Hưu trí','Lao động tại Hộ kinh doanh')))
						$wtid[] = $workingTypeId[$i];
				}
				$workingTypeId = $wtid;
                                $nation = array(array("id"=> 1,"name"=> "Kinh"),array("id"=> 2,"name"=> "Tày"),array("id"=> 3,"name"=> "Thái"),array("id"=> 4,"name"=> "Mường"),array("id"=> 5,"name"=> "Khơ Me"),array("id"=> 6,"name"=> "H'Mông"),array("id"=> 7,"name"=> "Nùng"),array("id"=> 8,"name"=> "Hoa"),array("id"=> 9,"name"=> "Dao"),array("id"=> 10,"name"=> "Gia Rai"),array("id"=> 11,"name"=> "Ê Đê"),array("id"=> 12,"name"=> "Ba Na"),array("id"=> 13,"name"=> "Xơ Đăng"),array("id"=> 14,"name"=> "Sán Chay"),array("id"=> 15,"name"=> "Cơ Ho"),array("id"=> 16,"name"=> "Chăm"),array("id"=> 17,"name"=> "Sán Dìu"),array("id"=> 18,"name"=> "Hrê"),array("id"=> 19,"name"=> "Ra Glai"),array("id"=> 20,"name"=> "M'Nông"),array("id"=> 21,"name"=> "X’Tiêng"),array("id"=> 22,"name"=> "Bru-Vân Kiều"),array("id"=> 23,"name"=> "Thổ"),array("id"=> 24,"name"=> "Khơ Mú"),array("id"=> 25,"name"=> "Cơ Tu"),array("id"=> 26,"name"=> "Giáy"),array("id"=> 27,"name"=> "Giẻ Triêng"),array("id"=> 28,"name"=> "Tà Ôi"),array("id"=> 29,"name"=> "Mạ"),array("id"=> 30,"name"=> "Co"),array("id"=> 31,"name"=> "Chơ Ro"),array("id"=> 32,"name"=> "Xinh Mun"),array("id"=> 33,"name"=> "Hà Nhì"),array("id"=> 34,"name"=> "Chu Ru"),array("id"=> 35,"name"=> "Lào"),array("id"=> 36,"name"=> "Kháng"),array("id"=> 37,"name"=> "La Chí"),array("id"=> 38,"name"=> "Phù Lá"),array("id"=> 39,"name"=> "La Hủ"),array("id"=> 40,"name"=> "La Ha"),array("id"=> 41,"name"=> "Pà Thẻn"),array("id"=> 42,"name"=> "Chứt"),array("id"=> 43,"name"=> "Lự"),array("id"=> 44,"name"=> "Lô Lô"),array("id"=> 45,"name"=> "Mảng"),array("id"=> 46,"name"=> "Cờ Lao"),array("id"=> 47,"name"=> "Bố Y"),array("id"=> 48,"name"=> "Cống"),array("id"=> 49,"name"=> "Ngái"),array("id"=> 50,"name"=> "Si La"),array("id"=> 51,"name"=> "Pu Péo"),array("id"=> 52,"name"=> "Rơ măm"),array("id"=> 53,"name"=> "Brâu"),array("id"=> 54,"name"=> "Ơ Đu"),array("id"=> 55,"name"=> "người nước ngoài"),array("id"=> 56,"name"=> "Không xác định"));
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
                                        if($formData[$i]['name'] == 'referenceRelationship1' or $formData[$i]['name'] == 'referenceRelationship2' or $formData[$i]['name'] ==  'referenceRelationship3')
                                                $formData[$i]['data'] = $relationship;
                                        if($formData[$i]['name'] == 'workingTypeId')
                                                $formData[$i]['data'] = $workingTypeId;
                                        if($formData[$i]['name'] == 'regAddressWardId' or $formData[$i]['name'] == 'actAddressWardId' or $formData[$i]['name'] == 'curAddressWardId' or $formData[$i]['name'] == 'worAddressWardId' or $formData[$i]['name'] == 'companyAddressWardId')
                                                $formData[$i]['data'] = $wardData;
                                        if($formData[$i]['name'] == 'regAddressProvinceId' or $formData[$i]['name'] == 'actAddressProvinceId' or $formData[$i]['name'] == 'curAddressProvinceId' or $formData[$i]['name'] == 'worAddressProvinceId' or $formData[$i]['name'] == 'companyAddressProvinceId' or $formData[$i]['name'] == 'idIssuePlaceId')
                                                $formData[$i]['data'] = $provinceData;			
					if($formData[$i]['name'] == 'regAddressDistId' or $formData[$i]['name'] == 'actAddressDistId' or $formData[$i]['name'] == 'curAddressDistId' or $formData[$i]['name'] == 'worAddressDistId' or $formData[$i]['name'] == 'companyAddressDistId')
                                                $formData[$i]['data'] = $cityData;
                                        if($formData[$i]['name'] == 'hasOcbAccount' or $formData[$i]['name'] == 'supplyAccountLater')
                                                $formData[$i]['data'] = $bool;
                                        if($formData[$i]['name'] == 'disbursementMethod')
                                                $formData[$i]['data'] = $disbursementMethod;
                                        if($formData[$i]['name'] == 'Ethnic')
                                                $formData[$i]['data'] = ($nation);
                                        //if($formData[$i]['name'] == 'commodityName')
                                        //        $formData[$i]['data'] = $productData;
                                        //if($formData[$i]['name'] == 'hasOtherAccount')
                                        //        $formData[$i]['data'] = $bankData;		
                                        if($formData[$i]['name'] == 'Telco')
                                                $formData[$i]['data'] = $telco;		
					if(in_array($formData[$i]['name'], $disabled))
						$formData[$i]['editable'] = 'disabled';
					else
						$formData[$i]['editable'] = '';	
					if(in_array($formData[$i]['name'], $hidden))
						$formData[$i]['type'] = 'hidden';
					if($formData[$i]['name'] == 'metaData')
						$formData[$i]['type'] = 'nothing';
				}
				
				$data = $this -> ModelSubscription -> getSubscriptionById($subscriptionId);	
				$newData = array();	
				for($i=0;$i<sizeof($data);$i++){
					//$lappInfo = $data[$i]['appInfo'];
					//print_r( json_decode(json_decode($data[$i]['appInfo'],true)['metaData'],true));
					$lappInfo = $this->getAllItem(json_decode($data[$i]['appInfo'],true));
					unset($data[$i]['appInfo']);
					$nd = array_merge($data[$i],$lappInfo);
					for($k=0;$k<sizeof($formData);$k++){
						if(isset($nd[$formData[$k]['name']])){
							$formData[$k]['value'] = $nd[$formData[$k]['name']];
						
						}	
					}
				}
				$output = array("status" => "Success", "msg" => $formData, 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getSubscriptionFormDataMAFC()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionFormDataMAFC'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$programData = $this -> ModelProgram -> getProgram();
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this -> ModelSubscription -> getSubscriptionById($subscriptionId);
				$programId = $subscriptionData[0]['programId'];
				$d = array("isDeleted" => 0, "programId" => $programId, "phase" => "Application Submission");
				if($bfd = $this->ModelProgram->getFormData($d))
					$formData = json_decode($bfd['data'][0]['data'],true);
				// if($bfd = $this->ModelProgram->getProgramForm('1', 'advance'))
				// 	$formData = json_decode($bfd['data'][0]['formData'],true);

                                $dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
                                $dictionaryData = array();
                                foreach($dictionaryDataRow as $d)
                                {
                                        $dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);

                                }
				$disabled = array('idCard', 'idIssueDate', 'fullName', 'birthday', 'mobilephone', 'downPayment', 'tenure', 'ReferenceRelationCode', 'ReferenceRelationCode_2');
				$hidden = array("applicationDate","sellerNote","loanAmount","loanPurpose","loanTerm","downpayment","downpaymentPercent","commodityName","productId","shopId","sellerId","requestLoanAmount","requestLoanTerm","hasOcbAccount","hasOtherAccount","supplyAccountLater","customerAccount","customerBankProvince","customerBank","customerBankBranch","tradeId","disbursementMethod","BeneficiaryName","BeneficiaryAccount","BeneficiaryProvinceId","BeneficiaryBankId","BeneficiaryBankBranchId","customerBankBrand", "addressCheckbox");
                                $productDataRow = $this -> ModelProduct -> getProductbyProgram(1);
                                for($i=0;$i<sizeof($productDataRow);$i++)
                                        $productData[] = array("id" => $productDataRow[$i]['partnerSKU'], "name" => $productDataRow[$i]['name']." - ".$productDataRow[$i]['partnerSKU']);
                                //print_r($dictionaryData);
                                $bool = array(array("id" => 1, "name" => "Yes"), array("id" => 0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"Viettel"),array("id"=>"mb", "name"=>"Mobiphone"),array("id"=>"vn", "name"=>"Vinaphone"),array("id"=>"other", "name"=>"Khác"));
                                $disbursementMethod = array(array("id" => "BANK", "name" => "BANK"),array("id" => "CARD", "name" => "CARD"));
                                $provinceDataRow =  mafcMasterData('getCity')['msg'];
								for($i=0;$i<sizeof($provinceDataRow);$i++)
										$provinceData[] = array("id"=>$provinceDataRow[$i]['stateid'], "name" => $provinceDataRow[$i]['statedesc']);
								$cityDataRow =  mafcMasterData('getDistrict')['msg'];
								for($i=0;$i<sizeof($cityDataRow);$i++)
										$cityData[] = array("id"=>$cityDataRow[$i]['lmc_CITYID_C'], "name" => $cityDataRow[$i]['lmc_CITYNAME_C']);
								$wardDataRow =  mafcMasterData('getWard')['msg'];
								for($i=0;$i<sizeof($wardDataRow);$i++)
										$wardData[] = array("id"=>$wardDataRow[$i]['zipcode'], "name" => $wardDataRow[$i]['zipdesc']." (".$wardDataRow[$i]['zipcode'].")");
                                $bankDataRow =  json_decode(getOCBBank()['msg'],true);
                                for($i=0;$i<sizeof($bankDataRow);$i++)
                                        $bankData[] = array("id"=>$bankDataRow[$i]['BankBranchId'], "name" => $bankDataRow[$i]['BankBranchName']);
								$relationCodeRow = mafcRelationCodeData();
								for($i=0;$i<sizeof($relationCodeRow);$i++)
									$relationData[] = array("id"=>$relationCodeRow[$i]['CODE'], "name" => $relationCodeRow[$i]['LABEL']);

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
				$wtid = array();
				for($i=0;$i<sizeof($workingTypeId); $i++){
					if(!in_array($workingTypeId[$i]['name'], array('Nội trợ','Sinh viên','Hợp đồng thời vụ','Lao động tự do','Hưu trí','Lao động tại Hộ kinh doanh')))
						$wtid[] = $workingTypeId[$i];
				}
				$workingTypeId = $wtid;
                                $nation = array(array("id"=> 1,"name"=> "Kinh"),array("id"=> 2,"name"=> "Tày"),array("id"=> 3,"name"=> "Thái"),array("id"=> 4,"name"=> "Mường"),array("id"=> 5,"name"=> "Khơ Me"),array("id"=> 6,"name"=> "H'Mông"),array("id"=> 7,"name"=> "Nùng"),array("id"=> 8,"name"=> "Hoa"),array("id"=> 9,"name"=> "Dao"),array("id"=> 10,"name"=> "Gia Rai"),array("id"=> 11,"name"=> "Ê Đê"),array("id"=> 12,"name"=> "Ba Na"),array("id"=> 13,"name"=> "Xơ Đăng"),array("id"=> 14,"name"=> "Sán Chay"),array("id"=> 15,"name"=> "Cơ Ho"),array("id"=> 16,"name"=> "Chăm"),array("id"=> 17,"name"=> "Sán Dìu"),array("id"=> 18,"name"=> "Hrê"),array("id"=> 19,"name"=> "Ra Glai"),array("id"=> 20,"name"=> "M'Nông"),array("id"=> 21,"name"=> "X’Tiêng"),array("id"=> 22,"name"=> "Bru-Vân Kiều"),array("id"=> 23,"name"=> "Thổ"),array("id"=> 24,"name"=> "Khơ Mú"),array("id"=> 25,"name"=> "Cơ Tu"),array("id"=> 26,"name"=> "Giáy"),array("id"=> 27,"name"=> "Giẻ Triêng"),array("id"=> 28,"name"=> "Tà Ôi"),array("id"=> 29,"name"=> "Mạ"),array("id"=> 30,"name"=> "Co"),array("id"=> 31,"name"=> "Chơ Ro"),array("id"=> 32,"name"=> "Xinh Mun"),array("id"=> 33,"name"=> "Hà Nhì"),array("id"=> 34,"name"=> "Chu Ru"),array("id"=> 35,"name"=> "Lào"),array("id"=> 36,"name"=> "Kháng"),array("id"=> 37,"name"=> "La Chí"),array("id"=> 38,"name"=> "Phù Lá"),array("id"=> 39,"name"=> "La Hủ"),array("id"=> 40,"name"=> "La Ha"),array("id"=> 41,"name"=> "Pà Thẻn"),array("id"=> 42,"name"=> "Chứt"),array("id"=> 43,"name"=> "Lự"),array("id"=> 44,"name"=> "Lô Lô"),array("id"=> 45,"name"=> "Mảng"),array("id"=> 46,"name"=> "Cờ Lao"),array("id"=> 47,"name"=> "Bố Y"),array("id"=> 48,"name"=> "Cống"),array("id"=> 49,"name"=> "Ngái"),array("id"=> 50,"name"=> "Si La"),array("id"=> 51,"name"=> "Pu Péo"),array("id"=> 52,"name"=> "Rơ măm"),array("id"=> 53,"name"=> "Brâu"),array("id"=> 54,"name"=> "Ơ Đu"),array("id"=> 55,"name"=> "người nước ngoài"),array("id"=> 56,"name"=> "Không xác định"));
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
                                        if($formData[$i]['name'] == 'referenceRelationship1' or $formData[$i]['name'] == 'referenceRelationship2' or $formData[$i]['name'] ==  'referenceRelationship3')
                                                $formData[$i]['data'] = $relationship;
                                        if($formData[$i]['name'] == 'workingTypeId')
                                                $formData[$i]['data'] = $workingTypeId;
												if($formData[$i]['name'] == 'zipcode' or $formData[$i]['name'] == 'zipcode_2' or $formData[$i]['name'] == 'empZipcode')
												$formData[$i]['data'] = $wardData;
											if($formData[$i]['name'] == 'stateId' or $formData[$i]['name'] == 'stateId_2' or $formData[$i]['name'] == 'empStateId' or $formData[$i]['name'] == 'statePA'){
												$formData[$i]['data'] = $provinceData;			
												// echo "hello";
											}	
											if($formData[$i]['name'] == 'ReferenceRelationCode_2' or $formData[$i]['name'] == 'ReferenceRelationCode')
												$formData[$i]['data'] = $relationData;
											if($formData[$i]['name'] == 'city' or $formData[$i]['name'] == 'city_2' or $formData[$i]['name'] == 'empCity')
												$formData[$i]['data'] = $cityData;
											if($formData[$i]['name'] == 'hasOcbAccount' or $formData[$i]['name'] == 'supplyAccountLater')
                                                $formData[$i]['data'] = $bool;
                                        if($formData[$i]['name'] == 'disbursementMethod')
                                                $formData[$i]['data'] = $disbursementMethod;
                                        if($formData[$i]['name'] == 'Ethnic')
                                                $formData[$i]['data'] = ($nation);
                                        //if($formData[$i]['name'] == 'commodityName')
                                        //        $formData[$i]['data'] = $productData;
                                        //if($formData[$i]['name'] == 'hasOtherAccount')
                                        //        $formData[$i]['data'] = $bankData;		
                                        if($formData[$i]['name'] == 'Telco')
                                                $formData[$i]['data'] = $telco;	
										$select = array('zipcode', 'zipcode_2', 'empZipcode', 'stateId', 'stateId_2', 'empStateId', 'city', 'city_2', 'empCity', 'statePA', 'ReferenceRelationCode_2', 'ReferenceRelationCode');	
					if(in_array($formData[$i]['name'], $disabled))
						$formData[$i]['editable'] = 'disabled';
					else
						$formData[$i]['editable'] = '';	
					if(in_array($formData[$i]['name'], $hidden))
						$formData[$i]['type'] = 'hidden';
					if(in_array($formData[$i]['name'],$select))
						$formData[$i]['type'] = 'select';
					if($formData[$i]['name'] == 'metaData')
						$formData[$i]['type'] = 'nothing';
				}
				
				$data = $subscriptionData;
				// $data = $this -> ModelSubscription -> getSubscriptionById($subscriptionId);	
				$newData = array();	
				for($i=0;$i<sizeof($data);$i++){
					//$lappInfo = $data[$i]['appInfo'];
					//print_r( json_decode(json_decode($data[$i]['appInfo'],true)['metaData'],true));
					$lappInfo = $this->getAllItem(json_decode($data[$i]['appInfo'],true));
					unset($data[$i]['appInfo']);
					$nd = array_merge($data[$i],$lappInfo);
					for($k=0;$k<sizeof($formData);$k++){
						if(isset($nd[$formData[$k]['name']])){
							$formData[$k]['value'] = $nd[$formData[$k]['name']];
						
						}	
					}
				}
				$output = array("status" => "Success", "msg" => $formData, 'timeStamp' => date('Y-m-d H:i:s'));
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

	// Get subscription By Store / Channel Partner 
	public function getSubscriptionByStore()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionByStore'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("storeId", "Store Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$storeId = $this->input->post('storeId');
			$status = $this->input->post('status');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this -> ModelSubscription -> getSubscriptionByStore($storeId, $status);
				$output = array("status" => "Success", "msg" => $subscriptionData, 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getSubscriptionByStoreMAFC(){
		$input = $this->input->post();
		$apiName = 'getSubscriptionByStoreMAFC'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("storeId", "Store Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$storeId = $this->input->post('storeId');
			$status = $this->input->post('status');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this -> ModelSubscription -> getSubscriptionByStoreMAFC($storeId, $status);
				$output = array("status" => "Success", "msg" => $subscriptionData, 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
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

	// Get subscription By Store / Channel Partner 
	public function getSubscriptionStatus()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionStatus'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this -> ModelSubscription -> getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$programId = $subscriptionData[0]['programId'];
					if($programId == 1)
					{
						$appId  = $subscriptionData[0]['appId']; 
						if($appId > 0)
						{
							$data = getSubscriptionStatusOCB($appId);
							if($data['status'] == 'Success'){
								$data = $data['msg'][0];
								$newStatus = '';
								if($data['status'] == -1)
									$newStatus = '12';
								if($subscriptionData[0]['status'] != $newStatus and trim($newStatus) != ''){
									$this -> updateSubscriptionStatus($subscriptionId, $newStatus);
									addSubscriptionAudit($subscriptionId, 0, 'OCB', $newStatus, $data['statusDesc']);	
								}	

								$output = array("status" => "Success", "msg" => $newStatus, "remark" => $data['statusDesc'], 'timeStamp' => date('Y-m-d H:i:s'));
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
							else
							{
								$output = $data; 
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
						}
						else
						{
							$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
					}
					else
					{	
						$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
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

	// Cancel subscription By Store / Channel Partner 
	public function cancelSubscriptionByStore()
	{
		$input = $this->input->post();
		$apiName = 'cancelSubscriptionByStore'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		$this->form_validation->set_rules("storeId", "storeId", "required");
		$this->form_validation->set_rules("reason", "Reason", "required");
		$this->form_validation->set_rules("remark", "Remark", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this -> ModelSubscription -> getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$programId = $subscriptionData[0]['programId'];
					$customerId = $subscriptionData[0]['customerId'];
					if($programId == 1)
					{
						$appId  = $subscriptionData[0]['appId']; 
						if($appId > 0)
						{
							$cancelType = array("ni"=>"1", "wi"=>2);
							$reason = $this->input->post('reason'); 
							$remark = $this->input->post('remark'); 
							$apiData = array("appId" => $appId, "cancelType" => $cancelType[$reason], "cancelReason" => $remark);
							$data = cancelApplicationOCB(json_encode($apiData));
							if($data['status'] == 'Success'){
								if($reason == 'ni')
									$this -> ModelCustomer -> blockCustomer($customerId, 30);
								if($reason == 'wi')
									$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($customerId, 'lead');
								$data = $data['msg'][0];
								$newStatus = $cancelType[$reason] == 1 ? '14' :($cancelType[$reason] == 2 ? '15' : '12');
								$this -> updateSubscriptionStatus($subscriptionId, $newStatus);
								$this->ModelSubscription->updateSubscription(array("remarks"=>$remark), array("id" => $subscriptionId));
								addSubscriptionAudit($subscriptionId, 0, 'OCB', $newStatus, $reason);	

								$output = array("status" => "Success", "msg" => $newStatus, "remark" => $data, 'timeStamp' => date('Y-m-d H:i:s'));
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
							else
							{
								$output = $data; 
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
						}
						else
						{
							$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
					}
					else
					{	
						$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function cancelApplicationByCRM()
	{
		$input = $this->input->post();
		$apiName = 'cancelSubscriptionByCRM'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		$this->form_validation->set_rules("reason", "Reason", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this -> ModelSubscription -> getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$programId = $subscriptionData[0]['programId'];
					$customerId = $subscriptionData[0]['customerId'];
					if($programId == 1)
					{
						$appId  = $subscriptionData[0]['appId']; 
						if($appId > 0)
						{
							$cancelType = array("ni"=>"1", "wi"=>2);
							$reason = $this->input->post('reason'); 
							$apiData = array("appId" => $appId, "cancelType" => $cancelType[$reason], "cancelReason" => 'Cancel');
							$data = cancelApplicationOCB(json_encode($apiData));
							if($data['status'] == 'Success'){
								if($reason == 'ni')
									$this -> ModelCustomer -> blockCustomer($customerId, 30);
								if($reason == 'wi')
									$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($customerId, 'lead');
								$data = $data['msg'][0];
								$this->ModelSubscription->updateSubscription(array("remarks"=>$remark), array("id" => $subscriptionId));

								$output = array("status" => "Success", "msg" => $newStatus, "remark" => $data, 'timeStamp' => date('Y-m-d H:i:s'));
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
							else
							{
								$output = $data; 
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
						}
						else
						{
							$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
							//$this -> ModelUtility -> saveLog($apiName, $input, $output);
							$this->LogManager->logApi($apiName, $input, $output);
							echo json_encode($output, JSON_UNESCAPED_UNICODE);
						}
					}
					else
					{	
						$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function updateSubscriptionStatus($subscriptionId, $status){
		$data = array("status" => $status, "modifiedDate" => date('Y-m-d H:i:s'));
		$whereData = array("id" => $subscriptionId);		
		$this->ModelSubscription->updateSubscription($data, $whereData);
	}
	
	public function getSubscriptionFormDataByCustomerGroupMAFC()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionFormDataByCustomerMAFC'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("customerId", "Customer Id", "required");
		$this->form_validation->set_rules("programId", "programId", "required");
		$this->form_validation->set_rules("sku", "sku", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$customerId = $this->input->post('customerId');
			$sku = $this->input->post('sku');
			$programId = $this->input->post('programId');
			$employeeId = $this -> input -> post('employeeId');
			$subscriptionId = $this -> input -> post('subscriptionId');
			$sellerId = '';	
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				// echo "hello".mafcMasterData('getDistrict');
				$d = array("isDeleted" => 0, "programId" => $programId, "phase" => "Application Submission");
				if($bfd = $this->ModelProgram->getFormData($d))
					$formData = json_decode($bfd['data'][0]['data'],true);
					// print_r($formData);
				$sellerData = $this->ModelEmployee->getEmployeeById($employeeId);
				if($sellerData){
					$sellerId = $sellerData[0]['userId']; 
				}	
				$dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
				$dictionaryData = array();
				foreach($dictionaryDataRow as $d){
					$dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);
			    }
			
				$big= array("dob", "mobile", "partnerId", "posId", "idIssuedDate", "idIssuedBy", "idCard", "idIssuePlaceId", "workingTypeId", "phone", "mobilephone", "Telco", "email", "IdExpireDate");
				$pig = array("title", "stateId", "city", "zipcode", "address", "stateId_2", "city_2", "zipcode_2", "address_2", "houseStatus", "maritalStatus", "addressCheckbox");
				$eig = array("empTitle", "empName", "empPosition", "empSalary", "empYear", "empMonth", "otherIncome", "empAddress", "empCity", "empStateId", "empZipcode", "empPhone", "additionalIdType", "addIdentification", "empContractType");
				$rig = array("refName", "refRelationship", "ReferenceRelationCode", "refContact", "refName_2", "referenceRelationship2", "refRelationship_2", "ReferenceRelationCode_2", "refContact_2");
                
				$productDataRow = $this -> ModelProduct -> getProductDetailBySKU($sku);
				for($i=0;$i<sizeof($productDataRow);$i++)
					$productData[] = array("id" => $productDataRow[$i]['partnerSKU'], "name" => $productDataRow[$i]['name']." - ".$productDataRow[$i]['partnerSKU']);
				//print_r($dictionaryData);
				$bool = array(array("id" => 1, "name" => "Yes"), array("id" => 0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"Viettel"),array("id"=>"mb", "name"=>"Mobiphone"),array("id"=>"vn", "name"=>"Vinaphone"),array("id"=>"other", "name"=>"Khác"));
				$disbursementMethod = array(array("id" => "BANK", "name" => "BANK"),array("id" => "CARD", "name" => "CARD"));
				$provinceDataRow =  mafcMasterData('getCity')['msg'];
				for($i=0;$i<sizeof($provinceDataRow);$i++)
					$provinceData[] = array("id"=>$provinceDataRow[$i]['stateid'], "name" => $provinceDataRow[$i]['statedesc']);
				$cityDataRow =  mafcMasterData('getDistrict')['msg'];
				for($i=0;$i<sizeof($cityDataRow);$i++)
					$cityData[] = array("id"=>$cityDataRow[$i]['lmc_CITYID_C'], "name" => $cityDataRow[$i]['lmc_CITYNAME_C']);
				$wardDataRow =  mafcMasterData('getWard')['msg'];
				for($i=0;$i<sizeof($wardDataRow);$i++)
					$wardData[] = array("id"=>$wardDataRow[$i]['zipcode'], "name" => $wardDataRow[$i]['zipdesc']." (".$wardDataRow[$i]['zipcode'].")");
				$bankDataRow =  json_decode(getOCBBank()['msg'],true);
				for($i=0;$i<sizeof($bankDataRow);$i++)
					$bankData[] = array("id"=>$bankDataRow[$i]['BankBranchId'], "name" => $bankDataRow[$i]['BankBranchName']);
				$relationCodeRow = mafcRelationCodeData();
				for($i=0;$i<sizeof($relationCodeRow);$i++)
					$relationData[] = array("id"=>$relationCodeRow[$i]['CODE'], "name" => $relationCodeRow[$i]['LABEL']);

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
				$wtid = array();
				for($i=0;$i<sizeof($workingTypeId); $i++){
					if(!in_array($workingTypeId[$i]['name'], array('Nội trợ','Sinh viên','Hợp đồng thời vụ','Lao động tự do','Hưu trí','Lao động tại Hộ kinh doanh')))
						$wtid[] = $workingTypeId[$i];
				}
				$workingTypeId = $wtid;
				$nation = array(array("id"=> 1,"name"=> "Kinh"),array("id"=> 2,"name"=> "Tày"),array("id"=> 3,"name"=> "Thái"),array("id"=> 4,"name"=> "Mường"),array("id"=> 5,"name"=> "Khơ Me"),array("id"=> 6,"name"=> "H'Mông"),array("id"=> 7,"name"=> "Nùng"),array("id"=> 8,"name"=> "Hoa"),array("id"=> 9,"name"=> "Dao"),array("id"=> 10,"name"=> "Gia Rai"),array("id"=> 11,"name"=> "Ê Đê"),array("id"=> 12,"name"=> "Ba Na"),array("id"=> 13,"name"=> "Xơ Đăng"),array("id"=> 14,"name"=> "Sán Chay"),array("id"=> 15,"name"=> "Cơ Ho"),array("id"=> 16,"name"=> "Chăm"),array("id"=> 17,"name"=> "Sán Dìu"),array("id"=> 18,"name"=> "Hrê"),array("id"=> 19,"name"=> "Ra Glai"),array("id"=> 20,"name"=> "M'Nông"),array("id"=> 21,"name"=> "X’Tiêng"),array("id"=> 22,"name"=> "Bru-Vân Kiều"),array("id"=> 23,"name"=> "Thổ"),array("id"=> 24,"name"=> "Khơ Mú"),array("id"=> 25,"name"=> "Cơ Tu"),array("id"=> 26,"name"=> "Giáy"),array("id"=> 27,"name"=> "Giẻ Triêng"),array("id"=> 28,"name"=> "Tà Ôi"),array("id"=> 29,"name"=> "Mạ"),array("id"=> 30,"name"=> "Co"),array("id"=> 31,"name"=> "Chơ Ro"),array("id"=> 32,"name"=> "Xinh Mun"),array("id"=> 33,"name"=> "Hà Nhì"),array("id"=> 34,"name"=> "Chu Ru"),array("id"=> 35,"name"=> "Lào"),array("id"=> 36,"name"=> "Kháng"),array("id"=> 37,"name"=> "La Chí"),array("id"=> 38,"name"=> "Phù Lá"),array("id"=> 39,"name"=> "La Hủ"),array("id"=> 40,"name"=> "La Ha"),array("id"=> 41,"name"=> "Pà Thẻn"),array("id"=> 42,"name"=> "Chứt"),array("id"=> 43,"name"=> "Lự"),array("id"=> 44,"name"=> "Lô Lô"),array("id"=> 45,"name"=> "Mảng"),array("id"=> 46,"name"=> "Cờ Lao"),array("id"=> 47,"name"=> "Bố Y"),array("id"=> 48,"name"=> "Cống"),array("id"=> 49,"name"=> "Ngái"),array("id"=> 50,"name"=> "Si La"),array("id"=> 51,"name"=> "Pu Péo"),array("id"=> 52,"name"=> "Rơ măm"),array("id"=> 53,"name"=> "Brâu"),array("id"=> 54,"name"=> "Ơ Đu"),array("id"=> 55,"name"=> "người nước ngoài"),array("id"=> 56,"name"=> "Không xác định"));
				for($i=0;$i<sizeof($formData); $i++){
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
					if($formData[$i]['name'] == 'referenceRelationship1' or $formData[$i]['name'] == 'referenceRelationship2' or $formData[$i]['name'] ==  'referenceRelationship3')
						$formData[$i]['data'] = $relationship;
					if($formData[$i]['name'] == 'workingTypeId')
						$formData[$i]['data'] = $workingTypeId;
					if($formData[$i]['name'] == 'zipcode' or $formData[$i]['name'] == 'zipcode_2' or $formData[$i]['name'] == 'empZipcode')
						$formData[$i]['data'] = $wardData;
					if($formData[$i]['name'] == 'stateId' or $formData[$i]['name'] == 'stateId_2' or $formData[$i]['name'] == 'empStateId' or $formData[$i]['name'] == 'statePA'){
						$formData[$i]['data'] = $provinceData;			
						// echo "hello";
					}	
					if($formData[$i]['name'] == 'ReferenceRelationCode' or $formData[$i]['name'] == 'ReferenceRelationCode_2')
						$formData[$i]['data'] = $relationData;
					if($formData[$i]['name'] == 'city' or $formData[$i]['name'] == 'city_2' or $formData[$i]['name'] == 'empCity')
						$formData[$i]['data'] = $cityData;
					if($formData[$i]['name'] == 'hasOcbAccount' or $formData[$i]['name'] == 'supplyAccountLater')
						$formData[$i]['data'] = $bool;
					if($formData[$i]['name'] == 'disbursementMethod')
						$formData[$i]['data'] = $disbursementMethod;
					if($formData[$i]['name'] == 'Ethnic')
						$formData[$i]['data'] = ($nation);
					//if($formData[$i]['name'] == 'commodityName')
					//        $formData[$i]['data'] = $productData;
					if($formData[$i]['name'] == 'hasOtherAccount')
						$formData[$i]['data'] = $bankData;		
					if($formData[$i]['name'] == 'Telco')
						$formData[$i]['data'] = $telco;		
				}
				$oldSubscriptionId = 0;
				$oldData = $this->ModelSubscription->getSubscriptionByCustomerId($customerId);
				$appId = '';
				if($oldData){
					$oldData = $oldData[sizeof($oldData)-1];
					//print_r($oldData);
					$oldSubscriptionId = $oldData['id'];
					$appId = $oldData['appId'];
					//$oldAppInfo = json_decode($oldData['appInfo'], true);
					//$oldAppInfo['metaData'] = json_decode($oldAppInfo['metaData'], true); 	
					// print_r($oldAppInfo);
				}
				$oldKeys = array("referenceFullName1", "referenceRelationship1", "referencePhone1", "reference1Gender", "referenceFullName2", "referenceRelationship2", "referencePhone2", "reference2Gender", "referenceFullName3", "referenceRelationship3", "referencePhone3", "reference3Gender", "gender", "educationId", "regAddressStatus", "companyName", "companyAddressProvinceId", "companyAddressDistId", "companyAddressWardId", "worAddressProvinceId", "worAddressDistId", "worAddressWardId", "companyPhone", "companyTypeId", "careerId", "positionId", "income", "familyIncome", "expense", "familyExpense", "numerOfFamilyExpense", "marriedId", "spouseFullName", "spouseIdCard", "spousePhone", "numerOfFamily", "hasOcbAccount", "livingYear", "livingMonth", "accommodationsId", "workingYear", "workingMonth", "mailAddressId");
				$oldMetaKeys = array("oldIdCard", "Ethnic", "RegAddressNumber", "RegAddressStreet", "RegAddressRegion", "CurAddressNumber", "CurAddressStreet", "CurAddressRegion", "ComAddressNumber", "ComAddressStreet", "ComAddressRegion", "WorAddressNumber", "WorAddressStreet", "WorAddressRegion", "IdExpireDate", "idIssuePlaceId");
				$data = $this -> ModelCustomer -> getCustomer('id', $customerId);	
				$productData = $this -> ModelProduct -> getProductDetailBySKU($sku);	
				$storeData = $this-> ModelStore->getStore($data['data'][0]['storeId']);
				//print_r($productData);
				$disabled = array("dob", "downPayment", "tenure", "ReferenceRelationCode_2", "ReferenceRelationCode");
				$hidden = array();
				$select = array('zipcode', 'zipcode_2', 'empZipcode', 'stateId', 'stateId_2', 'empStateId', 'city', 'city_2', 'empCity', 'statePA', 'ReferenceRelationCode_2', 'ReferenceRelationCode');
				if($data and $productData){
					$productData = $productData[0];
					$customerData = json_decode($data['data'][0]['cData'],true);
					$customerData = array_merge($customerData, $data['data'][0]);
					$storeData = $storeData[0];	
					// $daAddress = json_decode($customerData['daAddress'], true);
					// $caAddress = json_decode($customerData['caAddress'],true);
					for($k=0;$k<sizeof($formData);$k++){
						if(in_array($formData[$k]['name'],$hidden))
							$formData[$k]['type'] = 'hidden';
						if(in_array($formData[$k]['name'],$disabled))
							$formData[$k]['editable'] = 'disabled';
						if(in_array($formData[$k]['name'],$select))
							$formData[$k]['type'] = 'select';
						if($formData[$k]['name'] == 'dob')
							$formData[$k]['value'] = $customerData['dob'];
						else if($formData[$k]['name'] == 'fullName')
							$formData[$k]['value'] = $customerData['name'];
						else if($formData[$k]['name'] == 'familyOwnerFullname')
							$formData[$k]['value'] = $customerData['name'];
						else if($formData[$k]['name'] == 'mobilephone')
							$formData[$k]['value'] = $customerData['mobile'];
						else if($formData[$k]['name'] == 'phone')
							$formData[$k]['value'] = $customerData['mobile'];
						else if($formData[$k]['name'] == 'idCard')
							$formData[$k]['value'] = $customerData['docData'];
						else if($formData[$k]['name'] == 'downPayment')
							$formData[$k]['value'] = $productData['dpv'];
						else if($formData[$k]['name'] == 'tenure' or $formData[$k]['name'] == 'requestLoanTerm')
							$formData[$k]['value'] = $productData['tenure'];
						else if($formData[$k]['name'] == 'assetName')
							$formData[$k]['value'] = $productDataRow[0]['model'];
						else if($formData[$k]['name'] == 'assetProduct')
							$formData[$k]['value'] = $productDataRow[0]['name'];
						else if($formData[$k]['name'] == 'assetBrand')
							$formData[$k]['value'] = $productDataRow[0]['brandName'];
						else if($formData[$k]['name'] == 'assetCost')
							$formData[$k]['value'] = $productDataRow[0]['drp'];
						else if($formData[$k]['name'] == 'applicationId')
							$formData[$k]['value'] = $appId;
						else if($formData[$k]['name'] == 'regAddressProvinceId')
							$formData[$k]['value'] = $daAddress['daState'];
						else if($formData[$k]['name'] == 'actAddressProvinceId' or $formData[$k]['name'] == 'curAddressProvinceId' or $formData[$k]['name'] == 'regAddressProvinceId')
							$formData[$k]['value'] = $caAddress['caState'];
						else if($formData[$k]['name'] == 'actAddressStreet' or $formData[$k]['name'] == 'regAddressWardId' or $formData[$k]['name'] == 'actAddressWardId' or $formData[$k]['name'] == 'curAddressWardId') 
							$formData[$k]['value'] = $caAddress['caStreet'];
						else if($formData[$k]['name'] == 'actAddressRegion' or $formData[$k]['name'] == 'regAddressDistId' or $formData[$k]['name'] == 'actAddressDistId' or $formData[$k]['name'] == 'curAddressDistId')
							$formData[$k]['value'] = $caAddress['caCity'];
						else if($formData[$k]['name'] == 'RegAddressNumber' or $formData[$k]['name'] == 'CurAddressNumber')
							$formData[$k]['value'] = isset($caAddress['caHNumber']) ? $caAddress['caHNumber'] : '';
						else if($formData[$k]['name'] == 'RegAddressStreet' or $formData[$k]['name'] == 'CurAddressStreet')
							$formData[$k]['value'] = isset($caAddress['caHStreet']) ? $caAddress['caHStreet'] : '';
						else if($formData[$k]['name'] == 'RegAddressRegion' or $formData[$k]['name'] == 'CurAddressRegion')
							$formData[$k]['value'] = isset($caAddress['caHregion']) ? $caAddress['caHregion'] : '';
						else if($formData[$k]['name'] == 'applicationDate')
							$formData[$k]['value'] = date('Y-m-d');
						else if($formData[$k]['name'] == 'productId')
							$formData[$k]['value'] = $productDataRow[0]['partnerSKU'];
						else if($formData[$k]['name'] == 'sku')
							$formData[$k]['value'] = $sku;
						else if($formData[$k]['name'] == 'loanAmount' or $formData[$k]['name'] == 'requestLoanAmount')
							$formData[$k]['value'] = $productDataRow[0]['drp'] + $productDataRow[0]['cpf'] - $productDataRow[0]['dpv'];
						else if($formData[$k]['name'] == 'downpayment')
							$formData[$k]['value'] = $productDataRow[0]['dpv'];
						else if($formData[$k]['name'] == 'priceOfProduct')
							$formData[$k]['value'] = $productDataRow[0]['drp'] + $productDataRow[0]['cpf'];
						else if($formData[$k]['name'] == 'downpaymentPercent')
							$formData[$k]['value'] = 40;
						else if($formData[$k]['name'] == 'traceCode')
							$formData[$k]['value'] = 'COMPASIA.'.date('YMDHis');
						else if($formData[$k]['name'] == 'idIssueDate')
							$formData[$k]['value'] = $customerData['idIssuedDate'];
						else if($formData[$k]['name'] == 'email')
							$formData[$k]['value'] = $customerData['email'];
						else if($formData[$k]['name'] == 'Telco')
							$formData[$k]['value'] = $customerData['telco'];
						else if($formData[$k]['name'] == 'workingTypeId')
							$formData[$k]['value'] = getElementKeyByVlaue($workingTypeId, 'name', $customerData['occupation'], 'id');
						else if($formData[$k]['name'] == 'shopId')
							$formData[$k]['value'] = $storeData['storeCode'];
						else if($formData[$k]['name'] == 'BeneficiaryBankBranchId')
							$formData[$k]['value'] = '006';
						else if($formData[$k]['name'] == 'BeneficiaryBankId')
							$formData[$k]['value'] = '333';
						else if($formData[$k]['name'] == 'BeneficiaryProvinceId')
							$formData[$k]['value'] = '79';
						else if($formData[$k]['name'] == 'BeneficiaryAccount')
							$formData[$k]['value'] = '0020100026725004';
						else if($formData[$k]['name'] == 'BeneficiaryName')
							$formData[$k]['value'] = 'CÔNG TY TNHH COMPASIA VIỆT NAM';
						else if($formData[$k]['name'] == 'tradeId')
							$formData[$k]['value'] = 'NA';
						else if($formData[$k]['name'] == 'customerBankBranch')
							$formData[$k]['value'] = '006';
						else if($formData[$k]['name'] == 'customerBank')
							$formData[$k]['value'] = '333';
						else if($formData[$k]['name'] == 'customerBankProvince')
							$formData[$k]['value'] = '79';
						else if($formData[$k]['name'] == 'customerAccount')
							$formData[$k]['value'] = '0020100026725004';
						else if($formData[$k]['name'] == 'hasOtherAccount')
							$formData[$k]['value'] = '0';
						else if($formData[$k]['name'] == 'hasOcbAccount')
							$formData[$k]['value'] = '1';
						else if($formData[$k]['name'] == 'sellerNote')
							$formData[$k]['value'] = $productDataRow[0]['name'];
						else if($formData[$k]['name'] == 'sellerId')
							$formData[$k]['value'] = $sellerId;
						else if($formData[$k]['name'] == 'disbursementMethod')
							$formData[$k]['value'] = 'BANK';
						else if($formData[$k]['name'] == 'loanPurpose')
							$formData[$k]['value'] = $loanPurpose[sizeof($loanPurpose)-1]['id'];
						else if($formData[$k]['name'] == 'customerBankBrand')
							$formData[$k]['value'] = '006';
						else if($formData[$k]['name'] == 'supplyAccountLater')
							$formData[$k]['value'] = '0';
						else if(isset($customerData[$formData[$k]['name']]))
							$formData[$k]['value'] = $customerData[$formData[$k]['name']];
						else if($oldSubscriptionId > 0){
							if(in_array($formData[$k]['name'], $oldKeys))
								 $formData[$k]['value'] = isset($oldAppInfo[$formData[$k]['name']]) ? $oldAppInfo[$formData[$k]['name']] : '';
							else if(in_array($formData[$k]['name'], $oldMetaKeys))
								 $formData[$k]['value'] = isset($oldAppInfo['metaData'][$formData[$k]['name']]) ? $oldAppInfo['metaData'][$formData[$k]['name']] : '';
							// else
							// 	$formData[$k]['value'] = '';
						}
						// else
						// 	$formData[$k]['value'] = '';
					}
					$formData[] = array("name" => "programId", "type"=>"hidden", "value" => $customerData['programId']);
					$formData[] = array("name" => "customerId", "type"=>"hidden", "value" => $customerId);
					$formData[] = array("name" => "employeeId", "type"=>"hidden", "value" => $employeeId);
					$formData[] = array("name" => "subscriptionId", "type"=>"hidden", "value" => $subscriptionId);
					$formData[] = array("name" => "sku", "type"=>"hidden", "value" => $sku);
				}
				$newData = array();	
				$basicInformation = array();
				$personInformation = array();
				$employeeInformation = array();
				$referenceInformation = array();
				$otherInformation = array();
				for($i=0;$i<sizeof($formData); $i++){
					if(in_array($formData[$i]['name'],$big))
						$basicInformation[] = $formData[$i];
					else if(in_array($formData[$i]['name'],$pig))
						$personInformation[] = $formData[$i];
					else if(in_array($formData[$i]['name'],$eig))
						$employeeInformation[] = $formData[$i];
					else if(in_array($formData[$i]['name'],$rig))
						$referenceInformation[] = $formData[$i];
					else
						$otherInformation[] = $formData[$i];
				}
				$lastData = array(array("heading"=>"Basic Information", "fields"=>$basicInformation), array("heading"=>"Personal Information", "fields" => $personInformation), array("heading"=>"Employee Information", "fields"=>$employeeInformation), array("heading"=>"Reference Information", "fields"=>$referenceInformation));
				$i=0;
				$tmp = array();
				foreach($otherInformation as $ai){
					$tmp[] = $ai;
					if($ai['type'] != 'hidden')
						$i++;
					if($i==9){
						$i=0;
						$lastData[] = array("heading"=>"Other Information", "fields"=>$tmp);
						$tmp = array();
					}	
				}
				$lastData[] = array("heading"=>"Other Information", "fields"=>$tmp);
				$output = array("status" => "Success", "msg" => array("formData"=>$lastData), 'timeStamp' => date('Y-m-d H:i:s'));
				$this -> ModelUtility -> saveLog($apiName, $input, $output);
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
	
	public function getSubscriptionFormDataByCustomerGroup()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionFormDataByCustomer'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("customerId", "Customer Id", "required");
		$this->form_validation->set_rules("sku", "sku", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$customerId = $this->input->post('customerId');
			$sku = $this->input->post('sku');
			$programData = $this -> ModelProgram -> getProgram();
			$employeeId = $this -> input -> post('employeeId');
			$sellerId = '';	
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				if($bfd = $this->ModelProgram->getProgramForm('1', 'advance'))
                                        $formData = json_decode($bfd['data'][0]['formData'],true);
				$sellerData = $this->ModelEmployee->getEmployeeById($employeeId);
				if($sellerData)
				{
					$sellerId = $sellerData[0]['userId']; 
				}	
                                $dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
                                $dictionaryData = array();
                                foreach($dictionaryDataRow as $d)
                                {
                                        $dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);

                                }
								$big= array("OldIdCard", "fullName", "birthday", "Ethnic", "idIssueDate", "idCard", "idIssuePlaceId", "workingTypeId", "phone", "mobilephone", "Telco", "email", "IdExpireDate");
								$pig = array("gender", "numerOfFamilyExpense", "educationId", "regAddressWardId", "regAddressDistId", "regAddressProvinceId", "livingYear", "livingMonth", "accommodationsId", "actAddressWardId", "actAddressDistId", "actAddressProvinceId", "RegAddressNumber", "RegAddressStreet", "RegAddressRegion", "CurAddressNumber", "CurAddressStreet", "CurAddressRegion", "curAddressProvinceId", "curAddressDistId", "curAddressWardId");
								$eig = array("careerId", "tradeId", "companyName", "workingYear", "workingMonth", "companyAddressWardId", "companyAddressDistId", "companyAddressProvinceId", "companyPhone", "companyTypeId", "positionId", "income", "expense", "ComAddressStreet", "ComAddressRegion", "ComAddressNumber", "WorAddressStreet", "WorAddressRegion", "WorAddressNumber", "worAddressProvinceId", "worAddressDistId", "worAddressWardId");
								$rig = array("referenceFullName1", "referenceRelationship1", "referencePhone1", "reference1Gender", "referenceFullName2", "referenceRelationship2", "referencePhone2", "reference2Gender", "referenceFullName3", "referenceRelationship3", "referencePhone3", "reference3Gender");				
			                $productDataRow = $this -> ModelProduct -> getProductDetailBySKU($sku);
                                for($i=0;$i<sizeof($productDataRow);$i++)
                                        $productData[] = array("id" => $productDataRow[$i]['partnerSKU'], "name" => $productDataRow[$i]['name']." - ".$productDataRow[$i]['partnerSKU']);
                                //print_r($dictionaryData);
                                $bool = array(array("id" => 1, "name" => "Yes"), array("id" => 0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"Viettel"),array("id"=>"mb", "name"=>"Mobiphone"),array("id"=>"vn", "name"=>"Vinaphone"),array("id"=>"other", "name"=>"Khác"));
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
				$wtid = array();
				for($i=0;$i<sizeof($workingTypeId); $i++){
					if(!in_array($workingTypeId[$i]['name'], array('Nội trợ','Sinh viên','Hợp đồng thời vụ','Lao động tự do','Hưu trí','Lao động tại Hộ kinh doanh')))
						$wtid[] = $workingTypeId[$i];
				}
				$workingTypeId = $wtid;
                                $nation = array(array("id"=> 1,"name"=> "Kinh"),array("id"=> 2,"name"=> "Tày"),array("id"=> 3,"name"=> "Thái"),array("id"=> 4,"name"=> "Mường"),array("id"=> 5,"name"=> "Khơ Me"),array("id"=> 6,"name"=> "H'Mông"),array("id"=> 7,"name"=> "Nùng"),array("id"=> 8,"name"=> "Hoa"),array("id"=> 9,"name"=> "Dao"),array("id"=> 10,"name"=> "Gia Rai"),array("id"=> 11,"name"=> "Ê Đê"),array("id"=> 12,"name"=> "Ba Na"),array("id"=> 13,"name"=> "Xơ Đăng"),array("id"=> 14,"name"=> "Sán Chay"),array("id"=> 15,"name"=> "Cơ Ho"),array("id"=> 16,"name"=> "Chăm"),array("id"=> 17,"name"=> "Sán Dìu"),array("id"=> 18,"name"=> "Hrê"),array("id"=> 19,"name"=> "Ra Glai"),array("id"=> 20,"name"=> "M'Nông"),array("id"=> 21,"name"=> "X’Tiêng"),array("id"=> 22,"name"=> "Bru-Vân Kiều"),array("id"=> 23,"name"=> "Thổ"),array("id"=> 24,"name"=> "Khơ Mú"),array("id"=> 25,"name"=> "Cơ Tu"),array("id"=> 26,"name"=> "Giáy"),array("id"=> 27,"name"=> "Giẻ Triêng"),array("id"=> 28,"name"=> "Tà Ôi"),array("id"=> 29,"name"=> "Mạ"),array("id"=> 30,"name"=> "Co"),array("id"=> 31,"name"=> "Chơ Ro"),array("id"=> 32,"name"=> "Xinh Mun"),array("id"=> 33,"name"=> "Hà Nhì"),array("id"=> 34,"name"=> "Chu Ru"),array("id"=> 35,"name"=> "Lào"),array("id"=> 36,"name"=> "Kháng"),array("id"=> 37,"name"=> "La Chí"),array("id"=> 38,"name"=> "Phù Lá"),array("id"=> 39,"name"=> "La Hủ"),array("id"=> 40,"name"=> "La Ha"),array("id"=> 41,"name"=> "Pà Thẻn"),array("id"=> 42,"name"=> "Chứt"),array("id"=> 43,"name"=> "Lự"),array("id"=> 44,"name"=> "Lô Lô"),array("id"=> 45,"name"=> "Mảng"),array("id"=> 46,"name"=> "Cờ Lao"),array("id"=> 47,"name"=> "Bố Y"),array("id"=> 48,"name"=> "Cống"),array("id"=> 49,"name"=> "Ngái"),array("id"=> 50,"name"=> "Si La"),array("id"=> 51,"name"=> "Pu Péo"),array("id"=> 52,"name"=> "Rơ măm"),array("id"=> 53,"name"=> "Brâu"),array("id"=> 54,"name"=> "Ơ Đu"),array("id"=> 55,"name"=> "người nước ngoài"),array("id"=> 56,"name"=> "Không xác định"));
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
                                        if($formData[$i]['name'] == 'referenceRelationship1' or $formData[$i]['name'] == 'referenceRelationship2' or $formData[$i]['name'] ==  'referenceRelationship3')
                                                $formData[$i]['data'] = $relationship;
                                        if($formData[$i]['name'] == 'workingTypeId')
                                                $formData[$i]['data'] = $workingTypeId;
                                        if($formData[$i]['name'] == 'regAddressWardId' or $formData[$i]['name'] == 'actAddressWardId' or $formData[$i]['name'] == 'curAddressWardId' or $formData[$i]['name'] == 'worAddressWardId' or $formData[$i]['name'] == 'companyAddressWardId')
                                                $formData[$i]['data'] = $wardData;
                                        if($formData[$i]['name'] == 'regAddressProvinceId' or $formData[$i]['name'] == 'actAddressProvinceId' or $formData[$i]['name'] == 'curAddressProvinceId' or $formData[$i]['name'] == 'worAddressProvinceId' or $formData[$i]['name'] == 'companyAddressProvinceId' or $formData[$i]['name'] == 'idIssuePlaceId')
                                                $formData[$i]['data'] = $provinceData;			
					if($formData[$i]['name'] == 'regAddressDistId' or $formData[$i]['name'] == 'actAddressDistId' or $formData[$i]['name'] == 'curAddressDistId' or $formData[$i]['name'] == 'worAddressDistId' or $formData[$i]['name'] == 'companyAddressDistId')
                                                $formData[$i]['data'] = $cityData;
                                        if($formData[$i]['name'] == 'hasOcbAccount' or $formData[$i]['name'] == 'supplyAccountLater')
                                                $formData[$i]['data'] = $bool;
                                        if($formData[$i]['name'] == 'disbursementMethod')
                                                $formData[$i]['data'] = $disbursementMethod;
                                        if($formData[$i]['name'] == 'Ethnic')
                                                $formData[$i]['data'] = ($nation);
                                        //if($formData[$i]['name'] == 'commodityName')
                                        //        $formData[$i]['data'] = $productData;
                                        if($formData[$i]['name'] == 'hasOtherAccount')
                                                $formData[$i]['data'] = $bankData;		
                                        if($formData[$i]['name'] == 'Telco')
                                                $formData[$i]['data'] = $telco;		
				}
				$oldSubscriptionId = 0;
				$oldData = $this->ModelSubscription->getSubscriptionByCustomerId($customerId);
				if($oldData){
					$oldData = $oldData[sizeof($oldData)-1];
					//print_r($oldData);
					$oldSubscriptionId = $oldData['id'];
					$oldAppInfo = json_decode($oldData['appInfo'], true);
					$oldAppInfo['metaData'] = json_decode($oldAppInfo['metaData'], true); 	
					//print_r($oldAppInfo);
				}
				$oldKeys = array("referenceFullName1", "referenceRelationship1", "referencePhone1", "reference1Gender", "referenceFullName2", "referenceRelationship2", "referencePhone2", "reference2Gender", "referenceFullName3", "referenceRelationship3", "referencePhone3", "reference3Gender", "gender", "educationId", "regAddressStatus", "companyName", "companyAddressProvinceId", "companyAddressDistId", "companyAddressWardId", "worAddressProvinceId", "worAddressDistId", "worAddressWardId", "companyPhone", "companyTypeId", "careerId", "positionId", "income", "familyIncome", "expense", "familyExpense", "numerOfFamilyExpense", "marriedId", "spouseFullName", "spouseIdCard", "spousePhone", "numerOfFamily", "hasOcbAccount", "livingYear", "livingMonth", "accommodationsId", "workingYear", "workingMonth", "mailAddressId");
				$oldMetaKeys = array("oldIdCard", "Ethnic", "RegAddressNumber", "RegAddressStreet", "RegAddressRegion", "CurAddressNumber", "CurAddressStreet", "CurAddressRegion", "ComAddressNumber", "ComAddressStreet", "ComAddressRegion", "WorAddressNumber", "WorAddressStreet", "WorAddressRegion", "IdExpireDate", "idIssuePlaceId");
				$data = $this -> ModelCustomer -> getCustomer('id', $customerId);	
				$productData = $this -> ModelProduct -> getProductDetailBySKU($sku);	
				$storeData = $this-> ModelStore->getStore($data['data'][0]['channelPartnerStoreId']);
				//print_r($productData);
				$disabled = array('idCard', 'idIssueDate', 'fullName', 'birthday', 'mobilephone');
				$hidden = array("applicationDate","sellerNote","loanAmount","loanPurpose","loanTerm","downpayment","downpaymentPercent","commodityName","productId","shopId","sellerId","requestLoanAmount","requestLoanTerm","hasOcbAccount","hasOtherAccount","supplyAccountLater","customerAccount","customerBankProvince","customerBank","customerBankBranch","tradeId","disbursementMethod","BeneficiaryName","BeneficiaryAccount","BeneficiaryProvinceId","BeneficiaryBankId","BeneficiaryBankBranchId","customerBankBrand");
				if($data and $productData){
					$productData = $productData[0];
					$customerData = $data['data'][0];
					$storeData = $storeData[0];	
					$daAddress = json_decode($customerData['daAddress'], true);
					$caAddress = json_decode($customerData['caAddress'],true);
					for($k=0;$k<sizeof($formData);$k++){
						if(in_array($formData[$k]['name'],$hidden))
							$formData[$k]['type'] = 'hidden';
						if(in_array($formData[$k]['name'],$disabled))
							$formData[$k]['editable'] = 'disabled';
						if($formData[$k]['name'] == 'birthday')
							$formData[$k]['value'] = $customerData['DOB'];
						else if($formData[$k]['name'] == 'fullName')
							$formData[$k]['value'] = $customerData['name'];
						else if($formData[$k]['name'] == 'familyOwnerFullname')
							$formData[$k]['value'] = $customerData['name'];
						else if($formData[$k]['name'] == 'mobilephone')
							$formData[$k]['value'] = $customerData['mobile'];
						else if($formData[$k]['name'] == 'phone')
							$formData[$k]['value'] = $customerData['mobile'];
						else if($formData[$k]['name'] == 'idCard')
							$formData[$k]['value'] = $customerData['docData'];
						else if($formData[$k]['name'] == 'downpayment')
							$formData[$k]['value'] = $productData['dpv'];
						else if($formData[$k]['name'] == 'loanTerm' or $formData[$k]['name'] == 'requestLoanTerm')
							$formData[$k]['value'] = $productData['tenure'];
						else if($formData[$k]['name'] == 'commodityName')
							$formData[$k]['value'] = $productDataRow[0]['name'];
						else if($formData[$k]['name'] == 'regAddressProvinceId')
							$formData[$k]['value'] = $daAddress['daState'];
						else if($formData[$k]['name'] == 'actAddressProvinceId' or $formData[$k]['name'] == 'curAddressProvinceId' or $formData[$k]['name'] == 'regAddressProvinceId')
							$formData[$k]['value'] = $caAddress['caState'];
						else if($formData[$k]['name'] == 'actAddressStreet' or $formData[$k]['name'] == 'regAddressWardId' or $formData[$k]['name'] == 'actAddressWardId' or $formData[$k]['name'] == 'curAddressWardId') 
							$formData[$k]['value'] = $caAddress['caStreet'];
						else if($formData[$k]['name'] == 'actAddressRegion' or $formData[$k]['name'] == 'regAddressDistId' or $formData[$k]['name'] == 'actAddressDistId' or $formData[$k]['name'] == 'curAddressDistId')
							$formData[$k]['value'] = $caAddress['caCity'];
						else if($formData[$k]['name'] == 'RegAddressNumber' or $formData[$k]['name'] == 'CurAddressNumber')
							$formData[$k]['value'] = isset($caAddress['caHNumber']) ? $caAddress['caHNumber'] : '';
						else if($formData[$k]['name'] == 'RegAddressStreet' or $formData[$k]['name'] == 'CurAddressStreet')
							$formData[$k]['value'] = isset($caAddress['caHStreet']) ? $caAddress['caHStreet'] : '';
						else if($formData[$k]['name'] == 'RegAddressRegion' or $formData[$k]['name'] == 'CurAddressRegion')
							$formData[$k]['value'] = isset($caAddress['caHregion']) ? $caAddress['caHregion'] : '';
						else if($formData[$k]['name'] == 'applicationDate')
							$formData[$k]['value'] = date('Y-m-d');
						else if($formData[$k]['name'] == 'productId')
							$formData[$k]['value'] = $productDataRow[0]['partnerSKU'];
						else if($formData[$k]['name'] == 'sku')
							$formData[$k]['value'] = $sku;
						else if($formData[$k]['name'] == 'loanAmount' or $formData[$k]['name'] == 'requestLoanAmount')
							$formData[$k]['value'] = $productDataRow[0]['drp'] + $productDataRow[0]['cpf'] - $productDataRow[0]['dpv'];
						else if($formData[$k]['name'] == 'downpayment')
							$formData[$k]['value'] = $productDataRow[0]['dpv'];
						else if($formData[$k]['name'] == 'priceOfProduct')
							$formData[$k]['value'] = $productDataRow[0]['drp'] + $productDataRow[0]['cpf'];
						else if($formData[$k]['name'] == 'downpaymentPercent')
							$formData[$k]['value'] = 40;
						else if($formData[$k]['name'] == 'traceCode')
							$formData[$k]['value'] = 'COMPASIA.'.date('YMDHis');
						else if($formData[$k]['name'] == 'idIssueDate')
							$formData[$k]['value'] = $customerData['docIssuedDate'];
						else if($formData[$k]['name'] == 'email')
							$formData[$k]['value'] = $customerData['email'];
						else if($formData[$k]['name'] == 'Telco')
							$formData[$k]['value'] = $customerData['telco'];
						else if($formData[$k]['name'] == 'workingTypeId')
							$formData[$k]['value'] = getElementKeyByVlaue($workingTypeId, 'name', $customerData['occupation'], 'id');
						else if($formData[$k]['name'] == 'shopId')
							$formData[$k]['value'] = $storeData['storeCode'];
						else if($formData[$k]['name'] == 'BeneficiaryBankBranchId')
							$formData[$k]['value'] = '006';
						else if($formData[$k]['name'] == 'BeneficiaryBankId')
							$formData[$k]['value'] = '333';
						else if($formData[$k]['name'] == 'BeneficiaryProvinceId')
							$formData[$k]['value'] = '79';
						else if($formData[$k]['name'] == 'BeneficiaryAccount')
							$formData[$k]['value'] = '0020100026725004';
						else if($formData[$k]['name'] == 'BeneficiaryName')
							$formData[$k]['value'] = 'CÔNG TY TNHH COMPASIA VIỆT NAM';
						else if($formData[$k]['name'] == 'tradeId')
							$formData[$k]['value'] = 'NA';
						else if($formData[$k]['name'] == 'customerBankBranch')
							$formData[$k]['value'] = '006';
						else if($formData[$k]['name'] == 'customerBank')
							$formData[$k]['value'] = '333';
						else if($formData[$k]['name'] == 'customerBankProvince')
							$formData[$k]['value'] = '79';
						else if($formData[$k]['name'] == 'customerAccount')
							$formData[$k]['value'] = '0020100026725004';
						else if($formData[$k]['name'] == 'hasOtherAccount')
							$formData[$k]['value'] = '0';
						else if($formData[$k]['name'] == 'hasOcbAccount')
							$formData[$k]['value'] = '1';
						else if($formData[$k]['name'] == 'sellerNote')
							$formData[$k]['value'] = $productDataRow[0]['name'];
						else if($formData[$k]['name'] == 'sellerId')
							$formData[$k]['value'] = $sellerId;
						else if($formData[$k]['name'] == 'disbursementMethod')
							$formData[$k]['value'] = 'BANK';
						else if($formData[$k]['name'] == 'loanPurpose')
							$formData[$k]['value'] = $loanPurpose[sizeof($loanPurpose)-1]['id'];
						else if($formData[$k]['name'] == 'customerBankBrand')
							$formData[$k]['value'] = '006';
						else if($formData[$k]['name'] == 'supplyAccountLater')
							$formData[$k]['value'] = '0';
						else if($oldSubscriptionId > 0){
							if(in_array($formData[$k]['name'], $oldKeys))
								 $formData[$k]['value'] = isset($oldAppInfo[$formData[$k]['name']]) ? $oldAppInfo[$formData[$k]['name']] : '';
							else if(in_array($formData[$k]['name'], $oldMetaKeys))
								 $formData[$k]['value'] = isset($oldAppInfo['metaData'][$formData[$k]['name']]) ? $oldAppInfo['metaData'][$formData[$k]['name']] : '';
							else
								$formData[$k]['value'] = '';
						}else
							$formData[$k]['value'] = '';

					}
					//$formData[] = array("name" => "programId", "type"=>"hidden", "value" => $customerData['programId']);
					$formData[] = array("name" => "customerId", "type"=>"hidden", "value" => $customerId);
					$formData[] = array("name" => "employeeId", "type"=>"hidden", "value" => $employeeId);
	
					//print_r($customerData);
				}
				$newData = array();	
				/*	
				for($i=0;$i<sizeof($data);$i++){
					//$lappInfo = $data[$i]['appInfo'];
					//print_r( json_decode(json_decode($data[$i]['appInfo'],true)['metaData'],true));
					$lappInfo = $this->getAllItem(json_decode($data[$i]['appInfo'],true));
					unset($data[$i]['appInfo']);
					$nd = array_merge($data[$i],$lappInfo);
					for($k=0;$k<sizeof($formData);$k++){
						if(isset($nd[$formData[$k]['name']])){
							$formData[$k]['value'] = $nd[$formData[$k]['name']];
						}	
						if($formData[$k]['name'] == 'commodityName'){
							$formData[$k]['value'] = $nd['productId'];
						}
					}
					
					for($k=0;$k<sizeof($programData);$k++){
						if($programData[$k]['id'] = $nd['programId'])
							$nd['programId'] = $programData[$k]['name'];
					}
					$nd['companyAddress'] = (isset($nd['ComAddressNumber']) ? $nd['ComAddressNumber'] : '').' '.(isset($nd['ComAddressStreet']) ? $nd['ComAddressStreet'] : '').' '.(isset($nd['ComAddressRegion']) ? $nd['ComAddressRegion'] : '').' '.(isset($nd['comAddressWardId']) ? $nd['comAddressWardId'] : '').' '.(isset($nd['comAddressDistId']) ? $nd['comAddressDistId'] : '').' '. (isset($nd['comAddressProvinceId']) ? $nd['comAddressProvinceId'] : '');
					$nd['registeredAddress'] = (isset($nd['RegAddressNumber']) ? $nd['RegAddressNumber'] : '').' '.(isset($nd['RegAddressStreet']) ? $nd['RegAddressStreet'] : '').' '.(isset($nd['RegAddressRegion']) ? $nd['RegAddressRegion'] : '').' '.(isset($nd['regAddressWardId']) ? $nd['regAddressWardId'] : '').' '.(isset($nd['regAddressDistId']) ? $nd['regAddressDistId'] : '').' '. (isset($nd['regAddressProvinceId']) ? $nd['regAddressProvinceId'] : '');
					$nd['currentAddress'] = (isset($nd['CurAddressNumber']) ? $nd['CurAddressNumber'] : '').' '.(isset($nd['CurAddressStreet']) ? $nd['CurAddressStreet'] : '').' '.(isset($nd['CurAddressRegion']) ? $nd['CurAddressRegion'] : '').' '.(isset($nd['curAddressWardId']) ? $nd['curAddressWardId'] : '').' '.(isset($nd['curAddressDistId']) ? $nd['curAddressDistId'] : '').' '. (isset($nd['curAddressProvinceId']) ? $nd['curAddressProvinceId'] : '');
					$nd['activeAddress'] = (isset($nd['ActAddressNumber']) ? $nd['ActAddressNumber'] : '').' '.(isset($nd['ActAddressStreet']) ? $nd['ActAddressStreet'] : '').' '.(isset($nd['ActAddressRegion']) ? $nd['ActAddressRegion'] : '').' '.(isset($nd['actAddressWardId']) ? $nd['actAddressWardId'] : '').' '.(isset($nd['actAddressDistId']) ? $nd['actAddressDistId'] : '').' '. (isset($nd['actAddressProvinceId']) ? $nd['actAddressProvinceId'] : '');
					$nd['workAddress'] = (isset($nd['WorAddressNumber']) ? $nd['WorAddressNumber'] : '').' '.(isset($nd['WorAddressStreet']) ? $nd['WorAddressStreet'] : '').' '.(isset($nd['WorAddressRegion']) ? $nd['WorAddressRegion'] : '').' '.(isset($nd['warAddressWardId']) ? $nd['warAddressWardId'] : '').' '.(isset($nd['warAddressDistId']) ? $nd['warAddressDistId'] : '').' '. (isset($nd['warAddressProvinceId']) ? $nd['warAddressProvinceId'] : '');
					$newData[] = $nd;
					
				}*/
				$basicInformation = array();
				$personalInformation = array();
				$employeeInformation = array();
				$referenceInformation = array();
				$otherInformation = array();
                                for($i=0;$i<sizeof($formData); $i++)
                                {
                                        if(in_array($formData[$i]['name'],$big))
						$basicInformation[] = $formData[$i];
                                        else if(in_array($formData[$i]['name'],$pig))
						$personInformation[] = $formData[$i];
                                        else if(in_array($formData[$i]['name'],$eig))
						$employeeInformation[] = $formData[$i];
                                        else if(in_array($formData[$i]['name'],$rig))
						$referenceInformation[] = $formData[$i];
                                        else
						$otherInformation[] = $formData[$i];
				}
				$lastData = array(array("heading"=>"Basic Information", "fields"=>$basicInformation), array("heading"=>"Personal Information", "fields" => $personInformation), array("heading"=>"Employee Information", "fields"=>$employeeInformation), array("heading"=>"Reference Information", "fields"=>$referenceInformation));
				$i=0;
				$tmp = array();
				foreach($otherInformation as $ai){
					$tmp[] = $ai;
					if($ai['type'] != 'hidden')
						$i++;
					if($i==9){
						$i=0;
						$lastData[] = array("heading"=>"Other Information", "fields"=>$tmp);
						$tmp = array();
					}	
				}
				$lastData[] = array("heading"=>"Other Information", "fields"=>$tmp);

				$output = array("status" => "Success", "msg" => array("formData"=>$lastData), 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getSubscriptionFormDataByCustomer()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionFormDataByCustomer'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("customerId", "Customer Id", "required");
		$this->form_validation->set_rules("sku", "sku", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$customerId = $this->input->post('customerId');
			$sku = $this->input->post('sku');
			$programData = $this -> ModelProgram -> getProgram();
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				if($bfd = $this->ModelProgram->getProgramForm('1', 'advance'))
                                        $formData = json_decode($bfd['data'][0]['formData'],true);

                                $dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
                                $dictionaryData = array();
                                foreach($dictionaryDataRow as $d)
                                {
                                        $dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);

                                }
				$big= array("fullName", "birthday", "Ethnic", "Ethnic", "idIssueDate", "idCard", "idIssuePlaceId", "workingTypeId", "phone", "mobilephone", "Telco", "actAddressWardId", "actAddressDistId", "actAddressProvinceId", "email");
				$pig = array("OldIdCard", "gender", "marriedId", "numerOfFamilyExpense", "educationId", "regAddressWardId", "regAddressDistId", "regAddressProvinceId", "livingYear", "livingMonth", "accommodationsId");
				$eig = array("careerId", "tradeId", "companyName", "workingYear", "workingMonth", "companyAddressWardId", "companyAddressDistId", "companyAddressProvinceId", "companyPhone", "companyTypeId", "positionId", "income", "expense");
                                $productDataRow = $this -> ModelProduct -> getProductDetailBySKU($sku);
                                for($i=0;$i<sizeof($productDataRow);$i++)
                                        $productData[] = array("id" => $productDataRow[$i]['partnerSKU'], "name" => $productDataRow[$i]['name']." - ".$productDataRow[$i]['partnerSKU']);
                                //print_r($dictionaryData);
                                $bool = array(array("id" => 1, "name" => "Yes"), array("id" => 0, "name" => "No"));
				$telco = array(array("id"=>"vt", "name"=>"Viettel"),array("id"=>"mb", "name"=>"Mobiphone"),array("id"=>"vn", "name"=>"Vinaphone"),array("id"=>"other", "name"=>"Khác"));
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
				$wtid = array();
				for($i=0;$i<sizeof($workingTypeId); $i++){
					if(!in_array($workingTypeId[$i]['name'], array('Nội trợ','Sinh viên','Hợp đồng thời vụ','Lao động tự do','Hưu trí','Lao động tại Hộ kinh doanh')))
						$wtid[] = $workingTypeId[$i];
				}
				$workingTypeId = $wtid;
                                $nation = array(array("id"=> 1,"name"=> "Kinh"),array("id"=> 2,"name"=> "Tày"),array("id"=> 3,"name"=> "Thái"),array("id"=> 4,"name"=> "Mường"),array("id"=> 5,"name"=> "Khơ Me"),array("id"=> 6,"name"=> "H'Mông"),array("id"=> 7,"name"=> "Nùng"),array("id"=> 8,"name"=> "Hoa"),array("id"=> 9,"name"=> "Dao"),array("id"=> 10,"name"=> "Gia Rai"),array("id"=> 11,"name"=> "Ê Đê"),array("id"=> 12,"name"=> "Ba Na"),array("id"=> 13,"name"=> "Xơ Đăng"),array("id"=> 14,"name"=> "Sán Chay"),array("id"=> 15,"name"=> "Cơ Ho"),array("id"=> 16,"name"=> "Chăm"),array("id"=> 17,"name"=> "Sán Dìu"),array("id"=> 18,"name"=> "Hrê"),array("id"=> 19,"name"=> "Ra Glai"),array("id"=> 20,"name"=> "M'Nông"),array("id"=> 21,"name"=> "X’Tiêng"),array("id"=> 22,"name"=> "Bru-Vân Kiều"),array("id"=> 23,"name"=> "Thổ"),array("id"=> 24,"name"=> "Khơ Mú"),array("id"=> 25,"name"=> "Cơ Tu"),array("id"=> 26,"name"=> "Giáy"),array("id"=> 27,"name"=> "Giẻ Triêng"),array("id"=> 28,"name"=> "Tà Ôi"),array("id"=> 29,"name"=> "Mạ"),array("id"=> 30,"name"=> "Co"),array("id"=> 31,"name"=> "Chơ Ro"),array("id"=> 32,"name"=> "Xinh Mun"),array("id"=> 33,"name"=> "Hà Nhì"),array("id"=> 34,"name"=> "Chu Ru"),array("id"=> 35,"name"=> "Lào"),array("id"=> 36,"name"=> "Kháng"),array("id"=> 37,"name"=> "La Chí"),array("id"=> 38,"name"=> "Phù Lá"),array("id"=> 39,"name"=> "La Hủ"),array("id"=> 40,"name"=> "La Ha"),array("id"=> 41,"name"=> "Pà Thẻn"),array("id"=> 42,"name"=> "Chứt"),array("id"=> 43,"name"=> "Lự"),array("id"=> 44,"name"=> "Lô Lô"),array("id"=> 45,"name"=> "Mảng"),array("id"=> 46,"name"=> "Cờ Lao"),array("id"=> 47,"name"=> "Bố Y"),array("id"=> 48,"name"=> "Cống"),array("id"=> 49,"name"=> "Ngái"),array("id"=> 50,"name"=> "Si La"),array("id"=> 51,"name"=> "Pu Péo"),array("id"=> 52,"name"=> "Rơ măm"),array("id"=> 53,"name"=> "Brâu"),array("id"=> 54,"name"=> "Ơ Đu"),array("id"=> 55,"name"=> "người nước ngoài"),array("id"=> 56,"name"=> "Không xác định"));

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
                                        if($formData[$i]['name'] == 'referenceRelationship1' or $formData[$i]['name'] == 'referenceRelationship2' or $formData[$i]['name'] ==  'referenceRelationship3')
                                                $formData[$i]['data'] = $relationship;
                                        if($formData[$i]['name'] == 'workingTypeId')
                                                $formData[$i]['data'] = $workingTypeId;
                                        if($formData[$i]['name'] == 'regAddressWardId' or $formData[$i]['name'] == 'actAddressWardId' or $formData[$i]['name'] == 'curAddressWardId' or $formData[$i]['name'] == 'worAddressWardId' or $formData[$i]['name'] == 'companyAddressWardId')
                                                $formData[$i]['data'] = $wardData;
                                        if($formData[$i]['name'] == 'regAddressProvinceId' or $formData[$i]['name'] == 'actAddressProvinceId' or $formData[$i]['name'] == 'curAddressProvinceId' or $formData[$i]['name'] == 'worAddressProvinceId' or $formData[$i]['name'] == 'companyAddressProvinceId' or $formData[$i]['name'] == 'idIssuePlaceId')
                                                $formData[$i]['data'] = $provinceData;			
					if($formData[$i]['name'] == 'regAddressDistId' or $formData[$i]['name'] == 'actAddressDistId' or $formData[$i]['name'] == 'curAddressDistId' or $formData[$i]['name'] == 'worAddressDistId' or $formData[$i]['name'] == 'companyAddressDistId')
                                                $formData[$i]['data'] = $cityData;
                                        if($formData[$i]['name'] == 'hasOcbAccount' or $formData[$i]['name'] == 'supplyAccountLater')
                                                $formData[$i]['data'] = $bool;
                                        if($formData[$i]['name'] == 'disbursementMethod')
                                                $formData[$i]['data'] = $disbursementMethod;
                                        if($formData[$i]['name'] == 'Ethnic')
                                                $formData[$i]['data'] = ($nation);
                                        if($formData[$i]['name'] == 'commodityName')
                                                $formData[$i]['data'] = $productData;
                                        if($formData[$i]['name'] == 'hasOtherAccount')
                                                $formData[$i]['data'] = $bankData;		
                                        if($formData[$i]['name'] == 'Telco')
                                                $formData[$i]['data'] = $telco;		
				}
				$data = $this -> ModelCustomer -> getCustomer('id', $customerId);	
				$productData = $this -> ModelProduct -> getProductDetailBySKU($sku);	
				$storeData = $this-> ModelStore->getStore($data['data'][0]['channelPartnerStoreId']);
				//print_r($productData);
				if($data and $productData){
					$productData = $productData[0];
					$customerData = $data['data'][0];
					$storeData = $storeData[0];	
					$daAddress = json_decode($customerData['daAddress'], true);
					$caAddress = json_decode($customerData['caAddress'],true);
					for($k=0;$k<sizeof($formData);$k++){
						if($formData[$k]['name'] == 'birthday')
							$formData[$k]['value'] = $customerData['DOB'];
						else if($formData[$k]['name'] == 'fullName')
							$formData[$k]['value'] = $customerData['name'];
						else if($formData[$k]['name'] == 'familyOwnerFullname')
							$formData[$k]['value'] = $customerData['name'];
						else if($formData[$k]['name'] == 'mobilephone')
							$formData[$k]['value'] = $customerData['mobile'];
						else if($formData[$k]['name'] == 'phone')
							$formData[$k]['value'] = $customerData['mobile'];
						else if($formData[$k]['name'] == 'mailAddressId')
							$formData[$k]['value'] = $customerData['email'];
						else if($formData[$k]['name'] == 'idCard')
							$formData[$k]['value'] = $customerData['docData'];
						else if($formData[$k]['name'] == 'downpayment')
							$formData[$k]['value'] = $productData['dpv'];
						else if($formData[$k]['name'] == 'loanTerm' or $formData[$k]['name'] == 'requestLoanTerm')
							$formData[$k]['value'] = $productData['tenure'];
						else if($formData[$k]['name'] == 'commodityName')
							$formData[$k]['value'] = $productData['partnerSKU'];
						else if($formData[$k]['name'] == 'regAddressProvinceId')
							$formData[$k]['value'] = $daAddress['daState'];
						else if($formData[$k]['name'] == 'regAddressStreet')
							$formData[$k]['value'] = $daAddress['daStreet'];
						else if($formData[$k]['name'] == 'RegAddressRegion')
							$formData[$k]['value'] = $daAddress['daCity'];
						else if($formData[$k]['name'] == 'actAddressProvinceId')
							$formData[$k]['value'] = $caAddress['caState'];
						else if($formData[$k]['name'] == 'actAddressStreet')
							$formData[$k]['value'] = $caAddress['caStreet'];
						else if($formData[$k]['name'] == 'actAddressRegion')
							$formData[$k]['value'] = $caAddress['caCity'];
						else if($formData[$k]['name'] == 'applicationDate')
							$formData[$k]['value'] = date('Y-m-d');
						else if($formData[$k]['name'] == 'productId')
							$formData[$k]['value'] = $productDataRow[0]['partnerSKU'];
						else if($formData[$k]['name'] == 'loanAmount' or $formData[$k]['name'] == 'requestLoanAmount')
							$formData[$k]['value'] = $productDataRow[0]['drp'] + $productDataRow[0]['cpf'] - $productDataRow[0]['dpv'];
						else if($formData[$k]['name'] == 'downpayment')
							$formData[$k]['value'] = $productDataRow[0]['dpv'];
						else if($formData[$k]['name'] == 'priceOfProduct')
							$formData[$k]['value'] = $productDataRow[0]['drp'] + $productDataRow[0]['cpf'];
						else if($formData[$k]['name'] == 'downpaymentPercent')
							$formData[$k]['value'] = 40;
						else if($formData[$k]['name'] == 'traceCode')
							$formData[$k]['value'] = 'COMPASIA.'.date('YMDHis');
						else if($formData[$k]['name'] == 'idIssueDate')
							$formData[$k]['value'] = $customerData['docIssuedDate'];
						else if($formData[$k]['name'] == 'Telco')
							$formData[$k]['value'] = $customerData['telco'];
						else if($formData[$k]['name'] == 'workingTypeId')
							$formData[$k]['value'] = getElementKeyByVlaue($workingTypeId, 'name', $customerData['occupation'], 'id');
						else if($formData[$k]['name'] == 'Ethnic')
							$formData[$k]['value'] = $customerData['nationality'];
						else if($formData[$k]['name'] == 'shopId')
							$formData[$k]['value'] = $storeData['storeCode'];
						else
							$formData[$k]['value'] = '';

					}
					$formData[] = array("name" => "programId", "type"=>"hidden", "value" => $customerData['programId']);
					$formData[] = array("name" => "customerId", "type"=>"hidden", "value" => $customerId);
	
					//print_r($customerData);
				}
				$newData = array();	
				/*	
				for($i=0;$i<sizeof($data);$i++){
					//$lappInfo = $data[$i]['appInfo'];
					//print_r( json_decode(json_decode($data[$i]['appInfo'],true)['metaData'],true));
					$lappInfo = $this->getAllItem(json_decode($data[$i]['appInfo'],true));
					unset($data[$i]['appInfo']);
					$nd = array_merge($data[$i],$lappInfo);
					for($k=0;$k<sizeof($formData);$k++){
						if(isset($nd[$formData[$k]['name']])){
							$formData[$k]['value'] = $nd[$formData[$k]['name']];
						}	
						if($formData[$k]['name'] == 'commodityName'){
							$formData[$k]['value'] = $nd['productId'];
						}
					}
					
					for($k=0;$k<sizeof($programData);$k++){
						if($programData[$k]['id'] = $nd['programId'])
							$nd['programId'] = $programData[$k]['name'];
					}
					$nd['companyAddress'] = (isset($nd['ComAddressNumber']) ? $nd['ComAddressNumber'] : '').' '.(isset($nd['ComAddressStreet']) ? $nd['ComAddressStreet'] : '').' '.(isset($nd['ComAddressRegion']) ? $nd['ComAddressRegion'] : '').' '.(isset($nd['comAddressWardId']) ? $nd['comAddressWardId'] : '').' '.(isset($nd['comAddressDistId']) ? $nd['comAddressDistId'] : '').' '. (isset($nd['comAddressProvinceId']) ? $nd['comAddressProvinceId'] : '');
					$nd['registeredAddress'] = (isset($nd['RegAddressNumber']) ? $nd['RegAddressNumber'] : '').' '.(isset($nd['RegAddressStreet']) ? $nd['RegAddressStreet'] : '').' '.(isset($nd['RegAddressRegion']) ? $nd['RegAddressRegion'] : '').' '.(isset($nd['regAddressWardId']) ? $nd['regAddressWardId'] : '').' '.(isset($nd['regAddressDistId']) ? $nd['regAddressDistId'] : '').' '. (isset($nd['regAddressProvinceId']) ? $nd['regAddressProvinceId'] : '');
					$nd['currentAddress'] = (isset($nd['CurAddressNumber']) ? $nd['CurAddressNumber'] : '').' '.(isset($nd['CurAddressStreet']) ? $nd['CurAddressStreet'] : '').' '.(isset($nd['CurAddressRegion']) ? $nd['CurAddressRegion'] : '').' '.(isset($nd['curAddressWardId']) ? $nd['curAddressWardId'] : '').' '.(isset($nd['curAddressDistId']) ? $nd['curAddressDistId'] : '').' '. (isset($nd['curAddressProvinceId']) ? $nd['curAddressProvinceId'] : '');
					$nd['activeAddress'] = (isset($nd['ActAddressNumber']) ? $nd['ActAddressNumber'] : '').' '.(isset($nd['ActAddressStreet']) ? $nd['ActAddressStreet'] : '').' '.(isset($nd['ActAddressRegion']) ? $nd['ActAddressRegion'] : '').' '.(isset($nd['actAddressWardId']) ? $nd['actAddressWardId'] : '').' '.(isset($nd['actAddressDistId']) ? $nd['actAddressDistId'] : '').' '. (isset($nd['actAddressProvinceId']) ? $nd['actAddressProvinceId'] : '');
					$nd['workAddress'] = (isset($nd['WorAddressNumber']) ? $nd['WorAddressNumber'] : '').' '.(isset($nd['WorAddressStreet']) ? $nd['WorAddressStreet'] : '').' '.(isset($nd['WorAddressRegion']) ? $nd['WorAddressRegion'] : '').' '.(isset($nd['warAddressWardId']) ? $nd['warAddressWardId'] : '').' '.(isset($nd['warAddressDistId']) ? $nd['warAddressDistId'] : '').' '. (isset($nd['warAddressProvinceId']) ? $nd['warAddressProvinceId'] : '');
					$newData[] = $nd;
					
				}*/
				$basicInformation = array();
				$personalInformation = array();
				$employeeInformation = array();
				$otherInformation = array();
                                for($i=0;$i<sizeof($formData); $i++)
                                {
                                        if(in_array($formData[$i]['name'],$big))
						$basicInformation[] = $formData[$i];
                                        else if(in_array($formData[$i]['name'],$pig))
						$personInformation[] = $formData[$i];
                                        else if(in_array($formData[$i]['name'],$eig))
						$employeeInformation[] = $formData[$i];
                                        else
						$otherInformation[] = $formData[$i];
				}
				//$output = array("status" => "Success", "msg" => array("formData"=>array(array("heading"=>"Basic Information", "fields"=>$basicInformation), array("heading"=>"Persnoal Information", "fields" => $personInformation), array("heading"=>"Employee Information", "fields"=>$employeeInformation), array("heading"=>"Other Information", "fields"=>$otherInformation))), 'timeStamp' => date('Y-m-d H:i:s'));
				$output = array("status" => "Success", "msg" => $formData, 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function updateApplication()
	{
		$input = $this->input->post();
		$apiName = 'updateApplication'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate)
			{
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{	
					$programId = $subscriptionData[0]['programId'];
					$d = array("isDeleted" => 0, "programId" => $programId, "phase" => "Application Submission");
					if($bfd = $this->ModelProgram->getFormData($d))
						$formData = json_decode($bfd['data'][0]['data'],true);
					$isValidFormData = validateFormData(json_encode($this->input->post()), $formData);
					if($isValidFormData['status'] == 'Success')
					{
						$appInfo = array();
						$partnerSKU = $this->input->post('productId');
						$sku = $this->input->post('sku');
						$data = $this->ModelProduct->getProductDetailBySKU($sku);
						for($i=0; $i<sizeof($formData); $i++){
							$appInfo[$formData[$i]['name']] = $this->input->post($formData[$i]['name']); 
						}
						for($i=0; $i<sizeof($formData); $i++){
							$n = $formData[$i]['name']; 
							$sCheck  = array('referencePhone1', 'referencePhone2', 'referencePhone3', 'mobilephone', 'idCard', 'OldIdCard', 'spouseIdCard', 'companyAddressDistId', 'companyAddressProvinceId', 'companyPhone', 'phone', 'worAddressProvinceId', 'worAddressDistId', 'worAddressWardId', 'curAddressProvinceId', 'curAddressDistId', 'curAddressWardId', 'actAddressProvinceId', 'actAddressDistId', 'actAddressWardId', 'regAddressProvinceId', 'regAddressDistId', 'regAddressWardId', 'idIssuePlaceId', 'sellerId', 'shopId', 'productId','spousePhone', 'customerAccount', 'customerBankProvince', 'customerBank', 'customerBankBrand', 'BeneficiaryProvinceId', 'BeneficiaryBankId', 'BeneficiaryBankBranchId', 'BeneficiaryAccount');
							if(in_array($n, $sCheck))
								$appInfo[$formData[$i]['name']] = $this->input->post($formData[$i]['name']);
							else
								$appInfo[$formData[$i]['name']] = is_numeric($this->input->post($formData[$i]['name'])) ? intval($this->input->post($formData[$i]['name'])) : $this->input->post($formData[$i]['name']); 
						}
						$meta = array("OldIdCard","Ethnic", "IdExpireDate", "BeneficiaryName", "BeneficiaryAccount", "BeneficiaryProvinceId", "BeneficiaryBankId", "BeneficiaryBankBranchId", "RegAddressNumber", "RegAddressStreet", "RegAddressRegion", "CurAddressNumber", "CurAddressStreet", "CurAddressRegion", "ComAddressNumber", "ComAddressStreet", "ComAddressRegion", "WorAddressNumber", "WorAddressStreet", "WorAddressRegion", "email", "Telco");
						$metaArray = array();
						for($i=0;$i<sizeof($meta);$i++){
							$metaArray[$meta[$i]] = isset($appInfo[$meta[$i]]) ? $appInfo[$meta[$i]] : "";
							unset($appInfo[$meta[$i]]);
						}
						$appInfo['metaData'] = json_encode($metaArray, JSON_UNESCAPED_UNICODE);
						unset($appInfo['abc']);	
						if($subscriptionData[0]['appId'] == NULL and $subscriptionData[0]['appId'] == ''){
							$appInfo['traceCode'] = 'COMPASIA.'.date('dHis');
							//$d =createApplicationOCB(json_encode($appInfo));
							//$this -> ModelUtility -> saveLog('OCBApplication', $appInfo, $d);
							$this->LogManager->logApi('OCBApplication', $appInfo, $d);
							if(1){
								$newData = array("orderValue" => "0", "orderUpfront"=>"0", "loanAmount"=>$this->input->post('loanAmount'), "monthlyFee" => 0, "rrp" => "", "caFee" => "", "status" => "11", "appInfo" => json_encode($appInfo, JSON_UNESCAPED_UNICODE));
								$whereData = array("id" => $subscriptionId);
								$res = $this -> ModelSubscription -> updateSubscription($newData, $whereData);
								if($res){
									//addSubscriptionAudit($subscriptionId, 0, 'ocb', 'pending', '');
									$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($subscriptionData[0]['customerId'], 'In-Progress');
									$output = array("status" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output, JSON_UNESCAPED_UNICODE);						
								}
								else{
									$output = array("status" => "Error", "msg" => "Something went wrong", 'timeStamp' => date('Y-m-d H:i:s'));
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output, JSON_UNESCAPED_UNICODE);
								}
							}
							else{
								//$this -> ModelUtility -> saveLog($apiName, $input, $d);
								$this->LogManager->logApi($apiName, $input, $d);
								echo json_encode($d, JSON_UNESCAPED_UNICODE);
							}
						}
						else{
							$newData = array("orderValue" => "0", "orderUpfront"=>"0", "loanAmount"=>$this->input->post('loanAmount'), "monthlyFee" => 0, "rrp" => "", "caFee" => "", "status" => "11", "appInfo" => json_encode($appInfo, JSON_UNESCAPED_UNICODE));
							$whereData = array("id" => $subscriptionId);
							$res = $this -> ModelSubscription -> updateSubscription($newData, $whereData);
							if($res){
								$output = array("status" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);						
							}
							else{
								$output = array("status" => "Error", "msg" => "Something went wrong", 'timeStamp' => date('Y-m-d H:i:s'));
								//$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
								echo json_encode($output, JSON_UNESCAPED_UNICODE);
							}
						}
					}
					else{
						$output = $isValidFormData;
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
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
	
	public function updateApplicationMAFC(){
		$input = $this->input->post();
		$apiName = 'updateApplicationMAFC'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData){	
					$programId = $subscriptionData[0]['programId'];
					$d = array("isDeleted" => 0, "programId" => $programId, "phase" => "Application Submission");
					if($bfd = $this->ModelProgram->getFormData($d))
						$formData = json_decode($bfd['data'][0]['data'],true);
					$isValidFormData = validateFormData(json_encode($this->input->post()), $formData);
					if($isValidFormData['status'] == 'Success'){
						$appInfo = array();
						$partnerSKU = $this->input->post('productId');
						$sku = $this->input->post('sku');
						$data = $this->ModelProduct->getProductDetailBySKU($sku);
						for($i=0; $i<sizeof($formData); $i++){
							$appInfo[$formData[$i]['name']] = $this->input->post($formData[$i]['name']); 
						}
						for($i=0; $i<sizeof($formData); $i++){
							$n = $formData[$i]['name']; 
							$sCheck  = array('referencePhone1', 'referencePhone2', 'referencePhone3', 'mobilephone', 'idCard', 'OldIdCard', 'spouseIdCard', 'companyAddressDistId', 'companyAddressProvinceId', 'companyPhone', 'phone', 'worAddressProvinceId', 'worAddressDistId', 'worAddressWardId', 'curAddressProvinceId', 'curAddressDistId', 'curAddressWardId', 'actAddressProvinceId', 'actAddressDistId', 'actAddressWardId', 'regAddressProvinceId', 'regAddressDistId', 'regAddressWardId', 'idIssuePlaceId', 'sellerId', 'shopId', 'productId','spousePhone', 'customerAccount', 'customerBankProvince', 'customerBank', 'customerBankBrand', 'BeneficiaryProvinceId', 'BeneficiaryBankId', 'BeneficiaryBankBranchId', 'BeneficiaryAccount');
							if(in_array($n, $sCheck))
								$appInfo[$formData[$i]['name']] = $this->input->post($formData[$i]['name']);
							else
								$appInfo[$formData[$i]['name']] = is_numeric($this->input->post($formData[$i]['name'])) ? intval($this->input->post($formData[$i]['name'])) : $this->input->post($formData[$i]['name']); 
							if($n == 'dueDate'){
								$appInfo['firstEMI']  = getFEMI($appInfo[$formData[$i]['name']]);
							}

						}
						$appInfo['insrureId'] = 0;
						if($this->input->post('insured') == 'Y'){
							$appInfo['insrureId'] = 4;
						}
						$meta = array("");
						$metaArray = array();
						// for($i=0;$i<sizeof($meta);$i++){
						// 	$metaArray[$meta[$i]] = isset($appInfo[$meta[$i]]) ? $appInfo[$meta[$i]] : "";
						// 	unset($appInfo[$meta[$i]]);
						// }
						// $appInfo['metaData'] = json_encode($metaArray, JSON_UNESCAPED_UNICODE);
						unset($appInfo['abc']);	
						//if($subscriptionData[0]['appId'] == NULL and $subscriptionData[0]['appId'] == ''){
							$d =mafcSendApplication(json_encode($appInfo));
							$this -> ModelUtility -> saveLog('MAFCApplication', $appInfo, $d);
							if(1){
								$newData = array("loanAmount"=>$this->input->post('loanAmount'), "status" => "11", "appInfo" => json_encode($appInfo, JSON_UNESCAPED_UNICODE));
								$whereData = array("id" => $subscriptionId);
								$res = $this -> ModelSubscription -> updateSubscription($newData, $whereData);
								// if($res){
									//addSubscriptionAudit($subscriptionId, 0, 'ocb', 'pending', '');
									$this -> ModelCustomer -> updateCustomerVerificationStatusByCustomerId($subscriptionData[0]['customerId'], 'In-Progress');
									$output = array("status" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
									//$this -> ModelUtility -> saveLog($apiName, $input, $output);
									$this->LogManager->logApi($apiName, $input, $output);
									echo json_encode($output, JSON_UNESCAPED_UNICODE);						
								// }else{
								// 	$output = array("status" => "Error", "msg" => "Something went wrong", 'timeStamp' => date('Y-m-d H:i:s'));
								// 	$this -> ModelUtility -> saveLog($apiName, $input, $output);
								// 	echo json_encode($output, JSON_UNESCAPED_UNICODE);
								// }
							}
							else{
								//$this -> ModelUtility -> saveLog($apiName, $input, $d);
								$this->LogManager->logApi($apiName, $input, $d);
								echo json_encode($d, JSON_UNESCAPED_UNICODE);
							}
						// }else{
						// 	$newData = array("orderValue" => "0", "orderUpfront"=>"0", "loanAmount"=>$this->input->post('loanAmount'), "monthlyFee" => 0, "rrp" => "", "caFee" => "", "status" => "11", "appInfo" => json_encode($appInfo, JSON_UNESCAPED_UNICODE));
						// 	$whereData = array("id" => $subscriptionId);
						// 	$res = $this -> ModelSubscription -> updateSubscription($newData, $whereData);
						// 	if($res){
						// 		$output = array("status" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
						// 		$this -> ModelUtility -> saveLog($apiName, $input, $output);
						// 		echo json_encode($output, JSON_UNESCAPED_UNICODE);						
						// 	}else{
						// 		$output = array("status" => "Error", "msg" => "Something went wrong", 'timeStamp' => date('Y-m-d H:i:s'));
						// 		$this -> ModelUtility -> saveLog($apiName, $input, $output);
						// 		echo json_encode($output, JSON_UNESCAPED_UNICODE);
						// 	}
						// }
					}
					else{
						$output = $isValidFormData;
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
			}
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}	
		}else{
			$arr = array("Errors"=>validation_errors());
			if($arr){	
				$output = array("status" => "Error", "msg" => $arr['Errors'], 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
		}	
	}
	
	public function getBase64FromFileData($file_tmp)
        {
                 $type = pathinfo($file_tmp, PATHINFO_EXTENSION);
                 $data = file_get_contents($file_tmp);
                 return $base64 = base64_encode($data);
                // return $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
	
	
	public function addSubscriptionDocument()
	{
		$input = $this->input->post();
		$apiName = 'addSubscriptionDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			//$subscriptionId = 100034;
			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$requiredFile = array("icPictureFront" => '', "icPictureBack" => "");
					$oldFiles = $this->ModelCustomer->getCustomerDocument($subscriptionData[0]['customerId']);
					//print_r($oldFiles);
					$dData = $oldFiles;
					foreach($requiredFile as $k => $v){
						for($i=0;$i<sizeof($dData);$i++){
							if($k == $dData[$i]['label']){
								$requiredFile[$k] = $dData[$i]['docData'];
								break;
							}
						}	
					}
					//print_r($_FILES);
					/*  if(!isset($_FILES['CLIENT_PICTURE_ATTACH'])){
						$output = array("status" => "Error", "msg" => "CLIENT_PICTURE_ATTACH Required", 'timeStamp' => date('Y-m-d H:i:s'));
       	        		                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_FILES['DRIVING_LICENSE_ATTACH_FRONT'])){
						$output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH_FRONT Required", 'timeStamp' => date('Y-m-d H:i:s'));
       	        		                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_FILES['DRIVING_LICENSE_ATTACH_BACK'])){
						$output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH_BACK Required", 'timeStamp' => date('Y-m-d H:i:s'));
       	        		                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if($_FILES['CLIENT_PICTURE_ATTACH']['size'] > 2000000){
						$output = array("status" => "Error", "msg" => "CLIENT_PICTURE_ATTACH File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
       	        		                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if($_FILES['DRIVING_LICENSE_ATTACH_FRONT']['size'] > 2000000){
						$output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
	       	        	                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if($_FILES['DRIVING_LICENSE_ATTACH_BACK']['size'] > 2000000){
						$output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
	       	        	                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_FILES['icPictureFront']) and $oldFiles['icPictureFront'] != ''){
						$output = array("status" => "Error", "msg" => "icPictureFront Required", 'timeStamp' => date('Y-m-d H:i:s'));
	       	        	                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_FILES['icPictureBack']) and $oldFiles['icPictureBack'] != ''){
						$output = array("status" => "Error", "msg" => "icPictureBack Required", 'timeStamp' => date('Y-m-d H:i:s'));
	       	        	                $this -> ModelUtility -> saveLog($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else{*/		
					  if(!isset($_POST['CLIENT_PICTURE_ATTACH'])){
						$output = array("status" => "Error", "msg" => "CLIENT_PICTURE_ATTACH Required", 'timeStamp' => date('Y-m-d H:i:s'));
       	        		                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
										   $this->LogManager->logApi($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_POST['DRIVING_LICENSE_ATTACH_FRONT'])){
						$output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH_FRONT Required", 'timeStamp' => date('Y-m-d H:i:s'));
       	        		                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
										   $this->LogManager->logApi($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_POST['DRIVING_LICENSE_ATTACH_BACK'])){
						$output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH_BACK Required", 'timeStamp' => date('Y-m-d H:i:s'));
       	        		               // $this -> ModelUtility -> saveLog($apiName, $input, $output);
										   $this->LogManager->logApi($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_POST['icPictureFront']) and $oldFiles['icPictureFront'] != ''){
						$output = array("status" => "Error", "msg" => "icPictureFront Required", 'timeStamp' => date('Y-m-d H:i:s'));
	       	        	                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
										   $this->LogManager->logApi($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_POST['icPictureBack']) and $oldFiles['icPictureBack'] != ''){
						$output = array("status" => "Error", "msg" => "icPictureBack Required", 'timeStamp' => date('Y-m-d H:i:s'));
	       	        	                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
										   $this->LogManager->logApi($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }else if(!isset($_POST['SALE_IMAGE_ATTACH']) and $oldFiles['SALE_IMAGE_ATTACH'] != ''){
						$output = array("status" => "Error", "msg" => "SALE_IMAGE_ATTACH Required", 'timeStamp' => date('Y-m-d H:i:s'));
	       	        	                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
										   $this->LogManager->logApi($apiName, $input, $output);
        		                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					  }
					  else
					  {		
						$appId = $subscriptionData[0]['appId'];
						$imgPath = './img/subscriptionDoc/';
						
						//$file_tmp = $_FILES['CLIENT_PICTURE_ATTACH']['tmp_name'];
	                        	        //$base64Data = $this->getBase64FromFileData($file_tmp);
	                        	        $base64Data = $_POST['CLIENT_PICTURE_ATTACH'];
        	                        	$fileName = date('YmdHis').$subscriptionId.'CLIENT_PICTURE_ATTACH.jpg';
	                	                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "CLIENT_PICTURE_ATTACH", "displayName" => "CLIENT_PICTURE_ATTACH", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
                                		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
						
						$pdfFilePath = $imgPath.date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH1.pdf';
				                $this->convertpdf($html, $pdfFilePath);
						$base64Data = $this->getBase64FromFileData($pdfFilePath);

						$data = array("appId" => $appId, "fieldName" => "CLIENT_PICTURE_ATTACH", "fileType" => "jpg", "traceCode" => 'COMPASIA.'.date('YmdHis').'1', "fileContent" => $base64Data);
						$d = $this->addAppDocument($data);
					
						//$file_tmp = $_FILES['SALE_IMAGE_ATTACH']['tmp_name'];
	                        	        //$base64Data = $this->getBase64FromFileData($file_tmp);
	                        	        $base64Data = $_POST['SALE_IMAGE_ATTACH'];
        	                        	$fileName = date('YmdHis').$subscriptionId.'SALE_IMAGE_ATTACH.jpg';
	                	                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "SALE_IMAGE_ATTACH", "displayName" => "SALE_IMAGE_ATTACH", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
                                		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
						
						$pdfFilePath = $imgPath.date('YmdHis').$subscriptionId.'SALE_IMAGE_ATTACH.pdf';
				                $this->convertpdf($html, $pdfFilePath);
						$base64Data = $this->getBase64FromFileData($pdfFilePath);

						$data = array("appId" => $appId, "fieldName" => "SALE_IMAGE_ATTACH", "fileType" => "jpg", "traceCode" => 'COMPASIA.'.date('YmdHis').'1', "fileContent" => $base64Data);
						$d = $this->addAppDocument($data);
					
						//$file_tmp = $_FILES['DRIVING_LICENSE_ATTACH_FRONT']['tmp_name'];
		                                //$base64Data = $this->getBase64FromFileData($file_tmp);
		                                $base64Data = $_POST['DRIVING_LICENSE_ATTACH_FRONT'];
        		                        $fileName = date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH_FRONT.jpg';
                		                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "DRIVING_LICENSE_ATTACH_FRONT", "displayName" => "DRIVING_LICENSE_ATTACH_FRONT", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
                                		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
		
						//$file_tmp = $_FILES['DRIVING_LICENSE_ATTACH_BACK']['tmp_name'];
		                                //$base64Data = $this->getBase64FromFileData($file_tmp);
		                                $base64Data = $_POST['DRIVING_LICENSE_ATTACH_BACK'];
        		                        $fileName = date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH_BACK.jpg';
                		                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "DRIVING_LICENSE_ATTACH_BACK", "displayName" => "DRIVING_LICENSE_ATTACH_BACK", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
                                		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html .= '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
						
						$pdfFilePath = $imgPath.date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH.pdf';
				                $this->convertpdf($html, $pdfFilePath);
						$base64Data = $this->getBase64FromFileData($pdfFilePath);
						
						$data = array("appId" => $appId, "fieldName" => "DRIVING_LICENSE_ATTACH", "fileType" => "pdf", "traceCode" => 'COMPASIA.'.date('YmdHis').'2', "fileContent" => $base64Data);
						$d = $this->addAppDocument($data);
						if($requiredFile['icPictureFront'] == ''){
							//$file_tmp = $_FILES['icPictureFront']['tmp_name'];
			                                //$base64Data = $this->getBase64FromFileData($file_tmp);
			                                $base64Data = $_POST['icPictureFront'];
						}else{
							$base64Data = $this->getBase64FromFileData($requiredFile['icPictureFront']);
						}
        		                        $fileName = date('YmdHis').$subscriptionId.'icPictureFront.jpg';
                		                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "icPictureFront", "displayName" => "icPictureFront", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
                                		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
		
						if($requiredFile['icPictureBack'] == ''){
							//$file_tmp = $_FILES['icPictureBack']['tmp_name'];
			                                //$base64Data = $this->getBase64FromFileData($file_tmp);
			                                $base64Data = $_POST['icPictureBack'];;
						}else{
							$base64Data = $this->getBase64FromFileData($requiredFile['icPictureBack']);
						}
        		                        $fileName = date('YmdHis').$subscriptionId.'icPictureBack.jpg';
                		                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "icPictureBack", "displayName" => "icPictureBack", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
                                		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html .= '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
						
						$pdfFilePath = $imgPath.date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH.pdf';
				                $this->convertpdf($html, $pdfFilePath);
						$base64Data = $this->getBase64FromFileData($pdfFilePath);
						
						$data = array("appId" => $appId, "fieldName" => "IDCARD_ATTACH", "fileType" => "pdf", "traceCode" => 'COMPASIA.'.date('YmdHis').'3', "fileContent" => $base64Data);
						$d = $this->addAppDocument($data);
					
						/*$customerId = $subscriptionData[0]['customerId'];
						$docData = $this->ModelCustomer->getCustomerDocData($customerId);
						if($docData){
							$docPath = $docData['data'][0]['docData'];
							$file = file_get_contents($docPath); 	
			                                $base64Data = base64_encode($file);
        			                        $fileName = date('YmdHis').$subscriptionId.'IDCARD_ATTACH.jpg';
                			                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
							$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "IDCARD_ATTACH", "displayName" => "IDCARD_ATTACH", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
   							$data = array("appId" => $appId, "fieldName" => "IDCARD_ATTACH", "fileType" => "jpg", "traceCode" => 'COMPASIA.'.date('YmdHis').'3', "fileContent" => $base64Data);
							$d = $this->addAppDocument($data);
							if($d['status'] == 'Success')
			                             		$this -> ModelSubscription -> addSubscriptionDocument($docData);
						}*/
						$cotp = $this->checkRequestOTPByTelco1($subscriptionId);	
						$output = array("status" => "Success", "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
						if($cotp['status'] == 'Success')
						{
							$output['otpRequired'] = isset($cotp['otpRequired']) ? $cotp['otpRequired'] : 0;
							$output['attemptLeft'] = isset($cotp['attemptLeft']) ? $cotp['attemptLeft'] : 0;		
						}
                	                        //$this -> ModelUtility -> saveLog($apiName, $input, $output);
                        	                $this->LogManager->logApi($apiName, $input, $output);
											echo json_encode($output, JSON_UNESCAPED_UNICODE);						
					  }	
					
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
                                        //$this -> ModelUtility -> saveLog($apiName, $input, $output);
                                        $this->LogManager->logApi($apiName, $input, $output);
										echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
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
				$output = array("status" => "Error", "msg" => $arr['Errors'], "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
				//print_r($arr);
			}
		}	
	//echo true;
	}

	public function addSubscriptionDocumentMAFC(){
		$input = $this->input->post();
		$apiName = 'addSubscriptionDocumentMAFC'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');
			//$subscriptionId = 100034;
			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData){
					$appId = $subscriptionData[0]['appId'];
					
					$requiredFile = array("IDCARD_FRONT" => '', "IDCARD_BEHIND" => "");
					$oldFiles = $this->ModelCustomer->getCustomerDocument($subscriptionData[0]['customerId']);
					//print_r($oldFiles);
					$dData = $oldFiles;
					// foreach($requiredFile as $k => $v){
					// 	for($i=0;$i<sizeof($dData);$i++){
					// 		if($k == $dData[$i]['label']){
					// 			$requiredFile[$k] = $dData[$i]['docData'];
					// 			break;
					// 		}
					// 	}	
					// }
					if($_FILES['SELFIE']['size'] == 0){
						$output = array("status" => "Error", "msg" => "SELFIE Required", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else if(!isset($_FILES['DL'])){
						$output = array("status" => "Error", "msg" => "DL Required", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else if(!isset($_FILES['FB'])){
						$output = array("status" => "Error", "msg" => "FB Required", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else if($_FILES['IDCARD_FRONT']['size'] == 0 and $oldFiles['IDCARD_FRONT'] != ''){
						$output = array("status" => "Error", "msg" => "IDCARD_FRONT Required", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else if($_FILES['IDCARD_BEHIND']['size'] == 0 and $oldFiles['IDCARD_BEHIND'] != ''){
						$output = array("status" => "Error", "msg" => "IDCARD_BEHIND Required", 'timeStamp' => date('Y-m-d H:i:s'));
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else{		
						$appId = $subscriptionData[0]['appId'];
						$imgPath = './img/subscriptionDoc/';
						
						$base64Data = $this->getBase64FromFileData($_FILES['SELFIE']['tmp_name']);
						$fileName = date('YmdHis').$subscriptionId.'SELFIE.jpg';
						file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "SELFIE", "displayName" => "SELFIE", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
						$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$data = array("partnerId" => "COMP", "applicationId" => $appId, "fileType" => "SELFIE", "fileName" => $fileName, "base64" => $base64Data, "msgName" => "updateImage");
						$d = mafcUploadDocument($data);
						
						//$base64Data = $_POST['DL'];
						if(sizeof($_FILES['DL']['tmp_name']) == 1){
							$base64Data = $this->getBase64FromFileData($_FILES['DL']['tmp_name'][0]);
							$fileName = date('YmdHis').$subscriptionId.'DL.jpg';
							file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
							$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "DL", "displayName" => "DL", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
							$this -> ModelSubscription -> addSubscriptionDocument($docData);
							$data = array("partnerId" => "COMP", "applicationId" => $appId, "fileType" => "DL", "fileName" => $fileName, "base64" => $base64Data, "msgName" => "updateImage");
							$d = mafcUploadDocument($data);
						}
						else{
							for($i=0;$i<sizeof($_FILES['DL']['tmp_name']);$i++){
								$base64Data = $this->getBase64FromFileData($_FILES['DL']['tmp_name'][$i]);
								$fileName = date('YmdHis').$i.$subscriptionId.'DL.jpg';
								file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
								$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "DL".$i, "displayName" => "DL", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
								$this -> ModelSubscription -> addSubscriptionDocument($docData);
								$data = array("partnerId" => "COMP", "applicationId" => $appId, "fileType" => "DL", "fileName" => $fileName, "base64" => $base64Data, "msgName" => "updateImage");
								$d = mafcUploadDocument($data);
							}
						}	
						if(sizeof($_FILES['FB']['tmp_name']) == 1){
							$base64Data = $this->getBase64FromFileData($_FILES['FB']['tmp_name'][0]);
							$fileName = date('YmdHis').$subscriptionId.'FB.jpg';
							file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
							$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "FB", "displayName" => "DRIVING_LICENSE_ATTACH_FRONT", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
							$this -> ModelSubscription -> addSubscriptionDocument($docData);

							$data = array("partnerId" => "COMP", "applicationId" => $appId, "fileType" => "FB", "fileName" => $fileName, "base64" => $base64Data, "msgName" => "updateImage");
							$d = mafcUploadDocument($data);
						}
						else{
							for($i=0;$i<sizeof($_FILES['FB']['tmp_name']);$i++){
								$base64Data = $this->getBase64FromFileData($_FILES['FB']['tmp_name'][$i]);
								$fileName = date('YmdHis').$i.$subscriptionId.'FB.jpg';
								file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
								$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "FB".$i, "displayName" => "DRIVING_LICENSE_ATTACH_FRONT", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
								$this -> ModelSubscription -> addSubscriptionDocument($docData);

								$data = array("partnerId" => "COMP", "applicationId" => $appId, "fileType" => "FB", "fileName" => $fileName, "base64" => $base64Data, "msgName" => "updateImage");
								$d = mafcUploadDocument($data);
							}	
						}
						if($requiredFile['IDCARD_FRONT'] == ''){
							$base64Data = $this->getBase64FromFileData($_FILES['IDCARD_FRONT']['tmp_name']); //$_POST['IDCARD_FRONT'];
						}else{
							$base64Data = $this->getBase64FromFileData($requiredFile['IDCARD_FRONT']);
						}
						$fileName = date('YmdHis').$subscriptionId.'IDCARD_FRONT.jpg';
						file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "IDCARD_FRONT", "displayName" => "IDCARD_FRONT", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
						$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$data = array("partnerId" => "COMP", "applicationId" => $appId, "fileType" => "IDCARD_FRONT", "fileName" => $fileName, "base64" => $base64Data, "msgName" => "updateImage");
						$d = mafcUploadDocument($data);

						if($requiredFile['IDCARD_BEHIND'] == ''){
							$base64Data = $this->getBase64FromFileData($_FILES['IDCARD_BEHIND']['tmp_name']); //$_POST['IDCARD_BEHIND'];;
						}else{
							$base64Data = $this->getBase64FromFileData($requiredFile['IDCARD_BEHIND']);
						}
						$fileName = date('YmdHis').$subscriptionId.'IDCARD_BEHIND.jpg';
						file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "IDCARD_BEHIND", "displayName" => "IDCARD_BEHIND", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
						$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$data = array("partnerId" => "COMP", "applicationId" => $appId, "fileType" => "IDCARD_BEHIND", "fileName" => $fileName, "base64" => $base64Data, "msgName" => "updateImage");
						$d = mafcUploadDocument($data);

						// $cotp = $this->checkRequestOTPByTelco1($subscriptionId);	
						$output = array("status" => "Success", "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
						// if($cotp['status'] == 'Success')
						// {
						// 	$output['otpRequired'] = isset($cotp['otpRequired']) ? $cotp['otpRequired'] : 0;
						// 	$output['attemptLeft'] = isset($cotp['attemptLeft']) ? $cotp['attemptLeft'] : 0;		
						// }
						//$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output, JSON_UNESCAPED_UNICODE);						
					}	
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
					//$this -> ModelUtility -> saveLog($apiName, $input, $output);
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
			}
			else{
				$output = array("status" => "Error", "msg" => "Invalid User Name or Api Key", "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}	
		}
		else{
			$arr = array("Errors"=>validation_errors());
			if($arr){	
				$output = array("status" => "Error", "msg" => $arr['Errors'], "otpRequired" => 0, "attemptLeft" => 0, 'timeStamp' => date('Y-m-d H:i:s'));
				//$this -> ModelUtility -> saveLog($apiName, $input, $output);
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
		}	
	}	
	
	public function getSubscriptionSummary()
	{
		$input = $this->input->post();
		$apiName = 'getSubscriptionSummary'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
        	                        $dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
                	                $dictionaryData = array();
                        	        foreach($dictionaryDataRow as $d)
                                	{
                                        	$dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);
                                	}
	                                $loanPurpose = $dictionaryData['LOAN_PURPOSE_ID'];
					$allData = $subscriptionData[0];
					$appInfo = json_decode($allData['appInfo'], true);
					for($i=0;$i<sizeof($loanPurpose);$i++){
						if($loanPurpose[$i]['id'] == $appInfo['loanPurpose']){
							$loanPurpose = $loanPurpose[$i]['name'];
							break;
						}
					}	
					$subscriptionPriceItem = $this->ModelSubscription->getSubscriptionPriceItem($subscriptionId);
					$sid = array();
					if($subscriptionPriceItem){
						// print_r($subscriptionPriceItem);
						for($i=0;$i<sizeof($subscriptionPriceItem); $i++)
							$sid[$subscriptionPriceItem[$i]['itemType']] = $subscriptionPriceItem[$i]['itemValue'];
						
					} 
					$productImage = $allData['image'];
					if($allData['isRelative'] == 1)
						$productImage = CRM_URL.$productImage;
					$data  = array("subscriptionId" => $subscriptionId, "tenure" => $allData['tenure'].' Months', "storage" => $allData['rom'].' '.$allData['romUnit'], "ram" => $allData['ram'].' '.$allData['ramUnit'], "color" => $allData['color'], "productName" => $allData['productName'], "productImage" => $productImage, "loanPurpose" => $loanPurpose, "downPayment" => $appInfo['downpayment'], "monthlyPayment"=>$sid['dmof'], "fincoResidualValue"=>$sid['fsv']);	
					$data = array_merge($data,$sid);
					$output = array("status" => "Success", "msg" => $data, 'timeStamp' => date('Y-m-d H:i:s'));
                                        //$this -> ModelUtility -> saveLog($apiName, $input, $output);
										$this->LogManager->logApi($apiName, $input, $output);
                                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getSubscriptionSummaryMAFC(){
		$input = $this->input->post();
		$apiName = 'getSubscriptionSummaryMAFC'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()){
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData){
					                $dictionaryDataRow = json_decode(getDictionaryList()['msg'],true);
                	                $dictionaryData = array();
                        	        foreach($dictionaryDataRow as $d)
                                	{
                                        	$dictionaryData[$d['GroupId']][] = array("id" => $d['Code'], "name" => $d['DisplayName']);
                                	}
					$loanPurpose = '';				
	                                
					$allData = $subscriptionData[0];
					$appInfo = json_decode($allData['appInfo'], true);
						
					$subscriptionPriceItem = $this->ModelSubscription->getSubscriptionPriceItem($subscriptionId);
					$sid = array();
					if($subscriptionPriceItem){
						// print_r($subscriptionPriceItem);
						for($i=0;$i<sizeof($subscriptionPriceItem); $i++)
							$sid[$subscriptionPriceItem[$i]['itemType']] = $subscriptionPriceItem[$i]['itemValue'];
						
					} 
					$productImage = $allData['image'];
					if($allData['isRelative'] == 1)
						$productImage = CRM_URL.$productImage;
					$data  = array("subscriptionId" => $subscriptionId, "tenure" => $allData['tenure'].' Months', "storage" => $allData['rom'].' '.$allData['romUnit'], "ram" => $allData['ram'].' '.$allData['ramUnit'], "color" => $allData['color'], "productName" => $allData['productName'], "productImage" => $productImage, "loanPurpose" => $loanPurpose, "downPayment" => 0, "monthlyPayment"=>$sid['dmof'], "fincoResidualValue"=>$sid['fsv']);	
					$data = array_merge($data,$sid);
					$output = array("status" => "Success", "msg" => $data, 'timeStamp' => date('Y-m-d H:i:s'));
                                        //$this -> ModelUtility -> saveLog($apiName, $input, $output);
										$this->LogManager->logApi($apiName, $input, $output);
                                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function addSubscriptionMissingDocument()
	{
		$input = $this->input->post();
		$apiName = 'addSubscriptionMissingDocument'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		$this->form_validation->set_rules("subscriptionId", "Subscription Id", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$subscriptionId = $this->input->post('subscriptionId');

			//$this -> load -> library('../controllers/Utility');
			$isAuthenticate = $this -> ModelUtility -> checkApiAuthentication($userName, $apiKey);	
			if($isAuthenticate){
				$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
				if($subscriptionData)
				{
					$appId = $subscriptionData[0]['appId'];
					//$appId = 1908687;
					$imgPath = './img/subscriptionDoc/';
					
					$success = 1;

					if(isset($_FILES['CLIENT_PICTURE_ATTACH'])){
					  if($_FILES['CLIENT_PICTURE_ATTACH']['size'] > 2000000){
                                                $output = array("status" => "Error", "msg" => "CLIENT_PICTURE_ATTACH File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
                                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
												$this->LogManager->logApi($apiName, $input, $output);
                                                echo json_encode($output, JSON_UNESCAPED_UNICODE);
                                          }else{
						$file_tmp = $_FILES['CLIENT_PICTURE_ATTACH']['tmp_name'];
	                        	        $base64Data = $this->getBase64FromFileData($file_tmp);
        	                        	$fileName = date('YmdHis').$subscriptionId.'CLIENT_PICTURE_ATTACH.jpg';
	                	                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));

						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "CLIENT_PICTURE_ATTACH", "displayName" => "CLIENT_PICTURE_ATTACH", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
	                                	$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
						$pdfFilePath = $imgPath.date('YmdHis').$subscriptionId.'CLIENT_PICTURE_ATTACH.pdf';
				                $this->convertpdf($html, $pdfFilePath);
						$base64Data = $this->getBase64FromFileData($pdfFilePath);

						$data = array("appId" => $appId, "fieldName" => "CLIENT_PICTURE_ATTACH", "fileType" => "jpg", "traceCode" => 'COMPASIA.'.date('YmdHis').'1', "fileContent" => $base64Data);
						$d = $this->addAppDocument($data);
						if(!$d)
							$success = 0;
            	                    		$this -> ModelSubscription -> addSubscriptionDocument($docData);


					  }
					}
					if(isset($_FILES['DRIVING_LICENSE_ATTACH_FRONT']) and isset($_FILES['DRIVING_LICENSE_ATTACH_BACK'])){
                                          if($_FILES['DRIVING_LICENSE_ATTACH_FRONT']['size'] > 2000000){
                                                $output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH_FRONT File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
                                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
												$this->LogManager->logApi($apiName, $input, $output);
                                                echo json_encode($output, JSON_UNESCAPED_UNICODE);
                                          }else if($_FILES['DRIVING_LICENSE_ATTACH_BACK']['size'] > 2000000){
                                                $output = array("status" => "Error", "msg" => "DRIVING_LICENSE_ATTACH_BACK File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
                                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
												$this->LogManager->logApi($apiName, $input, $output);
                                                echo json_encode($output, JSON_UNESCAPED_UNICODE);
                                          }else{
						$file_tmp = $_FILES['DRIVING_LICENSE_ATTACH_FRONT']['tmp_name'];
		                                $base64Data = $this->getBase64FromFileData($file_tmp);
        		                        $fileName = date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH_FRONT.jpg';
                		                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "DRIVING_LICENSE_ATTACH_FRONT", "displayName" => "DRIVING_LICENSE_ATTACH_FRONT", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
            	                    		$this -> ModelSubscription -> addSubscriptionDocument($docData);
						
						$file_tmp2 = $_FILES['DRIVING_LICENSE_ATTACH_BACK']['tmp_name'];
		                                $base64Data2 = $this->getBase64FromFileData($file_tmp2);
        		                        $fileName2 = date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH_BACK.jpg';
                		                file_put_contents($imgPath.$fileName2, base64_decode(str_replace(" ","+",$base64Data2)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "DRIVING_LICENSE_ATTACH_BACK", "displayName" => "DRIVING_LICENSE_ATTACH_BACK", "docData" => BASE_URL."img/subscriptionDoc/".$fileName2);
            	                    		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
						$html .= '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName2).'" style="max-width:500;">';
						$pdfFilePath = $imgPath.date('YmdHis').$subscriptionId.'DRIVING_LICENSE_ATTACH.pdf';
				                $this->convertpdf($html, $pdfFilePath);
						$base64Data = $this->getBase64FromFileData($pdfFilePath);

						$data = array("appId" => $appId, "fieldName" => "DRIVING_LICENSE_ATTACH", "fileType" => "jpg", "traceCode" => 'COMPASIA.'.date('YmdHis').'2', "fileContent" => $base64Data);
						$d = $this->addAppDocument($data);
						if(!$d)
							$success = 0;
                                          }
                                        }	
					if(isset($_FILES['IDCARD_ATTACH_FRONT']) and isset($_FILES['IDCARD_ATTACH_BACK'])){
                                          if($_FILES['IDCARD_ATTACH_FRONT']['size'] > 2000000){
                                                $output = array("status" => "Error", "msg" => "IDCARD_ATTACH_FRONT File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
                                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
												$this->LogManager->logApi($apiName, $input, $output);
                                                echo json_encode($output, JSON_UNESCAPED_UNICODE);
                                          }else if($_FILES['IDCARD_ATTACH_BACK']['size'] > 2000000){
                                                $output = array("status" => "Error", "msg" => "IDCARD_ATTACH_BACK File is too large", 'timeStamp' => date('Y-m-d H:i:s'));
                                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
												$this->LogManager->logApi($apiName, $input, $output);
                                                echo json_encode($output, JSON_UNESCAPED_UNICODE);
                                          }else{
						$file_tmp = $_FILES['IDCARD_ATTACH_FRONT']['tmp_name'];
		                                $base64Data = $this->getBase64FromFileData($file_tmp);
        		                        $fileName = date('YmdHis').$subscriptionId.'IDCARD_ATTACH_FRONT.jpg';
                		                file_put_contents($imgPath.$fileName, base64_decode(str_replace(" ","+",$base64Data)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "IDCARD_ATTACH_FRONT", "displayName" => "IDCARD_ATTACH_FRONT", "docData" => BASE_URL."img/subscriptionDoc/".$fileName);
            	                    		$this -> ModelSubscription -> addSubscriptionDocument($docData);
						
						$file_tmp2 = $_FILES['IDCARD_ATTACH_BACK']['tmp_name'];
		                                $base64Data2 = $this->getBase64FromFileData($file_tmp2);
        		                        $fileName2 = date('YmdHis').$subscriptionId.'IDCARD_ATTACH_BACK.jpg';
                		                file_put_contents($imgPath.$fileName2, base64_decode(str_replace(" ","+",$base64Data2)));
						$docData = array("subscriptionId" => $subscriptionId, "docType" => "file", "label" => "IDCARD_ATTACH_BACK", "displayName" => "IDCARD_ATTACH_BACK", "docData" => BASE_URL."img/subscriptionDoc/".$fileName2);
            	                    		$this -> ModelSubscription -> addSubscriptionDocument($docData);

						$html = '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName).'" style="max-width:500;">';
						$html .= '<img src="data:image/jpeg;base64,'.$this->getBase64FromFileData($imgPath.$fileName2).'" style="max-width:500;">';
						$pdfFilePath = $imgPath.date('YmdHis').$subscriptionId.'IDCARD_ATTACH.pdf';
				                $this->convertpdf($html, $pdfFilePath);
						$base64Data = $this->getBase64FromFileData($pdfFilePath);

						$data = array("appId" => $appId, "fieldName" => "IDCARD_ATTACH", "fileType" => "jpg", "traceCode" => 'COMPASIA.'.date('YmdHis').'2', "fileContent" => $base64Data);
						$d = $this->addAppDocument($data);
						if(!$d)
							$success = 0;
                                          }
                                        }
					if($success == 1)
					{
						$newStatus = '11';
						$this -> updateSubscriptionStatus($subscriptionId, $newStatus);
						addSubscriptionAudit($subscriptionId, 0, 'OCB', $newStatus, $data['statusDesc']);	

						$output = array("status" => "Success", "msg" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
        	                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
											$this->LogManager->logApi($apiName, $input, $output);
                	                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}
					else
					{
						$output = array("status" => "Error", "msg" => "Invalid Files", 'timeStamp' => date('Y-m-d H:i:s'));
        	                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
											$this->LogManager->logApi($apiName, $input, $output);
                	                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
					}		
				}
				else
				{
					$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function getMissingDocumentList()
	{
		$input = $this->input->get();
		$apiName = 'getMissingDocumentList'; 
		$subscriptionId = $this->input->get('subscriptionId');
			
		$subscriptionData = $this->ModelSubscription->getSubscriptionById($subscriptionId);
		if($subscriptionData)
		{
			$whereData = array("subscriptionId" => $subscriptionId, "status" => "document missing");
			$d = $this->ModelSubscription->getSubscriptionAuditData($whereData, 'id', '1');
			if($d)
			{
				$data = $d[0];
				$files = explode(';',$data['remark']);
				$item = array();
				if(in_array('CLIENT_PICTURE_ATTACH', $files))
					$item[] = array("name" => "CLIENT_PICTURE_ATTACH", "type"=>"file", "placeHolder"=>"CLIENT_PICTURE_ATTACH", "isRequired"=>"1");
				if(in_array('DRIVING_LICENSE_ATTACH', $files)){
					$item[] = array("name" => "DRIVING_LICENSE_ATTACH_FRONT", "type"=>"file", "placeHolder"=>"Driving License Front", "isRequired"=>"1");
					$item[] = array("name" => "DRIVING_LICENSE_ATTACH_BACK", "type"=>"file", "placeHolder"=>"Driving License Back", "isRequired"=>"1");
				}
				if(in_array('IDCARD_ATTACH', $files)){
					$item[] = array("name" => "IDCARD_ATTACH_FRONT", "type"=>"file", "placeHolder"=>"ID Card Front", "isRequired"=>"1");
					$item[] = array("name" => "IDCARD_ATTACH_BACK", "type"=>"file", "placeHolder"=>"ID Card Back", "isRequired="=>"1");
				}
				$output = array("status" => "Success", "msg" => $item, "subscriptionId" => $subscriptionId, 'timeStamp' => date('Y-m-d H:i:s'));
                                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
                                echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
			else
			{
				$output = array("status" => "Error", "msg" => "Invalid Subscription Id", 'timeStamp' => date('Y-m-d H:i:s'));
      	      	                //$this -> ModelUtility -> saveLog($apiName, $input, $output);
								$this->LogManager->logApi($apiName, $input, $output);
	                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
		}
		else
		{
			$output = array("status" => "Error", "msg" => "Invalid SubscriptionId", 'timeStamp' => date('Y-m-d H:i:s'));
                        //$this -> ModelUtility -> saveLog($apiName, $input, $output);
						$this->LogManager->logApi($apiName, $input, $output);
                        echo json_encode($output, JSON_UNESCAPED_UNICODE);
		}
	//echo true;
	}

	public function getAllItem($mA){
		//print_r($mA);
		$nA = array();
		foreach($mA as $k => $v){
			if(is_array(json_decode($v,true))){
				$subArray = $this->getAllItem(json_decode($v,true));
				$nA = array_merge($nA,$subArray);
			}else{
				$nA[$k] = $v;
			}
		}
		return $nA;
	}
	
	public function addAppDocument($data, $count = 0)
	{
		$rData = $data;
                $url = OCBLINK."api/CompAsia/AddDocument";
                foreach($data as $k => $v)
			if(is_numeric($v))	
                      $data[$k] = intval($v);

                $data = str_replace('\\','',json_encode($data));

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
		$errorCode = curl_errno($ch);	
                if ($errorCode)
                {
			// Error code  28 for TIMEOUT
			if($errorCode == 28 and $count<5){
				$count++;
				$this->addAppDocument($rData, $count);
			}else{
	                        return array("status" => "Error", "msg" => curl_error($ch));
			}
		}
                else
                {
                        $transaction = json_decode($data, TRUE);
                        if(isset($transaction['status']))
                        {
                                if($transaction['status'] == 200){
                                        return array("status" => "Success", "msg" => "Success");
				}else{
					if(isset($transaction['errorCode'])){
						if($transaction['errorCode'] == 0){
		                                        return array("status" => "Success", "msg" => "Success");
						}else{
							$errorCode = array('400'=>'Missing parameters','1001'=>'Wrong data','1002'=>'Application is wrong status','1003'=>'The current address is not in OCB active area','1004'=>'The company  address is not in OCB active area','1005'=>'Trace code is existed','1006'=>'Application is not belong to vender','1007'=>'Wrong cancel type','1999'=>'Violate ComBs policy','9999'=>'Error in processing','9998'=>'Sending failed, Please try again','2001'=>'Invalid document','2002'=>'Document is existed','2003'=>'Document path can not empty','3001'=>'Not yet send OTP','3002'=>'Get telco score fail','3003'=>'Wrong otp number','3004'=>'Wrong telco','3005'=>'Cannot calculate score');
		                                        return array("status" => "Error", "msg" =>$errorCode[$transaction['errorCode']]);
						}
					}else{
	                                        return array("status" => "Error", "msg" =>$transaction['message']);
					}
				}
                        }
                        else
                        {
                             return array("status" => "Error", "msg" =>$data );
                        }
                        curl_close($ch);
                }
        }		
}
