<?php
require APPPATH.'/libraries/REST_Controller.php';

//
class PaypalCtrl extends REST_Controller {

    var $me;

    function __construct() {
        // Call the Model constructor
        parent::__construct();

        //
        $this->load->model ( 'auth' );
        $this->load->model ( 'payment' );
        $this->load->model ( 'user' );

        // date_default_timezone_set($this->me->mb_time);
        date_default_timezone_set('UTC');
    }

    private function getUserSession() {
        $userSession = userSessionData ();
        if (!empty($userSession)) {
            $this->me = $this->auth->getUserByNo ( $userSession->mb_no );
        }

        return $userSession;
    }

    function paypalNew_post() {
        $this->getUserSession();

        $item_name = $this->post('item_name');
        $item_number = $this->post('item_number');
        $amount = $this->post('amount');

        $tempPaypal = $this->payment->insertTempPaypal($this->me->mb_no, $item_name, $item_number, $amount);

        $this->response($tempPaypal);
    }

    function paypalPdt_get() {
        // sandbox
        // $pp_hostname = "www.sandbox.paypal.com";
        // $auth_token = "IRNpBgQ2f4YRhpujbBEw7PSVvSGeQtFi-WGr-wH6fV3hnJUKzbyv2BlllOO";

        // live
        $pp_hostname = "www.paypal.com";
        $auth_token = "jt2oRIYNJKuGF4AyUqdtxaNfmy7NtyChMPt2kmFDNffjX5c2cE6DRbBA3LW";

        $req = 'cmd=_notify-synch';
        $tx_token = $_GET['tx'];
        $req .= "&tx=$tx_token&at=$auth_token";
        // error_log('tx_token :: '. $tx_token);
        // error_log('req :: '. $req);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://$pp_hostname/cgi-bin/webscr");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: $pp_hostname"));
        $res = curl_exec($ch);

        // error_log('curl exec:'.$res);
        // error_log(curl_getinfo($ch)); //모든정보 출력
        // error_log(curl_errno($ch)) ; //에러정보 출력
        // error_log(curl_error($ch)) ; //에러정보 출력
        curl_close($ch);

        // 결과값을 로그로 기록해 보기
        $date_time = date("Y-m-d H:i:s");
        $fp = fopen("./tutork_pay_log.txt", "a");
        fwrite($fp, "\n[".$date_time."]==========================================\n");

        // pdt 결과
        if(!$res) {
            //HTTP ERROR
            fwrite($fp, "server error"."tx token : ".$tx_token."\n");
            // error_log('server error');
        } else {
            // error_log('log write!');


            $lines = explode("\n", $res);
            $keyarray = array();
            if (strcmp ($lines[0], "SUCCESS") == 0) {
                // 결제가 성공한 경우
                for ($i=1; $i<count($lines);$i++){
                    $tempArray = explode("=", $lines[$i]);

                    if (count($tempArray) == 2) {
                        list($key, $val) = $tempArray;
                        $keyarray[urldecode($key)] = urldecode($val);
                    }
                }

                // error_log('txn_id  '.$keyarray['txn_id']);

                $find_paypal_info = $this->payment->getPaypalInfo($keyarray['txn_id']);

                // error_log('find_paypal_info  '.$find_paypal_info);

                if ($find_paypal_info == 0) {
                    for ( $i = 1; $i < count($lines) ; $i++) {
                        $tempArray = explode("=", $lines[$i]);
                        if (count($tempArray) == 2) {
                            list($key, $val) = $tempArray;
                            fwrite($fp, urldecode($key).":".urldecode($val)."\n");
                        }
                    }

                    $array_item_number = explode ("|", $keyarray['item_number']);
                    $item_number = $array_item_number[0];
                    $mb_no = $array_item_number[1];
                    $tp_seq = $array_item_number[2];

                    // 상품권 id가 있을때는 length 가 4
                    $tc_id;
                    if (count($array_item_number) == 4) {
                        $tc_id = $array_item_number[3];
                    }

                    // paypal 저장
                    $new_paypal_info = array(
                        'txn_id' => $keyarray['txn_id'],
                        'payer_email' => $keyarray['payer_email'],
                        'payment_date' => $keyarray['payment_date'],
                        'first_name' => $keyarray['first_name'],
                        'last_name' => $keyarray['last_name'],
                        'item_name' => $keyarray['item_name'],
                        'mc_currency' => $keyarray['mc_currency'],
                        'item_number' => $item_number,
                        'payment_fee' => $keyarray['payment_fee'],
                        'payment_gross' => $keyarray['payment_gross'],
                        'mb_no' => $mb_no,
                        'reg_dt' => date("Y-m-d H:i:s")
                    );

                    $this->payment->insertPaypalInfo($new_paypal_info);

                    $tp_in_koin=$keyarray['item_name'];
                    $tp_in_koin=str_replace(" KOIN","",$tp_in_koin);
                    $tp_in_koin=str_replace(",","",$tp_in_koin);

                    $ROOT_URL = TUTORK_ROOT_URL;

                    if (isset($tc_id)) {
                        // 상품권 구매
                        $couponData = $this->payment->giftcardSetcode($tc_id, $tp_in_koin);

                        // 메일발송
                        $mail_subject="[TutorK] Send Giftcard";
                        // $mail_content_r="<p>{$couponData->tc_recv_name}님 {$couponData->tc_mb_name}님께서 {$tp_in_koin} KOIN의 상품권을 전달했습니다.</p>";
                        $mail_content_r="<p>{$couponData->tc_mb_name}이 당신에게 온라인 한국어 회화 매칭 플랫폼, Tutor-K의 {$tp_in_koin} Koin 상품권을 선물했습니다.</p>";
                        $mail_content_r=$mail_content_r."<br/>";
                        $mail_content_r=$mail_content_r."<p>{$couponData->tc_mb_name} presented Tutor-K, an online Korean conversation matching platform, {$tp_in_koin} Koin gift certificate to you.</p>";
                        $mail_content_r=$mail_content_r."<br/>";
                        $mail_content_r=$mail_content_r."<br><p>CDOE : {$couponData->tc_code}</p><br>";
                        $mail_content_r=$mail_content_r."<br><br><p>{$couponData->tc_email_content}</p><br><br>";
                        $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"$ROOT_URL\">Check charged KOIN in your account.</a></p>";
                        sendMail($couponData->tc_recv_email, $mail_subject, $mail_content_r);
                    } else {
                        // koin 구매
                        // payment 저장
                        $payment_params = array(
                            'tp_mb_no' => $mb_no,
                            // 'tp_lr_seq' => $reservation_id,
                            'tp_payment_code' => $keyarray['txn_id'],
                            'tp_mode' => 'I',
                            'tp_in_koin' => $tp_in_koin,
                            'tp_reg_dt' => date("Y-m-d H:i:s"),
                            'tp_memo' => 'Buy KOIN',
                        );
                        $this->payment->insertPayment($payment_params);

                        $temp_params = array('tp_use_yn' => 'Y', 'tp_user_ip' => $_SERVER['REMOTE_ADDR']);
                        $conditions = array('tp_seq' => $tp_seq, 'tp_mb_no' => $mb_no);
                        $this->payment->updateTempPaypal($temp_params, $conditions);

                        // send mail
                        $member = $this->user->simpleProfile($mb_no);
                        $mail_subject="[TutorK] Charging KOIN has been completed";
                        $mail_content_r="<p>{$tp_in_koin} KOIN has been charged.</p>";
                        $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"$ROOT_URL\">Check charged KOIN in your account.</a></p>";
                        sendMail($member->mb_email, $mail_subject, $mail_content_r);
                    }
                }
            } else if (strcmp ($lines[0], "FAIL") == 0) {
                fwrite($fp, "pdt FAIL"."tx token : ".$tx_token."\n");
            }
        }

        fclose($fp);
        $this->load->helper('url');
        // redirect('http://localhost:3000/finance/overview');
        // redirect('http://www.tutor-k.com/bt2/#/finance/overview');
        // redirect('http://www.tutor-k.com/bt2_1/#/finance/overview');
        redirect(TUTORK_ROOT_URL.'/finance/overview');
        // $this->response ( 'success' );
    }

