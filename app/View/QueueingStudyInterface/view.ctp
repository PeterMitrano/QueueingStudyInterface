<?php
/**
 * Queueing study Interface Controller
 *
 * The Queueing Study interface controller. This interface will allow for navigation and manipulation controls.
 *
 * @author		Russell Toris - rctoris@wpi.edu
 * @copyright	2014 Worcester Polytechnic Institute
 * @link		https://github.com/petermitrano/QueueingStudyInterface
 * @since		QueueingStudyInterface v 0.0.1
 * @version		0.0.6
 * @package		app.Controller
 */
?>

<?php
//custom styling
echo $this->Html->css('QueueingStudyInterface');
?>

<?php
// connect to ROS
echo $this->Rms->ros($environment['Rosbridge']['uri']);

// setup the TF client
echo $this->Rms->tf(
	$environment['Tf']['frame'],
	$environment['Tf']['angular'],
	$environment['Tf']['translational'],
	$environment['Tf']['rate']
);
?>

<section class='wrapper style4'>
	<div class='content center'>
		<section>
			<div class='row'>
				<div class='12u'>
					<h3 id='queueStatus'></h3>
				</div>
			</div>
		</section>
		<div class='overlay hidden' id='tutorial'>
			<div class='highlight' id='welcome_highlight'>
				<strong>
					Welcome! Press next to proceed to the next instruction.
				</strong>
			</div>
			<div class='highlight' id='urdf_highlight'>
				control the robot's arm by dragging the arrows, or spinning the rings.
				<br/>
				Click on the green markers to drive to fixed locations.
			</div>
			<div class='highlight' id='buttons_highlight'>
				Clicking these buttons will control the arm.
				<br/>
				Segment will look for objects and add them to the 3D world.
			</div>
			<div class='highlight' id='keyboard_highlight'>
				Drive the robot with the <strong>W, A, S, D</strong> keys
				<br/>
				Tilt the camera with <strong>Up</strong> and <strong>Down</strong> keys
			</div>
			<div class='highlight' id='feedback_highlight'>
				Feedback may appear if something goes wrong
			</div>
			<button id='next' class='button'>Next</button>
		</div>
		<div class='row' id='main_content'>
			<div id='important_feedback' class='feedback-overlay hidden'>
				<h1>ERROR: ...</h1>
			</div>
			<div id='fatal_feedback' class='feedback-overlay fatal hidden'>
				<h1>FATAL ERROR: ...</h1>
			</div>
			<div class='6u'>
				<div id='viewer'>
				</div>
			</div>
			<div class='6u stream'>
				<div id='mjpeg'>
				</div>
			</div>
		</div>
		<div class='row'>
			<section class='4u'>
				<a href='#' class='button fit' id='segment'>Segment</a>
				<br/>
				<a href='#' class='button fit' id='ready'>Ready Arm</a>
				<br/>
				<a href='#' class='button fit' id='retract'>Retract Arm</a>
			</section>
			<section class='4u'>
				Use the <strong>W, A, S, D</strong> keys to drive the robot. Use the <strong>arrow keys</strong> to
				move the camera.Use the <strong>3D interface</strong> to control the arm. Right clicking the gripper
				will
				provide additional
				actions.
			</section>
			<section class='4u'>
				<div id='feedback'>
				</div>
				<button id='clearFeedback' class='button special'>clear</button>
			</section>
		</div>
	</div>
</section>

<script>
	_S = Math.min(((window.innerWidth / 2) - 120), window.innerHeight * 0.60);
	new MJPEGCANVAS.MultiStreamViewer({
		divID: 'mjpeg',
		host: 'carl-bot',
		width: _S,
		height: _S * 0.85,
		quality: 60,
		topics: ['/camera/rgb/image_raw', '/sink_camera/rgb/image_raw', '/coffee_table_camera/rgb/image_raw'],
		labels: ['First Person', 'Sink', 'Coffee Table']
	});


	_VIEWER = new ROS3D.Viewer({
		divID: 'viewer',
		width: _S,
		height: _S * 0.85,
		antialias: true,
		background: '#50817b',
		intensity: 0.660000
	});
</script>

<script>
	var armClient = new ROSLIB.ActionClient({
		ros: _ROS,
		serverName: 'carl_moveit_wrapper/common_actions/arm_action',
		actionName: 'carl_moveit/ArmAction'
	});

	var segmentClient = new ROSLIB.Service({
		ros: _ROS,
		name: '/rail_segmentation/segment',
		serviceType: 'std_srvs/Empty'
	});
</script>

