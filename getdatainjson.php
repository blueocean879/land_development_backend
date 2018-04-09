<?php
//if( ! defined('BASEPATH')) exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
class Getdatainjson extends CI_Controller {

	function __construct() 
	{
		parent::__construct(); 
	}

	function development($development_id)
	{
		//DB Query - Get Development data for development_id
        $this->db->select('developments.*,multiple_developments_cluster_circles.*, update_messages.update_message as message, lot_icons.*, external_amenity_settings.*,stage_polygon_settings.*, hd_settings.*,'); 
        $this->db->join('multiple_developments_cluster_circles', 'multiple_developments_cluster_circles.development_id = developments.development_id', 'left');
        $this->db->join('update_messages', 'update_messages.development_id = developments.development_id', 'left');
        $this->db->join('lot_icons', 'lot_icons.development_id = developments.development_id', 'left');  
        $this->db->join('external_amenity_settings', 'external_amenity_settings.development_id = developments.development_id', 'left');
        $this->db->join('stage_polygon_settings', 'stage_polygon_settings.development_id = developments.development_id', 'left');
        $this->db->join('hd_settings', 'hd_settings.development_id = developments.development_id', 'left');   
        
        if($development_id){
            $this->db->where('developments.development_id', $development_id);
        }
        $query  = $this->db->get('developments');
        $result = $query->result();

		/* If all "reserve now" fields are set to NULL then remove them from the results */
		if(@$result[0]->reserve_now_message === NULL && @$result[0]->reserve_now_url === NULL && @$result[0]->reserve_now_logo === NULL){
			unset($result[0]->reserve_now_message);
			unset($result[0]->reserve_now_url);
			unset($result[0]->reserve_now_logo);
		}

        $this->output->set_content_type('application/json'); 
        $this->output->set_output(json_encode($result));
	}

    private function getDevelopmentURI($development_id){
        $this->db->select('developments.development_uri');
        if($development_id){
            $this->db->where('developments.development_id', $development_id);
        }

        $query  = $this->db->get('developments');
        $result = $query->result_array();

        return $result[0]['development_uri'];

    }
 
    function getmapstyles($development_id)
    {
        $this->db->select('developments.custom_googlemaps_style');
        $this->db->where('developments.development_id', $development_id);
        $custom_googlemaps_style = $this->db->get('developments')->result_array(); 
        $this->output->set_content_type('application/json'); 
        $this->output->set_output(json_encode($custom_googlemaps_style));  
    }

