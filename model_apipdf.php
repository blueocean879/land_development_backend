<?php
Class Model_apipdf extends CI_Model
{

	function __construct(){
		parent::__construct();
		$this->load->library('myfpdi');
		$this->load->library('pdf');
		$this->load->library('mapovis_lib');
		$this->load->model('Model_development', '', TRUE);
	}

	/**
	 * Generates a PDF file with lots of given development by Stage. It is used in devpdffilewithlots()
	 * @param int $development_id
	 * @param boolean $save
	 */
	public function generatelotspdffile($development_id = '', $save = TRUE)
	{
		$development = $this->Model_development->getDevelopment($development_id);
		$this->pdf   = new Pdf();
		// set document information
		$this->pdf->SetSubject('Development lots');
		$this->pdf->SetKeywords('Masterplan, Mapovis');
		$header = (!empty($development->pdf_header_html))? $development->pdf_header_html: '';
		$footer = (!empty($development->pdf_footer_html))? $development->pdf_footer_html: $this->getPdfFooter($development_id);
		$this->pdf->setHeaderHtml($header);
		$this->pdf->setFooterHtml($footer);
		$this->pdf->SetMargins(PDF_MARGIN_LEFT, $development->pdf_header_margin);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetAutoPageBreak(TRUE, $development->pdf_footer_margin+5);
		$this->pdf->SetFooterMargin($development->pdf_footer_margin);

		// add a page
		$this->pdf->AddPage();

		$lots_availability = $this->getLotsAvailability($development_id);
		$file_name         = 'lot_available_dev_'.$development_id.'.pdf';

		// if there are no available lots do not generate the pdf file.
		if($lots_availability === FALSE){
			// if the PDF is meant to be saved then delete the previous version
			if($save){
				$file_path_name = BASEPATH.'../../mpvs/templates/availablelotspdf/'.$file_name;
				if(file_exists($file_path_name)){
					unlink($file_path_name);
				}
			}
			return FALSE;
		}
		$this->pdf->writeHTML($lots_availability, false, false, true, false, '');

		//Close and output PDF document
		if($save){
			$file_path_name = BASEPATH.'../../mpvs/templates/availablelotspdf/'.$file_name;
			if(file_exists($file_path_name)){
				unlink($file_path_name);
			}
			$this->pdf->Output($file_path_name, 'F');
			// store duplicate file on $global_config->pdf_docs_path like /var/www/html/stage3.mapovis.com.au/public/docs/{development_uri}/
			$this->devpdffilewithlots($development_id, TRUE);
		}
		else{
			$this->pdf->Output($file_name, 'I');
		}
	}

	/**
	 * Generates a PDF file with the given stage lots. This PDf will be used in devstagepdffile to merge with the stage PDF file
	 * @param int $development_id
	 * @param int $stage_number
	 * @param boolean $save
	 * @return boolean
	 */
	public function generatestagepdffile($development_id, $stage_number, $save = TRUE)
	{
		$development       = $this->Model_development->getDevelopment($development_id);
		$stage_lots = $this->getStageLots($development_id, $stage_number);
		$file_name  = 'dev_'.$development_id.'_stage_'.$stage_number.'.pdf';
		if(!$stage_lots){
			if($save){
				$file_path_name = BASEPATH.'../../mpvs/templates/stagepdf/'.$file_name;
				if(file_exists($file_path_name)){
					unlink($file_path_name);
				}
			}
			return FALSE;
		}
		$this->pdf = new Pdf();
		// set document information
		$this->pdf->SetSubject('Development Stage lots');
		$this->pdf->SetKeywords('Masterplan, Stage Mapovis');
		$header = (!empty($development->pdf_header_html))? $development->pdf_header_html: '';
		$footer = (!empty($development->pdf_footer_html))? $development->pdf_footer_html: $this->getPdfFooter($development_id);
		$this->pdf->setHeaderHtml($header);
		$this->pdf->setFooterHtml($footer);
		$this->pdf->SetMargins(PDF_MARGIN_LEFT, $development->pdf_header_margin);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetAutoPageBreak(TRUE, $development->pdf_footer_margin+5);
		$this->pdf->SetFooterMargin($development->pdf_footer_margin);

		// add a page
		$this->pdf->AddPage();

		$this->pdf->writeHTML($stage_lots, true, false, true, false, '');

		//Close and output PDF document
		if($save){
			$file_path_name = BASEPATH.'../../mpvs/templates/stagepdf/'.$file_name;
			if(file_exists($file_path_name)){
				unlink($file_path_name);
			}
			$this->pdf->Output($file_path_name, 'F');
			// store duplicate file on $global_config->pdf_docs_path like /var/www/html/stage3.mapovis.com.au/public/docs/{development_uri}/
			$this->devstagepdffile($development_id, $stage_number, TRUE);
		}
		else{
			$this->pdf->Output($file_name, 'I');
		}
	}

	public function devpdffilewithlots($development_id, $save = FALSE)
	{
		try
		{
			$development       = $this->Model_development->getDevelopment($development_id);
			$development_files = BASEPATH.'../../mpvs/templates/dev_pdfs/'.$development->lots_pdf_file;
			if(!$development || $development->generated_pdf != 1){
				return FALSE;
			}
			if(empty($development->lots_pdf_file) || !file_exists($development_files)){
				$development_files = FALSE;
			}
			
			switch($development->show_brochure){
				case 'never';
					$pdf_files   = array(
						BASEPATH.'../../mpvs/templates/availablelotspdf/lot_available_dev_'.$development_id.'.pdf',
					);
					break;
				case 'last';
					$pdf_files   = array(
						BASEPATH.'../../mpvs/templates/availablelotspdf/lot_available_dev_'.$development_id.'.pdf',
						$development_files,
					);
					break;
				case 'first':
				default:
					$pdf_files   = array(
						$development_files,
						BASEPATH.'../../mpvs/templates/availablelotspdf/lot_available_dev_'.$development_id.'.pdf',
					);
					break;
			}

			$this->myfpdi = new MyFpdi();
			$this->myfpdi->setPrintHeader(false);
			$this->myfpdi->setPrintFooter(false);

			$mim_1_file = FALSE;
			foreach($pdf_files as $pdf_file){
				if(!$pdf_file || !file_exists($pdf_file)){
					continue;
				}
				$mim_1_file = TRUE;
				$pageCount  = $this->myfpdi->setSourceFile($pdf_file);
				for($x = 1; $x <= $pageCount; $x++){
					// import page 1
					$tplIdx = $this->myfpdi->importPage($x);
					// use the imported page and place it at point 10,10 with a width of 100 mm
					$this->myfpdi->addPage();
					$this->myfpdi->useTemplate($tplIdx, null, null, 0, 0, TRUE);
				}
			}

			if(!$mim_1_file){
				return FALSE;
			}

			$file_name = str_replace(' ', '_', $development->development_name.'_Master Plan.pdf');
			if($save){
				$file_path      = BASEPATH."../../docs/";
				if(!file_exists($file_path)){
					return FALSE;
				}
				if(!empty($development->development_uri)){
					$file_path      .= $development->development_uri.'/';
					if(!file_exists($file_path)){
						mkdir($file_path, 0777, true);
					}
				}
				$file_path_name = $file_path . $file_name;
				// Change this based
				if(file_exists($file_path_name)){
					unlink($file_path_name);
				}
				$this->myfpdi->Output($file_path_name, 'F');
				return TRUE;
			}
			else{
				$this->myfpdi->Output($file_name, 'I');
			}
		}
		catch (Exception $e)
		{
			if($save){
				return FALSE;
			}
			echo('It was not possible to generate the PDF file. Please contact the admin');
			$debug = $this->input->get('debug', 0);
			if($debug){
				echo '<br>Message: ' .$e->getMessage().'<br>';
			}
		}
	}

	/**
	 * Generates a PDF file. It merge the stage PDF file with the lots details and the PDF uploaded for the stage if provided.
	 * Will need to call generatestagepdffile($development_id, $stage_number, $save = TRUE) to generate the stage PDF file first
	 * @param int $development_id
	 * @param int $stage_number
	 * @param boolean $save
	 * @return boolean
	 */
	public function devstagepdffile($development_id, $stage_number, $save = FALSE)
	{
		try
		{
			$this->load->model('Model_stage', '', TRUE);
			$development       = $this->Model_development->getDevelopment($development_id);
			$stage             = $this->Model_stage->getStageByNumber($development_id, $stage_number);
			if(!$stage){
				echo 'Invalid stage.';
				return FALSE;
			}
			$stage_file        = (!empty($stage->stage_pdf_file))? BASEPATH.'../../mpvs/templates/dev_stage_pdfs/'.$stage->stage_pdf_file: '';
			switch($stage->stage_show_brochure){
				case 'never';
					$pdf_files   = array(
						BASEPATH.'../../mpvs/templates/stagepdf/dev_'.$development_id.'_stage_'.$stage_number.'.pdf',
					);
					break;
				case 'last';
					$pdf_files   = array(
						BASEPATH.'../../mpvs/templates/stagepdf/dev_'.$development_id.'_stage_'.$stage_number.'.pdf',
						$stage_file,
					);
					break;
				case 'first':
				default:
					$pdf_files   = array(
						$stage_file,
						BASEPATH.'../../mpvs/templates/stagepdf/dev_'.$development_id.'_stage_'.$stage_number.'.pdf',
					);
					break;
			}
			
			$this->myfpdi = new MyFpdi();
			$this->myfpdi->setPrintHeader(false);
			$this->myfpdi->setPrintFooter(false);

			$mim_1_file = FALSE;
			foreach($pdf_files as $pdf_file){
				if(empty($pdf_file) || !file_exists($pdf_file)){
					continue;
				}
				$mim_1_file = TRUE;
				$pageCount = $this->myfpdi->setSourceFile($pdf_file);
				for($x = 1; $x <= $pageCount; $x++){
					// import page 1
					$tplIdx = $this->myfpdi->importPage($x);
					// use the imported page and place it at point 10,10 with a width of 100 mm
					$this->myfpdi->addPage();
					$this->myfpdi->useTemplate($tplIdx, null, null, 0, 0, TRUE);
				}
			}
			if(!$mim_1_file){
				return FALSE;
			}

			$file_name = str_replace(' ', '_', $development->development_name.'-Stage-'.$stage->stage_code.'.pdf');
			if($save){
				$file_path      = BASEPATH."../../docs/";
				if(!file_exists($file_path)){
					return FALSE;
				}
				if(!empty($development->development_uri)){
					$file_path      .= $development->development_uri.'/';
					if(!file_exists($file_path)){
						mkdir($file_path, 0777, true);
					}
				}
				$file_path_name = $file_path . $file_name;
				// Change this based
				if(file_exists($file_path_name)){
					unlink($file_path_name);
				}
				$this->myfpdi->Output($file_path_name, 'F');
				return TRUE;
			}
			else{
				$this->myfpdi->Output($file_name, 'I');
			}
		}
		catch (Exception $e)
		{
			if($save){
				return FALSE;
			}
			echo('It was not possible to generate the PDF file. Please contact the admin');
			$debug = $this->input->get('debug', 0);
			if($debug){
				echo '<br>Message: ' .$e->getMessage().'<br>';
				var_dump($e);
			}
		}
	}

	public function devpdfpricelist($development_id, $save = FALSE)
	{
		$development = $this->Model_development->getDevelopment($development_id);
		$this->pdf   = new Pdf();
		// set document information
		$this->pdf->SetSubject('Price List');
		$this->pdf->SetKeywords('Masterplan, Mapovis');

		$header = (!empty($development->builder_pdf_header_html))? $development->builder_pdf_header_html: '';
		$footer = (!empty($development->builder_pdf_footer_html))? $development->builder_pdf_footer_html: $this->getPdfFooter($development_id);
		$this->pdf->setHeaderHtml($header);
		$this->pdf->setFooterHtml($footer);

		$this->pdf->SetMargins(PDF_MARGIN_LEFT, $development->builder_pdf_header_margin);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetAutoPageBreak(TRUE, $development->builder_pdf_footer_margin+5);
		$this->pdf->SetFooterMargin($development->builder_pdf_footer_margin);

		// add a page
		$this->pdf->AddPage();

		$lots_availability = $this->getLotsAvailability($development_id, TRUE, FALSE);
		$this->pdf->writeHTML($lots_availability, false, false, true, false, '');

		//Close and output PDF document
		$file_name = 'price_list_dev_'.$development_id.'.pdf';
		if($save){
			$file_path_name = BASEPATH.'../../mpvs/templates/availablelotspdf/'.$file_name;
			if(file_exists($file_path_name)){
				unlink($file_path_name);
			}
			$this->pdf->Output($file_path_name, 'F');
			return $file_path_name;
		}
		else{
			$this->pdf->Output($file_name, 'I');
		}
	}

	private function getPdfFooter($development_id){
		$development = $this->Model_development->getDevelopment($development_id);
		$footer      = '
		<style>
		body{
			font-family:'.$development->land_pdf_fontfamily_css.'
			font-size: 10px;
		}
		table{
			width: 100%; font-family:'.$development->land_pdf_fontfamily_css.'
			font-size: 10px;
			font-weight: bold;
			border-collapse: collapse;
		}
		table td.header{
			background-color: #'.$development->pdf_footer_header_colour.';
			font-size: 12px;
		}
		table td.header b{
			font-size: 10px;
		}
		table td.row{
			background-color: #'.$development->pdf_footer_row_colour.';
		}
		</style>
		<table cellpadding="3px;" cellspacing="0" align="left" border="0">
			<tr>
				<td width="60%" colspan="2" class="header"><b>'.$development->sales_office_title.'</b></td>
				<td width="40%" colspan="2" align="right" class="header">'.$development->sales_office_opening_house.'</td>
			</tr>
			<tr>
				<td width="100%" colspan="4" class="row">'.$development->sales_office_address.'</td>
			</tr>
			<tr>
				<td width="30%" class="row">Sales Person: '.$development->sales_person_name.'</td>
				<td width="30%" class="row">Phone: '.$development->local_sales_telephone_number.'</td>
				<td width="40%" colspan="2" class="row">'.$development->sales_email_address.'</td>
			</tr>
		</table>';
		return $footer;
	}

	private function getLotsAvailability($development_id, $include_price = FALSE, $exclude_builder_lot = TRUE)
	{
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_stage', '', TRUE);
		$form_data         = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development       = $this->Model_development->getDevelopment($development_id);
		$search_parameters = $this->Model_lot->landSearchParameters($development_id);
		// validate if the access key is valid
		$tmpstages         = $this->Model_stage->getStages($development_id);
		$active_stages     = array();
		foreach($tmpstages as $stage){
			$stage->lots            = $this->Model_lot->getStageLots($stage->stage_id, $form_data, $development->land_show_sold_lots, $development->land_pdf_order_by, $exclude_builder_lot);
			$stage->sold_percentage = $this->Model_stage->getStageSoldPercentage($stage->stage_id, $form_data, $exclude_builder_lot);
			/* If the stage does not have any lots or all lots are sold does not display */
			if($stage->sold_percentage == 100 || empty($stage->lots)){
				continue;
			}
			$stage->word_of_number  = $this->mapovis_lib->convert_number_to_words($stage->stage_number);
			$stage->min_price       = $this->Model_lot->getStageMinLotPrice($stage->stage_id, $form_data);
			$active_stages[]        = $stage;
		}
		if(empty($active_stages)){
			return FALSE;
		}

		$data        = array(
			'stages'            => $active_stages,
			'development'       => $development,
			'search_parameters' => $search_parameters,
			'include_price'     => $include_price
		);
		$page_pdf_view = $this->Model_development->getLandPdfView($development->land_pdf_view);
		return $this->load->view($page_pdf_view, $data, TRUE);
	}

	private function getStageLots($development_id, $stage_number){
		$this->load->model('Model_development', '', TRUE);
		$this->load->model('Model_lot', '', TRUE);
		$this->load->model('Model_stage', '', TRUE);
		$form_data         = $this->input->get();
		if(!$form_data){
			$form_data = array();
		}
		$development            = $this->Model_development->getDevelopment($development_id);
		$search_parameters      = $this->Model_lot->landSearchParameters($development_id);
		// validate if the access key is valid
		$stage                  = $this->Model_stage->getStageByNumber($development_id, $stage_number);
		if(!$stage){
			return FALSE;
		}
		$stage->lots            = $this->Model_lot->getStageLots($stage->stage_id, $form_data, $development->land_show_sold_lots, $development->land_pdf_order_by);
		$stage->sold_percentage = $this->Model_stage->getStageSoldPercentage($stage->stage_id, $form_data);
		/* If the stage does not have any lots or all lots are sold does not display */
		$stages                 = array($stage);
		if($stage->sold_percentage == 100 || empty($stage->lots)){
			return FALSE;
		}
		$stage->word_of_number  = $this->mapovis_lib->convert_number_to_words($stage->stage_number);
		$stage->min_price       = $this->Model_lot->getStageMinLotPrice($stage->stage_id, $form_data);

		$data        = array(
			'stages'            => $stages,
			'development'       => $development,
			'search_parameters' => $search_parameters
		);
		$page_pdf_view = $this->Model_development->getLandPdfView($development->land_pdf_view);
		return $this->load->view($page_pdf_view, $data, TRUE);
	}

}
?>