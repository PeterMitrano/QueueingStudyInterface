<?php
/**
 * Basic Interface View
 *
 * The basic interface displays a camera feed and keyboard teleop.
 *
 * @author		Russell Toris - rctoris@wpi.edu
 * @copyright	2014 Worcester Polytechnic Institute
 * @link		https://github.com/WPI-RAIL/CarlDemoInterface
 * @since		CarlDemoInterface v 0.0.1
 * @version		0.0.6
 * @package		app.View.CarlDemoInterface
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

<section class="wrapper style4 container">
	<div class="content center">
		<section>
			<div>
				<h3 id="queue_status"></h3>
			</div>
			<div>
				<?php
					echo $this->Html->link(
						'Return To My Account',
						array('controller' => 'users' , 'action' => 'view')
					);
				?>
			</div>
			<div class="row">
				<section class="6u">
					<?php echo $this->Rms->ros3d('#50817b', 0.66, 0.75); ?>
				</section>
				<section class="6u stream">
					<?php
						echo $this->Rms->mjpegStream(
							$environment['Mjpeg']['host'],
							$environment['Mjpeg']['port'],
							$environment['Stream'][0]['topic'],
							$environment['Stream'][0]
						);
					?>
				</section>
				<section class="4u">
					<?php echo $this->Rms->ros2d('#00817b'); ?>
				</section>
				<section class="2u">
					<a href="#" class="button small special" id="segment">Segment</a>
					<br />
					<a href="#" class="button small special" id="ready">Ready Arm</a>
					<br />
					<a  href="#" class="button small special" id="retract">Retract Arm</a>
				</section>
				<section class="6u">
					<br />
					Use the <strong>W, A, S, D</strong> keys to drive the robot. Use the <strong>arrow keys</strong> to
					move the camera. Click on the map to <strong>autonomously drive</strong> the robot. Use the
					<strong>3D interface</strong> to control the arm. Right clicking the gripper will provide additional
					actions.
					<br />
				</section>
			</div>
		</section>
	</div>
</section>

<script>
	var armClient = new ROSLIB.ActionClient({
		ros : _ROS,
		serverName : 'carl_moveit_wrapper/common_actions/ready_arm',
		actionName : 'wpi_jaco_msgs/HomeArmAction'
	});

	var segmentClient = new ROSLIB.Service({
		ros : _ROS,
		name : '/rail_segmentation/segment_auto',
		serviceType : 'std_srvs/Empty'
	});

	document.getElementById('segment').onclick=function() {
		var request = new ROSLIB.ServiceRequest({});
		segmentClient.callService(request, function(result) {});
	};
	document.getElementById('ready').onclick=function() {
		var goal = new ROSLIB.Goal({
			actionClient : armClient,
			goalMessage : {
				retract : false
			}
		});
		goal.send();
	};
	document.getElementById('retract').onclick=function() {
		var goal = new ROSLIB.Goal({
			actionClient : armClient,
			goalMessage : {
				retract : true,
				retractPosition : {
					position : true,
					armCommand : true,
					fingerCommand : false,
					repeat : false,
					joints : [-2.57, 1.39, 0.527, -.084, .515, -1.745]
				},
				numAttempts : 3
			}
		});
		goal.send();
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
			object : new ROS3D.Grid({cellSize:0.75, size:20, color:'#2B0000'}),
			tfClient : _TF,
			frameID : '/map'
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
new NAV2D.ImageMapClientNav({
	ros : _ROS,
	rootObject : _VIEWER2D.scene,
	viewer : _VIEWER2D,
	serverName : '/move_base_safe',
	image : '/img/CarlDemoInterface/CarlSpace.png',
	withOrientation : true
});
</script>

<script>
	// add camera controls
	var headControl = new ROSLIB.Topic({
		ros : _ROS,
		name : 'asus_controller/tilt',
		messageType : 'std_msgs/Float64'
	});
	var frontControl = new ROSLIB.Topic({
		ros : _ROS,
		name : 'creative_controller/pan',
		messageType : 'std_msgs/Float64'
	});

	 var handleKey = function(keyCode, keyDown) {
		var pan = 0;
		var tilt = 0;

		// check which key was pressed
		switch (keyCode) {
			case 38:
				// up
				tilt = (keyDown) ? -10 : 0;
				headControl.publish(new ROSLIB.Message({data:tilt}));
				break;
			case 40:
				// down
				tilt = (keyDown) ? 10 : 0;
				headControl.publish(new ROSLIB.Message({data:tilt}));
				break;
			case 37:
				// left
				pan = (keyDown) ? 10 : 0;
				frontControl.publish(new ROSLIB.Message({data:pan}));
				break;
			case 39:
				// right
				pan = (keyDown) ? -10 : 0;
				frontControl.publish(new ROSLIB.Message({data:pan}));
				break;
		}
	}

	var body = document.getElementsByTagName('body')[0];
	body.addEventListener('keydown', function(e) {
		// arrow keys
		if([37, 38, 39, 40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
		handleKey(e.keyCode, true);
	}, false);
	body.addEventListener('keyup', function(e) {
		handleKey(e.keyCode, false);
	}, false);
</script>

<script>

	var enqueue_pub = new ROSLIB.Topic({
		ros : _ROS,
		name : 'rms_enqueue',
		messageType: 'std_msgs/Int32'
	});
	enqueue_pub.advertise();

	var dequeue_pub= new ROSLIB.Topic({
		ros : _ROS,
		name : 'rms_dequeue',
		messageType: 'std_msgs/Int32'
	});
	dequeue_pub.advertise();

	var user_id = <?php echo $appointment['Appointment']['user_id']?>;

	var enqueue = function(){
		console.log("enqueue");
		enqueue_pub.publish(new ROSLIB.Message({data : user_id}));
	};

	var dequeue = function(){
		console.log("dequeue");
		dequeue_pub.publish(new ROSLIB.Message({data : user_id}));
	};

	var queue_sub = new ROSLIB.Topic({
		ros : _ROS,
		name : 'rms_queue',
		messageType : 'rms_queue_manager/RMSQueue'
	});

	var pop_front_sub = new ROSLIB.Topic({
		ros : _ROS,
		name : 'rms_pop_front',
		messageType : 'std_msgs/Int32'
	});


	/**
	 * when I receive a queue, set instructions/enable controls based on position
	 * if I am not in queue, send message to rms_queue_manager node to add me
	 * @param message RMSQueue message, list of UserStatus messages
	 */
	queue_sub.subscribe(function(message){

		var queue_status = document.getElementById("queue_status");
		var queue_status_msg = ""; //do we want a default value?

		var i= message.queue.length;
		while (i--){
			if (user_id === message.queue[i]['user_id']){
				if (i == 0){
					queue_status_msg = "GO GO GO!!!!";
				}
				else
				{
					var wait_time = message.queue[i]['wait_time'].secs;
					queue_status_msg = "position = " + i + "   wait = " + wait_time;
				}
			}
		}

		queue_status.innerHTML = queue_status_msg;
	});

	/**
	 * if I receive a pop_front message with my id, deqeue
	 */
	pop_front_sub.subscribe(function(message){
		var pop_user_id = message.data;
		if (user_id === pop_user_id){
			alert("Sorry, your time with carl is up...");

			//here we should present the user with the options (leave or re-enter queue)

			dequeue();
		}
	});

	/**
	 * when I exit the webpage, kick me out
	 */
	window.onbeforeunload = function(){
		dequeue();
		return undefined;
	}

	/**
	 * Add me!
	 */
	enqueue();
</script>
