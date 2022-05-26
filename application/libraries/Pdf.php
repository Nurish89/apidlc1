<?php defined('BASEPATH') OR exit('No direct script access allowed');

// include autoloader
        require_once dirname(__FILE__).'/dompdf/autoload.inc.php';

// reference the Dompdf namespace
use Dompdf\Dompdf;

class Pdf extends Dompdf
{
    	public function __construct()
    	{
		parent::__construct();
                
    	}
}
?>