<script>
	/** display tutorial information as overlays with timeouts */
	var tutorial_hl = $('#tutorial');
	var welcome_hl = $('#welcome_highlight');
	var urdf_hl = $('#urdf_highlight');
	var buttons_hl = $('#buttons_highlight');
	var feedback_hl = $('#feedback_highlight');
	var keyboard_hl = $('#keyboard_highlight');

	function tutorial(){
		tutorial_hl.animate({opacity: 0.8},2000);
		welcome_hl.fadeIn('slow');
		$("#next").fadeIn('slow');
	}

	function urdf_tutorial() {
		urdf_hl.fadeIn('slow');

		// Interactive Markers for parking and carl's hand
		_IM = new ROS3D.InteractiveMarkerClient({
			ros: _ROS,
			tfClient: _TF,
			camera: _VIEWER.camera,
			rootObject: _VIEWER.selectableObjects,
			loader: 1,
			path: 'http://resources.robotwebtools.org/',
			topic: '/carl_interactive_manipulation'
		});
		_PARKING_MARKERS = new ROS3D.InteractiveMarkerClient({
			ros: _ROS,
			tfClient: _TF,
			camera: _VIEWER.camera,
			rootObject: _VIEWER.selectableObjects,
			topic: '/parking_markers'
		});
	}

	function buttons_tutorial(){
		urdf_hl.fadeOut('slow');
		buttons_hl.fadeIn('slow');
		$('#segment').addClass('special');
		$('#ready').addClass('special');
		$('#retract').addClass('special');
		//create the callbacks for the segment/ready/retract buttons
		$('#segment').click(function (e) {
			e.preventDefault();
			console.log('segmenting');
			var request = new ROSLIB.ServiceRequest({});
			segmentClient.callService(request, function (result) {
			});
		});
		$('#ready').click(function (e) {
			console.log('readying...');
			e.preventDefault();
			var goal = new ROSLIB.Goal({
				actionClient: armClient,
				goalMessage: {
					action: 0
				}
			});
			goal.send();
		});
		$('#retract').click(function (e) {
			console.log('retracting...');
			e.preventDefault();
			var goal = new ROSLIB.Goal({
				actionClient: armClient,
				goalMessage: {
					action: 1
				}
			});
			goal.send();
		});
	}

	function feedback_tutorial() {
		buttons_hl.fadeOut('slow');
		feedback_hl.fadeIn('slow');
		$('#fatal_feedback').fadeIn('slow');
		$('#important_feedback').fadeIn('slow');
	}

	function keyboard_tutorial() {
		$('#fatal_feedback').fadeOut('slow');
		$('#important_feedback').fadeOut('slow');
		feedback_hl.fadeOut('slow');
		keyboard_hl.fadeIn('slow');

		//keyboard tele-op
		_TELEOP = new KEYBOARDTELEOP.Teleop({ros: _ROS, topic: '/cmd_vel_safe'});
		_TELEOP.throttle = 0.800000;

		/** arrow keys
		 * on key up and key down send commands to drive or tilt camera
		 */
		var body = document.getElementsByTagName('body')[0];
		body.addEventListener('keydown', function (e) {
			if ([37, 38, 39, 40].indexOf(e.keyCode) > -1) {
				e.preventDefault();
			}
			handleKey(e.keyCode, true);
		}, false);
		body.addEventListener('keyup', function (e) {
			handleKey(e.keyCode, false);
		}, false);

	}

	function closeTutorial() {
		tutorial_hl.fadeOut();
		urdf_hl.fadeOut();
		feedback_hl.fadeOut();
		keyboard_hl.fadeOut();
		initiateControl();
	}



	var step = 0;
	$("#next").click(function (){
		switch(step){
			case 0:
				urdf_tutorial();
				break;
			case 1:
				buttons_tutorial();
				break;
			case 2:
				feedback_tutorial();
				break;
			case 3:
				keyboard_tutorial();
				break;
			case 4:
				closeTutorial();
				break;
		}
		step++;
	});

</script>

