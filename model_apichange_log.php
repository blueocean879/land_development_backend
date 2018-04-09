<?php
Class Model_apichange_log extends CI_Model{
	public function getChangeLogs($with_time_formatted = TRUE, $limit =	2000){
		$this->db->select('lots.lot_number,developments.development_name,api_change_logs.log_note,api_change_logs.api_key,
			api_change_logs.api_change_log_id,api_change_logs.datetime, api_change_logs.api_username');

		$this->db->join('developments', 'developments.development_id = api_change_logs.development_id');
		$this->db->join('lots', 'lots.lot_id = api_change_logs.lot_id');
		$this->db->order_by('api_change_logs.datetime', 'desc');

		$query  = $this->db->get('api_change_logs',$limit);

		$result = array();

		if ($query->num_rows() > 0)
		{
			$result = $query->result();
		}
		if($with_time_formatted){
			$this->load->library('mapovis_lib');
			$timezone = $this->mapovis_lib->getTimeZone();
			foreach($result as $row){
				// chaning time to default time zone
				$row_datetime   = date_create(date('Y-m-d H:i:s', $row->datetime));
				$row_datetime->setTimeZone(timezone_open($timezone));

				$row->formatted_date = date_format($row_datetime, 'd/m/Y');
				$row->formatted_time = date_format($row_datetime, 'h:i a');
			}
		}
		
		return $result;
	}
}
?>