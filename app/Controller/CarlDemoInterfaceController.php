<?php
App::uses('InterfaceController', 'Controller');

/**
 * CARL Demo Interface Controller
 *
 * The CARL demo interface controller. This interface will allow for navigation and manipulation controls.
 *
 * @author		Russell Toris - rctoris@wpi.edu
 * @copyright	2014 Worcester Polytechnic Institute
 * @link		https://github.com/WPI-RAIL/CarlDemoInterface
 * @since		CarlDemoInterface v 0.0.1
 * @version		0.0.3
 * @package		app.Controller
 */
class CarlDemoInterfaceController extends InterfaceController {

/**
 * The basic view action. All necessary variables are set in the main interface controller.
 *
 * @return null
 */
	public function view() {
		// set the title of the HTML page
		$this->set('title_for_layout', 'CARL (Crowdsourcing for Autonomous Robot Learning)');
		// we will need some RWT libraries
		$this->set('rwt',
			array(
				'roslibjs' => 'current',
				'ros2djs' => 'current',
				'nav2djs' => 'current',
				'ros3djs' => 'current',
				'keyboardteleopjs' => 'current'
			)
		);
	}
}
