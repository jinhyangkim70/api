<?php
require APPPATH.'/libraries/REST_Controller.php';

//
class PaymentCtrl extends REST_Controller {

	var $me;

	function __construct() {
		// Call the Model constructor
		parent::__construct();

		//
		$this->load->model ( 'auth' );
		$this->load->model ( 'payment' );

		$userSession = userSessionData ();


		$this->me = $this->auth->getUserByNo ( $userSession->mb_no );

		// date_default_timezone_set($this->me->mb_time);
        date_default_timezone_set('UTC');
	}

	function myPayment_post() {
        $search_params = array(
            'tp_mb_no' => $this->me->mb_no
        );

        $startDate = $this->post('startDateStr');
        $endDate = $this->post('endDateStr');
        if ($startDate !== '') {
            $search_params['tp_reg_dt >='] = $startDate;
        }
        if ($endDate !== '') {
            $search_params['tp_reg_dt <='] = $endDate;
        }
        $page = $this->post('page');
        $count = $this->post('count');

        // error_log('params ==== '.json_encode($search_params));
        // error_log('page ==== '.$page);
        // error_log('count ==== '.$count);

		// $result = $this->payment->getPaymentList($search_params, $start_index);
        $result = $this->payment->getPaymentList($search_params, $page, $count);
        $this->response($result);
	}

    function insertDraw_post() {
        $password = $this->post('mb_password');
        $account = $this->post('account');
        $drawout_payment = $this->post('koin');

        $ret = $this->payment->insertDrawPayment($this->me->mb_no, $password, $account, $drawout_payment);

        $this->response(array('msg' => $ret));
    }

    function insertCode_post() {
        $code = $this->post('code');

        $ret = $this->payment->insertCodePayment($this->me->mb_no, $code);
        $this->response(array('msg' => $ret));
    }

    function newGiftcard_post() {
        $tc_mb_email = $this->post('tc_mb_email');
        $tc_mb_name = $this->post('tc_mb_name');
        $tc_user_email = $this->post('tc_user_email');
        $tc_user_name = $this->post('tc_user_name');
        $tc_email_content = $this->post('tc_email_content');


        $ret = $this->payment->insertGiftcard($this->me->mb_no, $tc_mb_email, $tc_mb_name, $tc_user_email, $tc_user_name, $tc_email_content);
        $this->response(array('giftcard_id' => $ret));
    }
}