<script>
	var enabled = false;
	var rosQueue = new ROSQUEUE.Queue({
		ros: _ROS,
		studyTime: 10,
		userId: <?php
			if (isset($appointment['Appointment']['user_id'])){
				echo $appointment['Appointment']['user_id'];
			}
			else {
				echo -1;
			}
		?>
	});

	/*
	 * notify user if I receive a now_active message
	 * This method is called once when you're first enabled
	 * for a method called continuously, use on 'enabled'
	 * When this is called, add all the control elements to the interface.
	 * This includes interactive markers, keyboard controls, and button controls
	 * @param message Int32 message, the id of the user to remove
	 */
	rosQueue.on('first_enabled', function () {
		//begin the tutorial!!!
		setTimeout(tutorial, 500);
	});

	function initiateControl() {
		$('#queueStatus').html('robot active, begin your control');
		enabled = true;
	}

	/**
	 * when I receive a new time update the interface
	 * @param data objected with time in min & sec
	 */
	rosQueue.on('wait_time', setTime);
	function setTime(data) {
		var d = new Date();
		d.setSeconds(data.sec);
		d.setMinutes(data.min);
		//substring removes hours and AM/PM
		document.getElementById('queueStatus').innerHTML = 'Your waiting time is ' + d.toLocaleTimeString().substring(2, 8);
	}

	/*
	 * notify user if I receive a pop_front message
	 * @param message Int32 message, the id of the user to remove
	 */
	rosQueue.on('disabled', function () {
		enabled = false;
		document.getElementById('segment').className = 'button fit';
		document.getElementById('ready').className = 'button fit';
		document.getElementById('retract').className = 'button fit';

	});

	/**
	 * whne the user is dequeued, force refresh the page. This will add them at the end of the queue and end all controls
	 */
	rosQueue.on('dequeue', function () {
		location.reload();
	});

	/**
	 * when I exit the webpage, kick me out
	 */
	window.onbeforeunload = function () {
		rosQueue.dequeue();
		return undefined;
	};

	/**
	 * display feedback to the user. Feedback has a string to display and a severity level (0-3).
	 * 0 - debug. will be displayed under the interface in smaller test
	 * 2 - error. will be overlayed on the interface
	 * 3 - fatal. will be overlayed on the interface in red
	 */
	var feedback = new ROSLIB.Topic({
		ros: _ROS,
		name: 'carl_safety/error',
		messageType: 'carl_safety/Error'
	});

	feedback.subscribe(function (message) {
		console.log(message);
		var feedback = document.getElementById('feedback');
		var feedbackOverlay = document.getElementById('important_feedback');
		var fatalFeedbackOverlay = document.getElementById('fatal_feedback');

		switch (message.severity) {
			case 2:
				if (message.resolved) {
					fatalFeedbackOverlay.className = 'feedback-overlay fatal hidden';
					feedbackOverlay.className = 'feedback-overlay hidden';
				}
				else {
					fatalFeedbackOverlay.className = 'feedback-overlay fatal';
					fatalFeedbackOverlay.innerHTML = message.message;
				}
				break;

			case 1:
				if (message.resolved) {
					feedbackOverlay.className = 'feedback-overlay hidden';
				}
				else {
					feedbackOverlay.className = 'feedback-overlay';
					feedbackOverlay.innerHTML = message.message;
				}
				break;

			case 0:
				feedback.innerHTML += message.message;
				feedback.innerHTML += '<br/><br/>';
				//this will keep the div scrolled to the bottom
				feedback.scrollTop = feedback.scrollHeight;
		}

	});

	$('#clearFeedback').click(function () {
		document.getElementById('feedback').innerHTML = 'awaiting feedback..';
	});

	/**
	 * Add me when I first visit the site
	 */
	rosQueue.enqueue();
</script>

<script>
	_VIEWER.camera.position.x = 1.8;
	_VIEWER.camera.position.y = 1.0;
	_VIEWER.camera.position.z = 3.0;
	_VIEWER.camera.rotation.x = -0.65;
	_VIEWER.camera.rotation.y = 0.82;
	_VIEWER.camera.rotation.z = 2.38;

	_VIEWER.addObject(
		new ROS3D.SceneNode({
			object: new ROS3D.Grid({cellSize: 0.75, size: 20, color: '#2B0000'}),
			tfClient: _TF,
			frameID: '/map'
		})
	);
</script>
<?php
// URDF
foreach ($environment['Urdf'] as $urdf) {
	echo $this->Rms->urdf(
		$urdf['param'],
		$urdf['Collada']['id'],
		$urdf['Resource']['url']
	);
}
?>

<script>
	// add camera controls
	var headControl = new ROSLIB.Topic({
		ros: _ROS,
		name: 'asus_controller/tilt',
		messageType: 'std_msgs/Float64'
	});
	var frontControl = new ROSLIB.Topic({
		ros: _ROS,
		name: 'creative_controller/pan',
		messageType: 'std_msgs/Float64'
	});

	var handleKey = function (keyCode, keyDown) {
		var pan = 0;
		var tilt = 0;

// check which key was pressed
		switch (keyCode) {
			case 38:
// up
				tilt = (keyDown) ? -10 : 0;
				headControl.publish(new ROSLIB.Message({data: tilt}));
				break;
			case 40:
// down
				tilt = (keyDown) ? 10 : 0;
				headControl.publish(new ROSLIB.Message({data: tilt}));
				break;
			case 37:
// left
				pan = (keyDown) ? 10 : 0;
				frontControl.publish(new ROSLIB.Message({data: pan}));
				break;
			case 39:
// right
				pan = (keyDown) ? -10 : 0;
				frontControl.publish(new ROSLIB.Message({data: pan}));
				break;
		}
	}


</script>
