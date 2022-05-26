<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	function getOCBToken()
	{
		if(file_exists("./application/files/ocbToken.json"))
		{
			$ocbTokenData = file_get_contents('./application/files/ocbToken.json');
			if($ocbTokenData)
			{
				$ocbTokenData = json_decode($ocbTokenData, true);
				if($ocbTokenData['expiry'] > date('Y-m-d H:i:s'))
				{
					return $ocbTokenData['access_token'];
				}
				else
				{
					$d = generateOCBToken();
					if($d['status'] == 'Success'){
						writeOCBToken($d['msg'], $d['expiry']);
						return $d['msg'];
					}
					else
					{
						
					}
				}	
			}
			else
			{
				$d = generateOCBToken();
				if($d['status'] == 'Success')
				{
					writeOCBToken($d['msg'], $d['expiry']);
					return $d['msg'];
				}
				else
				{
					
				}
            }
		}
		else
		{
			$d = generateOCBToken();
			if($d['status'] == 'Success')
			{
				writeOCBToken($d['msg'], $d['expiry']);
				return $d['msg'];
			}
			else
			{
				
			}
		}
	}

	function createApplicationOCB($data)
	{
		$url = OCBLINK."api/CompAsia/CreateNewApp";
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
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
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
		
	function writeOCBToken($token, $expiry = 43200)
	{
		//echo "hello";
		$data = json_encode(array("access_token" => $token, "expiry" => date('Y-m-d H:i:s', time() + $expiry)));
		if(file_exists("./application/files/ocbToken.json"))
			unlink('./application/files/ocbToken.json');
		write_file('./application/files/ocbToken.json', $data, 'x+');
	}

	function writeFile($fileName, $data)
	{
		if(file_exists($fileName))
			unlink($fileName);
		write_file($fileName, $data, 'x+');
		
	}	

	// Generate OCB Token when token file/ token not exist or token expire
	function generateOCBToken()
	{
		$url = OCBLINK."token";
	    // append the header putting the secret key and hash
		$request_headers = array('Content-Type:application/x-www-form-urlencoded');
		$data = "username=".OCBUSERNAME."&password=".OCBPASSWORD."&grant_type=password";
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
		print "Error: " . curl_error($ch);
		}
		else
		{
			// Show me the result
		
			$transaction = json_decode($data, TRUE);
			if(isset($transaction['access_token']))
			{
				return array("status" => "Success", "msg" => $transaction['token_type'].' '.$transaction['access_token'], "expiry" => $transaction['expires_in']);
			}
			else
			{
				return array("status" => "Error", "msg" => $data);
			}
	        curl_close($ch);
        	//var_dump($transaction['data']);
      	}
	}

	// 
	function cancelApplicationOCB($data)
	{
		$url = OCBLINK."api/CompAsia/CancelApp";

		$token =  getOCBToken();
		$request_headers = array('Content-Type:application/json', 'Authorization:'.$token);
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
			print "Error: " . curl_error($ch);
		}
		else
		{
			// Show me the result
	
			$transaction = json_decode($data, TRUE);
			if(isset($transaction['status']))
			{
				if($transaction['status'] == '200')
				{
					return array("status" => "Success", "msg" => $transaction['message']);
				}
				else
				{
					return array("status" => "Error", "msg" => $transaction['message']);
				}	
			}
			else
			{
				return array("status" => "Error", "msg" => $data);
			}
			curl_close($ch);
			//var_dump($transaction['data']);
		}
	}

	// 
	function getTelcoScoreOCB($data)
	{
		$url = OCBLINK."api/CompAsia/GetScoreByOTP";

		$token =  getOCBToken();
        $request_headers = array('Content-Type:application/json', 'Authorization:'.$token);
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
			print "Error: " . curl_error($ch);
		}
		else
		{
			// Show me the result
	
			$transaction = json_decode($data, TRUE);
			if(isset($transaction['status']))
			{
				if($transaction['status'] == '200')
				{
					return array("status" => "Success", "msg" => $transaction['message']);
				}
				else
				{
					return array("status" => "Error", "msg" => $transaction['message']);
				}	
			}
			else
			{
				return array("status" => "Error", "msg" => $data);
			}
			curl_close($ch);
			//var_dump($transaction['data']);
		}
	}

	// 
	function requestOTPOCB($data)
	{
		$url = OCBLINK."api/CompAsia/RequestOTP";

		$token =  getOCBToken();
        $request_headers = array('Content-Type:application/json', 'Authorization:'.$token);
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
			print "Error: " . curl_error($ch);
		}
		else
		{
		// Show me the result
	
			$transaction = json_decode($data, TRUE);
			if(isset($transaction['status']))
			{
				if($transaction['status'] == '200')
				{
					return array("status" => "Success", "msg" => $transaction['message']);
				}
				else
				{
					return array("status" => "Error", "msg" => $transaction['message']);
				}	
			}
			else
			{
				return array("status" => "Error", "msg" => $data);
			}
			curl_close($ch);
			//var_dump($transaction['data']);
		}
	}

	// 
	function getDictionaryList()
	{
		$fileName = './application/files/ocbDictionaryData.json';
		if(file_exists($fileName))
		{
			return array("status" => "Success", "msg" =>json_decode(file_get_contents($fileName),true));
		}
		else
		{
		
			$url = OCBLINK."api/MasterData/GetDictionaryList";
			//append the header putting the secret key and hash
			$token =  getOCBToken();
			$request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			$data = curl_exec($ch);
			if (curl_errno($ch))
			{
				print "Error: " . curl_error($ch);
			}
			else
			{
				// Show me the result
		
				$transaction = json_decode($data, TRUE);
				if(isset($transaction['responseMessage']))
				{
					if($transaction['responseMessage'] == 'Successful')
					{
						writeFile($fileName, json_encode(str_replace('\\','',$transaction['responseData']),JSON_UNESCAPED_UNICODE));
						return array("status" => "Success", "msg" => str_replace('\\','',$transaction['responseData']));
					}
					else
					{
						return array("status" => "Error", "msg" => $data);
					}	
				}
				else
				{
					return array("status" => "Error", "msg" => $data);
				}
				curl_close($ch);
				//var_dump($transaction['data']);
			}
		}
	}

	// Get SubscriptionStatus
	function getSubscriptionStatusOCB($appId)
	{
		$url = OCBLINK."api/CompAsia/GetAppStatus?listAppId=".$appId;
	    //append the header putting the secret key and hash
		$token =  getOCBToken();
        $request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data = curl_exec($ch);
		if (curl_errno($ch))
		{
			print "Error: " . curl_error($ch);
		}
		else
		{
			// Show me the result
	
			$transaction = json_decode($data, TRUE);
			if(isset($transaction['status']))
			{
				if($transaction['status'] == '200')
				{
					return array("status" => "Success", "msg" => $transaction['data']);
				}
				else
				{
					return array("status" => "Error", "msg" => $data);
				}	
			}
			else
			{
				return array("status" => "Error", "msg" => $data);
			}
			curl_close($ch);
		}
	}

	function getOCBBank()
	{
		$fileName = './application/files/ocbBankData.json';
		if(file_exists($fileName))
		{
			return array("status" => "Success", "msg" =>json_decode(file_get_contents($fileName),true));
		}
		else
		{
			$url = OCBLINK."api/MasterData/GetAllCitad";
	      	// append the header putting the secret key and hash
			$token =  getOCBToken();
            $request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	        $data = curl_exec($ch);
	        if (curl_errno($ch))
        	{
	        	print "Error: " . curl_error($ch);
        	}
	        else
        	{
		        // Show me the result
		
        		$transaction = json_decode($data, TRUE);
				if(isset($transaction['responseMessage']))
				{
					if($transaction['responseMessage'] == 'Successful')
					{
						writeFile($fileName, json_encode(str_replace('\\','',$transaction['responseData']),JSON_UNESCAPED_UNICODE));
						return array("status" => "Success", "msg" => str_replace('\\','',$transaction['responseData']));
					}
					else
					{
						return array("status" => "Error", "msg" => $data);
					}	
				}
				else
				{
					return array("status" => "Error", "msg" => $data);
				}
		        curl_close($ch);
        		//var_dump($transaction['data']);
	      	}
		}
	}

	function getAllProvince()
	{
		$fileName = './application/files/ocbProvinceData.json';
		if(file_exists($fileName)){
			return array("status" => "Success", "msg" =>json_decode(file_get_contents($fileName),true));
		}
		else
		{
			$url = OCBLINK."api/MasterData/GetAllProvince";
	      	// append the header putting the secret key and hash
			$token =  getOCBToken();
            $request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	        $data = curl_exec($ch);
	        if (curl_errno($ch))
        	{
	        	print "Error: " . curl_error($ch);
        	}
	        else
        	{
        		$transaction = json_decode($data, TRUE);
				if(isset($transaction['responseMessage']))
				{
					if($transaction['responseMessage'] == 'Successful')
					{
						$myData = json_decode($transaction['responseData'],true);
						$restrictId = array();
						//$restrictId = array(1050,1100,1150,1200,1250,1300,1350,1400,1450,1500,1550,1600,1650,1700,1750,1800,1850,1900,1950,2050,2100,2150,2200,2250,3100,4000,4050,4100,4150,4200,4250,4350,4400,4500,4650,4700,4750,4800,4850,6666,7100,7750,7777,7950,8888,8899,9996,9997,9999);
						$newData = array();
						for($i=0;$i<sizeof($myData);$i++){
							if(!in_array($myData[$i]['ProvinceId'], $restrictId))
								$newData[] = $myData[$i];
						}
						$newData =  json_encode($newData, JSON_UNESCAPED_UNICODE);
						//writeFile($fileName, json_encode(str_replace('\\','',$transaction['responseData']),JSON_UNESCAPED_UNICODE));
						writeFile($fileName, json_encode(str_replace('\\','',$newData),JSON_UNESCAPED_UNICODE));
						return array("status" => "Success", "msg" => str_replace('\\','',$transaction['responseData']));
					}
					else
					{
						return array("status" => "Error", "msg" => $data);
					}	
				}
				else
				{
					return array("status" => "Error", "msg" => $data);
				}
		        curl_close($ch);
        		//var_dump($transaction['data']);
	      	}
		}
	}

	function getAllCity()
	{
		$fileName = './application/files/ocbCityData.json';
		if(file_exists($fileName)){
			return array("status" => "Success", "msg" =>json_decode(file_get_contents($fileName),true));
		}
		else
		{

			$url = OCBLINK."api/MasterData/GetAllCity";
			// append the header putting the secret key and hash
			$token =  getOCBToken();
			$request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	        $data = curl_exec($ch);
	        if (curl_errno($ch))
        	{
	        	print "Error: " . curl_error($ch);
        	}
	        else
        	{
		        // Show me the result
		
        		$transaction = json_decode($data, TRUE);
				if(isset($transaction['responseMessage']))
				{
					if($transaction['responseMessage'] == 'Successful')
					{
						/*$myData = json_decode($transaction['responseData'], true);	
						echo 	
						print_r($myData);
						$newData = array();
						$restrictId = array(1,3,4,5,10,11,12,13,14,16,20,21,23,24,25,26,29,30,33,34,35,36,37,38,39,40,41,47,50,51,52,53,54,56,61,62,63,64,65,66,73,75,77,80,81,82,82,84,87,88,89,109,110,111,112,115,117,118,119,120,122,123,125,127,129,130,132,137,138,139,143,144,148,149,150,151,153,154,155,156,157,158,159,161,164,165,166,167,168,169,170,171,172,173,175,176,178,179,180,181,182,183,184,185,186,187,190,194,195,197,199,200,202,203,204,206,207,217,218,220,221,222,223,224,227,229,233,234,235,236,237,238,240,249,250,251,254,255,257,258,260,261,263,264,265,266,267,268,269,270,271,272,273,276,279,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,304,305,306,308,309,310,311,312,314,316,317,318,325,328,329,330,331,332,333,334,335,336,337,338,339,341,342,343,345,346,349,350,351,353,354,355,356,357,358,359,360,362,363,365,366,367,368,369,371,372,373,374,375,377,378,379,380,381,382,383,384,385,387,388,389,391,394,396,397,398,399,400,403,404,405,406,408,409,411,413,414,415,417,418,419,421,426,427,428,431,431,433,434,435,437,438,440,442,443,456,457,458,459,460,461,462,463,464,465,466,467,468,469,470,473,474,475,476,477,480,481,482,483,484,486,487,488,489,490,491,492,493,494,495,496,498,499,502,503,504,505,507,518,519,523,528,529,530,534,535,536,537,539,540,541,542,543,544,546,547,548,550,551,553,554,559,561,562,563,565,573,574,575,576,578,579,580,581,582,583,584,586,588,590,593,594,597,600,601,602,603,605,606,607,608,609,610,613,614,615,616,618,619,622,625,626,627,628,629,630,631,633,636,637,638,639,640,641,644,646,651,652,654,655,656,659,661,663,664,665,666,667,668,670,671,672,673,674,675,676,677,678,679,680,681,682,683,684,686,687,688,689,690,691,692,694,696,697,699,700,701,702,706,707,708,709,710,711,712,713,714,719,730,731,732,736,962,963,964,966,967,968,969,972,973,975,976,978,980,981,983,984,985,986,989,990,991,993,994,995,996,1077,1078,1309,1310,1311,1312,1313,1314,1315,1316,1317,1319,1321);
						for($i=0; sizeof($myData);$i++){
							if(!in_array($myData[$i]['CityId'], $restrictId))
								$newData[] = $myData[$i];
						}
						$newData = json_encode($newData,JSON_UNESCAPED_UNICODE);*/
						writeFile($fileName, json_encode(str_replace('\\','',$transaction['responseData']),JSON_UNESCAPED_UNICODE));
						//writeFile($fileName, json_encode(str_replace('\\','',$newData),JSON_UNESCAPED_UNICODE));
						return array("status" => "Success", "msg" => str_replace('\\','',$transaction['responseData']));
					}
					else
					{
						return array("status" => "Error", "msg" => $data);
					}	
				}
				else
				{
					return array("status" => "Error", "msg" => $data);
				}
		        curl_close($ch);
        		//var_dump($transaction['data']);
	      	}
		}
	}

	function getAllWard()
	{
		$fileName = './application/files/ocbWardData.json';
		if(file_exists($fileName)){
			return array("status" => "Success", "msg" =>json_decode(file_get_contents($fileName),true));
		}
		else
		{
			$url = OCBLINK."api/MasterData/GetAllWard";
			// append the header putting the secret key and hash
			$token =  getOCBToken();
			$request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	        $data = curl_exec($ch);
	        if (curl_errno($ch))
        	{
	        	print "Error: " . curl_error($ch);
        	}
	        else
        	{
		        // Show me the result
        		$transaction = json_decode($data, TRUE);
				if(isset($transaction['responseMessage']))
				{
					if($transaction['responseMessage'] == 'Successful')
					{
						writeFile($fileName, json_encode(str_replace('\\','',$transaction['responseData']),JSON_UNESCAPED_UNICODE));
						return array("status" => "Success", "msg" => str_replace('\\','',$transaction['responseData']));
					}
					else
					{
						return array("status" => "Error", "msg" => $data);
					}	
				}
				else
				{
					return array("status" => "Error", "msg" => $data);
				}
		        curl_close($ch);
        		//var_dump($transaction['data']);
	      	}
		}
	}

	// Get Employee from 
	function getEmployees()
	{
		$url = OCBLINK."api/CompAsia/GetEmployees";
	    // append the header putting the secret key and hash
		$token =  getOCBToken();
		$request_headers = array('Content-Type:application/x-www-form-urlencoded', 'Authorization:'.$token);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data = curl_exec($ch);
		if (curl_errno($ch))
		{
			print "Error: " . curl_error($ch);
		}
		else
		{
			// Show me the result
		
        	$transaction = json_decode($data, TRUE);
			if(isset($transaction['status']))
			{
				if($transaction['status'] == 200)
				{
					return array("status" => "Success", "msg" => $transaction['data']);
				}
				else
				{
					return array("status" => "Error", "msg" => $data);
				}
			}
			else
			{
				return array("status" => "Error", "msg" => $data);
			}
	        curl_close($ch);
      	}
	}

