<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BuilderLogin extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('ssl_helper'));
		force_ssl();
	}

	function index()
	{
		/* if user is already logged in, redirect to main page */
		if($this->session->userdata('builder_logged_id'))
		{
			redirect('builder');
			exit;
		}
		$loginmessage  = $this->session->flashdata('loginmessage');
		$login_message = (!empty($loginmessage))? '<div class="alert alert-warning">'.$loginmessage.'</div>': '';
		$this->load->view('builder/login', array('login_message' => $login_message));
	}

	function forgotpassword()
	{
		$this->load->library('form_validation');
		$this->form_validation->set_rules('emailaddress', 'Email Address', 'trim|required|valid_email|callback_finduser');
		if($this->form_validation->run() != FALSE){
			$email_address = $this->input->post('emailaddress');
			if($this->Model_user->resetBuilderPasswordSendNotification($email_address)){
				$this->session->set_flashdata('loginmessage', 'The password has beem reset and an email notification should arrive shortly.');
				echo 'The password has beem reset and an email notification should arrive shortly.';
			}
			else{
				$this->session->set_flashdata('loginmessage', 'It was not possible to send the email notification.');
				echo 'It was not possible to send the email notification.';
			}
			redirect('builderlogin');
		}
		$this->load->view('builder/forgotpassword');
	}

	function finduser(){
		$this->load->model('Model_user', '', TRUE);
		$email_address = $this->input->post('emailaddress');
		$user          = $this->Model_user->findUserEmailAdress($email_address, 'Builder');
		if(!$user){
			$this->form_validation->set_message('finduser', 'Email address not found');
			return FALSE;
		}
		return TRUE;
	}

	function logout()
	{
		//log out builder user
		$this->load->helper('cookie');
		delete_cookie("builder_logged_role");
		$this->session->unset_userdata('builder_logged_id');
		$this->session->unset_userdata('builder_logged_role');
		$this->session->unset_userdata('builder_logged_username');
		redirect('builderlogin');
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

}
?>