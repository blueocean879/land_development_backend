<?php
if( ! defined('BASEPATH')) exit('No direct script access allowed');
class apistagepdf extends CI_Controller {

	function __construct(){
		parent::__construct(); 
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_stage', '', TRUE);
	}

	function stages($development_id, $developmentkey = '')
	{
		$form_data   = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development = $this->Model_development->getDevelopment($development_id);
		$stages      = array();
		// validate if the access key is valid
		if($this->Model_development->validateDevelomentKey($development_id, $developmentkey)){
			$stages = $this->Model_stage->getStagesActiveLots($development_id, FALSE, FALSE);
		}
		$data        = array(
			'stages'            => $stages,
			'development_id'    => $development_id,
		);
		$page_view = $this->Model_development->getStagePdfView($development->stagepdf_view);
		$this->load->view($page_view, $data);
	}

}
?>