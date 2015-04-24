<?php
/**
 * Basic Interface View
 *
 * The basic interface displays a camera feed and keyboard teleop.
 *
 * @author        Peter Mitrano - pdmitrano@wpi.edu
 * @author        Russell Toris - rctoris@wpi.edu
 * @copyright    2014 Worcester Polytechnic Institute
 * @link        https://github.com/WPI-RAIL/CarlDemoInterface
 * @since        CarlDemoInterface v 0.0.1
 * @version        0.0.6
 * @package        app.View.CarlDemoInterface
 */
?>

<?php
//custom styling
echo $this->Html->css('CarlDemoInterface');
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
					<h3 id='queueStatus'>Queue Status...</h3>
				</div>
			</div>
		</section>
		<div class="overlay hidden" id='tutorial'>
			<div class="highlight hidden"  id="urdf_highlight">
				control the robot's arm here
			</div>
			<div class="highlight hidden"  id="keyboard_highlight">
				Drive the robot with the <strong>W, A, S, D</strong> keys
			</div>
			<div class="highlight hidden" id="feedback_highlight">
				Feedback may appear if something goes wrong
			</div>
		</div>
		<div class='row' id='main_content'>
			<div id='important_feedback' class='feedback-overlay hidden'>
				<h1>ERROR: ...</h1>
			</div>
			<div id='fatal_feedback' class='feedback-overlay fatal hidden'>
				<h1>FATAL ERROR: ...</h1>
			</div>
			<div class='6u'>
				<?php echo $this->Rms->ros3d('#50817b', 0.66, 0.75); ?>
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
				<br/>
				Use the <strong>W, A, S, D</strong> keys to drive the robot. Use the <strong>arrow keys</strong> to
				move the camera.Use the <strong>3D interface</strong> to control the arm. Right clicking the gripper
				will
				provide additional
				actions.
				<br/>
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
	var tutorial_hl = $("#tutorial");
	var urdf_hl = $("#urdf_highlight");
	var feedback_hl = $("#feedback_highlight");
	var keyboard_hl = $("#keyboard_highlight");


	setTimeout(urdf_tutorial,500);

	function urdf_tutorial() {
		tutorial_hl.animate({opacity:0.7});
		urdf_hl.animate({opacity:1.0});;
		setTimeout(feedback_tutorial,2500);
	}

	function feedback_tutorial() {
		urdf_hl.fadeOut();
		$("#fatal_feedback").animate({opacity:1.0});
		$("#important_feedback").animate({opacity:1.0});
		feedback_hl.animate({opacity:1.0});;
		setTimeout(keyboard_tutorial,2500);
	}

	function keyboard_tutorial() {
		feedback_hl.fadeOut();
		$("#fatal_feedback").removeAttr('style');
		$("#important_feedback").removeAttr('style');
		keyboard_hl.animate({opacity:1.0});;
		setTimeout(closeTutorial,2500);
	}

	function closeTutorial(){
		tutorial_hl.fadeOut();
		urdf_hl.fadeOut();
		feedback_hl.fadeOut();
		keyboard_hl.fadeOut();
	}

</script>

<script>
	var enabled = false;
	var rosQueue = new ROSQUEUE.Queue({
		ros: _ROS,
		studyTime: 1,
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
	 * for a method called continuously, use on "enabled"
	 * When this is called, add all the control elements to the interface.
	 * This includes interactive markers, keyboard controls, and button controls
	 * @param message Int32 message, the id of the user to remove
	 */
	rosQueue.on('first_enabled', function () {
		$('#queueStatus').html('robot active, begin your control');
		$('#segment').addClass('special');
		$('#ready').addClass('special');
		$('#retract').addClass('special');

		//keyboard tele-op
		_TELEOP = new KEYBOARDTELEOP.Teleop({ros: _ROS, topic: '/cmd_vel_safe'});
		_TELEOP.throttle = 0.800000;

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

		//create the callbacks for the segment/ready/retract buttons
		$('#segment').click(function (e) {
			e.preventDefault();
			console.log("segmenting");
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

		enabled = true;
	});

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

<script>
	new MJPEGCANVAS.MultiStreamViewer({
		divID: 'mjpeg',
		host: 'carl-bot',
		width: 480,
		height: 430,
		quality: 20,
		topics: ['/camera/rgb/image_raw', '/sink_camera/rgb/image_raw', '/coffee_table_camera/rgb/image_raw'],
		labels: ['First Person', 'Sink', 'Coffee Table']
	});
</script>
