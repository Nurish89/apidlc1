<?php

defined('BASEPATH') OR exit('Error in API Controller');
 
class Test extends CI_Controller
{
	
	public fucntion firstView(){
		$this->load->view('welcome_message');
	}
}
?>
