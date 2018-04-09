<?php
if( ! defined('BASEPATH')) exit('No direct script access allowed');
class apiland extends CI_Controller {

	function __construct(){
		parent::__construct(); 
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
	}

	function index($developmentkey = '')
	{
		$development = $this->Model_development->getDevelopment(8);
		$form_data   = $this->input->post();
		if(!$form_data){
			$form_data = array();
		}
		$data        = array(
			'form_data'         => $form_data,
			'developmentkey'    => $developmentkey,
			'development'       => $development,
		);
		$this->load->view('land/index', $data);
	}

	function land($development_id, $developmentkey = '')
	{
		$form_data         = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development       = $this->Model_development->getDevelopment($development_id);
		$search_parameters = $this->getSearchParameters($development_id);
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$active_stages    = array();
			$nofilters_lots   = 0;
		}
		else{
			$active_stages    = $this->Model_lot->getLandSearchResults($development_id, $form_data);
			$nofilters_lots   = $this->Model_lot->getLandResultsNoSearch($development_id);
		}
			
		$data        = array(
			'stages'            => $active_stages,
			'development'       => $development,
			'developmentkey'    => $developmentkey,
			'search_parameters' => $search_parameters,
			'form_data'         => $form_data,
			'error_type'        => ($nofilters_lots > 0)? 'searchFailed': 'noLand',
			'available_lots'    => $nofilters_lots,
		);
		$dev_land_view = (isset($form_data['land-view-template']) && $form_data['land-view-template'] == 2)? $development->land_view_second: $development->land_view;
		$page_view     = $this->Model_development->getLandView($dev_land_view);
		$this->load->view($page_view, $data);
	}

	function landlot($development_id, $developmentkey = '', $lot_id = '')
	{
		$development       = $this->Model_development->getDevelopment($development_id);
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$lot = FALSE;
		}
		else{
			$lot = $this->Model_lot->getDevelopmentLot($development_id, $lot_id);
		}
		$data        = array(
			'lot'            => $lot,
			'development'    => $development,
		);
		$page_view = $this->Model_development->getLandView($development->land_view);
		$this->load->view($page_view.'__lot', $data);
	}

	function landwithhouses($development_id, $developmentkey = '')
	{
		$form_data   = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development = $this->Model_development->getDevelopment($development_id);
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$houses        = array();
			$houses_images = array();
		}
		else{
			$active_stages = $this->Model_lot->getLandSearchResults($development_id, $form_data);
			// get the houses for those lots
			$results       = $this->Model_lot->getHouseAndLandSearchResults($development_id, $form_data);
			$houses        = $results['houses'];
			$houses_images = $results['houses_images'];
		}
		$data        = array(
			'stages'            => $active_stages,
			'houses'            => $houses,
			'houses_images'     => $houses_images,
			'development'       => $development,
			'search_parameters' => $this->getSearchParameters($development_id),
			'form_data'         => $form_data
		);
		$dev_land_view = (isset($form_data['land-view-template']) && $form_data['land-view-template'] == 2)? $development->land_view_second: $development->land_view;
		$page_view     = $this->Model_development->getLandView($dev_land_view);
		$this->load->view($page_view, $data);
	}

	function land_full($development_id, $developmentkey = '')
	{
		$form_data         = $this->input->post();
		if(!$form_data){
			$form_data = array();
		}
		$development       = $this->Model_development->getDevelopment($development_id);
		$search_parameters = $this->Model_lot->landSearchParameters($development_id);
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$active_stages = array();
		}
		else{
			$active_stages = $this->Model_lot->getLandSearchResults($development_id, $form_data);
		}
			
		$data        = array(
			'stages'            => $active_stages,
			'development'       => $development,
			'developmentkey'    => $developmentkey,
			'search_parameters' => $search_parameters,
			'form_data'         => $form_data,
			'page_view'         => $this->Model_development->getLandView($development->land_view)
		);
		$this->load->view('land/land-full', $data);
	}

	private function getSearchParameters($development_id){
		$hnl_search_parameters  = $this->Model_house->houseLandSearchParameters($development_id);
		$land_search_parameters = $this->Model_lot->landSearchParameters($development_id);
		return array_merge($hnl_search_parameters, $land_search_parameters);
	}

}
?>