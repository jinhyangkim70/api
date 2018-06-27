<?php

use Firebase\JWT\JWT;
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );

	// 		$token = array(
	// 				"iss" => "http://example.org", 토큰을 발급한 발급자(Issuer)
	// 				"aud" => "http://example.com", 이 토큰을 사용할 수신자(Audience)
	// 				"iat" => 1356999524, 토큰이 발급된 시간(Issued At)
	// 				"nbf" => 1357000000,
	//				"exp" => 1357000000,  완료
	// 		);

if (! function_exists ( 'userSessionData' )) {
	function userSessionData() {
		$key = "tutorkK!@#$";

		$CI = &get_instance ();

		$jwt = $CI->input->get_request_header('Authorization');

        if (empty($jwt)) {
            return NULL;
        } else {
            $jwt = substr($jwt, 7);

    		$userItem = JWT::decode ( $jwt, $key, array (
    				'HS256'
    		) );

            return ( object ) $userItem;
        }
	}
}

if (! function_exists ( 'setUserSessionData' )) {
	function setUserSessionData($userItem) {
		$key = "tutorkK!@#$";

		// 기존에 한달뒤로 설정되어 있음
		$userItem["iat"] = now('Asia/Seoul');
		$userItem["exp"] = $userItem["iat"] + 86400 * 31;

		$jwt = JWT::encode ( $userItem, $key );

		return $jwt;
	}
}
