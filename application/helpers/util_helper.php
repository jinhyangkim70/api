<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );

if (! function_exists ( 'changeDateByTimezone' )) {
	function changeDateByTimezone($str_date_time, $member_time_zone) {
		if ($member_time_zone == "")
			$member_time_zone = "Asia/Seoul";
		date_default_timezone_set ( "Asia/Seoul" );
		$tmp_strtotime = strtotime ( $str_date_time );
		date_default_timezone_set ( $member_time_zone );
		$str_date_time = date ( "Y-m-d H:i:s", $tmp_strtotime );
		// echo $str_date_time."<br/>";
		return $str_date_time;
	}
}

if (! function_exists ( 'getBankData' )) {
	function getBankData($mb_k_bank) {
		$bank_arr = array("54"=>"HSBC은행","23"=>"SC제일은행","39"=>"경남은행","34"=>"광주은행","04"=>"국민은행","03"=>"기업은행","11"=>"농협","31"=>"대구은행","55"=>"도이치은행","32"=>"부산은행","02"=>"산업은행","50"=>"상호신용금고","45"=>"새마을금고","07"=>"수협","48"=>"신용협동조합","88"=>"신한은행","05"=>"외환은행","20"=>"우리은행","71"=>"우체국","37"=>"전북은행","35"=>"제주은행","81"=>"하나은행","27"=>"한국씨티");
		
		foreach($bank_arr as $key => $val) {
			if($mb_k_bank == $key) { 
				return $val; 
			}
		}
		
		return '';
	}
}
