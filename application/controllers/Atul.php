<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Atul extends CI_Controller {

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
                $this->load->helper('utility');
        }

        public function convertpdf($html, $filePath)
        {
		$this->load->library('pdf');
                // Load HTML content
                $this->pdf->loadHtml($html);

                // (Optional) Setup the paper size and orientation portrait, landscape
                $this->pdf->setPaper('A4', 'portrait');

                // Render the HTML as PDF
                $this->pdf->render();

                // Output the generated PDF (1 = download and 0 = preview)
                file_put_contents($filePath, $this->pdf->output()); 
	
		unset($this->pdf);
        }

	public function test(){
                $imgPath1 = './img/test1.pdf';
		$imgPath2 = './img/test2.pdf';
		$imgPath3 = './img/test3.pdf';
		
		$html1 = 'Hello World 1';
		$html2 = 'Hello World 2';
		$html3 = 'Hello World 3';
		
				
		$this->convertpdf($html1, $imgPath1);
		$this->convertpdf($html2, $imgPath2);
		$this->convertpdf($html3, $imgPath3);
	}
}
?>
