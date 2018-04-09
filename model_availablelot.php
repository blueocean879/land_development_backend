<?php
Class Model_availablelot extends CI_Model
{
	public function getAvailableLots($development_id, $limit = NULL)
	{
		$this->db->where('available_lots.development_id', $development_id);
		$this->db->order_by('available_lots.available_date', 'desc');
		$query  = $this->db->get('available_lots', $limit);
		$result = array();
		if ($query->num_rows() > 0)
		{
			$result = $query->result();
		}
		return $result;
	}

	public function addAvailableLots()
	{
		$this->load->model('Model_development', '', TRUE);
		$developments        = $this->Model_development->getDevelopments();
		foreach($developments as $development){
			$development_id      = $development->development_id;
			$start_month         = date('Y-m-01');
			$start_month_time    = strtotime($start_month);
			$available_lots_tots = $this->getAvailableLots($development_id, 1);

			// if the current start of the month has not been recored then insert the record with the data
			if(empty($available_lots_tots) || strtotime($available_lots_tots[0]->available_date) < $start_month_time){
				$month3 = date('Y-m-01', strtotime('-3 months', $start_month_time));
				$month6 = date('Y-m-01', strtotime('-6 months', $start_month_time));
				$total_lots = $this->getTotalLots($development_id, $month3, $month6);
				
				// insert total lots
				$data       = array(
					'development_id'     => $development_id,
					'available_date'     => $start_month,
					'lots_lessthan3mths' => $total_lots['lots_lessthan3mths'],
					'lots_btw3and6mths'  => $total_lots['lots_btw3and6mths'],
					'lots_morethan6mths' => $total_lots['lots_morethan6mths']
				);
				$this->db->insert('available_lots', $data);
			}
		}
	}

	public function getTotalLots($development_id, $month3, $month6)
	{
		$results    = array();
		$conditions = array(
			'lots_lessthan3mths' => "lots.creation_date > '{$month3}'",
			'lots_btw3and6mths'  => "lots.creation_date <= '{$month3}' AND lots.creation_date >= '{$month6}'",
			'lots_morethan6mths' => "lots.creation_date < '{$month6}'",
		);
		foreach($conditions as $condition_name => $condition_sql){
			
			$this->db->select('COUNT(lots.lot_id) as total_lots');
			$this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
			$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
			$this->db->where('precincts.development_id', $development_id);
			$this->db->where('lots.status', 'Available');
			$this->db->where($condition_sql);

			$this->db->where('lots.status', 'Available');


			$query      = $this->db->get_where('lots', array('precincts.development_id' => $development_id));
			$total_lots = $query->row();
			$results[$condition_name] = $total_lots->total_lots;
		}
		return $results;
	}

	public function getFields()
	{
		return array(
			'available_lot_id',
			'development_id',
			'available_date',
			'lots_lessthan3mths',
			'lots_btw3and6mths',
			'lots_morethan6mths'
		);
	}
}
?>