    // eximbay status 결과 처리 함
    // 중복으로 호출 될 수 있다함.
    function eximbayStatus_post() {
        // error_log('eximbayStatus POST call');
        // test
        // $secretKey = "289F40E6640124B2628640168C3C5464";//가맹점 secretkey

        // runnig
        $secretKey = "338DE477E0029999DB3E0016024C2AE0";//가맹점 secretkey

        //기본 응답 파라미터
        $ver = $_POST['ver'];//연동 버전
        $mid = $_POST['mid'];//가맹점 아이디
        $txntype = $_POST['txntype'];//거래 타입
        $ref = $_POST['ref'];//가맹점 지정에서 지정한 거래 아이디
        $cur = $_POST['cur'];//통화
        $amt = $_POST['amt'];//결제 금액
        $shop = $_POST['shop'];//가맹점명
        $buyer = $_POST['buyer'];//결제자명
        $tel = $_POST['tel'];//결제자 전화번호
        $email = $_POST['email'];//결제자 이메일
        $lang = $_POST['lang'];//결제정보 언어 타입

        $transid = $_POST['transid'];//Eximbay 내부 거래 아이디
        $rescode = $_POST['rescode'];//0000 : 정상
        $resmsg = $_POST['resmsg'];//결제 결과 메세지
        $authcode = $_POST['authcode'];//승인번호, PayPal, Alipay, Tenpay등 일부 결제수단은 승인번호가 없습니다.
        $cardco = $_POST['cardco'];//카드 타입
        $resdt = $_POST['resdt'];//결제 시간 정보 YYYYMMDDHHSS
        $paymethod = $_POST['paymethod'];//결제수단 코드 (연동문서 참고)

        $accesscountry = $_POST['accesscountry'];//결제자 접속 국가
        $allowedpvoid = $_POST['allowedpvoid'];//Y: 부분취소 가능. N: 부분취소 불가
        $fgkey = $_POST['fgkey'];//검증키, rescode=0000인 경우에만 값 세팅 됨
        $payto = $_POST['payto'];//청구 가맹점명

        //주문 상품 파라미터
        $item_0_product = $_POST['item_0_product'];
        $item_0_quantity = $_POST['item_0_quantity'];
        $item_0_unitPrice = $_POST['item_0_unitPrice'];

        //추가 항목 파라미터
        // $surcharge_0_name = $_POST['surcharge_0_name'];
        // $surcharge_0_quantity = $_POST['surcharge_0_quantity'];
        // $surcharge_0_unitPrice = $_POST['surcharge_0_unitPrice'];

        //가맹점 지정 파라미터
        $item_number = $_POST['param1'];
        $mb_no = $_POST['param2'];
        $tp_seq = $_POST['param3'];

        //카드 결제 정보 파라미터
        $cardholder = $_POST['cardholder'];//결제자가 입력한 카드 명의자 영문명
        $cardno = $_POST['cardno'];
        // $cardno1 = $_POST['cardno1'];
        // $cardno4 = $_POST['cardno4'];


        //DCC 파라미터
        // $foreigncur = $_POST['foreigncur'];//고객 선택 통화
        // $foreignamt = $_POST['foreignamt'];//고객 선택 통화 금액
        // $convrate = $_POST['convrate'];//적용 환율
        // $rateid = $_POST['rateid'];//적용 환율 아이디

        //배송지 파라미터
        // $shipTo_city = $_POST['shipTo_city'];
        // $shipTo_country = $_POST['shipTo_country'];
        // $shipTo_firstName = $_POST['shipTo_firstName'];
        // $shipTo_lastName = $_POST['shipTo_lastName'];
        // $shipTo_phoneNumber = $_POST['shipTo_phoneNumber'];
        // $shipTo_postalCode = $_POST['shipTo_postalCode'];
        // $shipTo_state = $_POST['shipTo_state'];
        // $shipTo_street1 = $_POST['shipTo_street1'];

        //CyberSource의 DM을 사용 하는 경우 받는 파라미터
        // $dm_decision = $_POST['dm_decision'];
        // $dm_reject = $_POST['dm_reject'];
        // $dm_review = $_POST['dm_review'];

        //PayPal 거래 아이디
        // $pp_transid = $_POST['pp_transid'];

        //일본 결제 파라미터
        // $status = $_POST['status'];//(일본결제)Registered or Sale :: Sale은 입금완료 시, statusurl로만 전송됨 일본 편의점/온라인뱅킹 후불결제 이용 시, 결제정보 등록에 대한 통지가 설정된 경우 발송됩니다.
        // $paymentURL = $_POST['paymentURL'];//일본결제의 편의점/온라인뱅킹 후불 결제 이용시 고객에게 결제 방법을 안내하는 URL


    	//전체 파라미터 출력
    	// foreach($_POST as $Key=>$value) {
    	// 	error_log( $Key." : ".$value) ;
    	// }

        // log write
        $date_time = date("Y-m-d H:i:s");
        $fp = fopen("./tutork_pay_log.txt", "a");
        fwrite($fp, "\n[".$date_time."]==========================================\n");

        //rescode=0000 일때 fgkey 확인
        // error_log('transid : '.$transid);
        // error_log('rescode : '.$rescode);
        // error_log('authcode : '.$authcode);

        if($rescode == "0000"){
            //fgkey 검증키 생성
            $linkBuf = $secretKey. "?mid=" . $mid ."&ref=" . $ref ."&cur=" .$cur ."&amt=" .$amt ."&rescode=" .$rescode ."&transid=" .$transid;
            $newFgkey = hash("sha256", $linkBuf);

            // error_log('oldfgkey : '.strtolower($fgkey));
            // error_log('newfgkey : '.$newFgkey);
            // error_log('is same ? : '.strtolower($fgkey) != $newFgkey);

            //fgkey 검증 실패 시 에러 처리
            if(strtolower($fgkey) != $newFgkey) {
                // error_log('error');
                // $rescode = "ERROR";
                // $resmsg = "Invalid transaction";
                fwrite($fp, "eximbay Invalid transaction transid : ".$transid."\n");
            } else {
                // 검증 성공
                // error_log('success');
                $findCount = $this->payment->findEximbayInfo(array('transid' => $transid));
                // error_log('findCount : '.$findCount);
                // error_log('iqual zero : '.$findCount == 0);
                if ($findCount == 0) {
                    // 이미 저장된 내역 두번이상 호출될수 있음
                    foreach($_POST as $Key=>$value) {
                    	fwrite($fp, $Key." : ".$value."\n") ;
                    }

                    $array_item_number = explode ("|", $item_number);
                    $item_name = $array_item_number[0];
                    // 상품권 id가 있을때는 length 가 2
                    $tc_id;
                    if (count($array_item_number) == 2) {
                        $tc_id = $array_item_number[1];
                        // error_log("item_name : $array_item_number[0]");
                        // error_log("tc_id : $array_item_number[1]");
                    }

                    $new_eximbay_info = array(
                        'transid' => $transid,
                        'ref' => $ref,
                        'cur' => $cur,
                        'amt' => $amt,
                        'buyer' => $buyer,
                        'email' => $email,
                        'authcode' => $authcode,
                        'cardco' => $cardco,
                        'cardno' => $cardno,
                        'cardholder' => $cardholder,
                        'resdt' => $resdt,
                        'paymethod' => $paymethod,
                        'fgkey' => $fgkey,
                        'item_name' => $item_name,
                        'mb_no' => $mb_no,
                        'reg_dt' => date("Y-m-d H:i:s")
                    );
                    // error_log('eximbay new : '.json_encode($new_eximbay_info));

                    $this->payment->insertEximbayInfo($new_eximbay_info);
                    $tp_in_koin=$item_name;
                    $tp_in_koin=str_replace("K-","",$tp_in_koin);

                    $ROOT_URL = TUTORK_ROOT_URL;

                    // error_log("gift card ???");
                    if (isset($tc_id)) {
                        // error_log("gift card begin");
                        // 상품권 구매
                        $couponData = $this->payment->giftcardSetcode($tc_id, $tp_in_koin);

                        $mail_subject="[TutorK] Send Giftcard";
                        $mail_content_r="<p>{$couponData->tc_recv_name}님 {$couponData->tc_mb_name}님께서 {$tp_in_koin} KOIN의 상품권을 전달했습니다.</p>";
                        $mail_content_r=$mail_content_r."<br><p>CDOE : {$couponData->tc_code}</p><br>";
                        $mail_content_r=$mail_content_r."<br><br><p>{$couponData->tc_email_content}</p><br><br>";
                        $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"$ROOT_URL\">Check charged KOIN in your account.</a></p>";
                        sendMail($couponData->tc_recv_email, $mail_subject, $mail_content_r);

                    } else {
                        // payment 저장
                        $payment_params = array(
                            'tp_mb_no' => $mb_no,
                            // 'tp_lr_seq' => $reservation_id,
                            'tp_payment_code' => $transid,
                            'tp_mode' => 'I',
                            'tp_in_koin' => $tp_in_koin,
                            'tp_reg_dt' => date("Y-m-d H:i:s"),
                            'tp_memo' => 'Buy KOIN',
                        );
                        $this->payment->insertPayment($payment_params);

                        $temp_params = array('tp_use_yn' => 'Y', 'tp_user_ip' => $_SERVER['REMOTE_ADDR']);
                        $conditions = array('tp_seq' => $tp_seq, 'tp_mb_no' => $mb_no);
                        $this->payment->updateTempPaypal($temp_params, $conditions);

                        // $item_name=$keyarray['item_name'];
                        // $payment_gross=$keyarray['payment_gross'];
                        // $payment=str_replace(" KOIN","",$item_name);
                        // $payment=str_replace(",","",$payment)/10;
                        // $pament_fee=$payment_gross-$payment;

                        // send mail
                        $member = $this->user->simpleProfile($mb_no);
                        $mail_subject="[TutorK] Charging KOIN has been completed";
                        $mail_content_r="<p>{$tp_in_koin} KOIN has been charged.</p>";
                        $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"$ROOT_URL\">Check charged KOIN in your account.</a></p>";
                        sendMail($member->mb_email, $mail_subject, $mail_content_r);
                    }
                }
            }
        } else {
            fwrite($fp, "eximbay result fail rescode: ".$rescode."\n");
        }

        fclose($fp);

    }

    function eximbayReturn_post() {
        // error_log('eximbayReturn post call');

        //
        // //전체 파라미터 출력
        // echo "--------all return parameter-------------<br/>";
        // foreach($_POST as $Key=>$value) {
        //     error_log( $Key." : ".$value) ;
        // }
        // echo "----------------------------------------<br/>";


        $this->load->helper('url');
        // redirect('http://localhost:3000/finance/overview');
        // redirect('http://www.tutor-k.com/bt2/#/finance/overview');
        // redirect('http://www.tutor-k.com/bt2_1/#/finance/overview');
        redirect(TUTORK_ROOT_URL.'/finance/overview');
    }


    function newBankTransfer_post() {
        $this->getUserSession();

        $params = $this->post('params');

        $tempPaypal = $this->payment->insertBankTransfer($params);

        $this->response($tempPaypal);
    }
}
