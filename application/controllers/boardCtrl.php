<?php
//defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );

require APPPATH . '/libraries/REST_Controller.php';

class BoardCtrl extends REST_Controller {


    public function getBoardList_post() {
        // $page = $this->post('page');
        // $count = $this->post('count');
        $this->load->model ( 'board' );

        $page = $this->post ( 'page' );
        $count = $this->post ( 'count' );
        $tm_sub_cd = $this->post ( 'tm_sub_cd' );
        $tm_lang_cd = $this->post ( 'tm_lang_cd' );
        $teacher_name = $this->post ( 'teacher_name' );


        // error_log('type '.gettype($tm_sub_cd));
        // error_log('tm_sub_cd'.json_encode($tm_sub_cd));

        $result = $this->board->getBoardList($page, $count, $tm_sub_cd, $tm_lang_cd, $teacher_name);
        $this->response($result);
    }

    public function getTeachersBoard_post() {
        $this->load->model ( 'board' );
        $mb_no = $this->post ( 'mb_no' );
        $page = $this->post ( 'page' );
        $count = $this->post ( 'count' );


        $result = $this->board->getTeacherBoardList($page, $count, $mb_no);
        $this->response($result);
    }

    public function getTeachersBoardCount_post() {
        $this->load->model ( 'board' );
        $mb_no = $this->post ( 'mb_no' );

        $result = $this->board->getTeacherBoardList(1, 0, $mb_no);

        $this->response(array('count' => $result['count']));
    }

    public function deleteBoard_post() {
        $this->load->model ( 'board' );
        $this->load->model ( 'user' );
        $tm_id = $this->post ( 'tm_id' );

        $board = $this->board->deleteBoard($tm_id);
        $this->response(array('msg' => 'success'));
    }

    public function getBoardDetail_post() {
        // $page = $this->post('page');
        // $count = $this->post('count');
        $this->load->model ( 'board' );
        $this->load->model ( 'user' );
        $tm_id = $this->post ( 'tm_id' );

        $userSession = userSessionData ();
        $board = $this->board->getBoardDetail($tm_id);

        $attachs = $this->board->getFiles($tm_id);
        $writer = $this->user->simpleProfile($board->tm_mb_no);
        $tutors = $this->user->sameLangTutor($writer->mb_lang, $writer->mb_lang2, $board->tm_mb_no);

        $thumbsup = NULL;
        if (!empty($userSession)) {
            $thumbsup = $this->board->getThumbsup($tm_id, $userSession->mb_no);
        }

        // 해당 주제의 가장 인기글
        // error_log('tm_sub_cd : '.$board->tm_sub_cd);
        $populer = $this->board->getPopulerBoard($board->tm_sub_cd);

        // 해당 주제를 제외한 다른 주제별 인기글 랜덤 3개
        $othersPopuler = $this->board->getOthersPopuler($board->tm_sub_cd);

        // 강사의 렉처 정보
        $lectures = $this->user->profileLecture($board->tm_mb_no);

        $this->response(array(
            'board' => $board,
            'files' => $attachs,
            'thumbsInfo' => $thumbsup,
            'writer' => $writer,
            'populer' => $populer,
            'othersPopuler' => $othersPopuler,
            'tutors' => $tutors,
            'lectures' => $lectures
        ));
    }

    public function getComments_post() {
        $this->load->model ( 'board' );
        $tm_id = $this->post ( 'tm_id' );

        $comments = $this->board->getComments($tm_id);
        $this->response($comments);
    }

    public function insertComment_post() {
        $this->load->model ( 'board' );
        $userSession = userSessionData ();

        $mb_no = $userSession->mb_no;
        $content = $this->post ( 'content' );
        $tmc_tm_id = $this->post ( 'tm_id' );

        $result = $this->board->insertComment ( array (
                'tmc_mb_no' => $mb_no,
                'tmc_tm_id' => $tmc_tm_id,
                'tmc_content' => $content
        ) );

		$this->response ( $result );
    }

