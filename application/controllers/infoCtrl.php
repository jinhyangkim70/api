<?php
require APPPATH . '/libraries/REST_Controller.php';

class InfoCtrl extends REST_Controller {
	var $me;
	function __construct() {
		// Call the Model constructor
		parent::__construct ();

		//
// 		$this->load->model ( 'auth' );
		$this->load->model ( 'info' );
		$this->load->model ( 'tutor' );

// 		$userSession = userSessionData ();

// 		$this->me = $this->auth->getUserByNo ( $userSession->mb_no );

// 		date_default_timezone_set ( $this->me->mb_time );
	}
	
	function press_post() {
		$result = $this->info->getPressList();
		
		$this->response($result);
	}
	
	function tutor_post() {
		$result = $this->tutor->landing();
		
		$this->response($result);
	}
	
	function langList_post() {
		$result = $this->info->getLangList();
		
		$this->response($result);
	}
        function moneyEchange_post(){
       
 //           $ch = curl_init('http://data.fixer.io/api/latest?access_key=3410ffb861066c8151b8cd931950d454&base=USD&symbols=KRW');
            $ch = curl_init('http://data.fixer.io/api/latest?access_key=3410ffb861066c8151b8cd931950d454&base=USD');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Store the data:
            $json = curl_exec($ch);
            curl_close($ch);
            // Decode JSON response:
            $exchangeRates = json_decode($json, true);
            // Access the exchange rate values, e.g. GBP:         
            $result=$exchangeRates['rates']['KRW'];
            $this->response($result);
        }
}
