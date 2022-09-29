<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class diagnostic extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Modeldiagnostic');
		$this->load->library('form_validation');
		$this->load->helper('ocbhelper');
		$this->load->helper('utility');
		$this->load->library('LogManager');

		$this->LogManager = new LogManager();
	}

	public function index()
	{
		$data = $this->api_model->fetch_all();
		echo 'Welcome to CA APIs, Please pass API Sepcific URL to submit a request.';
	}

	public function deviceDiagnostic(){
		$data['nationalid'] = $this->input->get('id');
		$data['imei'] = $this->input->get('imei');
		$this->load->view('deviceDiagnostic', $data);
	} 

	public function getTransactioninfo()
	{
		$input = $this->input->post();
		$apiName = 'getTransactioninfo'; 

		$nationalid = $this->input->post('nationalid');
		$imei = $this->input->post('imei');	

		$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $gentransid= substr(str_shuffle($permitted_chars), 0, 10);

		$newSession = $this->Modeldiagnostic->addnewsession(array("transactionId" => $gentransid, "nationalid" => $nationalid, "imei" => $imei, "status" => "Running", "created_date" => date('Y-m-d h:i:s')));
		if($newSession)
		{
			$getSession = $this->Modeldiagnostic->checkDiagnosticTransaction($newSession);
			$output = array("status" => "Success", "cData" => $getSession, 'timeStamp' => date('Y-m-d H:i:s'));
			$this->LogManager->logApi($apiName, $input, $output);
			echo json_encode($output);
		}
		else
		{
			$output = array("status" => "Error", "msg" => "No record found", 'timeStamp' => date('Y-m-d H:i:s'));
			$this->LogManager->logApi($apiName, $input, $output);
			echo json_encode($output);
		}	
	}

	public function calculateDeviceValue()
	{
		$nID = $this->input->post('nationalid');
		$imei = $this->input->post('imei');
		$screenCalibration = $this->input->post('a1');
		$deviceRotation = $this->input->post('b1');
		$hardwareButton = $this->input->post('c1');
		$cameraCheck = $this->input->post('d1');
		$cameraAutofocus = $this->input->post('e1');
		$biometricId = $this->input->post('f1');
		$bluetoothGPSWifi = $this->input->post('g1');
		$GSM = $this->input->post('h1');
		$deviceMicrophone = $this->input->post('i1');
		$deviceSpeaker = $this->input->post('j1');
		$deviceVibrator = $this->input->post('k1');

		$displayNtouchScreen = $this->input->post('displayNtouchScreen');
		$deviceBody = $this->input->post('deviceBody');
		$deviceCondition = $this->input->post('deviceCondition');

		$deviceConditionList = implode(',', $deviceCondition);

		$a1Val = ($screenCalibration == 1)? 720000 : 0;
		$b1Val = ($deviceRotation == 1)? 700000 : 0;
		$c1Val = ($hardwareButton == 1)? 720000 : 0;
		$d1Val = ($cameraCheck == 1)? 700000 : 0;
		$e1Val = ($cameraAutofocus == 1)? 750000 : 0;
		$f1Val = ($biometricId == 1)? 770000 : 0;
		$g1Val = ($bluetoothGPSWifi == 1)? 750000 : 0;
		$h1Val = ($GSM == 1)? 700000 : 0;
		$i1Val = ($deviceMicrophone == 1)? 702000 : 0;
		$j1Val = ($deviceSpeaker == 1)? 720000 : 0;
		$k1Val = ($deviceVibrator == 1)? 840000 : 0;

		$factor1 = $a1Val + $b1Val + $c1Val + $d1Val + $e1Val + $f1Val + $g1Val + $h1Val + $i1Val + $j1Val + $k1Val;
		
		if($displayNtouchScreen == "Flawless")
			$displayNtouchScreenVal = 300000;
		if($displayNtouchScreen == "Minor Scratches")
			$displayNtouchScreenVal = 60000;
		if($displayNtouchScreen == "Heavily Scratched")
			$displayNtouchScreenVal = 30000;
		if($displayNtouchScreen == "Dented")
			$displayNtouchScreenVal = 23000;
		if($displayNtouchScreen == "Cracked")
			$displayNtouchScreenVal = 10000;
		if ($displayNtouchScreen == "Not working (Loose LCD)")
			$displayNtouchScreenVal = 0;

		if($deviceBody == "Flawless")
			$deviceBodyVal = 300000;
		if($deviceBody == "Minor Scratches")
			$deviceBodyVal = 60000;
		if($deviceBody == "Heavily Scratched")
			$deviceBodyVal = 30000;
		if($deviceBody == "Dented")
			$deviceBodyVal = 23000;
		if($deviceBody == "Cracked")
			$deviceBodyVal = 10000;
	
		$found = 0;
		$y = array('Bloated Battery','Liquid Damage','Ghost Touch','Sim Card Tray Broken','Home & Power Button','None of these');	
		foreach($deviceCondition as $i) {
			if (in_array($i,$y)) {
				$found += 100000;
			} 
		}
		
		$devicePrice = (($factor1 + $displayNtouchScreenVal + $deviceBodyVal) - $found);
		if($devicePrice >= 8672000)
			$grade = 'A';
		if(($devicePrice >= 7000000) && ($devicePrice <= 8672000))
			$grade = 'B';
		if(($devicePrice >= 5000000) && ($devicePrice <= 7000000))
			$grade = 'C';
		if($devicePrice < 5000000)	
			$grade = 'D';

		$getSession = $this->Modeldiagnostic->getDiagnosticTransactionID($nID,$imei);
		$transid = $getSession[0]['transactionId'];
		$sessionregId = $getSession[0]['id'];

		//store into physical_declaration tbl----------------------------------
		$dataArr = array(
			"sessionregid" => $sessionregId,
			"appDeviceValue" => $devicePrice,
			"appGrading" => $grade,
			"displayNtouchScreen" => $displayNtouchScreen,
			"deviceBody" => $deviceBody,
			"deviceCondition" => $deviceConditionList
			);

		$this->Modeldiagnostic->addnewphysicaldeclarationData($dataArr);

		//Post to portal-----------------------------------------------------------
		$dataArr1 = array(
				"transID" => $transid,
				"apiKey" => 'ddf6842084aae5011d0d927ee707bf2d',
				"userName" => 'CA@CRM',
				"devicePrice" => $devicePrice,
				"grade" => $grade,
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
				"deviceVibrator" => $deviceVibrator,
				"displayNtouchScreen" => $displayNtouchScreen,
				"deviceBody" => $deviceBody,
				"deviceCondition" => $deviceConditionList
				);
		
		$curl = curl_init();
		
		curl_setopt_array($curl, array(
			CURLOPT_URL => API_URL.'up/appDiagnosticResult',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $dataArr1,
		));
		
		$response = curl_exec($curl);
		
		curl_close($curl);
		$xxx = json_decode($response,true);
		if($xxx['status'] =='Success'){
			$condition = array("transactionId" => $transid);
			$data = array("status" => "Completed");
			$this->Modeldiagnostic->updatesessionregister($data,$condition);
		}
		else{
			$condition = array("transactionId" => $transid);
			$data = array("status" => "Error");
			$this->Modeldiagnostic->updatesessionregister($data,$condition);
		}
		
						
		$data['result'] = $xxx['status'];		
		$this->load->view('deviceDiagnostic',$data);

	}

	public function recalculatedevicevalue()
	{
		$transid = $this->input->post('transid');
		$rdisplayNtouchScreen = $this->input->post('displayNtouchScreen');
		$rdeviceBody = $this->input->post('deviceBody');
		$rdeviceCondition = $this->input->post('deviceCondition');

		$factor1 = 0;

		$dataX = $this->Modeldiagnostic->getDeviceDiagnosticHistory($transid);
		$id = $dataX[0]['pd_id'];
		$appDeviceValue = $dataX[0]['appDeviceValue'];
		$displayNtouchScreen = $dataX[0]['displayNtouchScreen'];
		$deviceBody = $dataX[0]['deviceBody'];
		
		
		if ($rdisplayNtouchScreen == $displayNtouchScreen){
			$displayNtouchScreenValtoAdd = 0;
			$displayNtouchScreenValtoMinus = 0;
		}
		else{
			if($rdisplayNtouchScreen == "Flawless"){
				$displayNtouchScreenValtoAdd = 100000;
			}
			
			if($rdisplayNtouchScreen == "Minor Scratches"){
				$displayNtouchScreenValtoAdd = 0;
			}
				
			if($rdisplayNtouchScreen == "Heavily Scratched"){
				$displayNtouchScreenValtoAdd = 0;
			}
				
			if($rdisplayNtouchScreen == "Dented"){
				$displayNtouchScreenValtoAdd = 0;
			}
				
			if($rdisplayNtouchScreen == "Cracked"){
				$displayNtouchScreenValtoAdd = 0;
			}
				
			if ($rdisplayNtouchScreen == "Not working (Loose LCD)"){
				$displayNtouchScreenValtoAdd = 0;
			}
			
			if($displayNtouchScreen == "Flawless"){
				$displayNtouchScreenValtoMinus = 0;
			}
			
			if($displayNtouchScreen == "Minor Scratches"){
				$displayNtouchScreenValtoMinus = 200000;
			}
				
			if($displayNtouchScreen == "Heavily Scratched"){
				$displayNtouchScreenValtoMinus = 300000;
			}
				
			if($displayNtouchScreen == "Dented"){
				$displayNtouchScreenValtoMinus = 350000;
			}
				
			if($displayNtouchScreen == "Cracked"){
				$displayNtouchScreenValtoMinus = 400000;
			}
				
			if ($displayNtouchScreen == "Not working (Loose LCD)"){
				$displayNtouchScreenValtoMinus = 1000000;
			}
		}
	
		if($rdeviceBody == $deviceBody) {
			$deviceBodyValtoAdd = 0;
			$deviceBodyValtoMinus = 0;
		}
		else{
			if($rdeviceBody == "Flawless"){
				$deviceBodyValtoAdd = 100000;
			}
				
			if($rdeviceBody == "Minor Scratches"){
				$deviceBodyValtoAdd = 0;
			}
				
			if($rdeviceBody == "Heavily Scratched"){
				$deviceBodyValtoAdd = 0;
			}
				
			if($rdeviceBody == "Dented"){
				$deviceBodyValtoAdd = 0;
			}
				
			if($rdeviceBody == "Cracked"){
				$deviceBodyValtoAdd = 0;
			}

			if($deviceBody == "Flawless"){
				$deviceBodyValtoMinus = 0;
			}
				
			if($deviceBody == "Minor Scratches"){
				$deviceBodyValtoMinus = 100000;
			}
				
			if($deviceBody == "Heavily Scratched"){
				$deviceBodyValtoMinus = 200000;
			}
				
			if($deviceBody == "Dented"){
				$deviceBodyValtoMinus = 300000;
			}
				
			if($deviceBody == "Cracked"){
				$deviceBodyValtoMinus = 800000;
			}
		}
		$found = 0;
		$y = array('Bloated Battery','Liquid Damage','Ghost Touch','Sim Card Tray Broken','Home & Power Button','None of these');	
		foreach($rdeviceCondition as $i) {
			if (in_array($i,$y)) {
				$found += 100000;
			} 
		}

		$dataX = $this->Modeldiagnostic->getDeviceDiagnosticHistory($transid);
		$appDeviceValue = $dataX[0]['appDeviceValue'];

		$newDevicePrice = (($appDeviceValue - $deviceBodyValtoMinus - $displayNtouchScreenValtoMinus - $found) + ($deviceBodyValtoAdd + $displayNtouchScreenValtoAdd));
		
		if($newDevicePrice >= 8672000)
			$grade = 'A';
		if(($newDevicePrice >= 7000000) && ($newDevicePrice <= 8672000))
			$grade = 'B';
		if(($newDevicePrice >= 5000000) && ($newDevicePrice <= 7000000))
			$grade = 'C';
		if($newDevicePrice < 5000000)	
			$grade = 'D';

		//store into physical_declaration tbl----------------------------------
		$condition = array("pd_id" => $id);
		$data = array(
					"appDeviceValue" => $newDevicePrice,
					"appGrading" => $grade,
					"displayNtouchScreen" => $rdisplayNtouchScreen,
					"deviceBody" => $rdeviceBody,
					"deviceCondition" => $rdeviceCondition
					);

		$this->Modeldiagnostic->updatePhysicaldeclarationData($data,$condition);

		$output = array("status" => "Success", "rData" => $data, 'timeStamp' => date('Y-m-d H:i:s'));
		echo json_encode($output);
	}
	
}