    function all_activeDevelopments(){
        
        //DB Query - Get Development data for active = 1
        $this->db->select('developments.*,client_website_links.*'); 
        $this->db->join('client_website_links', 'client_website_links.development_id = developments.development_id', 'left');
        $developmentarray = $this->db->get_where('developments', array('active' => 1))->result_array();
		// removing access_key password.
		foreach($developmentarray as $index => $development){
			unset($development['access_key']);
			$developmentarray[$index]= $development;
		}
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($developmentarray));
            
    }
    
    function roads($development_id){
        $this->db->select('*');
        $this->db->where('polylines.developmentID',$development_id);
        $roadsarray = $this->db->get('polylines')->result_array();
        $this->output->set_content_type('application/json'); 
        $this->output->set_output(json_encode($roadsarray));
    }
    
    function display_village_lots($development_id){
        $this->db->select('*');
        $this->db->where('lots_display_village.development_id',$development_id);
        $displayvillages = $this->db->get('lots_display_village')->result_array();
        $this->output->set_content_type('application/json'); 
        $this->output->set_output(json_encode($displayvillages));
    }
    
	function developer($developer_id)
	{
		$this->load->model('Model_development', '', TRUE);
		$developmentsarray = $this->Model_development->getDevelopmentsByDeveloper($developer_id);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($developmentsarray));
	}
    
	function stages($development_id)
	{
		$query       = $this->db->get_where('developments', array('development_id' => $development_id));
		$development = $query->row();

		$this->load->model('Model_stage', '', TRUE);
		$stages        = $this->Model_stage->getStages($development_id);
		$full_title    = $this->multiplePrecincts($development_id);
		$stage_results = array();
		foreach($stages as $stage){
			$this->db->select('COUNT(lots.lot_id) AS total');
			$this->db->where('lots.stage_id', $stage->stage_id);
			$this->db->where_in('lots.status', array('Available','Coming Soon'));
			$available_lots = $this->db->get('lots')->row()->total;

			$this->db->select('COUNT(lots.lot_id) AS total');
			$this->db->where('lots.stage_id', $stage->stage_id);
			$this->db->where('lots.status', 'Sold');
			$sold_lots = $this->db->get('lots')->row()->total;
            
            $this->db->select('COUNT(lots.lot_id) AS total');
            $this->db->where('lots.stage_id', $stage->stage_id);
            $this->db->where('lots.status', 'Deposited');
            $deposited_lots = $this->db->get('lots')->row()->total;

            $this->db->select('SUM(IF(lots.builder_lot = 1, 1, 0)) AS builder_lots, SUM(IF(lots.builder_lot = 0, 1, 0)) AS no_builder_lots', FALSE);
			$this->db->join('stages', 'stages.stage_id = lots.stage_id');
            $this->db->where('lots.stage_id', $stage->stage_id);
            $this->db->where('lots.status !=', 'Hidden');
			$this->db->where('stages.stage_hide_completely !=', '1');
            $builder_lots = $this->db->get('lots')->row();

			$array_key = ($development->stages_order == 'id')? $stage->stage_id: (empty($stage->stage_name)? 'zzz':$stage->stage_name).'_'.$stage->stage_id;
			$stage_results[$array_key] = array(
				'stage_id'               => $stage->stage_id,
				'precinct_id'            => $stage->precinct_id,
				'precinct_number'        => $stage->precinct_number,
				'stage_title'            => ($full_title)? $stage->precinct_number.'-'.$stage->stage_number: $stage->stage_number,
				'stage_number'           => $stage->stage_number,
				'stage_icon'             => $stage->stage_icon,
				'stage_icon_width'       => $stage->stage_icon_width,
				'stage_icon_height'      => $stage->stage_icon_height,
				'stage_code'             => $stage->stage_code,
				'stage_center_latitude'  => (float)$stage->stage_center_latitude,
				'stage_center_longitute' => (float)$stage->stage_center_longitute,
				'stage_zoomlevel'        => $stage->stage_zoomlevel,
				'stage_icon_latitude'	 => $stage->stage_icon_latitude,
				'stage_icon_longitude'	 => $stage->stage_icon_longitude,
				'stage_icon_zoomlevel_show' => $stage->stage_icon_zoomlevel_show,
				'stage_icon_zoomlevel_hide'	=> $stage->stage_icon_zoomlevel_hide,
				'stage_name'			=> $stage->stage_name,
				'display_stage_name'	=> $stage->display_stage_name,
				'stage_polygon_coords'	=> $stage->stage_polygon_coords,
                'stage_hide_completely' => $stage->stage_hide_completely,
				'lots_available'         => $available_lots,
				'lots_sold'              => $sold_lots,
                'lots_deposited'        => $deposited_lots,
                'stage_polygon_coords_clickable_at_zoom_level' => $stage->stage_polygon_coords_clickable_at_zoom_level,
                'stage_polygon_coords_unclickable_at_zoom_level' => $stage->stage_polygon_coords_unclickable_at_zoom_level,
				'percentage_lots_sold'   => (($sold_lots)? round(100 * $sold_lots/($sold_lots + $available_lots + $deposited_lots),2): 0).'%',
				'100_percent_builder_lots' => ($builder_lots && ($builder_lots->builder_lots > 0 && $builder_lots->no_builder_lots == 0))? 1:0,
			);
		}
		if($development->stages_order == 'id'){
	        krsort($stage_results);
		}
		else{
	        ksort($stage_results);
		}
        
        $stage_results1 = array_values($stage_results);
        
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($stage_results1));
	}

	function lots($development_id)
	{
		$this->load->model('Model_development', '', TRUE);
		$development    = $this->Model_development->getDevelopment($development_id);
		//DB Query - Get Lots data for lots from the development (development_id)
		$this->db->select('lots.*, lots.lot_titled AS titled, stages.*, precincts.*,lot_icons.*');
		$this->db->select('REPLACE(ROUND(lots.lot_width,2),".00","") AS lot_width', FALSE);
		$this->db->select('IF(lots.lot_corner = 1, "Corner", "Normal") AS type', FALSE);
		$this->db->select('lots.lot_irregular AS irregular');
		/*if($development->search_by_lot_price == 1){
			$this->db->select('CONCAT(CEIL(price_range_min/1000), "K") AS price_range_min, CONCAT(CEIL(price_range_max/1000), "K") AS price_range_max', FALSE);
		}else{
			$this->db->select('"n/a" AS price_range_min, "n/a" AS price_range_max', FALSE);
		}*/
        $this->db->select('price_range_min, price_range_max', FALSE);
		$this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
        $this->db->join('lot_icons', 'lot_icons.development_id = precincts.development_id', 'left');
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where_in('lots.status', array('Available','Coming Soon'));
		$this->db->where('stages.stage_hide_completely !=', '1');
		$lotsarray = $this->db->get_where('lots', array('precincts.development_id' => $development_id))->result_array(); 
		foreach($lotsarray as $index => $row){
			$lotsarray[$index]['lot_width'] = round($row['lot_width'],2);
		}

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode($lotsarray));
	}
    
    function deposited_lots($development_id)
    {
        //DB Query - Get Lots data for lots from the development (development_id)
        $this->db->select('lots.*, lots.lot_titled AS titled, stages.*, precincts.*, lot_icons.*, CONCAT(CEIL(price_range_min/1000), "K") AS price_range_min, CONCAT(CEIL(price_range_max/1000), "K") AS price_range_max ', FALSE);
        $this->db->select('REPLACE(ROUND(lots.lot_width,2),".00","") AS lot_width', FALSE);
        $this->db->select('IF(lots.lot_corner = 1, "Corner", "Normal") AS type', FALSE);
        $this->db->select('lots.lot_irregular AS irregular');
        $this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
        $this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
        $this->db->join('lot_icons', 'lot_icons.development_id = precincts.development_id', 'left');
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where('stages.stage_hide_completely !=', '1');
        $lotsarray = $this->db->get_where('lots', array('precincts.development_id' => $development_id, 'lots.status' => 'Deposited'))->result_array(); 
        foreach($lotsarray as $index => $row){
            $lotsarray[$index]['lot_width'] = round($row['lot_width'],2);
        }

        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($lotsarray));
    }
    
    function all_lots($development_id)
    {
		$this->load->model('Model_development', '', TRUE);
		$development    = $this->Model_development->getDevelopment($development_id);
        //DB Query - Get Lots data for lots from the development (development_id)
        $this->db->select('lots.*, stages.*, precincts.*, lot_icons.*');
        $this->db->select('REPLACE(ROUND(lots.lot_width,2),".00","") AS lot_width', FALSE);
		$this->db->select('IF(lots.lot_corner = 1, "Corner", "Normal") AS type', FALSE);
		$this->db->select('lots.lot_irregular AS irregular');
		if($development->search_by_lot_price == 1){
			$this->db->select('CONCAT(CEIL(price_range_min/1000), "K") AS price_range_min, , CONCAT(CEIL(price_range_max/1000), "K") AS price_range_max ', FALSE);
		}
		else{
			$this->db->select('"n/a" AS price_range_min, "n/a" AS price_range_max', FALSE);
		}
        $this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
        $this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
        $this->db->join('lot_icons', 'lot_icons.development_id = precincts.development_id', 'left'); 
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where('stages.stage_hide_completely !=', '1');
        $lotsarray = $this->db->get_where('lots', array('precincts.development_id' => $development_id))->result_array(); 
        foreach($lotsarray as $index => $row){
            $lotsarray[$index]['lot_width'] = round($row['lot_width'],2);
            
            $this->db->select('COUNT(lots.lot_id) AS total');
            $this->db->where('lots.stage_id', $row['stage_id']);
            $this->db->where_in('lots.status', array('Available','Coming Soon'));
            $available_lots = $this->db->get('lots')->row()->total;

            $this->db->select('COUNT(lots.lot_id) AS total');
            $this->db->where('lots.stage_id', $row['stage_id']);
            $this->db->where('lots.status', 'Sold');
            $sold_lots = $this->db->get('lots')->row()->total;
            
            $this->db->select('COUNT(lots.lot_id) AS total');
            $this->db->where('lots.stage_id', $row['stage_id']);
            $this->db->where('lots.status', 'Deposited');
            $deposited_lots = $this->db->get('lots')->row()->total;
            
            $percentage_lots_sold_in_stage   = (($sold_lots)? round(100 * $sold_lots/($sold_lots + $available_lots + $deposited_lots),2): 0).'%';        
            $lotsarray[$index]['percentage_lots_sold_in_stage'] = $percentage_lots_sold_in_stage;
        }

        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($lotsarray));
    }
    function all_lots_v2($development_id)
    {
		$this->load->model('Model_development', '', TRUE);
        $this->load->model('Model_house', '', TRUE);
		$development     = $this->Model_development->getDevelopment($development_id);
		$development_uri = $this->getDevelopmentURI($development_id);

		$hide_price_coming_soon_lots = $development->hide_price_coming_soon_lots;

        //DB Query - Get Lots data for lots from the development (development_id)
        $this->db->select('
        lots.lot_id,
        lots.lot_number,
        IF(stages.stage_hide_completely = 1, \'Hidden\', lots.status) AS status,
        lots.status AS original_status,
        lots.price_range_min,
        lots.price_range_max,
        lots.lot_latitude,
        lots.lot_longitude,
        lots.lot_width,
        lots.lot_depth,
        lots.lot_square_meters,
        lots.lot_corner,
        lots.lot_irregular,
        lots.polygon_coords,
		lots.lot_pdf_link,
		lots.lot_image_link,
		lots.lot_custom_form_link,
		lots.builder_lot,
		lots.lot_builder_email_address,
        lots.lot_builder_name,
        lots.lot_builder_phone,
		lots.lot_duplex_possible,
		lots.lot_reservable,
		lots.lot_reservable_date,
		lots.lot_message,
		lots.lot_coming_soon_message,
        stages.stage_pdf_file,
        stages.stage_id,
        stages.stage_number,
        stages.plan_subdivision_pdf,
        stages.bushfire_attack_level_pdf,
        stages.engineering_plan_pdf,
        stages.plan_fill_pdf,
        stages.covenants_fending_guidelines_pdf,
		precincts.development_id
        ', FALSE);

		$this->db->select('IF(lots.lot_corner = 1, "Corner", "Normal") AS type', FALSE);
		$this->db->select('lots.lot_irregular AS irregular');
		if($development->search_by_lot_price == 1){
			$this->db->select('CONCAT(CEIL(price_range_min/1000), "K") AS price_range_min, , CONCAT(CEIL(price_range_max/1000), "K") AS price_range_max ', FALSE);
		}
		else{
			$this->db->select('"n/a" AS price_range_min, "n/a" AS price_range_max', FALSE);
		}
        $this->db->select('lots.price_range_min AS price_range_min_full');
        $this->db->select('lots.price_range_max AS price_range_max_full');
        $this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
        $this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
		$this->db->where('stages.stage_hide_completely !=', '1');
		$this->db->where_not_in('lots.status', array('Hidden', 'Unlisted'));
        $this->db->order_by('lots.lot_id', 'asc');
        
        $temp_lots_array = $this->db->get_where('lots', array('precincts.development_id' => $development_id))->result_array(); 
		$lotsarray= array_map(function($v) use ($hide_price_coming_soon_lots){
			// START
			// if status is "Coming Soon" and the development is set to "Hide Prices on Coming Soon Lots" equal to "Yes" then overwrite the price to Zero
			if($v['status'] == 'Coming Soon' && $hide_price_coming_soon_lots == 1){
				$v['price_range_min_full'] = '0';
				$v['price_range_max_full'] = '0';
				$v['price_range_min'] = ($v['price_range_min']) == 'n/a'? $v['price_range_min']:'0';
				$v['price_range_max'] = ($v['price_range_min']) == 'n/a'? $v['price_range_min']:'0';
			}
			// END
			if(empty($v['lot_reservable_date'])){
				unset($v['lot_reservable_date']);
			}
			if(empty($v['lot_message'])){
				unset($v['lot_message']);
			}
			if(empty($v['lot_coming_soon_message'])){
				unset($v['lot_coming_soon_message']);
			}
			if(empty($v['plan_subdivision_pdf'])){
				unset($v['plan_subdivision_pdf']);
			}
			if(empty($v['bushfire_attack_level_pdf'])){
				unset($v['bushfire_attack_level_pdf']);
			}
			if(empty($v['engineering_plan_pdf'])){
				unset($v['engineering_plan_pdf']);
			}
			if(empty($v['plan_fill_pdf'])){
				unset($v['plan_fill_pdf']);
			}
			if(empty($v['covenants_fending_guidelines_pdf'])){
				unset($v['covenants_fending_guidelines_pdf']);
			}
			if(empty($v['lot_builder_email_address'])){
				unset($v['lot_builder_email_address']);
			}
			if(empty($v['lot_builder_name'])){
				unset($v['lot_builder_name']);
			}
			if(empty($v['lot_builder_phone'])){
				unset($v['lot_builder_phone']);
			}
			if(empty($v['lot_pdf_link'])){
				unset($v['lot_pdf_link']);
			}
			if(empty($v['lot_image_link'])){
				unset($v['lot_image_link']);
			}
			if(empty($v['lot_custom_form_link'])){
				unset($v['lot_custom_form_link']);
			}
			return $v;
		}, $temp_lots_array);
		
        foreach($lotsarray as $index => $row){
            $lotsarray[$index]['lot_width']                         = round($row['lot_width'],2);
            $lotsarray[$index]['development_id']                    = $development_id;
            $lotsarray[$index]['development_uri']                   = $development_uri;
            $lotsarray[$index]['number_of_house_and_land_packages'] = $this->getTotalLotPackages($lotsarray[$index]['lot_id']);
        }

        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($lotsarray));
    }

	/**
	 * This function will return the house matches for given lot 
	 * @param int $lot_id
	 * @return int
	 */
	private function getTotalLotPackages($lot_id)
	{
		$this->db->select('house_lot_packages.house_lot_package_id');
		$this->db->where('house_lot_packages.active = 1');
		$this->db->where('house_lot_packages.lot_id', $lot_id);
		$houses_packages = $this->db->get('house_lot_packages')->result_array();
		return count($houses_packages);
	}

	function lot_statuses($development_id)
    {
        $this->load->model('Model_development', '', TRUE);
        $this->load->model('Model_house', '', TRUE);
        $development    = $this->Model_development->getDevelopment($development_id);
        //DB Query - Get Lots data for lots from the development (development_id)
        $this->db->select('
        lots.lot_id,
        lots.lot_number,
        IF(stages.stage_hide_completely = 1, \'Hidden\', lots.status) AS status
        ', FALSE);

        $this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
        $this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
		$this->db->where('lots.status !=', 'Hidden');
        $this->db->where('lots.status !=', 'Unlisted');
		$this->db->where('stages.stage_hide_completely !=', '1');
        $this->db->order_by('lots.lot_id', 'asc');
        
        $lotsarray = $this->db->get_where('lots', array('precincts.development_id' => $development_id))->result_array();

        $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($lotsarray));
    }

	function lotsearch($stage_id)
	{
		$this->db->join('stages', 'stages.stage_id = lots.stage_id');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id');
		/*$this->db->where_in('lots.status', array('Available', 'Sold'));   */
		$this->db->where('stages.stage_hide_from_api', 0);
        $this->db->where_in('lots.status', array('Available', 'Coming Soon')); 
		$this->db->where('lots.stage_id', $stage_id);
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where('stages.stage_hide_completely !=', '1');

		$filter_fields = array(
			'min_price'     => 'price_range_min >=',
			'min_lot_width' => 'lot_width >=',
			'max_lot_width' => 'lot_width <=',
            'min_lot_depth' => 'lot_depth >=',
            'max_lot_depth' => 'lot_depth <=',
			'min_lot_size'  => 'lot_square_meters >=',
			'max_lot_size'  => 'lot_square_meters <='
		);
		$form_data = $this->input->get();
		if($form_data){
			foreach($filter_fields as $field_id => $db_field){
				if(isset($form_data[$field_id]) && $form_data[$field_id]){
					$this->db->where($db_field, $form_data[$field_id]);
				}
			}
			if(isset($form_data['max_price']) && $form_data['max_price']){
				$max_value = (float)$form_data['max_price'];
				$this->db->where("( price_range_min <= {$max_value} OR price_range_max <= {$max_value})");
			}
		}
		$lotsarray = $this->db->get('lots')->result_array();

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($lotsarray));
	}

	function lotsearchstages($development_id)
	{
		$stage_title    = ($this->multiplePrecincts($development_id))? 'CONCAT(precincts.precinct_number, "-", stages.stage_number)': 'stages.stage_number';
		$this->db->select("stages.stage_id, stages.stage_number, {$stage_title} AS stage_title", FALSE);
		$this->db->select('stages.stage_code, stages.stage_name, stages.stage_center_latitude, stages.stage_center_longitute, stages.stage_zoomlevel, ');
		$this->db->select('COUNT(lots.lot_id) AS matches ');
		$this->db->join('stages', 'stages.stage_id = lots.stage_id');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id');
	/*	$this->db->where_in('lots.status', array('Available', 'Sold'));*/
        $this->db->where_in('lots.status', array('Available', 'Coming Soon'));
		$this->db->where('precincts.development_id', $development_id);
		$this->db->where('stages.stage_hide_from_api', 0);
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where('stages.stage_hide_completely !=', '1');
		$this->db->group_by('stages.stage_id');
		$this->db->order_by('precincts.precinct_number');
		$this->db->order_by('stages.stage_number');

		$filter_fields = array(
			'min_price'     => 'price_range_min >=',
			'min_lot_width' => 'lot_width >=',
			'max_lot_width' => 'lot_width <=',
            'min_lot_depth' => 'lot_depth >=',
            'max_lot_depth' => 'lot_depth <=',
			'min_lot_size'  => 'lot_square_meters >=',
			'max_lot_size'  => 'lot_square_meters <='
		);
		$form_data = $this->input->get();
		if($form_data){
			foreach($filter_fields as $field_id => $db_field){
				if(isset($form_data[$field_id]) && $form_data[$field_id]){
					$this->db->where($db_field, $form_data[$field_id]);
				}
			}
			if(isset($form_data['max_price']) && $form_data['max_price']){
				$max_value = (float)$form_data['max_price'];
				$this->db->where("( price_range_min <= {$max_value} OR price_range_max <= {$max_value})");
			}
		}
		$stagesarray = $this->db->get('lots')->result_array();
        
        $volume = array();
                // Obtain a list of columns
        foreach ($stagesarray as $key => $row) {
            $volume[$key]  = $row['matches'];
        }

        // Sort the data with volume descending, edition ascending
        // Add $data as the last parameter, to sort by the common key
        array_multisort($volume, SORT_DESC, $stagesarray);
       
		$this->output->set_content_type('application/json');
		
		// START ANDY HACK  the below is a dirty hack from Andy to ensure that no data is returned if the user does not select any of the lot search check boxes in the mobile or tablet version. 
			if ( $_GET['min_price'] == "" && $_GET['max_price'] == "" && $_GET['min_lot_width'] == "" && $_GET['max_lot_width'] == "" && $_GET['min_lot_size'] == "" && $_GET['max_lot_size'] == "" && $_GET['min_lot_depth'] == "" && $_GET['max_lot_depth'] == "")
			 {  echo "[]"; }
			else { $this->output->set_output(json_encode($stagesarray)); }	
		// // END ANDY HACK
	}

	function soldlots($development_id)
	{
		//DB Query - Get Lots data for lots from the development (development_id)
		$this->db->join('stages', 'stages.stage_id = lots.stage_id');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id');
        $this->db->join('lot_icons', 'lot_icons.development_id = precincts.development_id', 'left'); 
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where('stages.stage_hide_completely !=', '1');
		$lotsarray = $this->db->get_where('lots', array('precincts.development_id' => $development_id, 'lots.status' => 'Sold'))->result_array(); 

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode($lotsarray));
	} 

	function lotsapi($development_id, $developmentkey = '')
	{
		$this->load->model('Model_development', '', TRUE);
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			echo json_encode(array());
			exit();
		}

		//DB Query - Get Lots data for lots from the development (development_id)
		$this->db->select('lots.lot_id, stages.stage_number, , precincts.precinct_number, lots.lot_number, lots.status, ');
		$this->db->select('lots.price_range_min, lots.price_range_max, lots.lot_width, lots.lot_square_meters, street_names.street_name ');
		$this->db->select('IF(lots.lot_corner = 1, "Corner", "Normal") AS type, lots.lot_irregular AS irregular', FALSE);
		$this->db->join('street_names', 'street_names.street_name_id = lots.street_name_id', 'left');
		$this->db->join('stages', 'stages.stage_id = lots.stage_id', 'left');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id', 'left');
		$this->db->where('precincts.development_id', $development_id);
		$this->db->where_in('lots.status', array('Available', 'Coming Soon', 'Sold'));
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where('stages.stage_hide_completely !=', '1');
		$lotsarray = $this->db->get('lots')->result_array(); 

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($lotsarray));
	}
    
	function searchsliders($development_id)
	{
        $this->db->select('developments.search_by_lot_price, developments.search_by_lot_width, developments.search_by_lot_size, ');
		$this->db->select('hd_settings.search_by_lot_depth, ');
		$this->db->select('MIN(lots.price_range_min) AS lot_price_range_min_cheapest, MAX(lots.price_range_max) AS lot_price_range_max_most_expensive, ');
        $this->db->select('MIN(lots.lot_width) AS lot_width_smallest, MAX(lots.lot_width) AS lot_width_largest, ');
		$this->db->select('MIN(lots.lot_depth) AS lot_depth_smallest, MAX(lots.lot_depth) AS lot_depth_largest, ');
		$this->db->select('MIN(lots.lot_square_meters) AS lot_square_meters_smallest, MAX(lots.lot_square_meters) AS lot_square_meters_largest');
		$this->db->join('stages', 'stages.stage_id = lots.stage_id');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id');
        $this->db->join('developments', 'developments.development_id = precincts.development_id');
		$this->db->join('hd_settings', 'hd_settings.development_id = precincts.development_id');
		$this->db->where('precincts.development_id', $development_id);
		$this->db->where_in('lots.status', array('Available', 'Coming Soon'));
		$this->db->where('lots.status !=', 'Hidden');
		$this->db->where('stages.stage_hide_completely !=', '1');
		$search_sliders = $this->db->get('lots')->row_array();
		if($search_sliders){
			$search_sliders['search_by_lot_price'] = ($search_sliders['search_by_lot_price'] == 1)? 'ON': 'OFF';
			$search_sliders['search_by_lot_width'] = ($search_sliders['search_by_lot_width'] == 1)? 'ON': 'OFF';
            $search_sliders['search_by_lot_size'] = ($search_sliders['search_by_lot_size'] == 1)? 'ON': 'OFF';
			$search_sliders['search_by_lot_depth'] = ($search_sliders['search_by_lot_depth'] == 1)? 'ON': 'OFF';
		}
        // Get LOT PRICE RANGE MIN & MAX values
        $search_sliders['lot_price_range_min_cheapest'] = floor($search_sliders['lot_price_range_min_cheapest']);
        $search_sliders['lot_price_range_max_most_expensive'] = ceil($search_sliders['lot_price_range_max_most_expensive']);
        // Get LOT WIDTH smallest & largest values
		$search_sliders['lot_width_smallest'] = floor($search_sliders['lot_width_smallest']);
		$search_sliders['lot_width_largest'] = ceil($search_sliders['lot_width_largest']);
        // Get LOT SQUARE METERS smallest & largest values
		$search_sliders['lot_square_meters_smallest'] = floor($search_sliders['lot_square_meters_smallest']/10)*10;
        $search_sliders['lot_square_meters_largest'] = ceil($search_sliders['lot_square_meters_largest']/10)*10;
        // Get LOT DEPTH smallest & largest values
        $search_sliders['lot_depth_smallest'] = floor($search_sliders['lot_depth_smallest']);
		$search_sliders['lot_depth_largest'] = ceil($search_sliders['lot_depth_largest']);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($search_sliders));
	}

	function amenities($development_id)
	{
		//DB Query - Get amenities for development_id
		$amenitiesarray = $this->db->get_where('amenities', array('development_id' => $development_id))->result_array();

		$this->output
		->set_content_type('application/json')
		->set_output(json_encode($amenitiesarray));
	}

	function externalamenities($development_id)
	{
		$this->load->model('Model_development', '', TRUE);
		$development    = $this->Model_development->getDevelopment($development_id);
		$amenitiesarray = array();
		if($development->show_external_amenities == 1){
			//DB Query - Get external amenities for development_id
			$this->db->select('external_amenity_id AS proposedExternalPlace_id, CASE WHEN external_amenities.e_amenity_icon != \'\' THEN external_amenities.e_amenity_icon ELSE external_amenity_types.e_amenity_pin END AS proposedExternalPlace_icon, e_amenity_name AS proposedExternalPlace_name, external_amenity_types.e_amenity_pin_width AS proposedExternalPlace_icon_width, external_amenity_types.e_amenity_pin_height AS proposedExternalPlace_icon_height, ', FALSE);
			$this->db->select('e_amenity_latitude AS proposedExternalPlace_latitude, e_amenity_longitude AS proposedExternalPlace_longitude, ');
			$this->db->select('e_amenity_description AS proposedExternalPlace_description, e_amenity_moreinfo_url AS proposedExternalPlace_moreinfolink ');
			$this->db->select('e_amenity_picture_url');
			$this->db->select('external_amenity_types.e_amenity_center_longitude, external_amenity_types.e_amenity_center_latitude, external_amenity_types.e_amenity_zoom_level, external_amenity_types.e_amenity_mobile_zoom_level, external_amenities.e_amenity_address');
			$this->db->select('external_amenity_types.external_amenity_type_id,  external_amenity_types.e_amenity_type_name, external_amenity_types.e_amenity_pin_height, external_amenity_types.e_amenity_pin_width, external_amenity_types.e_amenity_cluster_circle_url, external_amenity_types.e_amenity_cluster_circle_height, external_amenity_types.e_amenity_cluster_circle_width, external_amenity_types.e_amenity_cluster_text_hex_colour, external_amenity_types.e_amenity_cluster_text_size');
			$this->db->select('CASE WHEN external_amenity_types.e_amenity_type_name = "ALWAYSON" THEN 1 ELSE 0 END AS ALWAYSON ', FALSE);
			$this->db->join('external_amenity_types', 'external_amenity_types.external_amenity_type_id = external_amenities.e_amenity_type_id', 'left');
			$this->db->where('external_amenities.development_id', $development_id);
			$this->db->where('show_e_amenity', 1);
            $this->db->order_by('external_amenity_id','asc');
			$query  = $this->db->get('external_amenities');
            
			$amenitiesarray = $query->result_array();
		}
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($amenitiesarray));
	}

	/* This function will return the house matches for given lot.
	 */
	function housematches($development_id, $lot_id)
	{
		$this->load->model('Model_house', '', TRUE);

		$housematchesarray = $this->Model_house->getLotHouses($development_id, $lot_id);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($housematchesarray));
	}

	public function amenitycategories($development_id)
	{
		$this->load->model('Model_external_amenity_type', '', TRUE);
		$e_amenity_types = $this->Model_external_amenity_type->getExternalAmenityTypes($development_id, FALSE);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($e_amenity_types));
	}

	public function alwaysonexternalamenities($development_id)
	{
		$this->load->model('Model_external_amenity_type', '', TRUE);
		$e_amenity_types = $this->Model_external_amenity_type->getExternalAmenityTypes($development_id, TRUE);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($e_amenity_types));
	}

	public function externalamenitiesbytype($development_id, $e_amenity_type_id = 0)
	{
		$this->load->model('Model_development', '', TRUE);
		$development = $this->Model_development->getDevelopment($development_id);
		$e_amenities = array();
		if($development->show_external_amenities == 1){
			//DB Query - Get external amenities for development_id
			$this->db->select('external_amenity_id AS proposedExternalPlace_id, CASE WHEN external_amenities.e_amenity_icon != \'\' THEN external_amenities.e_amenity_icon ELSE external_amenity_types.e_amenity_pin END AS proposedExternalPlace_icon, e_amenity_name AS proposedExternalPlace_name, ', FALSE);
			$this->db->select('e_amenity_latitude AS proposedExternalPlace_latitude, e_amenity_longitude AS proposedExternalPlace_longitude, ');
			$this->db->select('e_amenity_description AS proposedExternalPlace_description, e_amenity_moreinfo_url AS proposedExternalPlace_moreinfolink ');
			$this->db->select('e_amenity_picture_url');
			$this->db->select('external_amenity_types.e_amenity_center_longitude, external_amenity_types.e_amenity_center_latitude, external_amenity_types.e_amenity_zoom_level, external_amenities.e_amenity_address');
			$this->db->join('external_amenity_types', 'external_amenity_types.external_amenity_type_id = external_amenities.e_amenity_type_id', 'left');
			$this->db->where('external_amenities.development_id', $development_id);
			$this->db->where('external_amenities.e_amenity_type_id', $e_amenity_type_id);
			$this->db->where('show_e_amenity', 1);
			$query  = $this->db->get('external_amenities');
			$e_amenities = $query->result_array();
		}

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($e_amenities));
	}

	public function radiusmarkers($development_id)
	{
		$this->load->model('Model_radius_marker', '', TRUE);
		$radius_markers = $this->Model_radius_marker->getRadiusMarkers($development_id);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($radius_markers));
	}

	public function linkeddevelopments($development_id)
	{
		$this->load->model('Model_development_link', '', TRUE);
		$linked_developments = $this->Model_development_link->getDevelopmentLinks($development_id);

		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode($linked_developments));
	}

	private function multiplePrecincts($development_id)
	{
		$this->load->model('Model_precinct', '', TRUE);
		$precincts = $this->Model_precinct->getPrecincts($development_id);
		return (count($precincts) > 1);
	}



	// ***** START - Andrew getdatainjson functions *****
	function mapovisminimarkers($development_id)
	{
		//DB Query - Get Development data for development_id
		$mapovisminimarkersarray = $this->db->get_where('mapovis_mini_markers', array('development_id' => $development_id))->result_array();                                

		$this->output->set_header('Content-Type: application/json; charset=utf-8');

		//echo $developmentarray in JSON format, without the opening [ and closing ]
		//echo str_replace(array('[', ']'), '', htmlspecialchars(json_encode($mapovisminimarkersarray), ENT_NOQUOTES));
        
        
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($mapovisminimarkersarray));
	}

	function mapovisminipolygon($development_id)
	{
		//DB Query - Get Development data for development_id
		//$mapovisminipolygonarray = $this->db->get_where('mapovis_mini_polygon', array('development_id' => $development_id))->result_array();                                
        $points = array();
        
        $this->db->select('mapovis_mini_polygon.polygon_coordinates');
        $this->db->where('mapovis_mini_polygon.development_id', $development_id);
        $query  = $this->db->get('mapovis_mini_polygon');
        $mapovisminipolygonarray = $query->result_array();
        
        if(count($mapovisminipolygonarray) == 0){
           echo json_encode($mapovisminipolygonarray); exit;    
        }
        
        $coordinate_str = $mapovisminipolygonarray[0]['polygon_coordinates'];
        $coordinate_str = trim($coordinate_str);
        
        $coordinates = explode(" ",$coordinate_str);
        
        for($i = 0; $i < count($coordinates);$i++){
            $latlng = explode(":",$coordinates[$i]);
            $latlng_pair = array('lat' => $latlng[0], 'lng' => $latlng[1]);
            array_push($points,$latlng_pair);
        }
        
		echo json_encode($points); exit;
        
	}

    
    
    function amenities_polygons($development_id)
    {

        //NOTE from ANDY to MARTHA: Seb wanted a multi-dimensional JSON array. Silly me was having trouble finding a way to produce that. My code is probably quite inefficient as I'm doing multiple DB queries
        // so I can produce the JSON in the correct structure. Martha, dont laugh at me! ;) Feel free to fix this up in the future.                                 

            // First I'm finding out what amenity polygons have been defined
            $this->db->select('amenities.amenity_id, amenities.development_id,amenities.amentiy_polygon_coords');
            $this->db->where('amenities.development_id', $development_id);
            $query  = $this->db->get('amenities');
            $polygons = $query->result_array();

        
            $this->output->set_content_type('application/json');
            $this->output->set_output(json_encode($polygons));

    }





	function mapovisminidirections($development_id)
	{
		//DB Query - get data for development_id
		$mapovisminidirectionsarray = $this->db->get_where('mapovis_mini_directions', array('development_id' => $development_id))->result_array();                                

		$this->output->set_header('Content-Type: application/json; charset=utf-8');      
        
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($mapovisminidirectionsarray));
        
	}
    
    function salesoffices($development_id)
    {
        //DB Query - get data for development_id
          $this->db->select('developments.sales_office_iframe_scrollbar, developments.sales_office_icon,developments.sales_office_latitude, developments.sales_office_longitude, developments.sales_office_title, developments.sales_office_html, developments.sales_office_googlemaps_directions_url,developments.sales_office_iframe_html');
          $this->db->where('developments.development_id', $development_id);
          $query  = $this->db->get('developments');
          $polygons = $query->result_array();

        
          $this->output->set_content_type('application/json');
          $this->output->set_output(json_encode($polygons));
        
    }

    function precincts($development_id)
    {
		//DB Query - Get Development data for development_id
		$developmentarray = $this->db->get_where('precincts', array('development_id' => $development_id))->result_array();                                

        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($developmentarray));
        
    }
    
    function precincts_polygons($development_id)
    {
        //DB Query - Get Development data for development_id
        $precincts_array = $this->db->get_where('precincts', array('development_id' => $development_id))->result_array();                                

        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($precincts_array));
        
    }

    function external_markers($development_id)
    {
        //DB Query - Get Development data for development_id
        $external_markers_array = $this->db->get_where('external_markers', array('development_id' => $development_id))->result_array();                                

        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($external_markers_array));
        
    }
    
	// ***** END - Andrew getdatainjson functions *****

    function all_house_and_land_packages($development_id, $edited_timestamp = NULL, $disabled_timestamp = NULL)
    {
		$this->load->model('Model_development', '', TRUE);
		$development        = $this->Model_development->getDevelopment($development_id);

		$pdf_file_url       = base_url('../mpvs/templates/lot_house_pdfs');
		$high_reso_file_url = base_url('../mpvs/images/high_reso_images');
		
		$image_base_url     = 'http://app.mapovis.com.au/mpvs/images/dev_houses';

		// determine the lot status to include depending on the development settings
		if((int)$development->land_show_sold_lots == 2){
			$lot_status = array('Available', 'Coming Soon');
		}
		elseif($development->land_show_sold_lots == 1){
			$lot_status = array('Available', 'Coming Soon', 'Sold','Deposited','On Hold');
		}
		else{
			$lot_status = array('Available', 'Coming Soon','Deposited','On Hold');
		}

        $this->db->select('
        house_lot_packages.house_lot_package_id AS package_id,
		house_lot_packages.created_timestamp,
		house_lot_packages.updated_timestamp,
		house_lot_packages.disabled_timestamp,
		houses.house_name,
		builders.builder_name,
		lots.lot_number,
		lots.lot_square_meters AS lot_size,
		lots.lot_width AS lot_frontage,
		lots.lot_depth,
		houses.house_size,
		houses.house_levels,
		houses.house_bedrooms,
		houses.house_bathrooms,
		houses.house_garages,
		house_lot_packages.description AS house_description,
		house_lot_packages.house_lot_price AS package_price,
		house_lot_packages.file_name AS package_brochure,
		"" AS facade_thumbnail,
		"" AS facade_large,
		package_high_resolution_images.facade_image AS facade_ultra_high_resolution,
		package_high_resolution_images.floorplan_image AS floorplan_ultra_high_resolution
        ', FALSE);

		$this->db->join('lots', 'lots.lot_id = house_lot_packages.lot_id');
		$this->db->join('stages', 'stages.stage_id = lots.stage_id');
		$this->db->join('precincts', 'precincts.precinct_id = stages.precinct_id');
		$this->db->join('houses', 'houses.house_id = house_lot_packages.house_id');
		$this->db->join('builders', 'builders.builder_id = houses.house_builder_id', 'left');
		$this->db->join('package_high_resolution_images', 'package_high_resolution_images.package_id = house_lot_packages.house_lot_package_id', 'left');

		$this->db->where_in('lots.status', $lot_status);
		$this->db->where('precincts.development_id', $development_id);

		// for packages edited after a particular time
		if($edited_timestamp){
			$this->db->where('house_lot_packages.updated_timestamp >=', $edited_timestamp);
		}

		// for packages disabled after a particular time
		if($disabled_timestamp){
			$this->db->where('house_lot_packages.disabled_timestamp >=', $disabled_timestamp);
		}
		else{
			$this->db->where('house_lot_packages.active', 1);
		}
		
        $packages        = $this->db->get('house_lot_packages')->result_array(); 
		$sorted_packages = array();
		if(count($packages)){
			foreach($packages as $package){
				$package['package_brochure']                = ($package['package_brochure'])? $pdf_file_url.'/'.$package['package_brochure']: '';
				$package['facade_ultra_high_resolution']    = ($package['facade_ultra_high_resolution'])? $high_reso_file_url.'/'.$package['facade_ultra_high_resolution']: '';
				$package['floorplan_ultra_high_resolution'] = ($package['floorplan_ultra_high_resolution'])? $high_reso_file_url.'/'.$package['floorplan_ultra_high_resolution']: '';
				$sorted_packages[$package['package_id']]    = $package;
			}
			$packages_ids    = array_keys($sorted_packages);

			// images for the house and land packages
			$this->db->select('house_images.*');
			$this->db->select('house_lot_packages.house_lot_package_id, house_images.*');

			$this->db->join('house_lot_packages', 'house_images.house_id = house_lot_packages.house_id');
			$this->db->join('package_order_images', 'package_order_images.house_image_id = house_images.house_image_id AND package_order_images.package_id = house_lot_packages.house_lot_package_id ', 'left');

			$this->db->where('(package_order_images.file_order = 1 OR (package_order_images.file_order is null AND house_images.file_order = 1)) ');
			$this->db->where_in('house_lot_packages.house_lot_package_id', $packages_ids);
			$this->db->where('house_images.file_type', 'facade');

			$this->db->group_by('house_lot_packages.house_lot_package_id');
			$this->db->order_by('house_images.file_order', 'asc');

			$query  = $this->db->get('house_images');
			$images = ($query)? $query->result(): array();
			foreach($images as $image){
				$sorted_packages[$image->house_lot_package_id]['facade_thumbnail'] = $image_base_url.'/'.$image->file_name;
				$sorted_packages[$image->house_lot_package_id]['facade_large'] = $image_base_url.'/'.$image->original_file_name;
			}
		}

		$this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($sorted_packages));
    }

}
?>