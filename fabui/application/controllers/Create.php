<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
 class Create extends FAB_Controller {
 	
	protected $runningTask = false;
	
	function __construct()
	{
		parent::__construct();
		if(!$this->input->is_cli_request()){ //avoid this form command line
			//check if there's a running task
			//load libraries, models, helpers
			$this->load->model('Tasks', 'tasks');
			//$this->tasks->truncate();
			$this->runningTask = $this->tasks->getRunning();
		}
	}
	
	//controller router
	public function index($type = 'print', $what_id = '-1'){
		
		if($this->runningTask){
			$method = 'do'.ucfirst($this->runningTask['type']);
			if(method_exists($this, $method)) $this->$method($what_id);
			else redirect('dashboard');
		}else{
			switch($type){
				case 'mill':
					$this->doMill($what_id);
					break;
				case 'print':
					$this->doPrint($what_id);
					break;
				case 'laser':
					$this->doLaser($what_id);
					break;
				default:
					$this->doPrint($what_id);
			}
		}
	}
	
	private function doPrint($fileId)
	{
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('plugin_helper');
		$this->load->model('Files', 'files');
		
		$data = array();
		$data['runningTask'] = $this->runningTask;
		$data['file_id'] = '';
		
		// Skip file selection step if fileID is provided
		$file = $this->files->get($fileId, 1);
		$file_is_ok = False;
		
		if($file)
		{
			if($file['print_type'] == 'additive')
			{
				$data['file_id'] = $fileId;
				$file_is_ok = True;
				$data['wizard_jump_to'] = 2; // jump to step 2 if fileID is available
			}
			else
			{
				$data['warning'] = "Selected file is not for printing";
			}
		}
		
		// Skip to Job Execution step if task is already running
		$task_is_running = False;
		if($data['runningTask'])
		{
			$data['wizard_jump_to'] = 3;
			$task_is_running = True;
		}
		
		$data['type']      = 'print';
		$data['type_label'] = _("Printing");
		
		// select_file
		$data['get_files_url'] = 'std/getFiles/additive';
		$data['get_reacent_url'] = 'std/getRecentFiles/print';
		
		// task_wizard
		$data['start_task_url'] = 'create/startPrintTask';
		
		$data['steps'] = array(
				array('number'  => 1,
				 'title'   => _("Choose file"),
				 'content' => $this->load->view( 'std/select_file', $data, true ),
				 'active'  => !$file_is_ok && !$task_is_running
			    ),
				array('number'  => 2,
				 'title'   => _("Get ready"),
				 'content' => $this->load->view( 'std/print_setup', $data, true ),
				 'active'  => $file_is_ok && !$task_is_running
			    ),
				array('number'  => 3,
				 'title'   => _("Printing"),
				 'content' => $this->load->view( 'std/task_execute', $data, true ),
				 'active' => $task_is_running
			    ),
				array('number'  => 4,
				 'title'   => _("Finish"),
				 'content' => $this->load->view( 'std/task_finished', $data, true )
			    )
			);
		
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widgeFooterButtons = '';

		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-head-installation';
		$widget->header = array('icon' => 'fa-cube', "title" => "<h2>"._("Print")."</h2>");
		$widget->body   = array('content' => $this->load->view('std/task_wizard', $data, true ), 'class'=>'fuelux', 'footer'=>$widgeFooterButtons);

		$this->addCssFile('/assets/css/std/select_file.css');
		$this->addCssFile('/assets/css/std/task_execute.css');

		$this->addJSFile('/assets/js/plugin/datatables/jquery.dataTables.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.colVis.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.tableTools.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.bootstrap.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatable-responsive/datatables.responsive.min.js'); //datatable */
		
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.cust.min.js'); 
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.resize.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.fillbetween.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.time.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.tooltip.min.js');

		$this->addJsInLine($this->load->view( 'create/print_js', $data, true));

		$this->addJSFile('/assets/js/plugin/fuelux/wizard/wizard.min.old.js'); //wizard
		$this->addJsInLine($this->load->view( 'std/task_wizard_js', $data, true));
		
		$this->addJsInLine($this->load->view( 'std/select_file_js', $data, true));
		
		$this->addJsInLine($this->load->view( 'std/print_setup_js', $data, true));
		
		$this->addJsInLine($this->load->view( 'std/task_execute_js', $data, true));
		$this->addJsInLine($this->load->view( 'std/task_finished_js', $data, true));

		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	private function doMill($fileId)
	{
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('plugin_helper');
		$this->load->model('Files', 'files');
		
		$data = array();
		$data['runningTask'] = $this->runningTask;
		$data['file_id'] = '';
		
		// Skip file selection step if fileID is provided
		$file = $this->files->get($fileId, 1);
		$file_is_ok = False;
		
		if($file)
		{
			if($file['print_type'] == 'subtractive')
			{
				$data['file_id'] = $fileId;
				$file_is_ok = True;
				$data['wizard_jump_to'] = 2; // jump to step 2 if fileId is available
			}
			else
			{
				$data['warning'] = "Selected file is not for milling";
			}
		}
		
		// Skip to Job Execution step if task is already running
		$task_is_running = False;
		if($data['runningTask'])
		{
			$data['wizard_jump_to'] = 3;
			$task_is_running = True;
		}
		
		$data['type']      = 'mill';
		$data['type_label'] = _("Milling");
		
		// select_file
		$data['get_files_url'] = 'std/getFiles/subtractive';
		$data['get_reacent_url'] = 'std/getRecentFiles/mill';
		
		// task_wizard
		$data['start_task_url'] = 'create/startMillTask';
		
		$data['steps'] = array(
				array('number'  => 1,
				 'title'   => _("Choose file"),
				 'content' => $this->load->view( 'std/select_file', $data, true ),
				 'active'  => !$file_is_ok && !$task_is_running
			    ),
				array('number'  => 2,
				 'title'   => _("Get ready"),
				 'content' => $this->load->view( 'std/jog_setup', $data, true ),
				 'active'  => $file_is_ok && !$task_is_running
			    ),
				array('number'  => 3,
				 'title'   => _("Milling"),
				 'content' => $this->load->view( 'std/task_execute', $data, true ),
				 'active' => $task_is_running
			    ),
				array('number'  => 4,
				 'title'   => _("Finish"),
				 'content' => $this->load->view( 'std/task_finished', $data, true )
			    )
			);
		
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widgeFooterButtons = '';

		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-head-installation';
		$widget->header = array('icon' => 'fa-cube', "title" => "<h2>"._("Mill")."</h2>");
		$widget->body   = array('content' => $this->load->view('std/task_wizard', $data, true ), 'class'=>'fuelux', 'footer'=>$widgeFooterButtons);

		$this->addCssFile('/assets/css/std/select_file.css');
		$this->addCssFile('/assets/css/std/task_execute.css');
		$this->addCssFile('/assets/css/std/jog_setup.css');
		$this->addCssFile('/assets/css/std/jogtouch.css');
		$this->addCssFile('/assets/css/std/jogcontrols.css');

		$this->addJSFile('/assets/js/plugin/datatables/jquery.dataTables.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.colVis.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.tableTools.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.bootstrap.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatable-responsive/datatables.responsive.min.js'); //datatable */
		
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.cust.min.js'); 
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.resize.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.fillbetween.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.time.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.tooltip.min.js');

		$this->addJsInLine($this->load->view( 'create/mill_js', $data, true));

		$this->addJSFile('/assets/js/std/raphael.js' ); //vector library
		$this->addJSFile('/assets/js/std/modernizr-touch.js' ); //touch device detection
		$this->addJSFile('/assets/js/std/jogcontrols.js' ); //jog controls
		$this->addJSFile('/assets/js/std/jogtouch.js' ); //jog controls

		$this->addJSFile('/assets/js/plugin/fuelux/wizard/wizard.min.old.js'); //wizard
		$this->addJsInLine($this->load->view( 'std/task_wizard_js', $data, true));
		
		$this->addJsInLine($this->load->view( 'std/select_file_js', $data, true));
		
		$this->addJSFile('/assets/js/plugin/knob/jquery.knob.min.js');
		$this->addJsInLine($this->load->view( 'std/jog_setup_js', $data, true));
		
		$this->addJsInLine($this->load->view( 'std/task_execute_js', $data, true));
		$this->addJsInLine($this->load->view( 'std/task_finished_js', $data, true));

		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	private function doLaser($fileId)
	{
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('plugin_helper');
		$this->load->model('Files', 'files');
		
		$data = array();
		$data['runningTask'] = $this->runningTask;
		$data['file_id'] = '';
		
		// Skip file selection step if fileID is provided
		$file = $this->files->get($fileId, 1);
		$file_is_ok = False;
		if($file)
		{
			if($file['print_type'] == 'laser')
			{
				$data['file_id'] = $fileId;
				$file_is_ok = True;
				$data['wizard_jump_to'] = 2; // jump to step 2 if fileID is available
			}
		}
		
		// Skip to Job Execution step if task is already running
		$task_is_running = False;
		if($data['runningTask'])
		{
			$data['wizard_jump_to'] = 4;
			$task_is_running = True;
		}
		
		//$data['wizard_jump_to'] = 0;
		
		$data['type']      = 'laser';
		$data['type_label'] = 'Engraving';
		
		//~ $data['z_height_values'] = array('0.1' => '0.1', '0.01' => '0.01');
		
		// select_file
		$data['get_files_url'] = 'std/getFiles/laser';
		$data['get_reacent_url'] = 'std/getRecentFiles/laser';
		
		// task_wizard
		$data['start_task_url'] = 'create/startLaserTask';
		
		// jog_setup
		$data['jog_message'] = 'Position the laser point to the origin (bottom-left corner) of the drawing. Jog to desired XY position, press <i class="fa fa-bullseye"></i> and then press "Start" ';
		$data['jog_image'] = '/assets/img/controllers/create/laser/fabui_laser_02a.png';
		$data['fourth_axis'] = False;
		
		// job_execute
		$data['set_rpm_function'] = 'setLaserPWM';
		$data['rpm_label'] = 'PWM';
		//~ $data['rpm_message'] = 'PWM value set to:';
		$data['rpm_min'] = 0;
		$data['rpm_max'] = 255;
		
		// job finish
		$data['z_height_save_message'] = "Z's height correction is <strong><span class=\"z-height\"></span></strong>, do you want to save it and override the value for the next engraving?";
		$data['task_jump_restart'] = 3;
		
		$data['steps'] = array(
				array('number'  => 1,
				 'title'   => 'Choose File',
				 'content' => $this->load->view( 'std/select_file', $data, true ),
				 'active'  => !$file_is_ok && !$task_is_running
			    ),
				array('number'  => 2,
				 'title'   => 'Safety',
				 'content' => $this->load->view( 'std/laser_safety', $data, true ),
				 'active'  => $file_is_ok && !$task_is_running
			    ),
				array('number'  => 3,
				 'title'   => 'Get Ready',
				 'content' => $this->load->view( 'std/jog_setup', $data, true ),
			    ),
				array('number'  => 4,
				 'title'   => 'Laser Engraving',
				 'content' => $this->load->view( 'std/task_execute', $data, true ),
				 'active' => $task_is_running
			    ),
				array('number'  => 5,
				 'title'   => 'Finish',
				 'content' => $this->load->view( 'std/task_finished', $data, true )
			    )
			);
		
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widgeFooterButtons = '';

		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-make-laser';
		$widget->header = array('icon' => 'fa-cube', "title" => "<h2>Laser Engraving</h2>");
		$widget->body   = array('content' => $this->load->view('std/task_wizard', $data, true ), 'class'=>'fuelux', 'footer'=>$widgeFooterButtons);

		$this->addCssFile('/assets/css/std/select_file.css');
		$this->addCssFile('/assets/css/std/jog_setup.css');
		$this->addCssFile('/assets/css/std/jogtouch.css');
		$this->addCssFile('/assets/css/std/jogcontrols.css');

		$this->addJSFile('/assets/js/plugin/datatables/jquery.dataTables.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.colVis.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.tableTools.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.bootstrap.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatable-responsive/datatables.responsive.min.js'); //datatable */
		
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.cust.min.js'); 
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.resize.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.fillbetween.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.time.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.tooltip.min.js');

		$this->addJsInLine($this->load->view( 'create/laser_js', $data, true));

		$this->addJSFile('/assets/js/std/raphael.js' ); //vector library
		$this->addJSFile('/assets/js/std/modernizr-touch.js' ); //touch device detection
		$this->addJSFile('/assets/js/std/jogcontrols.js' ); //jog controls
		$this->addJSFile('/assets/js/std/jogtouch.js' ); //jog controls

		$this->addJSFile('/assets/js/plugin/fuelux/wizard/wizard.min.old.js'); //wizard
		$this->addJsInLine($this->load->view( 'std/task_wizard_js', $data, true));
		
		$this->addJsInLine($this->load->view( 'std/select_file_js', $data, true));
		
		$this->addJSFile('/assets/js/plugin/knob/jquery.knob.min.js');
		$this->addJsInLine($this->load->view( 'std/jog_setup_js', $data, true));
		
		$this->addJsInLine($this->load->view( 'std/task_execute_js', $data, true));
		$this->addJsInLine($this->load->view( 'std/task_finished_js', $data, true));
		
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	public function startPrintTask()
	{
		$data = $this->input->post();
		//load helpers
		$this->load->helpers('fabtotum_helper');
		$this->load->model('Files', 'files');
		$fileToCreate = $this->files->get($data['idFile'], 1);
		$temperatures = readInitialTemperatures($fileToCreate['full_path']);
		resetTaskMonitor();
		if($temperatures == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => 'File not found')));
			return;
		}
		
		$preparingResult = doMacro('prepare_additive', '', [ $temperatures['extruder'], $temperatures['bed'] ]);
		if($preparingResult['response'] != 'ok'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $preparingResult['message'])));
			return;
		}
		
		if($data['calibration'] == 'auto_bed_leveling'){
			$calibrationResult = doMacro("auto_bed_leveling");
			if($calibrationResult['response'] != 'ok'){
				$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $calibrationResult['message'])));
				return;
			}
		}
		else
		{
			$calibrationResult = doMacro("home_all");
			if($calibrationResult['response'] != 'ok'){
				$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $calibrationResult['message'])));
				return;
			}
		}
		
		$startPrintResult = doMacro('start_additive');
		if($startPrintResult['response'] != 'ok'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $startPrintResult['message'], 'trace'=>$startPrintResult['trace'], 'error' => $startPrintResult['reply'])));
			return;
		}
		//get object record
		$object = $this->files->getObject($fileToCreate['id']);
		//ready to print
		//add record to DB
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
			'user'       => $this->session->user['id'],
			'controller' => 'make',
			'type'       => 'print',
			'status'     => 'running',
			'id_file'    => $data['idFile'],
			'id_object'  => $object['id'],
			'start_date' => date('Y-m-d H:i:s')
		);
		$taskId   = $this->tasks->add($taskData);
		$userID   = $this->session->user['id'];
		
		//start print
		$printArgs = array(
						'-T' => $taskId, 
						'-F' => $fileToCreate['full_path']
						);
		startPyScript('print.py', $printArgs);
		
		$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => true, 'id_task' => $taskId, 'temperatures' => $temperatures)));
	}
	
	public function startMillTask()
	{
		$data = $this->input->post();
		//load helpers
		$this->load->helpers('fabtotum_helper');
		$this->load->model('Files', 'files');
		$fileToCreate = $this->files->get($data['idFile'], 1);
		
		//reset task monitor file
		resetTaskMonitor();
		$startSubtractive = doMacro('start_subtractive');
		if($startSubtractive['response'] =! 'ok'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $startSubtractive['message'])));
			return;
		}
		//get object record
		$object = $this->files->getObject($fileToCreate['id']);
		//ready to print
		//add record to DB
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
				'user'       => $this->session->user['id'],
				'controller' => 'make',
				'type'       => 'mill',
				'status'     => 'running',
				'id_file'    => $data['idFile'],
				'id_object'  => $object['id'],
				'start_date' => date('Y-m-d H:i:s')
		);
		$taskId   = $this->tasks->add($taskData);
		
		//start milling
		$printArgs = array(
				'-T' => $taskId,
				'-F' => $fileToCreate['full_path']
		);
		startPyScript('mill.py', $printArgs);
		
		$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => true, 'id_task' => $taskId)));
	}
	
	public function startLaserTask()
	{
		$data = $this->input->post();
		//load helpers
		$this->load->helpers('fabtotum_helper');
		$this->load->model('Files', 'files');
		$fileToCreate = $this->files->get($data['idFile'], 1);
		
		//reset task monitor file
		resetTaskMonitor();
		$startSubtractive = doMacro('start_subtractive');
		if($startSubtractive['response'] =! 'ok'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $startSubtractive['message'])));
			return;
		}
		//get object record
		$object = $this->files->getObject($fileToCreate['id']);
		//ready to print
		//add record to DB
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
				'user'       => $this->session->user['id'],
				'controller' => 'make',
				'type'       => 'laser',
				'status'     => 'running',
				'id_file'    => $data['idFile'],
				'id_object'  => $object['id'],
				'start_date' => date('Y-m-d H:i:s')
		);
		$taskId   = $this->tasks->add($taskData);
		
		//start milling
		$printArgs = array(
				'-T' => $taskId,
				'-F' => $fileToCreate['full_path']
		);
		startPyScript('mill.py', $printArgs);
		
		$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => true, 'id_task' => $taskId)));
	}

}
 
?>
