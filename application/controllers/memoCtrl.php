<?php
require APPPATH.'/libraries/REST_Controller.php';

//
class MemoCtrl extends REST_Controller {

	var $me;

	function __construct() {
		// Call the Model constructor
		parent::__construct();

		//
		$this->load->model ( 'auth' );
        $this->load->model ( 'memo' );
        $this->load->model ( 'user' );

		$userSession = userSessionData ();


		$this->me = $this->auth->getUserByNo ( $userSession->mb_no );

		// date_default_timezone_set($this->me->mb_time);
	}

	function myPage_post() {
		$memoList = $this->memo->getMemoList($this->me);

		$memoCount = $this->memo->getMemoUnreadCount($this->me);

		$this->response(array(
				'memoList' => $memoList,
				'memoCount' => $memoCount
		));
	}

    function myUnreadMemoCount_post() {
        $memoCount = $this->memo->getMemoUnreadCount($this->me);
        $this->response(array(
				'memoCount' => $memoCount
		));
    }

    function myUnreadNotiCount_post() {
        $memoCount = $this->memo->getNotiUnreadCount($this->me);
        $this->response(array(
				'notiCount' => $memoCount
		));
    }

	function myMemoPage_post() {
		$search_nick_name = $this->post ( 'nick_name' );
		// $page_index = $this->post ( 'page_index' );
		// $page_count = $this->post ( 'page_count' );
		// $this->response( $this->memo->getMemoPage($this->me, $search_nick_name, $page_index, $page_count) );

        $this->response( $this->memo->getMemoListSortUser($this->me, $search_nick_name) );
	}

    function getNotification_post() {
        $page_index = $this->post ( 'page_index' );
        $page_count = $this->post ( 'page_count' );
        $this->response( $this->memo->getNotificationPage($this->me->mb_no, $page_index, $page_count) );
    }

    function readNotfication_post() {
        $me_id = $this->post ( 'me_id' );
        $this->memo->readNotfication($me_id);
        $this->response('success');
    }

    function chatMessage_post() {
        $chat_user_no = $this->post ( 'chat_user_no' );

        $memo = $this->memo->getMemoChat($this->me, $chat_user_no);

        $this->response(array(
				'user_no' => $this->me->mb_no,
				'memoList' => $memo
		));
    }

    function insertNewMemo_post() {
        $memo_data = $this->post ( 'memo' );
        // error_log("memoform == ".json_encode($memo_data));
        // error_log("memoform me_recv_mb_no == ".$memo_data['me_recv_mb_no']);

        $new_memo = $this->memo->insertNewMemo($memo_data);

        $recv = $this->user->simpleProfile($memo_data['me_recv_mb_no']);
        $send = $this->user->simpleProfile($memo_data['me_send_mb_no']);
        $memo_content = $memo_data['me_memo'];

		if ($recv) {
            $mail_subject="[$send->mb_nick] sent you a message.";
	        $mail_content_r="<p>Dear  [".$recv->mb_nick."]</p>";
	        $mail_content_r=$mail_content_r."<p>[".$send->mb_nick."] sent you a message on tutor-k.</p>";
	        $mail_content_r=$mail_content_r."<br/>";
	        $memo_content = nl2br($memo_content);
	        $mail_content_r=$mail_content_r.$memo_content;
	        $mail_content_r=$mail_content_r."<br/><br/>";

	        $mail_content_r=$mail_content_r."<a href=\"http://www.tutor-k.com\">View the message here</a><br/>";

	        $mail_content_r=$mail_content_r."<p>If you think this member is a spammer, please forward the message to tutor-k@naver.com.  We will address this problem immediately.</p><br/>";
	        $mail_content_r=$mail_content_r."<p>Sincerely,</p>";
	        $mail_content_r=$mail_content_r."<p>The Tutor-K Team</p>";

	        // $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"http://www.tutor-k.com/memo/list.php\">대화를 시작하려면 클릭하세요.</a></p>";
	        // $mail_content_r=$mail_content_r."<p style=\"margin-top:8px;\"><a href=\"http://www.tutor-k.com/\">대화를 시작하려면 클릭하세요.</a></p>";
	        sendMail($recv->mb_email ,$mail_subject, $mail_content_r);

	        $this->response($new_memo);
		} else {
            $this->response(array('msg' => 'fail'));
        }
    }

    function deleteMemo_post() {
        $memo_data = $this->post ( 'memo' );
        $ret = 'fail';

        if ($this->me->mb_no == $memo_data['me_send_mb_no']) {
            // error_log('send user equal');
            $this->memo->deleteMemo($memo_data['me_id']);
            $ret = 'success';
        }

        $this->response(array('message' => $ret));
    }


	function view_post() {

		$seq = $this->post('toUser');

		$result = $this->memo->getDetail($this->me, $seq);

		$this->response($result);
	}
}
