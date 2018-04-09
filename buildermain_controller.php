<?php
if( !defined('BASEPATH')){exit('No direct script access allowed');}

class BuilderMain_Controller extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('ssl_helper'));
		force_ssl();
	}

	/**
	 * This function will load the header, content layout and footer
	 * the controller can call load_template and provide the view and data, if the title and footer are different they can be specified
	 * @param string $layout_content
	 * @param array $data
	 * @param array $footer
	 * @param string $title
	 */
	function load_template($layout_content, $data = array(), $footer = array('builder/footer_standard'), $title = 'Mapovis')
	{
		$this->load->model('Model_user', '', TRUE);
		$this->load->library('mapovis_lib');
		$current_user                         = $this->Model_user->getBuilderLogged();
		$user_avatar                          = $this->mapovis_lib->UserDevAvatar($current_user);

		$section_segment                      = $this->uri->segment(2);
		$warningmessage                       = $this->session->flashdata('warningmessage');
		$successmessage                       = $this->session->flashdata('alertmessage');
		$errormessage                         = $this->session->flashdata('errormessage');
		$success_message                      = (!empty($successmessage))? '<div class="alert alert-success">'.$successmessage.'</div>': '';
		$error_message                        = (!empty($errormessage))? '<div class="alert alert-danger">'.$errormessage.'</div>': '';
		$warning_message                      = (!empty($warningmessage))? '<div class="alert alert-warning">'.$warningmessage.'</div>': '';

		$data['avatar_icon']                  = $user_avatar;
		$data['title']                        = $title;
		$data['layout_content']               = ($layout_content)? $layout_content: 'builder/view_dashboard';
		$data['footer_custom_content_chunks'] = $footer;
		$data['alert_message']                = "{$success_message} {$error_message} {$warning_message}";
		$data['section_segment']              = $section_segment;
		$data['default_pagination']           = $current_user->default_pagination;
		$this->load->view('builder/buildertemplate', $data);
	}

	function load_ajaxtemplate($layout_content, $data = array())
	{
		$warningmessage        = $this->session->flashdata('warningmessage');
		$successmessage        = $this->session->flashdata('alertmessage');
		$errormessage          = $this->session->flashdata('errormessage');
		$success_message       = (!empty($successmessage))? '<div class="alert alert-success">'.$successmessage.'</div>': '';
		$error_message         = (!empty($errormessage))? '<div class="alert alert-danger">'.$errormessage.'</div>': '';
		$warning_message       = (!empty($warningmessage))? '<div class="alert alert-warning">'.$warningmessage.'</div>': '';

		$data['alert_message'] = "{$success_message} {$error_message} {$warning_message}";
		$this->load->view($layout_content, $data);
	}
}
?>