<?php
if( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('buildermain_controller.php');

class Builder extends BuilderMain_Controller {

	function __construct() 
	{
		parent::__construct(); 
		if(!$this->session->userdata('builder_logged_id'))
		{
			redirect('builderlogin');
			exit;
		}
		$this->load->model('Model_user', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
		$this->load->model('Model_builder', '', TRUE);
		$this->load->model('Model_builder_user', '', TRUE);
		$this->load->model('Model_builder_sales_person', '', TRUE);
	}

	function index()
	{
		$this->dashboard();
	}

	function dashboard()
	{
		$this->load->model('Model_config', '', TRUE);

		$current_user     = $this->Model_user->getBuilderLogged();
		$welcome_message  = $this->Model_config->getWelcomeMessageBuilder($current_user);
		$builder          = $this->Model_builder_user->getLoggedUserBuilder($current_user->user_id);
		$data             = array(
			'current_user'    => $current_user,
			'welcome_message' => $welcome_message,
			'builder'         => $builder,
		);
		$this->load_template('builder/view_dashboard', $data);
	}

	function managehouses()
	{
		$current_user = $this->Model_user->getBuilderLogged();
		$builder      = $this->Model_builder_user->getLoggedUserBuilder($current_user->user_id);
		if(!$builder){
			$this->session->set_flashdata('errormessage', 'You need to be assigned to a Builder. Please contact the aministrator');
			redirect('builder/managehouses/dashboard');
		}
		$houses       = $this->Model_house->getBuilderHouses($current_user->user_id);
		$data         = array(
			'builder' => $builder,
			'houses'  => $houses,
		);
		$footer = array('builder/footer_standard', 'builder/view_houses_customjs', 'builder/view_managehouses_customjs');
		$this->load_template('builder/view_managehouses', $data, $footer);
	}

	function viewhouse($house_id)
	{
		$this->load->model('Model_house_image', '', TRUE);
		$house     = $this->Model_house->getHouse($house_id);
		$data      = array(
			'house_id'     => $house_id,
			'house'        => $house,
			'house_images' => $this->Model_house_image->getHouseImages($house_id, array('file_type' => 'facade')),
			'floor_plans'  => $this->Model_house_image->getHouseImages($house_id, array('file_type' => 'floor_plan')),
			'gallery'      => $this->Model_house_image->getHouseImages($house_id, array('file_type' => 'gallery')),
		);
		$this->load_ajaxtemplate('builder/view_edithouse', $data);
	}

	function updatehouse($house_id)
	{
		$this->load->model('Model_house', '', TRUE);

		$this->load->library('form_validation');
		$this->form_validation->set_rules('house_name', 'House Name', 'trim|required|xss_clean');

		if($this->form_validation->run() != FALSE){
			$form_data = $this->input->post();
			if($this->Model_house->updateHouse($house_id, $form_data)){
				$this->session->set_flashdata('alertmessage', 'The house was updated successfully.');
			}
			else{
				$this->session->set_flashdata('errormessage', 'It was not possible to update the house, please make sure the data is correct.');
			}
		}
		else{
			$this->session->set_flashdata('errormessage', 'The house name was not provided, please try again.');
		}
		redirect('builder/managehouses');
	}

	function validatehousename()
	{
		$house_name     = $this->input->post('house_name');
		$development_id = null;
		$house_id       = $this->input->post('house_id');
		$builder        = $this->Model_builder_user->getLoggedUserBuilder();
		if(!$builder){
			echo json_encode('You need to be assigned to a Builder. Please contact the aministrator');
			exit();
		}
		$builder_id     = $builder->builder_id;
		if($house_id && $house = $this->Model_house->getHouse($house_id)){
			$development_id = $house->development_id;
			if($builder->builder_id != $house->house_builder_id){
				echo json_encode('This house belongs to a differnt builder.');
				exit();
			}
		}
		$housematch     = $this->Model_house->validateDuplicate($house_name, $builder_id, $development_id, $house_id);
		if(!$housematch){
			$result = true;
		}
		else{
			if($housematch->development_id == null){
				$result = 'A house with this name already exists in the MAPOVIS directory.';
			}
			else{
				$result = 'A custom house with this name is already linked to a development.';
			}
		}
		echo json_encode($result);
		exit();
	}

	function uploadoriginalimage()
	{
		$this->load->model('Model_house_image', '', TRUE);
		$result = $this->Model_house_image->uploadoriginalimage();
		echo json_encode($result);
		exit();
	}

	function addhouse()
	{
		$builder = $this->Model_builder_user->getLoggedUserBuilder();
		if(!$builder){
			echo json_encode('You need to be assigned to a Builder. Please contact the aministrator');
			exit();
		}
		$this->load->library('form_validation');
		$this->form_validation->set_rules('house_name', 'House Name', 'trim|required|xss_clean');
		if($this->form_validation->run() != FALSE){
			$form_data                     = $this->input->post();
			$form_data['development_id']   = null;
			$form_data['house_builder_id'] = $builder->builder_id;
			$redirecto_to                  = (isset($form_data['action_continue']) && $form_data['action_continue'] == 1)? 'addhouse': 'managehouses';
			//Field validation success, add precinct
			if($this->Model_house->addHouse($form_data)){
				$this->session->set_flashdata('alertmessage', 'The house was added successfully.');
				redirect('builder/'.$redirecto_to);
			}
		}
		$data        = array('builder' => $builder);
		$footer      = array('builder/footer_standard', 'builder/view_houses_customjs');
		$this->load_template('builder/view_addhouse', $data, $footer);
	}

	function addhouselandpackage(){
		$this->load->model('Model_development', '', TRUE);
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		if(!$builder){
			$this->session->set_flashdata('errormessage', 'You need to be assigned to a Builder. Please contact the aministrator');
			redirect('builder/managehouses/dashboard');
		}
		$developments = $this->Model_development->getDevelopments(1);
		$data         = array(
			'builder'      => $builder,
			'developments' => $developments,
		);
		$this->load_template('builder/view_addhouselandpackagedevelopment', $data);
	}

	function addhouselandpackagelots($development_id){
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$builder     = $this->Model_builder_user->getLoggedUserBuilder();
		$development = $this->Model_development->getDevelopment($development_id);
		$lots        = $this->Model_lot->getLots($development_id, array('status' => 'Available'));
		$data        = array(
			'builder'     => $builder,
			'development' => $development,
			'lots'        => $lots,
		);
		$this->load_template('builder/view_addhouselandpackagelots', $data);
	}

	function addhouselandpackagehouses($lot_id)
	{
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
		$this->load->model('Model_house_lot_package', '', TRUE);
		$this->load->model('Model_house_lot_package_approval', '', TRUE);

		$builder           = $this->Model_builder_user->getLoggedUserBuilder();
		$lot               = $this->Model_lot->getLot($lot_id);
		$development       = $this->Model_development->getDevelopment($lot->development_id);
		$lot_house_pending = $this->Model_house_lot_package_approval->getBuilderPackages($builder->builder_id, array('lot_id' => $lot->lot_id, 'status' => 'Pending', 'development_id' => $development->development_id));
		$houses            = $this->Model_house->getHousesByBuilder($builder->builder_id, NULL, FALSE);
		$houses            = (count($houses))? array_combine(array_map(function($v){return $v->house_id;}, $houses), $houses):array();
		$data              = array(
			'development'        => $development,
			'builder'            => $builder,
			'lot'                => $lot,
			'houses'             => $houses,
			'lot_house_packages' => $this->Model_house_lot_package->getHouseLotPackages($lot_id, array('builder_id' => $builder->builder_id)),
			'lot_house_pending'  => $lot_house_pending,
		);
		$footer = array('builder/footer_standard', 'builder/view_addhouselandpackagehouses_customjs');
		$this->load_template('builder/view_addhouselandpackagehouses', $data, $footer);
	}

	function addhouselandpackagedata($lot_id, $house_id, $prev_package_id = NULL)
	{
		$this->load->model('Model_house_lot_package', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
		$this->load->model('Model_house_image', '', TRUE);

		$lot               = $this->Model_lot->getLot($lot_id);
		$house             = $this->Model_house->getHouse($house_id);
		$house_lot_package = ($prev_package_id)? $this->Model_house_lot_package->getHouseLotPackage($prev_package_id): FALSE;
		$sales_people      = $this->Model_builder_sales_person->getSalesPeople($house->house_builder_id);
		$data              = array(
			'house'             => $house,
			'lot'               => $lot,
			'house_lot_package' => $house_lot_package,
			'sales_people'      => $sales_people,
			'house_images'      => (!$house->development_id)? $this->Model_house_image->getHouseImages($house_id, array('file_type' => 'facade')): array(),
			'prev_package_id'   => $prev_package_id,
		);
		$this->load_ajaxtemplate('builder/view_addhouselandpackagedata', $data);
	}

	function submithouselotpackage($lot_id, $house_id)
	{
		$this->load->model('Model_house_lot_package_approval', '', TRUE);
		$form_data = $this->input->post();
		if($form_data && $package_id = $this->Model_house_lot_package_approval->submitRequestHouseLot($lot_id, $house_id, $form_data)){
			$house_lot_package_approval                           = $this->Model_house_lot_package_approval->getHouseLotPackage($package_id);
			$house_lot_package_approval->formated_house_lot_price = number_format($house_lot_package_approval->house_lot_price);
			$house_lot_package_approval->house_bathrooms          = round($house_lot_package_approval->house_bathrooms, 1);
			$result                                      = array('status' => 1, 'msg'=> 'The House and Land package has been submitted for approval.','obj' => $house_lot_package_approval);
		}
		else{
			$result            = array('status' => 0, 'msg'=> 'It was not possible to submit your request. Please check the information are correct.', 'obj' => FALSE);
		}
		echo json_encode($result);
		exit();
	}

	function houselandpackages(){
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
		$this->load->model('Model_house_lot_package', '', TRUE);
		$builder        = $this->Model_builder_user->getLoggedUserBuilder();
		$house_packages = $this->Model_house_lot_package->getHouseLotPackagesByBuilder($builder->builder_id);
		$data           = array(
			'builder'        => $builder,
			'house_packages' => $house_packages,
		);
		$this->load_template('builder/view_houselandpackages', $data);
	}

	function houselandpackageapproval()
	{
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
		$this->load->model('Model_house_lot_package_approval', '', TRUE);
		$builder               = $this->Model_builder_user->getLoggedUserBuilder();
		$packages_for_approval = $this->Model_house_lot_package_approval->getBuilderPackages($builder->builder_id, array('status' => 'Pending'));
		$data    = array(
			'builder'               => $builder,
			'packages_for_approval' => $packages_for_approval,
		);
		$this->load_template('builder/view_houselandpackageapproval', $data);
	}

	function edithouselandpackagedata($package_id)
	{
		$this->load->model('Model_house_lot_package_approval', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
		$this->load->model('Model_package_order_image', '', TRUE);

		$house_lot_package_approval = $this->Model_house_lot_package_approval->getHouseLotPackage($package_id);
		$lot               = $this->Model_lot->getLot($house_lot_package_approval->lot_id);
		$house             = $this->Model_house->getHouse($house_lot_package_approval->house_id);
		$sales_people      = $this->Model_builder_sales_person->getSalesPeople($house_lot_package_approval->house_builder_id);
		$data              = array(
			'house'             => $house,
			'lot'               => $lot,
			'house_lot_package' => $house_lot_package_approval,
			'sales_people'      => $sales_people,
			'house_images'      => (!$house_lot_package_approval->development_id)?$this->Model_package_order_image->getPackageImages($package_id): array(),
		);
		$this->load_ajaxtemplate('builder/view_edithouselandpackagedata', $data);
	}

	function updatehouselotpackage($package_id)
	{
		$this->load->model('Model_house_lot_package_approval', '', TRUE);
		$form_data = $this->input->post();
		if($form_data && $this->Model_house_lot_package_approval->updateRequestHouseLot($package_id, $form_data)){
			$this->session->set_flashdata('alertmessage', 'The package has been updated successfully.');
		}
		else{
			$this->session->set_flashdata('errormessage', 'It was not possible to update the package.');
		}
		redirect('builder/houselandpackageapproval/');
	}

	function deletepackage($package_id)
	{
		$this->load->model('Model_house_lot_package', '', TRUE);
		$this->load->model('Model_house_lot_package_approval', '', TRUE);
		$this->load->model('Model_house', '', TRUE);
		$builder  = $this->Model_builder_user->getLoggedUserBuilder();
		$package  = $this->Model_house_lot_package_approval->getHouseLotPackage($package_id);
		$house    = $this->Model_house->getHouse($package->house_id);
		if($builder->builder_id != $package->builder_id){
			$result = array('status' => 0, 'msg'=> 'Invalid Package.');
		}
		elseif($this->Model_house_lot_package_approval->deletePackage($package->house_lot_package_approval_id)){
			$current_package = ($package->prev_package_id)? $this->Model_house_lot_package->getHouseLotPackage($package->prev_package_id): FALSE;
			if($current_package){
				$result = array('status' => 2, 'msg'=> 'The House and Land package was deleted successfully.', 'obj' => $current_package);
			}
			else{
				$result = array('status' => 1, 'msg'=> 'The House and Land package was deleted successfully.', 'obj' => $house);
			}
		}
		else{
			$result = array('status' => 0, 'msg'=> 'It was not possible to delete the package, please try again.');
		}
		echo json_encode($result);
		exit();
	}

	function disablepackage($package_id)
	{
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_house_lot_package', '', TRUE);
		$this->load->model('Model_user', '', TRUE);
		$this->load->library('email_sender');
		$house_lot_package = $this->Model_house_lot_package->getHouseLotPackage($package_id);
		if(!$house_lot_package){
			$this->session->set_flashdata('errormessage', 'Invalid House and Land package. Please try again.');
		}
		$builder           = $this->Model_builder_user->getLoggedUserBuilder();
		$house             = $this->Model_house->getHouse($house_lot_package->house_id);
		$current_user      = $this->Model_user->getBuilderLogged();
		if($builder->builder_id != $house->house_builder_id){
			$this->session->set_flashdata('errormessage', 'Invalid House and Land package. Please try again.');
		}
		elseif($house_lot_package->active == 0){
			$this->session->set_flashdata('errormessage', 'The House and Land package was already disabled.');
		}
		elseif($this->Model_house_lot_package->enableDisableHouseLotPdf($package_id, FALSE, $current_user)){
			$lot              = $this->Model_lot->getLot($house_lot_package->lot_id); 
			$development      = $this->Model_development->getDevelopment($lot->development_id);
			$current_user     = $this->Model_user->getBuilderLogged();
			$development_user = $this->Model_user->getUser($development->primary_user);
			if(!empty($development_user)){
				$from             = (object)array('email' => $current_user->email, 'name' => $current_user->name);
				$to               = (object)array('name' => $development_user->name, 'email' => $development_user->email);
				$subject          = 'A House and Land Package has been disabled';
				$email_data       = (object)array(
					'developmentname'  => $development->development_name,
					'developmentid'    => $development->development_id,
					'lotnumber'        => $lot->lot_number,
					'primaryuser'      => $development_user->name,
					'builder'          => $builder->builder_name,
					'housename'        => $house->house_name,
					'builderusername'  => $current_user->name,
					'title'            => $subject,
					'packageprice'     => number_format($house_lot_package->house_lot_price,0),
				);
				// submit email to primary user of the development
				$this->email_sender->sendEmailNotificaton($subject, 'house_land_package_disable_by_builder', $from, $to, $email_data, null, $notes);
			}
			$this->session->set_flashdata('alertmessage', 'The package has been disabled successfully.');
		}
		else{
			$this->session->set_flashdata('errormessage', 'It was not possible to disable the package.');
		}
		redirect('builder/houselandpackages/');
	}

	function salespeople(){
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		$sales_people = $this->Model_builder_sales_person->getSalesPeople($builder->builder_id, TRUE);
		$data         = array(
			'builder'      => $builder,
			'sales_people' => $sales_people,
		);
		$footer = array('builder/footer_standard', 'builder/view_salespeople_customjs');
		$this->load_template('builder/view_sales_people', $data, $footer);
	}

	function addsalesperson()
	{
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		$this->load->library('form_validation');
		$this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');

		if($this->form_validation->run() != FALSE)
		{
			$form_data = $this->input->post();
			//Field validation success, add precinct
			if($this->Model_builder_sales_person->addSalesPerson($builder->builder_id, $form_data)){
				$this->session->set_flashdata('alertmessage', 'The builder was added successfully.');
				redirect('builder/salespeople/');
			}
		}
		$data   = array('builder' => $builder);
		$footer = array('builder/footer_standard', 'builder/view_salespeople_customjs');
		$this->load_template('builder/view_addsalesperson', $data, $footer);
	}

	function editsalesperson($sales_person_id)
	{
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		$sales_person = $this->Model_builder_sales_person->getSalesPerson($sales_person_id);
		if($builder->builder_id != $sales_person->builder_id){
			echo 'You do not have access to this Sales Person';
			exit();
		}
		$data         = array(
			'builder'      => $builder,
			'sales_person' => $sales_person
		);
		$this->load_ajaxtemplate('builder/view_editsalesperson', $data);
	}

	function updatesalesperson($sales_person_id)
	{
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		$sales_person = $this->Model_builder_sales_person->getSalesPerson($sales_person_id);

		$form_data = $this->input->post();
		if($builder->builder_id != $sales_person->builder_id){
			$this->session->set_flashdata('errormessage', 'You do not have access to selected Sales Person.');
		}
		elseif($this->Model_builder_sales_person->updateSalesPerson($sales_person_id, $form_data)){
			$this->session->set_flashdata('alertmessage', 'The sales person was udpated successfully.');
		}
		else{
			$this->session->set_flashdata('errormessage', 'It was not possible to update the sales person, please make sure the data is correct.');
		}
		redirect('builder/salespeople/');
	}

	function deletesalespersonimage($sales_person_id)
	{
		if($this->Model_builder_sales_person->deleteImage($sales_person_id)){
			$result = array('status' => 1, 'msg'=> 'The image was deleted successfully.');
		}
		else{
			$result = array('status' => 0, 'msg'=> 'It was not possible to delete the image, please try again.');
		}
		echo json_encode($result);
		exit();
	}

	function deletesalesperson($sales_person_id)
	{
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		$sales_person = $this->Model_builder_sales_person->getSalesPerson($sales_person_id);
		if($builder->builder_id != $sales_person->builder_id){
			$this->session->set_flashdata('errormessage', 'You do not have access to selected Sales Person.');
		}
		elseif(!$this->Model_builder_sales_person->canDeleteSalesPerson($sales_person_id)){
			$this->session->set_flashdata('errormessage', 'It is not possible to delete the sales person because is linked to one or more house and land packages.');
		}
		elseif($this->Model_builder_sales_person->deleteSalesPerson($sales_person_id)){
			$this->session->set_flashdata('alertmessage', 'The sales person was deleted successfully.');
		}
		else{
			$this->session->set_flashdata('errormessage', 'It was not possible to delete the sales person, please try again.');
		}
		redirect('builder/salespeople/');
	}

	function manageaccount()
	{
		$this->load->library('mapovis_lib');
		$current_user = $this->Model_user->getBuilderLogged();
		$user_id      = $current_user->user_id;

		//This method will have the credentials validation
		$this->load->library('form_validation');
		$this->form_validation->set_rules('name', 'Name', 'trim|required|xss_clean');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|callback_check_user_details');

		if($this->form_validation->run() != FALSE){
			$form_data = $this->input->post();
			$this->Model_user->updateUser($user_id, $form_data);
			$this->session->set_flashdata('alertmessage', 'Your account has been updated successfully.');
			redirect('builder/manageaccount');
		}
		$data         = array(
			'user'        => $current_user,
			'avatar_size' => $this->Model_user->getAvatarSize(),
		);
		$this->load_template('builder/view_manageaccount', $data);
	}

	function savedefaultpagination()
	{
		$current_user       = $this->Model_user->getBuilderLogged();
		$default_pagination = $this->input->post('default_pagination');
		$this->Model_user->setDefaultPagination($current_user->user_id, $default_pagination);
		echo 'save_'.$default_pagination;
	}

	function contactus()
	{
		$this->load->model('Model_config', '', TRUE);

		$settings      = $this->Model_config->getConfig();
		$current_user  = $this->Model_user->getBuilderLogged();
		$data          = array(
			'current_user' => $current_user,
			'settings'     => $settings,
		);
		$this->load_template('builder/view_contactus', $data);
	} 

	function documentportalmanage()
	{
		$this->load->model('Model_development', '', TRUE);
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		if(!$builder){
			$this->session->set_flashdata('errormessage', 'You need to be assigned to a Builder. Please contact the aministrator');
			redirect('builder/managehouses/dashboard');
		}
		$developments = $this->Model_development->getDevelopments(1);
		$data         = array(
			'builder'      => $builder,
			'developments' => $developments,
		);
		$this->load_template('builder/view_documentportaldevs', $data);
	}

	function developmentdocuments($development_id)
	{
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_development_document', '', TRUE);
		$builder      = $this->Model_builder_user->getLoggedUserBuilder();
		if(!$builder){
			$this->session->set_flashdata('errormessage', 'You need to be assigned to a Builder. Please contact the aministrator');
			redirect('builder/managehouses/dashboard');
		}
		$development = $this->Model_development->getDevelopment($development_id);
		$documents   = $this->Model_development_document->getDevelopmentDocuments($development_id);
		$data        = array(
			'builder'     => $builder,
			'development' => $development,
			'documents'   => $documents,
			'types'       => $this->Model_development_document->getTypes(),
		);
		$this->load_template('builder/view_developmentdocuments', $data);
	}

}
?>