    public function deleteComment_post() {
        $this->load->model ( 'board' );
        $userSession = userSessionData ();

        $tmc_id = $this->post ( 'tmc_id' );

        $result = $this->board->deleteComment ( array (
                'tmc_id' => $tmc_id
        ) );

		$this->response ( array('msg' => 'success') );
    }

    public function thumbsup_post(){
        $this->load->model ( 'board' );
        $userSession = userSessionData ();
        $mb_no = $userSession->mb_no;

        $isLike = $this->post ( 'isLike' );
        $tml_tm_id = $this->post ( 'tml_tm_id' );
        $tml_id = $this->post ( 'tml_id' );

        if (!empty($userSession)) {
            if (empty($tml_id)) {
                // error_log('insert thumbsup');
                $this->board->insertLike ( array(
                        'tml_mb_no' => $mb_no,
                        'tml_tm_id' => $tml_tm_id,
                        'tml_type' => $isLike,
                ), $tml_tm_id);
            } else {
                // error_log('update thumb');
                $this->board->updateLike ($isLike, $tml_id, $tml_tm_id);
            }
        }

		$this->response ( 'success' );
    }

    public function saveBoard_post() {
        $this->load->model ( 'board' );

        $userSession = userSessionData ();

		$mb_no = $userSession->mb_no;
        $tm_id = $this->post ( 'tm_id' );
		$tm_subject = $this->post ( 'tm_subject' );
        $tm_sub_cd = $this->post ( 'tm_sub_cd' );
        $tm_lang_cd = $this->post ( 'tm_lang_cd' );
		$tm_content = $this->post ( 'tm_content', false );
        // $tm_content = $_POST['tm_content'];

        if ($tm_id == '') {
			$result = $this->board->addNewBoard ( array (
                    'tm_mb_no' => $mb_no,
					'tm_subject' => $tm_subject,
                    'tm_lang_cd' => $tm_lang_cd,
                    'tm_sub_cd' => $tm_sub_cd,
					'tm_content' => $tm_content
			) );
		} else {
			$result = $this->board->updateBoard ( array (
                'tm_subject' => $tm_subject,
                'tm_lang_cd' => $tm_lang_cd,
                'tm_sub_cd' => $tm_sub_cd,
                'tm_content' => $tm_content,
			), $tm_id);
		}

		$this->response ( $result );
    }

	public function imageUpload_post() {

		// error_log("upload image~~~~~~~~~~~~~");
        $FILE_ROOT = 'upload_file';

		if(isset($_FILES['file'])) {

            // error_log("upload image2222222222222");
		    //The error validation could be done on the javascript client side.
		    $error_str = "";

		    //$file_name = uniqid('img_');
		    $file_name = $_FILES['file']['name'];

			//error_log("1111 $file_name");
		    $file_size =$_FILES['file']['size'];
		    $file_tmp =$_FILES['file']['tmp_name'];
		    $file_type=$_FILES['file']['type'];
		    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		    $extensions = array("jpeg","jpg","png");

            // foreach ($_FILES['file'] as $key => $value) {
            //     error_log("$key :: $value");
            // }
            // error_log("file info :: ".json_encode($_FILES['file']));

		    if(in_array($file_ext, $extensions) === false){
                $error_str = "file extension not allowed, please choose a JPEG or PNG file.";
		    }

            // 서버에서 2MB파일이 넘어가면 $_FILES로 아에 전달이 안되는것 같다.
		    if($file_size > 2097152 || $file_size == 0){
                $error_str = 'File size cannot exceed 2 MB';
		    }
			$newName = uniqid('img_');
			$file_name = $newName.'.'.$file_ext;

		    if ($error_str == "") {
                // error_log("file upload begin");
                move_uploaded_file($file_tmp, $FILE_ROOT."/".$file_name);
				//$full_path =" uploaded file: " . "images/" . $file_name;;
				$this->response ( array (
					'file_name' => "/".$FILE_ROOT."/".$file_name
				));
		    } else {
                // print_r($errors);
                // error_log("file upload fail");
                $this->response ( array (
		        		'errors' => $error_str
		        ));
		    }
		}
	}

