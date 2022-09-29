<?php
defined('BASEPATH') OR exit('No direct script access allowed');
date_default_timezone_set('Asia/Kuala_Lumpur');

class CustomerUpgrade extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		header('Access-Control-Allow-Origin: *');
		$this->load->model('ModelCustomerUpgrade', 'mcu');
		$this->load->library('form_validation');
		$this->load->model('ModelUtility');
		$this->load->helper('utility_helper');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	//Nurish - Upgrade program ---------------------------------------------------------
	public function getUpCustomerInfo()
	{
		$input = $this->input->post();
		$apiName = 'getUpCustomerInfo';
		
		$nationalid = $this->input->post('nationalid');
		
		//$nationalid = '079084005888';
		$imei = $this->input->post('imei');
		$this->LogManager->logApi($apiName, $imei, $nationalid);
		$customerData =$this->mcu->getCustomerInfo($nationalid, $imei);
		if($customerData)
		{
			if(!empty($customerData['data'][0])){
				$cData = $customerData['data'][0];
				//print_r($cData);
				$resultStr = '{"data":[';
				$resultStr .= '[					
				"'.$cData['id'].'",
				"'.$cData['purchaseDate'].'",
				"'.$cData['imei'].'",
				"'.$cData['customerName'].'",
				"'.$cData['product'].'",
				"'.$cData['insurance'].'",
				"'.$cData['isEligible'].'",
				"'.number_format(round($cData['grv'], 0)).'",
				"'.$cData['programId'].'",
				""
				],';
				
				$resultStr = substr($resultStr,0,-1);
				$resultStr .= ']}';
				
				echo $resultStr; 
			}
			else{
				$output = array("status" => "Error", "msg" => "No record found", 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				$resultStr = '{"data":[]}';
				echo $resultStr;
			}
		}
		else{
			$output = array("status" => "Error", "msg" => "No record found", 'timeStamp' => date('Y-m-d H:i:s'));
			$this->LogManager->logApi($apiName, $input, $output);
			$resultStr = '{"data":[]}';
			echo $resultStr;
		}
	}

	public function customerchecking()
	{
		$apiName = 'customerchecking';	
		$nationalid = $this->input->post('nationalid');
		
		$imei = $this->input->post('imei');
		$this->LogManager->logApi($apiName, $imei, $nationalid);
		$customerData =$this->mcu->customercheking($nationalid, $imei);
		
		echo $customerData;			
	}

	public function getTransactionInfo()
	{
		$input = $this->input->post();
		$apiName = 'getTransactionInfo';
		
		$nationalid = $this->input->post('nationalid');
		$imei = $this->input->post('imei');
		
		//$nationalid = '079084002022';
		//$imei = '354949615415148';

		$data = "nationalid=".$nationalid."&imei=".$imei;
		$url = BBTI_URL.'renewp/sessionregister';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		$output = json_decode($result,true);

		return $output;
	}

	public function testgetToken(){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => 'http://bbtiapidev.ddns.net:3000/api/v1/integration/diagnostic/token',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS =>'{
		"clientId": "3c58ec08-7f64-4335-a6dd-ca638acf1274",
		"clientSecret": "RRZ35OLE06svpV4YjRhFVHy/umI8ztFm55tbtK9g7jo39zWO7GE923GWCDnG98Org+Db0hZBCALyT+epvp9j0glKTYKb96t9IXy7hXXvhukkjEJtHxuCgqo/8y+TnqVIawAQidhwbiIh0Nh7yocWhoZtAqHZT4adj6BJry3KdxcomfoDr55QZhb62TMuGcaNeWINuZu/9b68BGZmRdbwuJVOkCUaLeyDcWkyJ+wornvxQg+uuyZN9mABjbiBj9ZzfKFk2NMrkitC/CGeqz6pO4A5DwG/EHchkKl/v/Ppcx43Wm34ZNNJA+0SrXxeUHaBs8s2voEjg4dtUkzPN3UlAA==",
		"deviceIdentifier": "35-332907"
		}',
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		),
		));

		$response = curl_exec($curl);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		echo $httpcode;
	}

    public function addCustomerUpgrade()
    {
        $nationalid = $this->input->post('nationalid');
		$imei = $this->input->post('imei');
        $customerId = $this->input->post('customerid');
		$product = $this->input->post('product');
		$grv = $this->input->post('deviceGRV');
		$staffid = $this->input->post('staffid');
		$programId = $this->input->post('programId');
		$clientId = $this->input->post('clientId');
		$clientSecret = $this->input->post('clientSecret');

		if($imei !=''){
			$curl = curl_init();

			curl_setopt_array($curl, array(
			CURLOPT_URL => BBTI_URL.'api/v1/integration/diagnostic/token',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{
			"clientId": "'.$clientId.'",
			"clientSecret": "'.$clientSecret.'",
			"deviceIdentifier": "'.$imei.'"
			}',
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
			));

			$response = curl_exec($curl);
			$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			$decoderesponse = json_decode($response);
			//print_r($decoderesponse); exit();
			if(in_array($httpcode, array(200,201))){

				$accessToken = $decoderesponse->data->accessToken;
				$tokenDecode = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $accessToken)[1]))));
				$trxId = $tokenDecode->clientDiagnosticSessionId;
				$iat = $tokenDecode->iat;
				$exp = $tokenDecode->exp;
				//echo $trxId; exit();
				$newData = array("customerId" => $customerId, 
								"programId" => $programId, 
								"status" => 'Running', 
								"token" => $accessToken,
								"transID" => $trxId, 
								"iat" => $iat, 
								"exp" => $exp, 
								"imei" => $imei, 
								"nationalid" => $nationalid, 
								"grv" => $grv, 
								"product" => $product, 
								"createdby" => $staffid, 
								"createdDate" => date('Y-m-d h:i:s'));

				$res = $this->mcu->insertCustomerUpgrade($newData);

				//create initial record in tbl diagnosticresult
				$diagnosticresultData = array("cuid" => $res );
				$res2 = $this->mcu->createRecordintbldiagnosticresult($diagnosticresultData);
				
				//create initial record in tbl physicaldeclaration
				$physicaldeclarationData = array("cuid" => $res, "dgresId" => $res2, "dvgrvVal" => $grv);
				$res = $this->mcu->createRecordintblphysicaldeclaration($physicaldeclarationData);

				$output = array("status" => "Success", "recordid" => $res, "transactionid" => $trxId, "token" => $accessToken);
			}
			else{
				$errmsg = $decoderesponse->message->EN;
				$output = array("status" => "Error", "msg" => $errmsg);
			}
		}
		else{
			$output = array("status" => "Error", "msg" => "Invalid IMEI");
		}
		echo json_encode($output);
    }

    public function getTransactionStatus()
    {
        $staffid = $this->input->post('staffid');
		$res = $this->mcu->getTransactionStatusData($staffid);
		//print_r($res); exit();
        $resultStr = '{"data":[';
        $iQ = 0;
        foreach($res['data'] as $eachQuery){
			$isUpgrade = 'Pending';
			if($eachQuery['isConfirmUpgrade'] == 1){
				$isUpgrade = 'Completed';
			}
			
			if($eachQuery['isUpgradeCancel'] == 1){
				$isUpgrade = 'Cancelled';
			}
			//echo $eachQuery['token']; exit();
            $iQ++;
            $resultStr .= '[					
            "'.$iQ.'",
			"'.$eachQuery['id'].'",
            "'.$eachQuery['customerId'].'",
            "'.$eachQuery['createdDate'].'",
            "'.$eachQuery['customerName'].'",
            "'.$eachQuery['product'].'",
            "'.$eachQuery['transID'].'",
            "'.$eachQuery['status'].'",
            "'.$isUpgrade.'",
			"'.$eachQuery['isRetry'].'",
			"'.$eachQuery['token'].'",
            ""
            ],';
        }
        if($iQ == 0)
        {
            $resultStr = '{"data":[]}';
        }
        else
        {
            $resultStr = substr($resultStr,0,-1);
            $resultStr .= ']}';
        }
        echo $resultStr;
    }

    public function canceldiagnosticSession(){
        $cid = $this->input->post('cid');
        $trxid = $this->input->post('trxid');
        $cuid = $this->input->post('cuid');
        $token = $this->input->post('token');

		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => BBTI_URL.'api/v1/integration/diagnostic/cancel',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'PATCH',
		CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer '.$token.''
		),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$decoderesponse = json_decode($response);
		//print_r($decoderesponse); exit();
		if($decoderesponse->data->cancelStatus == 1){
        	$res = $this->mcu->cancelcustomerupgrade($cid, $cuid, $trxid, 'Cancelled');
        	echo $res;
		}
    }

	public function getCustomerDiagnosticRslt()
	{
        $custID = $this->input->post('cuid');
        $res = $this->mcu->getCustomerDiagnosticRslt($custID);
        echo json_encode($res);
    }

	public function getdeviceConditionType()
	{
        $res = $this->mcu->getdeviceConditionType();
        echo json_encode($res);
    }

	public function getAppDiagnosticResult()
	{
        $input = $this->input->post();
		$apiName = 'getAppDiagnosticResult'; 
		
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");

		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			if($isAuthenticate)
			{
				$transid = $this->input->post('transID');
				$devicePrice = $this->input->post('devicePrice');
				$grade = $this->input->post('grade');
				$screenCalibration = $this->input->post('screenCalibration');
				$deviceRotation = $this->input->post('deviceRotation');
				$hardwareButton = $this->input->post('hardwareButton');
				$cameraCheck = $this->input->post('cameraCheck');
				$cameraAutofocus = $this->input->post('cameraAutofocus');
				$biometricId = $this->input->post('biometricId');
				$bluetoothGPSWifi = $this->input->post('bluetoothGPSWifi');
				$GSM = $this->input->post('GSM');
				$deviceMicrophone = $this->input->post('deviceMicrophone');
				$deviceSpeaker = $this->input->post('deviceSpeaker');
				$deviceVibrator = $this->input->post('deviceVibrator');
				$displayNtouchScreen = $this->input->post('displayNtouchScreen');
				$deviceBody = $this->input->post('deviceBody');
				$deviceCondition  = $this->input->post('deviceCondition');

				//update diagnostic result 
				$getCustomerUpgradeInfoByTransID = $this->mcu->getCustomerUpgradeInfoByTransID($transid);
				$CustomerUpgradeId = $getCustomerUpgradeInfoByTransID[0]['id'];

				$condition1 = array("customerupgradeId" => $CustomerUpgradeId);
				$diagnosticData = array(
										"screenCalibration" =>	$screenCalibration,
										"deviceRotation" => $deviceRotation,
										"hardwareButton" => $hardwareButton,
										"cameraCheck" => $cameraCheck,
										"cameraAutofocus" => $cameraAutofocus,
										"biometricId" => $biometricId,
										"bluetoothGPSWifi" => $bluetoothGPSWifi,
										"GSM" => $GSM,
										"deviceMicrophone" => $deviceMicrophone,
										"deviceSpeaker" => $deviceSpeaker,
										"deviceVibrator" => $deviceVibrator
										);
				$updateRes = $this->mcu->updatediagnosticresult($diagnosticData,$condition1);

				//update physicaldeclaration result
				if($updateRes > 0)
				{
					$getdiagnosticresultId = $this->mcu->getdiagnosticresultId($CustomerUpgradeId);
					$diagnosticresultId = $getdiagnosticresultId[0]['id'];

					$phydecData = $this->mcu->getphysicaldeclarationdata($CustomerUpgradeId,$diagnosticresultId);
					$grvValue = $phydecData[0]['deviceGRVvalue'];

					$deviceFinalvalue = 0;
					if($grvValue > $devicePrice){
						$deviceFinalvalue = $grvValue;
					}
					else{
						$deviceFinalvalue = $devicePrice;
					}

					$condition2 = array("customerupgradeId" => $CustomerUpgradeId, "diagnosticResultId" => $diagnosticresultId);
					$physicaldeclarationData = array(
										"displayNtouchScreen" => $displayNtouchScreen,
										"deviceBody" => $deviceBody,
										"deviceCondition" => $deviceCondition,
										"appDeviceValue" => number_format($devicePrice),
										"deviceFinalvalue" => number_format($deviceFinalvalue),
										"appGrading" => $grade
										);

					$updateRes2 = $this->mcu->updatephysicaldeclaration($physicaldeclarationData,$condition2);
					if($updateRes2 > 0){
						//update customerupgrade status to completed
						$condition3 = array("id" => $CustomerUpgradeId);
						$data = array("status" => "Completed");
						$this->mcu->updatecustomerupgrade($data,$condition3);
					}
					
					$output = array("status" => "Success", "msg" => "Diagnostic process completed", 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$output = array("status" => "Error", "msg" => "Something went wrong while updating data", 'timeStamp' => date('Y-m-d H:i:s'));
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
				echo json_encode($output, JSON_UNESCAPED_UNICODE);
			}
		}
    }

	public function recalculateDeviceCondition()
	{
        $input = $this->input->post();
		$apiName = 'recalculateDeviceCondition'; 
		$this->form_validation->set_rules("userName", "User Name", "required");
		$this->form_validation->set_rules("apiKey", "apiKey", "required");
		
		//deviceBody:dvbody,displayNtouchScreen:displayntsc,bathealthchck:bathealthchck,phonelock:phonelock,trxid:trxid,userName:api_username,apiKey:api_key
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');
			$deviceBody = $this->input->post('deviceBody');
			$displayNtouchScreen = $this->input->post('displayNtouchScreen');
			$bathealthchck = $this->input->post('bathealthchck');
			$phonelock = $this->input->post('phonelock');
			$trxid = $this->input->post('trxid');
			$cuid = $this->input->post('cuid');
			
			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			if($isAuthenticate){
				if(!empty($trxid)){		
						
					$cudata = $this->mcu->getcustupdata($cuid,$trxid);
					$token = $cudata[0]['token'];
					$dataArr = array("diagnosticResult"=> 
								array("WIFI-01"=>($cudata[0]['WIFI-01'] == 1? true:false), 
										"BLTH-01"=>($cudata[0]['BLTH-01'] == 1? true:false), 
										"GPS-01" =>($cudata[0]['GPS-01'] == 1? true:false), 
										"GSM-01" =>($cudata[0]['GSM-01'] == 1? true:false),
										"VBR-01" =>($cudata[0]['VBR-01'] == 1? true:false), 
										"ARTN-01"=>($cudata[0]['ARTN-01'] == 1? true:false), 
										"PROX-01"=>($cudata[0]['PROX-01'] == 1? true:false), 
										"FPSC-01"=>($cudata[0]['FPSC-01'] == 1? true:false), 
										"SCRN-01"=>($cudata[0]['SCRN-01'] == 1? true:false), 
										"CMRFB-01"=>($cudata[0]['CMRFB-01'] == 1? true:false), 
										"DBTN-01"=>($cudata[0]['DBTN-01'] == 1? true:false), 
										"SPKR-01"=>($cudata[0]['SPKR-01'] == 1? true:false), 
										"MPHN-01"=>($cudata[0]['MPHN-01'] == 1? true:false), 
										"SGLS-01"=>$displayNtouchScreen, 
										"DBDY-01"=>$deviceBody, 
										"BATH-01"=>$bathealthchck, 
										"PHNL-01"=>$phonelock
									)
								);

					$curl = curl_init();
					curl_setopt_array($curl, array(
						CURLOPT_URL => BBTI_URL.'api/v1/integration/diagnostic/diagnostic-result/recalculate',
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => '',
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 0,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => 'POST',
						CURLOPT_POSTFIELDS => json_encode($dataArr),
						CURLOPT_HTTPHEADER => array(
						'Authorization: Bearer '.$token.'',
						'Content-Type: application/json'
						),
					));
					
					$response = curl_exec($curl);
					
					curl_close($curl);
					echo $response;
				}
				else{
					$output = array("status" => "Error", "msg" => "Invalid transaction ID", 'timeStamp' => date('Y-m-d H:i:s'));
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

	public function canceldeviceUpgrade()
	{
		$input = $this->input->post();
		$apiName = 'cancel device upgrade'; 
		
		$cuid = $this->input->post('cuid');
		$trxid = $this->input->post('transId');

		if(!empty($cuid) && !empty($trxid))
		{
			$cData = $this->mcu->getCustomerUpgradeData($cuid,$trxid);
			if($cData){

				$token = $cData[0]['token'];
				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => BBTI_URL.'api/v1/integration/diagnostic/cancel',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'PATCH',
				CURLOPT_HTTPHEADER => array(
					'Authorization: Bearer '.$token.''
				),
				));

				$response = curl_exec($curl);
				$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				$dcResponse = json_decode($response);
				//print_r($dcResponse); exit();
				if(in_array($httpcode, array(200,201)) && $dcResponse->data->cancelStatus == 1){
					$condition = array("id" => $cuid, "transID" => $trxid);
					$data = array("isUpgradeCancel" => 1);
					$this->mcu->updatecustomerupgrade($data,$condition);

					$output = array("status" => "Success", "msg" => "Device upgrade has been cancelled", 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				else{
					$errmsg = $dcResponse->message->EN;
					$output = array("status" => "Error", "msg" => $errmsg, 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}				
			}
			else{
				$output = array("status" => "Error", "msg" => "No diagnostic record found", 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
		else{
			$output = array("status" => "Error", "msg" => "Customer ID & Transaction ID cannot be empty", 'timeStamp' => date('Y-m-d H:i:s'));
			$this->LogManager->logApi($apiName, $input, $output);
			echo json_encode($output);
		}
	}

	public function customerConfirmUpgrade()
	{
		$input = $this->input->post();
		$apiName = 'Confirm Upgrade'; 
		
		$cuid = $this->input->post('cuid');
		$email = $this->input->post('cusEmail');
		$trxid = $this->input->post('transId');

		if(!empty($cuid) && !empty($trxid))
		{
			$cData = $this->mcu->getCustomerUpgradeData($cuid,$trxid);
			
			if($cData){
				$token = $cData[0]['token'];
				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => BBTI_URL.'api/v1/integration/diagnostic/complete',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'PATCH',
				CURLOPT_HTTPHEADER => array(
					'Authorization: Bearer '.$token.''
				),
				));

				$response = curl_exec($curl);
				$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				$dcResponse = json_decode($response);
				//print_r($dcResponse); exit();
				if(in_array($httpcode, array(200,201)) && $dcResponse->data->cancelStatus == 1){

					$condition = array("id" => $cuid, "transID" => $trxid);
					$data = array("isConfirmUpgrade" => 1);
					$this->mcu->updatecustomerupgrade($data,$condition);

					//Send email device value details to customer----------------------------------------
					$customerName = $cData[0]['customerName'];
					$customerId = $cData[0]['customerId'];
					$deviceName = $cData[0]['product'];
					$transactionId = $cData[0]['transID'];
					$programId = $cData[0]['programId'];

					$dvDiagnosticData = $this->mcu->getCustomerDiagnosticRslt($cuid);
		
					$devicePrice = $dvDiagnosticData['data'][0]['appdvVal'];
					$deviceGrade = $dvDiagnosticData['data'][0]['grading'];
					
					$emailConfig = $this->load->config('email');
					$this->load->library('email');
					$from = $this->config->item('smtp_user');
					
					$body = "
					Dear ".$customerName." ,<br></br>

					Thank you for upgrading your device with us. <br></br>
					Your device diagnostic summary as follow:<br>
					
					<b>Transaction ID :</b> ".$transactionId."<br>
					<b>Model :</b> ".$deviceName."<br>
					<b>Device Value :</b> ".$devicePrice."<br></br><br></br>

					

					Regards<br>
					Renew+ Support
					";


					$this->email->set_newline("\r\n");
					$this->email->set_crlf( "\r\n" );
					$this->email->from($from);
					$this->email->to($email);
					$this->email->subject("Renew+ Device Upgrade");
					$this->email->message($body);
					
					if ($this->email->send())
					{
						$output = array("status" => "Success", "programId" => $programId, "msg" => "Device upgrade completed.", "timeStamp" => date('Y-m-d H:i:s'));
						$this->LogManager->logApi($apiName, $input, $output);
						echo json_encode($output);
					} 
					else
					{
						$output = array("status" => "Success", "programId" => $programId, "msg" => "show_error($this->email->print_debugger())","timeStamp" => date('Y-m-d H:i:s'));
						$this->Utility->saveLog($apiName, $input, $output);
						echo json_encode($output);
					}
				}
				else{
					$errmsg = $dcResponse->message->EN;
					$output = array("status" => "Error", "msg" => $errmsg, 'timeStamp' => date('Y-m-d H:i:s'));
					$this->LogManager->logApi($apiName, $input, $output);
					echo json_encode($output);
				}
				
			}
			else{
				$output = array("status" => "Error", "msg" => "No diagnostic record found", 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
		else{
			$output = array("status" => "Error", "msg" => "Customer ID & Transaction ID cannot be empty", 'timeStamp' => date('Y-m-d H:i:s'));
			$this->LogManager->logApi($apiName, $input, $output);
			echo json_encode($output);
		}
	}

	public function retry(){
		$input = $this->input->post();
		$apiName = 'Retry Upgrade'; 
		
		$pcuid = $this->input->post('cuid');
		$ptransid = $this->input->post('trxid');
		$clientId = $this->input->post('clientId');
		$clientSecret = $this->input->post('clientSecret');

		if(!empty($pcuid) && !empty($ptransid))
		{
			$cdata = $this->mcu->getCustomerUpgradeData($pcuid,$ptransid);
			
			if($cdata){			
				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => BBTI_URL.'api/v1/integration/diagnostic/token',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS =>'{
				"clientId": "'.$clientId.'",
				"clientSecret": "'.$clientSecret.'",
				"deviceIdentifier": "'.$cdata[0]['imei'].'"
				}',
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json'
				),
				));

				$response = curl_exec($curl);
				$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				$decoderesponse = json_decode($response);
				if(in_array($httpcode, array(200,201))){

					$condition = array("id" => $cdata[0]['id'], "transID" => $ptransid);
					$data = array("isRetry" => 1);
					$this->mcu->updatecustomerupgrade($data,$condition);

					$accessToken = $decoderesponse->data->accessToken;
					$tokenDecode = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $accessToken)[1]))));
					$trxId = $tokenDecode->clientDiagnosticSessionId;
					$iat = $tokenDecode->iat;
					$exp = $tokenDecode->exp;
					//echo $trxId; exit();
					$newData = array("customerId" => $cdata[0]['customerId'], 
									"programId" => $cdata[0]['programId'], 
									"status" => 'Running', 
									"token" => $accessToken,
									"transID" => $trxId, 
									"iat" => $iat, 
									"exp" => $exp, 
									"imei" => $cdata[0]['imei'], 
									"nationalid" => $cdata[0]['nationalid'],  
									"grv" => $cdata[0]['grv'], 
									"product" => $cdata[0]['product'],
									"createdby" => $cdata[0]['createdby'], 
									"createdDate" => date('Y-m-d h:i:s'));

					$res = $this->mcu->insertCustomerUpgrade($newData);

					//create initial record in tbl diagnosticresult
					$diagnosticresultData = array("customerupgradeId" => $res );
					$res2 = $this->mcu->createRecordintbldiagnosticresult($diagnosticresultData);
					
					//create initial record in tbl paymentschedule
					$physicaldeclarationData = array("customerupgradeId" => $res, "diagnosticResultId" => $res2, "deviceGRVvalue" => $cdata[0]['grv'],);
					$res = $this->mcu->createRecordintblphysicaldeclaration($physicaldeclarationData);

					$output = array("status" => "Success", "recordid" => $res, "transactionid" => $trxId, "token" => $accessToken);
				}
				else{
					$errmsg = $decoderesponse->message->EN;
					$output = array("status" => "Error", "msg" => $errmsg);
				}
			
				echo json_encode($output);
			}
			else{
				$output = array("status" => "Error", "msg" => "No diagnostic record found", 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
		else{
			$output = array("status" => "Error", "msg" => "Customer ID & Transaction ID cannot be empty", 'timeStamp' => date('Y-m-d H:i:s'));
			$this->LogManager->logApi($apiName, $input, $output);
			echo json_encode($output);
		}
	}
	//----------------------------------------------------------------------------------

	//for c2b app===============================================================================================

	public function getdiagnostic()
	{
        $input = $this->input->post();
		$apiName = 'getdiagnostic'; 
		
		$this->form_validation->set_rules("userName", "API Username", "required");
		$this->form_validation->set_rules("apiKey", "API Key", "required");
		$this->form_validation->set_rules("diagnosticResult", "Diagnostic Result", "required");
		$this->form_validation->set_rules("clientDiagnosticSessionId", "Client Diagnostic Session ID", "required");
		$this->form_validation->set_rules("pricing", "Pricing", "required");
		$this->form_validation->set_rules("grading", "Grading", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			if($isAuthenticate)
			{
				$diagnosticResult = $this->input->post('diagnosticResult');
				$trxId = $this->input->post('clientDiagnosticSessionId');
				$dataArr = json_decode($diagnosticResult, true);
				//echo $dataArr['diagnosticResult']['WIFI-01'];
				//print_r($dataArr['diagnosticResult']);
				
				$cuif = $this->mcu->getCustomerUpgradeInfoByTransID($trxId);
				$cuid = $cuif[0]['id'];

				$cond = array("cuid" => $cuid);
				$dgDt = array("WIFI-01" => ($dataArr['diagnosticResult']['WIFI-01'] == 1? 1:0),
						"BLTH-01" => ($dataArr['diagnosticResult']['BLTH-01'] == 1? 0:1),
						"GPS-01" => ($dataArr['diagnosticResult']['GPS-01'] == 1? 0:1),
						"GSM-01" => ($dataArr['diagnosticResult']['GSM-01'] == 1? 0:1),
						"VBR-01" => ($dataArr['diagnosticResult']['VBR-01'] == 1? 0:1),
						"ARTN-01" => ($dataArr['diagnosticResult']['ARTN-01'] == 1? 0:1),
						"PROX-01" => ($dataArr['diagnosticResult']['PROX-01'] == 1? 0:1),
						"FPSC-01" => ($dataArr['diagnosticResult']['FPSC-01'] == 1? 0:1),
						"SCRN-01" => ($dataArr['diagnosticResult']['SCRN-01'] == 1? 0:1),
						"CMRFB-01" => ($dataArr['diagnosticResult']['CMRFB-01'] == 1? 0:1),
						"DBTN-01" => ($dataArr['diagnosticResult']['DBTN-01'] == 1? 0:1),
						"SPKR-01" => ($dataArr['diagnosticResult']['SPKR-01'] == 1? 0:1),
						"MPHN-01" => ($dataArr['diagnosticResult']['MPHN-01'] == 1? 0:1),
						"AFB-01" => ($dataArr['diagnosticResult']['AFB-01'] == 1? 0:1),
						"jsondata" => $diagnosticResult
					);
				$res = $this->mcu->updatediagnosticresult($dgDt,$cond);
				if($res > 0){		
					$getdiagnosticresultId = $this->mcu->getdiagnosticresultId($cuid);
					$dgresId = $getdiagnosticresultId[0]['id'];					
					$phydecData = $this->mcu->getphysicaldeclarationdata($cuid,$dgresId);
					$grv = $phydecData[0]['deviceGRVvalue'];

					$dvfinalVal = 0;
					if($grv > $this->input->post('pricing')){
						$dvfinalVal = $grv;
					}
					else{
						$dvfinalVal = $this->input->post('pricing');
					}

					$cond2 = array("cuid" => $cuid, "dgresId" => $dgresId);
					$phydecDt = array(
									"displayntsc" => $dataArr['diagnosticResult']['SGLS-01'],
									"dvbody" => $dataArr['diagnosticResult']['DBDY-01'],
									"bathealthchck" => $dataArr['diagnosticResult']['BATH-01'],
									"phonelock" => $dataArr['diagnosticResult']['PHNL-01'],
									"appdvVal" => number_format($this->input->post('pricing')),
									"dvfinalVal" => number_format($dvfinalVal),
									"grading" => $this->input->post('grading')
								);

					$res2 = $this->mcu->updatephysicaldeclaration($phydecDt,$cond2);
					if($res2 > 0){
						//update customerupgrade status to completed
						$cond3 = array("id" => $cuid);
						$data = array("status" => "Completed");
						$this->mcu->updatecustomerupgrade($data,$cond3);
					}
					
					$output = array("status" => "Success", "msg" => "Diagnostic result successfully received", 'timeStamp' => date('Y-m-d H:i:s'));
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
				$errMsg = strip_tags($arr['Errors']);
				$errMsg = trim(preg_replace('/\s\s+/', ' ', $errMsg));
				$output = array("status" => "Error", "msg" => $errMsg, 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
    }

	public function getdiagnosticInitialize()
	{
        $input = $this->input->post();
		$apiName = 'getdiagnosticInitialize'; 
		
		$this->form_validation->set_rules("userName", "API Username", "required");
		$this->form_validation->set_rules("apiKey", "API Key", "required");
		$this->form_validation->set_rules("diagnosticData", "Diagnostic Result", "required");
		$this->form_validation->set_rules("clientDiagnosticSessionId", "Client Diagnostic Session ID", "required");
		if ($this->form_validation->run()) 
		{
			$userName = $this->input->post('userName');
			$apiKey = $this->input->post('apiKey');

			$isAuthenticate = $this->ModelUtility->checkApiAuthentication($userName, $apiKey);
			if($isAuthenticate)
			{
				$diagnosticResult = $this->input->post('diagnosticResult');
				$dataArr = json_decode($diagnosticResult, true);
				//echo $dataArr['diagnosticResult']['WIFI-01'];
				print_r($dataArr['diagnosticResult']['SGLS-01']);
				//$output = array("status" => "Success", 'timeStamp' => date('Y-m-d H:i:s'));
				//echo json_encode($output);
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
				$errMsg = strip_tags($arr['Errors']);
				$errMsg = trim(preg_replace('/\s\s+/', ' ', $errMsg));
				$output = array("status" => "Error", "msg" => $errMsg, 'timeStamp' => date('Y-m-d H:i:s'));
				$this->LogManager->logApi($apiName, $input, $output);
				echo json_encode($output);
			}
		}
    }
}
