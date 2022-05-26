<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Utility extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('ModelUtility');
	}

	public function apiAuthentication($userName, $apiKey){
		return $checkApiAuth = $this->utility->checkApiAuthentication($userName, $apiKey);
	}	
		
	public function getOCBToken()
	{
		$ocbTokenData = file_get_contents('./application/files/ocbToken.json');
		if($ocbTokenData)
		{
			return $ocbTokenData;
		}
		else
		{
			return "hero";
		}
	}	
}

?>
