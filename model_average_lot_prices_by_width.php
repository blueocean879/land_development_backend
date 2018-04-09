<?php
Class Model_average_lot_prices_by_width extends CI_Model
{
	public function getAverageLotPrices($development_id, $limit = NULL)
	{
		$this->db->where('average_lot_prices_by_width.development_id', $development_id);
		$this->db->where('average_lot_prices_by_width.row_date >=', date('Y-m-01', strtotime('-12 months')));
		if($limit == 1){
			$this->db->order_by('average_lot_prices_by_width.row_date', 'desc');
		}
		$this->db->order_by('average_lot_prices_by_width.row_date', 'asc');
		$this->db->order_by('average_lot_prices_by_width.lot_width', 'asc');
		$query  = $this->db->get('average_lot_prices_by_width', $limit);
		$results = $query->result();
		return ($results)? $results: array();
	}

	function getAverageLotPricesFormatted($development_id)
	{
		$this->load->library('mapovis_lib');
		$tmp_widths       = $this->getWidths($development_id);
		$results          = $this->getAverageLotPrices($development_id);
		$formated_resutls = array();
		$all_widths       = array();
		foreach($results as $row){
			$lot_width = (string)$this->mapovis_lib->roundNearestfromarrayvals($tmp_widths, $row->lot_width);
			if(!in_array($lot_width, $all_widths)){
				$all_widths[] = $lot_width;
			}
			if(!isset($formated_resutls[$row->row_date][$lot_width])){
				$formated_resutls[$row->row_date][$lot_width] = $row;
			}
			else{
				$formated_resutls[$row->row_date][$lot_width]->average_price += $row->average_price;
				$formated_resutls[$row->row_date][$lot_width]->average_price_min += $row->average_price_min;
				$formated_resutls[$row->row_date][$lot_width]->average_price_max += $row->average_price_max;
			}
		}
		sort($all_widths);
		return array('widths' => $all_widths, 'results' => $formated_resutls);
	}

	public function getWidths($development_id)
	{
		$this->db->select('average_lot_prices_by_width.lot_width');
		$this->db->where('average_lot_prices_by_width.development_id', $development_id);
		$this->db->where('average_lot_prices_by_width.lot_width % 0.5 = 0 ');
		$this->db->where('average_lot_prices_by_width.row_date >=', date('Y-m-01', strtotime('-12 months')));
		$this->db->order_by('average_lot_prices_by_width.lot_width', 'asc');
		$this->db->group_by('average_lot_prices_by_width.lot_width', 'asc');
		$query   = $this->db->get('average_lot_prices_by_width');
		$results = $query->result();
		$widths  = array();
		if($results){
			foreach($results as $result){
				$widths[] = $result->lot_width;
			}
		}
		return $widths;
	}

	public function addAverageLotPrices()
	{
		$this->load->model('Model_development', '', TRUE);
		$developments        = $this->Model_development->getDevelopments();
		foreach($developments as $development){
			$development_id      = $development->development_id;
			$start_month         = date('Y-m-01');
			$start_month_time    = strtotime($start_month);
			$previous_lots_tots = $this->getAverageLotPrices($development_id, 1);

			// if the current start of the month has not been recored then insert the record with the data
			if(empty($previous_lots_tots) || strtotime($previous_lots_tots[0]->row_date) < $start_month_time){
				$total_lots = $this->getTotalLots($development_id);
				foreach($total_lots as $total_lot){
					// insert total lots
					$data       = array(
						'development_id'     => $development_id,
						'row_date'           => $start_month,
						'row_date'           => $start_month,
						'lot_width'          => $total_lot->lot_width,
						'average_price_min'  => $total_lot->average_price_min,
						'average_price_max'  => $total_lot->average_price_max,
						'average_price'      => $total_lot->average_price,
						'lot_status'         => $total_lot->status,
					);
					$this->db->insert('average_lot_prices_by_width', $data);
				}
			}
		}
	}

	public function getTotalLots($development_id)
	{
		$this->db->select('AVG(price_range_min) as average_price_min, AVG(price_range_max) as average_price_max, AVG((price_range_min + price_range_max)/2) as average_price, lots.lot_width, lots.status');
		$this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
		$this->db->where('precincts.development_id', $development_id);
		$this->db->where_in('lots.status', array('Available'));

		$this->db->group_by(array('lots.lot_width', 'precincts.development_id', 'lots.status'));
		$this->db->order_by('lots.lot_width');
		$query      = $this->db->get('lots');
		$results = $query->result();
		return ($results)? $results: array();
	}

	function deleteDevelopmentRows($development_id)
	{
		$this->db->where('average_lot_prices_by_width.development_id', $development_id);
		return $this->db->delete('average_lot_prices_by_width');
	}

	public function getFields()
	{
		return array(
			'development_id',
			'lot_width',
			'row_date',
			'lot_status',
			'average_price_min',
			'average_price_max',
			'average_price',
		);
	}
}
?>