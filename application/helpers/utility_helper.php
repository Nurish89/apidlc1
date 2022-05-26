<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	function validateFormData($userForm, $metaForm)
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
									return array("status" => "Error", "msg"=> "Minimum ".$d['minLength']." characters required ".$d['name'], 'timeStamp' => date('Y-m-d H:i:s'));
								}
								else if($d['type'] == 'text' and strlen(trim($userFormData[$d['name']])) > $d['maxLength'])
								{
									return array("status" => "Error", "msg"=> "Maximum  ".$d['maxLength']." characters limit for ".$d['name'], 'timeStamp' => date('Y-m-d H:i:s'));
								}
							}
						}
						else
						{
							return array("status" => "Error", "msg"=> "requied field ".$d['name']." not to be empty", 'timeStamp' => date('Y-m-d H:i:s'));
						}
					}
					else
					{
						return array("status" => "Error", "msg"=> "requied field ".$d['name']." not found", 'timeStamp' => date('Y-m-d H:i:s'));
					}
				}
			}
			return array("status" => "Success");	
			//print_r($metaForm);	
		}else
		{
			return array("status" => "Error", "msg"=> "Customer info data is not valid", 'timeStamp' => date('Y-m-d H:i:s'));
		}		
	}


	function addCustomerAudit($customerId, $source, $sourceId, $tag, $description){
		$CI = get_instance();

		$CI->load->model('ModelCustomer');
		$customerData = array("customerId" => $customerId, "source" => $source, "sourceId" => $sourceId, "tag" => $tag, "description" => $description);
		$d = $CI->ModelCustomer->addCustomerAudit($customerData);
		return $d;
		
	}

	function addSubscriptionAudit($subscriptionId, $userId, $source, $status, $remark){
		$CI = get_instance();

		$CI->load->model('ModelSubscription');
		$auditData = array("subscriptionId" => $subscriptionId, "userId" => $userId, "source" => $source, "status" => $status, "remark" => $remark);
		$d = $CI->ModelSubscription->addSubscriptionAudit($auditData);
		return $d;
		
	}

	function getElementKeyByVlaue($data, $keyValue, $value, $key){
		for($i=0;$i<sizeof($data);$i++){
			if($data[$i][$keyValue] == $value)
				return $data[$i][$key];
		}
		return 0;
	}	

	/*function convertpdfs($html,function convertpdf($html, $filePath)
        {
                // Load HTML content
                $this->pdf->loadHtml($html);

                // (Optional) Setup the paper size and orientation
                $this->pdf->setPaper('A4', 'landscape');

                // Render the HTML as PDF
                $this->pdf->render();

                // Output the generated PDF (1 = download and 0 = preview)
                file_put_contents($filePath, $this->pdf->output());
        //      $this->pdf->stream($filePath, array("Attachment"=>0));
        }*/