	public function fileUpload_post() {

        $FILE_ROOT = 'upload_file';

		if(isset($_FILES['file'])){

            // error_log('file contain');

		    //The error validation could be done on the javascript client side.
		    $errors= array();

		    //$file_name = uniqid('img_');
		    $file_name = $_FILES['file']['name'];

			//error_log("1111 $file_name");
		    $file_size =$_FILES['file']['size'];
		    $file_tmp =$_FILES['file']['tmp_name'];
		    $file_type=$_FILES['file']['type'];
		    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

		    // if($file_size > 10485760){
            if($file_size > 2097152){
		    	$errors[]='File size cannot exceed 2 MB';
		    }
			$newName = uniqid('file_');
			$file_name = $newName.'.'.$file_ext;

		    if(empty($errors)==true){
		        move_uploaded_file($file_tmp, $FILE_ROOT."/".$file_name);
				//$full_path =" uploaded file: " . "images/" . $file_name;;
				$this->response ( array (
					'file_name' => "/".$FILE_ROOT."/".$file_name
				));
		    }else{
		        print_r($errors);
		        $this->response ( array (
		        		'errors' => $errors
		        ));
		    }
		}
	}

    // 업로드 된 파일 DB저장
    public function uploadFile_post() {
        // error_log('qqqq');

		$this->load->model ( 'board' );

        $userSession = userSessionData ();

		$mb_no = $userSession->mb_no;
		$tma_board_id = $this->post ( 'tma_board_id' );
		$tma_file_name = $this->post ( 'tma_file_name' );
		$tma_file_url = $this->post ( 'tma_file_url' );
        $tma_rel_file_url = $this->post ( 'tma_rel_file_url' );

        $tma_id = $this->post ( 'tma_id' );

        $result;
		if ($tma_board_id == '') {
			$params = array (
				'tma_board_id' => $tma_board_id,
				'tma_file_name' => $tma_file_name ,
				'tma_file_url' => $tma_file_url,
                'tma_rel_file_url' => $tma_rel_file_url,
                'tma_mb_no' => $mb_no,
			) ;

			$result = $this->board->addFile($params);
		} else {
			$result = $this->board->updateFileForBoardId ( $tma_id, array (
				'tma_board_id' => $tma_board_id
			) );
		}

		$this->response ( $result );
	}

    public function deleteFile_post() {
		$this->load->model ( 'board' );

        $userSession = userSessionData ();

        $tma_id = $this->post ( 'tma_id' );

        $board = $this->board->deleteAttachFile($tma_id);

        $this->response(array('msg' => 'success'));
    }

    public function getSubjectList_post() {
		$this->load->model ( 'board' );

        $this->response ( $this->board->getSubjectList() );
    }

    public function getFaqList_post() {
        $this->load->model ( 'board' );

        $this->response ( $this->board->getFaqList() );
    }

    public function getNewsList_post() {
        $this->load->model ( 'board' );

        $this->response ( $this->board->getNewsList() );
    }

    // 맞춤형 학습 신청
    public function saveCustomStudy_post() {
        $this->load->model ( 'board' );

        $form = $this->post ( 'form' );

        $return = $this->board->addNewCustomStudy( $form);
        // error_log('insert form'.json_encode($form));
        $this->response ( $return );
    }

    // 맞춤형 학습용 업로드 된 파일 DB저장
    public function customStudyUploadFile_post() {
        // error_log('qqqq');

		$this->load->model ( 'board' );

		$tcs_id = $this->post ( 'tcs_id' );
		$tcsa_file_name = $this->post ( 'tcsa_file_name' );
		$tcsa_file_url = $this->post ( 'tcsa_file_url' );

        $tcsa_id = $this->post ( 'tcsa_id' );

        $result;
		if ($tcs_id == '') {
			$params = array (
				'tcs_id' => '',
				'tcsa_file_name' => $tcsa_file_name ,
				'tcsa_file_url' => $tcsa_file_url,
			) ;

			$result = $this->board->customStudyAddFile($params);
		} else {
			$result = $this->board->customStudyUpdateFileForBoardId ( $tcsa_id, array (
				'tcs_id' => $tcs_id
			) );
		}

		$this->response ( $result );
	}

}
