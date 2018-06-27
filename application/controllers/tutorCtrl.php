<?php
require APPPATH.'/libraries/REST_Controller.php';

// lecture, lesson - 수업 한번
class TutorCtrl extends REST_Controller {

	var $me;

	function __construct() {
		// Call the Model constructor
		parent::__construct();

		//
		$this->load->model ( 'auth' );
		$this->load->model ( 'tutor' );

		// date_default_timezone_set($this->me->mb_time);
	}

    private function getUserSession() {
        $userSession = userSessionData ();
        if (!empty($userSession)) {
            $this->me = $this->auth->getUserByNo ( $userSession->mb_no );
        }

        return $userSession;
    }

	public function find_post() {
        //$page = $this->post ( 'page' );
        //$count = $this->post ( 'count' );
        $thema = $this->post('thema');
	$priceLevel = $this->post('priceLevel');
        $trialPrice = $this->post('trialPrice'); // charge, free
        $slang = $this->post('slang');

        $userSession = $this->getUserSession();

        $memeber = NULL;
        if (!empty($userSession)) {
            $memeber = $this->me;
        }

        $result = $this->tutor->find($memeber, '', $thema, $slang, $priceLevel, '', $trialPrice);

        $this->response($result);
	}

	// 공통 폐이지 자신이 등록한 즐겨찾기 선생림 리스트
	function myFavoriteList_post() {
        $this->getUserSession();
		$result = $this->tutor->favoriteList($this->me);

		$this->response($result);
	}

	// 공통 favorite 제거/추가
	function toggleFavorite_post() {
        $this->getUserSession();
		$fav = $this->post('fav');
		$mb_no = $this->post('mb_no');

		$result = $this->tutor->toggleFavorite($fav, $this->me->mb_no, $mb_no);

		$this->response(
			array('fav' => $result)
		);
	}

    // 선생님 최근 수업 평가
    //
    // response
    // (지난달, 지난 3개월, 전체 강의)평점, 시범강의 평점
    // (지난달, 지난 3개월, 전체 강의)완료한 강의갯수 시범강의갯수
	function tutorRecentLectureReport_post() {
		$mb_no = $this->post('mb_no');
        $scoreReport = $this->tutor->recentLectureReport($mb_no);
        $finishLectureReport = $this->tutor->recentLectureCount($mb_no);

		$this->response(array('scoreReport' => $scoreReport,'finishLectureReport' => $finishLectureReport));
	}
}
