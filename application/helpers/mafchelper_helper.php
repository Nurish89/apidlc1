<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	function mafcSendApplication($data){
		$url = MAFCLINK;
		$data = json_encode(json_decode($data,true)['msgName'] = 'submitApplication');
	    // append the header putting the secret key and hash
		$request_headers = array('Content-Type:application/json');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	        $data = curl_exec($ch);
		if (curl_errno($ch)){	        
			return array("status" => "Error", "msg" => curl_error($ch));
		}else{
			$transaction = json_decode($data, TRUE);
			if(isset($transaction['status'])){
				if($transaction['status'] == 201)
					return array("status" => "Success", "msg" => $transaction['data']['appId'], "contractId" => $transaction['data']['contractId']);
				else if ($transaction['status'] == 100){	
					return array("status" => "Error500", "msg" => $transaction['msg'], "fieldName"=>'');
				}else {
					return array("status" => "Error500", "msg" => $transaction['msg'], "fieldName"=>'');
				}
			}
			curl_close($ch);
		}
	}
		
	// Cancel MAFC Application
	function mafcCancelApplication($data){
		$url = MAFCLINK;
		$data = json_encode(json_decode($data,true)['msgName'] = 'cancelApplication');
		$request_headers = array('Content-Type:application/json');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data = curl_exec($ch);
		if (curl_errno($ch)){
	        	print "Error: " . curl_error($ch);
		}else{
			// Show me the result
			$transaction = json_decode($data, TRUE);
			if(isset($transaction['status'])){
				if($transaction['status'] == '201'){
					return array("status" => "Success", "msg" => $transaction['msg']);
				}else{
					return array("status" => "Error", "msg" => $transaction['msg']);
				}	
			}else{
				return array("status" => "Error", "msg" => $data);
			}
			curl_close($ch);
		}
	}

	function mafcUploadDocument($data){
		$input = $data;
		unset($input['base64']);
		$CI = get_instance();
		$CI->load->model('ModelUtility');
		$url = MAFCLINK;
		if(is_array($data)){
			$data = json_encode($data);
		}else{	
			
		}	
		$request_headers = array('Content-Type:application/json');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data = curl_exec($ch);
		if (curl_errno($ch)){
	        	print "Error: " . curl_error($ch);
		}else{
			// Show me the result
			$transaction = json_decode($data, TRUE);
			$CI -> ModelUtility -> saveLog('updateImageMAFC', $input, $transaction);
			if(isset($transaction['returnCode'])){
				if($transaction['returnCode'] == '0'){
					return array("status" => "Success", "msg" => $transaction['message']);
				}else{
					return array("status" => "Error", "msg" => $transaction['message']);
				}	
			}else{
				return array("status" => "Error", "msg" => $data);
			}
			curl_close($ch);
		}
	}

	function mafcRelationCodeData(){
		return json_decode('[{"CODE": "WH","LABEL": "Vợ chồng"},{"CODE": "PR","LABEL": "Cha mẹ"},{"CODE": "CD","LABEL": "Con cái"},{"CODE": "SI","LABEL": "Anh chị em ruột"},{"CODE": "GP","LABEL": "Ông bà"},{"CODE": "CO","LABEL": "Anh chị em họ"},{"CODE": "AU","LABEL": "cô, dì"},{"CODE": "UN","LABEL": "chú, bác"},{"CODE": "PN","LABEL": "Người yêu"},{"CODE": "F","LABEL": "Bạn bè"}]', true);
	}

	// 
	function mafcMasterData($msgName){
		$fileName = './application/files/mafcMasterData'.$msgName.'.json';
		if(file_exists($fileName)){
			return array("status" => "Success", "msg" =>json_decode(file_get_contents($fileName),true));
		}else{
			$username = 'masterdatamci';
			$password = 'mafc32412^&%^$';
			$url = MAFCMASTERDATALINK;
			// append the header putting the secret key and hash
			$data = json_encode(array("msgName" => $msgName));
			$request_headers = array('Content-Type:application/x-www-form-urlencoded');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 600);
			curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);	
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			$data = curl_exec($ch);
			if (curl_errno($ch)){
				print "Error: " . curl_error($ch);
			}
			else{
				// Show me the result
				$transaction = json_decode($data, TRUE);
				if(isset($transaction['success'])){
					if($transaction['success'] == 'true'){
						writeFile($fileName, json_encode(str_replace('\\','',$transaction['data']),JSON_UNESCAPED_UNICODE));
						return array("status" => "Success", "msg" => str_replace('\\','',$transaction['data']));
					}else{
						return array("status" => "Error", "msg" => $data);
					}	
				}else{
					return array("status" => "Error", "msg" => $data);
				}
				curl_close($ch);
				//var_dump($transaction['data']);
			}
		}
	}