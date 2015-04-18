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
		<div class='row' id="main_content">
			<div id="important_feedback" class='feedback-overlay hidden'>
				<h1>ERROR: Shit has hit the fan!</h1>
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
					action feedback...
				</div>
			</section>
		</div>
	</div>
</section>

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
	 * @param message Int32 message, the id of the user to remove
	 */
	rosQueue.on("enabled", function () {
		document.getElementById("queueStatus").innerHTML = "Go go go!";
		document.getElementById('segment').className = "button fit special";
		document.getElementById('ready').className = "button fit special";
		document.getElementById('retract').className = "button fit special";

		//keyboard tele-op
		if (typeof _TELEOP == 'undefined') {
			_TELEOP = new KEYBOARDTELEOP.Teleop({ros: _ROS, topic: "/cmd_vel_safe"});
			_TELEOP.throttle = 0.800000;
		}

		// Interactive Markers
		if (typeof _IM == 'undefined'){
			_IM = new ROS3D.InteractiveMarkerClient({
				ros: _ROS,
				tfClient: _TF,
				camera: _VIEWER.camera,
				rootObject: _VIEWER.selectableObjects,
				loader: 1,
				path: "http://resources.robotwebtools.org/",
				topic: "/carl_interactive_manipulation"
			});
		}
		enabled = true;
	});

	/**
	 * when I receive a new time update the interface
	 * @param data objected with time in min & sec
	 */
	rosQueue.on("wait_time", function (data) {
		document.getElementById("queueStatus").innerHTML = data.min + ":" + data.sec;
	});

	/*
	 * notify user if I receive a pop_front message
	 * @param message Int32 message, the id of the user to remove
	 */
	rosQueue.on("disabled", function () {
		enabled = false;
		document.getElementById('segment').className = "button fit";
		document.getElementById('ready').className = "button fit";
		document.getElementById('retract').className = "button fit";

	});

	rosQueue.on("dequeue", function(){
		document.getElementById("queueStatus").innerHTML = "You're time with carl is up! Refresh to re-enter the queue."
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
	 * 2 - error. will be overlayed on the interface.
	 * 3 - fatal. will be overlayed on the interface with read highlights
	 */
	var feedback = new ROSLIB.Topic({
		ros: _ROS,
		name: 'carl_safety/error',
		messageType: 'carl_safety/Error'
	});
	feedback.subscribe(function (message){
		console.log(message);

		var feedback = document.getElementById("feedback");

		var feedbackOverlay = document.getElementById("important_feedback");
		feedbackOverlay.className = "feedback-overlay hidden";

		switch(message.severity){
			case 2:
				if (message.resolved){
					feedbackOverlay.className = "feedback-overlay";
					fadeOverlay();
				}
				else {
					feedbackOverlay.className = "feedback-overlay fatal";
				}
			case 1:
				feedbackOverlay.innerHTML = message.message;
			case 0:
				feedback.innerHTML += message.message;
				feedback.innerHTML += "<br/>";
				flashFeedback();
		}

	});

	function flashFeedback(){
		$('#feedback').animate({opacity: 0}, 100 );
		$('#feedback').animate({opacity: 1}, 100 );
	}

	function fadeOverlay(){
		var feedbackOverlay = document.getElementById("important_feedback");
		$('important_feedback').animate({opacity: 0}, 1000 );
		feedbackOverlay.className = "feedback-overlay hidden";
	}

	/**
	 * Add me when I first visit the site
	 */
	rosQueue.enqueue();
</script>

<script>
	var armClient = new ROSLIB.ActionClient({
		ros: _ROS,
		serverName: 'carl_moveit_wrapper/common_actions/ready_arm',
		actionName: 'wpi_jaco_msgs/HomeArmAction'
	});

	var segmentClient = new ROSLIB.Service({
		ros: _ROS,
		name: '/rail_segmentation/segment_auto',
		serviceType: 'std_srvs/Empty'
	});

	document.getElementById('segment').onclick = function () {
		var request = new ROSLIB.ServiceRequest({});

		if (enabled) {
			segmentClient.callService(request, function (result) {
			});
		}
		else {
			console.log("disabled!");
		}
	};
	document.getElementById('ready').onclick = function () {
		var goal = new ROSLIB.Goal({
			actionClient: armClient,
			goalMessage: {
				retract: false
			}
		});

		if (enabled) {
			goal.send();
			console.log("readying...");
		}
		else {
			console.log("disabled!");
		}
	};
	document.getElementById('retract').onclick = function () {
		var goal = new ROSLIB.Goal({
			actionClient: armClient,
			goalMessage: {
				retract: true,
				retractPosition: {
					position: true,
					armCommand: true,
					fingerCommand: false,
					repeat: false,
					joints: [-2.57, 1.39, 0.527, -.084, .515, -1.745]
				},
				numAttempts: 3
			}
		});

		if (enabled) {
			goal.send();
			console.log("retracting...");
		}
		else {
			console.log("disabled!");
		}

	};
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

	var body = document.getElementsByTagName('body')[0];
	body.addEventListener('keydown', function (e) {
// arrow keys
		if ([37, 38, 39, 40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
		handleKey(e.keyCode, true);
	}, false);
	body.addEventListener('keyup', function (e) {
		handleKey(e.keyCode, false);
	}, false);
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

<script>
/**
* Read the feedback from the action request and make it show!
*/

</script>
