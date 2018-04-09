<?php
if( ! defined('BASEPATH')) exit('No direct script access allowed');
class apihouseandland extends CI_Controller {

	function __construct(){
		parent::__construct(); 
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
	}

	function index($developmentkey = '')
	{
		$form_data = $this->input->post();
		if(!$form_data){
			$form_data = array();
		}
		$data      = array(
			'form_data'         => $form_data,
			'developmentkey'    => $developmentkey,
			'development_id'    => 8,
		);
		$this->load->view('houseandland/index', $data);
	}

	function houseandland($development_id, $developmentkey = '')
	{
		$form_data   = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development = $this->Model_development->getDevelopment($development_id);
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$lots          = array();
			$houses        = array();
			$houses_images = array();
			$nofilters_packages = 0;
		}
		else{
			$results       = $this->Model_lot->getHouseAndLandSearchResults($development_id, $form_data);
			$lots          = $results['lots'];
			$houses        = $results['houses'];
			$houses_images = $results['houses_images'];
			$nofilters_packages = $this->Model_lot->getHouseAndLandResultsNoSearch($development_id, FALSE);
		}
		$data        = array(
			'houses'            => $houses,
			'houses_images'     => $houses_images,
			'lots'              => $lots,
			'development'       => $development,
			'search_parameters' => $this->getSearchParameters($development_id),
			'error_type'        => ($nofilters_packages > 0)? 'searchFailed': 'noPackages',
			'form_data'         => $form_data
		);
		$page_view = $this->Model_development->getHouseAndLandView($development->house_and_land_view);
		$this->load->view($page_view, $data);
	}

	function houseandlandbyhs($development_id, $developmentkey = '')
	{
		$this->load->library('mapovis_lib');
		$this->load->model('Model_stage', '', TRUE);
		$form_data   = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development   = $this->Model_development->getDevelopment($development_id);
		$stages        = $this->Model_stage->getStagesActiveLots($development_id, TRUE);
		$stages_format = array();
		foreach($stages as $stage){
			$stage->word_of_number           = $this->mapovis_lib->convert_number_to_words($stage->stage_number);
			$stages_format[$stage->stage_id] = $stage;
		}
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$results       = array();
			$lots          = array();
			$houses        = array();
			$houses_images = array();
			$packages_images = array();
			$nofilters_packages = 0;
		}
		else{
			$results       = $this->Model_lot->getHouseAndLandSearchResults($development_id, $form_data, FALSE, array_keys($stages_format), TRUE);
			$lots          = $results['lots'];
			$houses        = $results['houses'];
			$houses_images = $results['houses_images'];
			$packages_images = $results['packages_images'];
			$nofilters_packages = $this->Model_lot->getHouseAndLandResultsNoSearch($development_id, TRUE);
		}
		$data        = array(
			'houses'            => $houses,
			'houses_images'     => $houses_images,
			'packages_images'   => $packages_images,
			'lots'              => $lots,
			'development'       => $development,
			'search_parameters' => $this->getSearchParameters($development_id, TRUE),
			'stages'            => $stages_format,
			'error_type'        => ($nofilters_packages > 0)? 'searchFailed': 'noPackages',
			'form_data'         => $form_data
		);
		$page_view = $this->Model_development->getHouseAndLandView($development->house_and_land_view);
		$this->load->view($page_view, $data);
	}

	function houseandlandboth($development_id, $developmentkey = '')
	{
		$this->load->library('mapovis_lib');
		$this->load->model('Model_stage', '', TRUE);
		$form_data         = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$land_filters = $form_data;
		if(isset($form_data['land_size_from'])){
			$land_filters['lot_square_meters_from'] = isset($form_data['land_size_from'])?$form_data['land_size_from']:FALSE;
			$land_filters['lot_square_meters_to']   = isset($form_data['land_size_to'])?$form_data['land_size_to']:FALSE;
			$land_filters['stage_id']               = isset($form_data['l_stage_id'])?$form_data['l_stage_id']:FALSE;
		}
		$development       = $this->Model_development->getDevelopment($development_id);
		$search_parameters = $this->getSearchParameters($development_id);
		
		// validate if the access key is valid
		if($this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$land_stages         = $this->Model_lot->getLandSearchResults($development_id, $land_filters);
			$land_nofilters_lots = $this->Model_lot->getLandResultsNoSearch($development_id);

			$hnl_stages        = $this->Model_stage->getStagesActiveLots($development_id, TRUE);
			$hnl_stages_format = array();
			foreach($hnl_stages as $stage){
				$stage->word_of_number               = $this->mapovis_lib->convert_number_to_words($stage->stage_number);
				$hnl_stages_format[$stage->stage_id] = $stage;
			}

			$results            = $this->Model_lot->getHouseAndLandSearchResults($development_id, $form_data, FALSE, array_keys($hnl_stages_format), TRUE);
			$hnl_lots           = $results['lots'];
			$hnl_houses         = $results['houses'];
			$houses_images      = $results['houses_images'];
			$packages_images    = $results['packages_images'];
			$nofilters_packages = $this->Model_lot->getHouseAndLandResultsNoSearch($development_id, TRUE);
		}
		else{
			$land_stages         = array();
			$land_nofilters_lots = array();
			$hnl_stages_format   = array();
			$hnl_lots            = array();
			$hnl_houses          = array();
			$houses_images       = array();
			$packages_images     = array();
			$nofilters_packages  = array();
		}
			
		$data        = array(
			'land_stages'       => $land_stages,
			'development'       => $development,
			'search_parameters' => $search_parameters,
			'form_data'         => $form_data,
			'land_error_type'   => ($land_nofilters_lots > 0)? 'searchFailed': 'noLand',
			'available_lots'    => $land_nofilters_lots,

			'hnl_stages'        => $hnl_stages_format,
			'hnl_lots'          => $hnl_lots,
			'hnl_houses'        => $hnl_houses,
			'houses_images'     => $houses_images,
			'packages_images'   => $packages_images,
			'hnl_error_type'    => ($nofilters_packages > 0)? 'searchFailed': 'noPackages',
		);

		$page_view = $this->Model_development->getLandHouseAndLandView($development->land_house_and_land_view);
		$this->load->view($page_view, $data);
	}

	function houseandlandbylots($development_id, $developmentkey = '')
	{
		$this->load->library('mapovis_lib');
		$this->load->model('Model_stage', '', TRUE);
		$form_data   = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development   = $this->Model_development->getDevelopment($development_id);
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$lots          = array();
			$houses_images = array();
			$stages_format = array();
			$nofilters_packages = 0;
		}
		else{
			$stages            = $this->Model_stage->getStagesActiveLots($development_id, TRUE);
			$stages_format     = array();
			foreach($stages as $stage){
				$stage->word_of_number           = $this->mapovis_lib->convert_number_to_words($stage->stage_number);
				$stages_format[$stage->stage_id] = $stage;
			}
			$results       = $this->Model_lot->getHouseAndLandSearchResultsActiveLots($development_id, $form_data);
			$lots          = $results['lots'];
			$houses_images = $results['houses_images'];
			$nofilters_packages = $this->Model_lot->getHouseAndLandResultsNoSearch($development_id, TRUE);
		}
		$data        = array(
			'houses_images'     => $houses_images,
			'lots'              => $lots,
			'development'       => $development,
			'developmentkey'    => $developmentkey,
			'stages'            => $stages_format,
			'search_parameters' => $this->getSearchParameters($development_id, TRUE),
			'error_type'        => ($nofilters_packages > 0)? 'searchFailed': 'noPackages',
			'form_data'         => $form_data
		);
		$page_view = $this->Model_development->getHouseAndLandView($development->house_and_land_view);
		$this->load->view($page_view, $data);
	}

	function houseandlandlot($development_id, $developmentkey = '', $package_id = '')
	{
		$development       = $this->Model_development->getDevelopment($development_id);
		$houses_images     = array();
		$lot_house         = FALSE;
		$lot               = FALSE;

		// validate if the access key is valid
		if($this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$this->load->model('Model_house_lot_package', '', TRUE);
			$this->load->model('Model_house_image', '', TRUE);

			$lot_house = $this->Model_house_lot_package->getHouseLotPackageById($package_id, $development_id);
			if($lot_house){
				$lot            = $this->Model_lot->getDevelopmentLot($development_id, $lot_house->lot_id);
				$images_results = $this->Model_house_image->getFirstHousesImages(array($lot_house->house_id), array($lot_house->lot_id), array('file_type' => 'facade', 'group_by_lot' => TRUE));
				foreach($images_results as $image){
					$houses_images[$image->house_lot_package_id] = $image;
				}
			}
		}
		$data        = array(
			'lot'            => $lot,
			'lot_house'      => $lot_house,
			'houses_images'  => $houses_images,
			'development'    => $development,
		);
		$page_view = $this->Model_development->getHouseAndLandView($development->house_and_land_view);
		$this->load->view($page_view.'__lot', $data);
	}

	function houseandlandbybuilder($development_id, $developmentkey = '')
	{
		$this->load->library('mapovis_lib');
		$this->load->model('Model_builder', '', TRUE);
		$this->load->model('Model_stage', '', TRUE);
		$form_data   = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development   = $this->Model_development->getDevelopment($development_id);
		$builders      = $this->Model_builder->getBuilders();
		$builder_formated = array();
		$nofilters_packages = 0;
		foreach($builders as $builder){
			$builder_formated[$builder->builder_id]           = $builder;
			$builder_formated[$builder->builder_id]->packages = array();
		}
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$packages      = array();
			$houses_images = array();
		}
		else{
			$stages            = $this->Model_stage->getStagesActiveLots($development_id, TRUE);
			$stages_format     = array();
			foreach($stages as $stage){
				$stage->word_of_number           = $this->mapovis_lib->convert_number_to_words($stage->stage_number);
				$stages_format[$stage->stage_id] = $stage;
			}
			$results       = $this->Model_lot->getHouseAndLandByBuilderSearchResults($development_id, $form_data, array_keys($stages_format), TRUE);
			$packages      = $results['packages'];
			$houses_images = $results['houses_images'];
			$nofilters_packages = $this->Model_lot->getHouseAndLandResultsNoSearch($development_id, TRUE);
		}
		foreach($packages as $package){
			$builder_formated[$package->builder_id]->packages[] = $package;
		}
		$data        = array(
			'packages'          => $packages,
			'error_type'        => ($nofilters_packages > 0)? 'searchFailed': 'noPackages',
			'houses_images'     => $houses_images,
			'development'       => $development,
			'search_parameters' => $this->getSearchParameters($development_id, TRUE),
			'builders'          => $builder_formated,
			'form_data'         => $form_data
		);
		$page_view = $this->Model_development->getHouseAndLandView($development->house_and_land_builder_view);
		$this->load->view($page_view, $data);
	}

	function houseandland_full($development_id, $developmentkey = '')
	{
		$form_data         = $this->input->post();
		if(!$form_data){
			$form_data = array();
		}
		$development       = $this->Model_development->getDevelopment($development_id);
		$search_parameters = $this->Model_house->houseLandSearchParameters($development_id);
		// validate if the access key is valid
		if(!$this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$lots          = array();
			$houses        = array();
			$houses_images = array();
		}
		else{
			$results       = $this->Model_lot->getHouseAndLandSearchResults($development_id, $form_data);
			$lots          = $results['lots'];
			$houses        = $results['houses'];
			$houses_images = $results['houses_images'];
		}
		$data        = array(
			'houses'            => $houses,
			'houses_images'     => $houses_images,
			'lots'              => $lots,
			'development'       => $development,
			'developmentkey'    => $developmentkey,
			'search_parameters' => $search_parameters,
			'form_data'         => $form_data,
			'page_view'         => $this->Model_development->getHouseAndLandView($development->house_and_land_view)
		);
		$this->load->view('houseandland/houseandland-full', $data);
	}

	function template($development_id, $developmentkey = '', $repeater = '')
	{
		$this->load->library('mapovis_lib');
		$this->load->model('Model_stage', '', TRUE);
		$form_data         = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development       = $this->Model_development->getDevelopment($development_id);
		$stages            = $this->Model_stage->getStagesActiveLots($development_id);
		$stages_format     = array();
		foreach($stages as $stage){
			$stage->word_of_number           = $this->mapovis_lib->convert_number_to_words($stage->stage_number);
			$stages_format[$stage->stage_id] = $stage;
		}
		$search_parameters = $this->getSearchParameters($development_id);
		$data              = array(
			'development'       => $development,
			'developmentkey'    => $developmentkey,
			'repeater'    		=> $repeater,
			'search_parameters' => $search_parameters,
			'form_data'         => $form_data,
			'stages'            => $stages_format,
		);
		$page_view = $this->Model_development->getTemplateView($development->template_view);
		$this->load->view($page_view, $data);
	}

	private function getSearchParameters($development_id, $unlisted = FALSE){
		$hnl_search_parameters  = $this->Model_house->houseLandSearchParameters($development_id, $unlisted);
		$land_search_parameters = $this->Model_lot->landSearchParameters($development_id, $unlisted, TRUE);
		return array_merge($hnl_search_parameters, $land_search_parameters);
	}

	public function featuredpackage($development_id, $developmentkey = '', $package_id = NULL)
	{
		$this->load->model(array('Model_house_lot_package', 'Model_house_image', 'Model_house_lot_order_image'));
		$form_data   = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development = $this->Model_development->getDevelopment($development_id);
		// validate if the access key is valid
		$package           = FALSE;
		$house_images      = array();
		$floorplans_images = array();
		if($this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$package           = $this->Model_house_lot_package->getHouseLotPackageById($package_id, $development_id);
			if($package){
				$floorplans_images = $this->Model_house_image->getHouseImages($package->house_id, array('file_type' => 'floor_plan'));
				$limit             = (11-count($floorplans_images));
				$house_images      = $this->Model_house_lot_order_image->getHouseLotImages($package->house_id, $package->lot_id, $limit);
			}
		}
		$data        = array(
			'package'            => $package,
			'house_images'       => $house_images,
			'floorplans_images'  => $floorplans_images,
			'development'        => $development,
		);
		$page_view = $this->Model_development->getFeaturedPackageView($development->featured_package_view);
		$this->load->view($page_view, $data);
	}
}
?>