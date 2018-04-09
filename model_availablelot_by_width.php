<?php
Class Model_availablelot_by_width extends CI_Model
{
	public function getAvailableLots($development_id, $limit = NULL)
	{
		$this->db->where('available_lots_by_width.development_id', $development_id);
		$this->db->where('available_lots_by_width.available_date >=', date('Y-m-01', strtotime('-12 months')));
		if($limit == 1){
			$this->db->order_by('available_lots_by_width.available_date', 'desc');
		}
		$this->db->order_by('available_lots_by_width.available_date', 'asc');
		$this->db->order_by('available_lots_by_width.lot_width', 'asc');
		$query  = $this->db->get('available_lots_by_width', $limit);
		$results = $query->result();
		return ($results)? $results: array();
	}

	function getAvailableLotsFormatted($development_id)
	{
		$this->load->library('mapovis_lib');
		$tmp_widths       = $this->getWidths($development_id);
		$results          = $this->getAvailableLots($development_id);
		$formated_resutls = array();
		$all_widths       = array();
		foreach($results as $row){
			$lot_width = (string)$this->mapovis_lib->roundNearestfromarrayvals($tmp_widths, $row->lot_width);
			if(!in_array($lot_width, $all_widths)){
				$all_widths[] = $lot_width;
			}
			if(!isset($formated_resutls[$row->available_date][$lot_width])){
				$formated_resutls[$row->available_date][$lot_width] = $row;
			}
			else{
				$formated_resutls[$row->available_date][$lot_width]->number_lots += $row->number_lots;
			}
		}
		sort($all_widths);
		return array('widths' => $all_widths, 'results' => $formated_resutls);
	}

	public function getWidths($development_id)
	{
		$this->db->select('available_lots_by_width.lot_width');
		$this->db->where('available_lots_by_width.development_id', $development_id);
		$this->db->where('available_lots_by_width.lot_width % 0.5 = 0 ');
		$this->db->where('available_lots_by_width.available_date >=', date('Y-m-01', strtotime('-12 months')));
		$this->db->order_by('available_lots_by_width.lot_width', 'asc');
		$this->db->group_by('available_lots_by_width.lot_width', 'asc');
		$query   = $this->db->get('available_lots_by_width');
		$results = $query->result();
		$widths  = array();
		if($results){
			foreach($results as $result){
				$widths[] = $result->lot_width;
			}
		}
		return $widths;
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
				$total_lots = $this->getTotalLots($development_id);
				foreach($total_lots as $total_lot){
					// insert total lots
					$data       = array(
						'development_id'     => $development_id,
						'available_date'     => $start_month,
						'lot_width'          => $total_lot->lot_width,
						'number_lots'        => $total_lot->number_lots,
					);
					$this->db->insert('available_lots_by_width', $data);
				}
			}
		}
	}

	public function getTotalLots($development_id)
	{
		$this->db->select('COUNT(lots.lot_id) as number_lots, lots.lot_width');
		$this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
		$this->db->where('precincts.development_id', $development_id);
		$this->db->where('lots.status', 'Available');

		$this->db->group_by(array('lots.lot_width', 'precincts.development_id'));
		$this->db->order_by('lots.lot_width');
		$query      = $this->db->get('lots');
		$results = $query->result();
		return ($results)? $results: array();
	}

	function deleteDevelopmentViews($development_id)
	{
		$this->db->where('available_lots_by_width.development_id', $development_id);
		return $this->db->delete('available_lots_by_width');
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