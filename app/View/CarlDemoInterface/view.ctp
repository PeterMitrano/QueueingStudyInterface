<?php
/**
 * Basic Interface View
 *
 * The basic interface displays a camera feed and keyboard teleop.
 *
 * @author        Russell Toris - rctoris@wpi.edu
 * @copyright    2014 Worcester Polytechnic Institute
 * @link        https://github.com/WPI-RAIL/CarlDemoInterface
 * @since        CarlDemoInterface v 0.0.1
 * @version        0.0.6
 * @package        app.View.CarlDemoInterface
 */
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

// add teleop
echo $this->Rms->keyboardTeleop($environment['Teleop'][0]['topic'], $environment['Teleop'][0]['throttle']);
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
		<div class='row'>
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
				<a href='#' class='button fit special' id='segment'>Segment</a>
				<br/>
				<a href='#' class='button fit special' id='ready'>Ready Arm</a>
				<br/>
				<a href='#' class='button fit special' id='retract'>Retract Arm</a>
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
				<div id='action_feedback'>
					action feedback...
				</div>
			</section>
		</div>
	</div>
</section>

<script>
	var armClient = new ROSLIB.ActionClient({
		ros: _ROS,
		serverName: 'carl_moveit_wrapper/common_actions/ready_arm',
		actionName: 'wpi_jacoMsgs/HomeArmAction'
	});

	var segmentClient = new ROSLIB.Service({
		ros: _ROS,
		name: '/rail_segmentation/segment_auto',
		serviceType: 'std_srvs/Empty'
	});

	document.getElementById('segment').onclick = function () {
		var request = new ROSLIB.ServiceRequest({});
		segmentClient.callService(request, function (result) {
		});
	};
	document.getElementById('ready').onclick = function () {
		var goal = new ROSLIB.Goal({
			actionClient: armClient,
			goalMessage: {
				retract: false
			}
		});
		goal.on('feedback', function(feedback){
			console.log(feedback);
		});
		goal.send();
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

		goal.on('feedback', function(feedback){
			console.log(feedback);
		});
		goal.send();
		console.log("retracting...");
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

// Interactive Markers
echo $this->Rms->interactiveMarker(
	$environment['Im'][0]['topic'], $environment['Im'][0]['Collada']['id'], $environment['Im'][0]['Resource']['url']
);
?>

<script>
	// add camera controls
	var headControl = new ROSLIB.Topic({
		ros: _ROS,
		name: 'asus_controller/tilt',
		messageType: 'stdMsgs/Float64'
	});
	var frontControl = new ROSLIB.Topic({
		ros: _ROS,
		name: 'creative_controller/pan',
		messageType: 'stdMsgs/Float64'
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
	var rosQueue = new ROSQUEUE.Queue({
		ros : _ROS,
		userId : <?php echo $appointment['Appointment']['user_id']?>
	});


	/**
	 * when I receive a queue, set instructions/enable controls based on position
	 * if I am not in queue, send message to rms_queue_manager node to add me
	 * @param data objected with position, active, and wait keys
	 */
	rosQueue.queueSub.subscribe(function (message) {
		var queueStatus = document.getElementById("queueStatus");
		var queueStatusMsg = "Queue Status..."; //do we want a default value?

		var i = message.queue.length;
		while (i--) {
			if (rosQueue.userId === message.queue[i]['user_id']) {
				if (i == 0) {
					queueStatusMsg = "GO GO GO!!!!";
				}
				else {
					var wait_time = message.queue[i]['wait_time'].secs;
					queueStatusMsg = "position = " + i + "   wait = " + wait_time;
				}
			}
		}

		queueStatus.innerHTML = queueStatusMsg;
	});

	/**
	 * if I receive a pop_front message with my id, deqeue
	 * @param message Int32 message, the id of the user to remove
	 */
	rosQueue.popFrontSub.subscribe(function (message) {
		var pop_userId = message.data;
		if (rosQueue.userId === pop_userId) {
			alert("Sorry, your time with carl is up...");
			rosQueue.dequeue();
		}
	});

	/**
	 * when I exit the webpage, kick me out
	 */
	window.onbeforeunload = function () {
		rosQueue.dequeue();
		return undefined;
	};

	/**
	 * Add me when I first visit the site
	 */
	rosQueue.enqueue();
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
