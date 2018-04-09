<?php
if( ! defined('BASEPATH')) exit('No direct script access allowed');
class apipdf extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->load->model('Model_apipdf', '', TRUE);
		$this->load->model('Model_development', '', TRUE);
	}

	function index()
	{
		$this->generatelotspdffiles();
		$this->generatestagespdffiles();
	}

	function generatelotspdffiles()
	{
		$developments = $this->Model_development->getDevelopments();
		foreach($developments as $development){
			if($development->generated_pdf == 1){
				$this->generatelotspdffile($development->development_id);
			}
		}
	}

	function generatelotspdffile($development_id = '', $save = TRUE)
	{
		$this->Model_apipdf->generatelotspdffile($development_id, $save);
	}

	function generatestagespdffiles()
	{
		$this->load->model('Model_stage', '', TRUE);
		$developments = $this->Model_development->getDEvelopments();
		foreach($developments as $development){
			$stages      = $this->Model_stage->getStages($development->development_id);
			foreach($stages as $stage){
				$this->generatestagepdffile($development->development_id, $stage->stage_number, TRUE);
			}
		}
	}

	/*
	 * This function will refenerate all the PDF for hte given development
	 */
	function regeneratedevelopmentpdffiles($development_id)
	{
		$development = $this->Model_development->getDevelopment($development_id);
		if($development){
			$this->Model_apipdf->generatelotspdffile($development_id, TRUE);
			$stages      = $this->Model_stage->getStages($development_id);
			foreach($stages as $stage){
				$this->Model_apipdf->generatestagepdffile($development_id, $stage->stage_number, TRUE);
			}
			$this->Model_apipdf->devpdffilewithlots($development_id, TRUE);
		}
	}

	/**
	 * Retrieves a PDF file with the given stage details
	 * @param int $development_id
	 * @param int $stage_number
	 * @param boolean $save
	 */
	function generatestagepdffile($development_id, $stage_number, $save = TRUE)
	{
		$this->Model_apipdf->generatestagepdffile($development_id, $stage_number, $save);
	}

	/**
	 * Generates a PDF file with all development stages that have at least 1 lot available
	 * @param int $development_id
	 * @param boolean $save
	 */
	function devpdffilewithlots($development_id, $save = FALSE)
	{
		$this->Model_apipdf->devpdffilewithlots($development_id, $save);
	}

	/**
	 * Generates a PDF file with given stage lots
	 * @param int $development_id
	 * @param int $stage_number
	 * @param boolean $save
	 */
	function devstagepdffile($development_id, $stage_number, $save = FALSE)
	{
		$this->Model_apipdf->devstagepdffile($development_id, $stage_number, $save);
	}

	public function devpdfpricelist($development_id, $save = FALSE)
	{
		$this->Model_apipdf->devpdfpricelist($development_id, $save);
	}

}
?>