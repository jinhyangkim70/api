<?php
class info extends CI_Model {
	function __construct() {
		// Call the Model constructor
		parent::__construct ();
	}

	public function getPressList() {
		$sql = " select wr_datetime,wr_subject,wr_1,wr_2 from g4_write_news as b order by wr_datetime desc ";

		$stmt = $this->db->query($sql);

		return $stmt->result ();
	}

	public function getLangList() {
		$sql="select * from TB_language_zone order by TL_eng";

		$stmt = $this->db->query($sql);

		return $stmt->result();
	}
}
