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
 * @version		0.0.1
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
			<header>
				<p>Use the <strong>W, A, S, D</strong> keys to drive your robot.</p>
			</header>
			<div class="row">
				<section class="7u">
					<?php echo $this->Rms->ros3d('#50817b'); ?>
				</section>
				<section class="5u stream">
					<?php
						$topics = array();
						foreach ($environment['Stream'] as $stream) {
							$topics[] = $stream['topic'];
						}
						echo $this->Rms->mjpegPanel(
							$environment['Mjpeg']['host'], $environment['Mjpeg']['port'], $topics
						);
					?>
				</section>
			</div>
		</section>
	</div>
</section>

<script>
	_VIEWER.camera.position.x = 1.8;
	_VIEWER.camera.position.y = 1.0;
	_VIEWER.camera.position.z = 2.0;
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
echo $this->Rms->urdf(
	$environment['Urdf'][0]['param'],
	$environment['Urdf'][0]['Collada']['id'],
	$environment['Urdf'][0]['Resource']['url']
);

// Interactive Markers
echo $this->Rms->interactiveMarker(
	$environment['Im'][0]['topic'], $environment['Im'][0]['Collada']['id'], $environment['Im'][0]['Resource']['url']
);
?>

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
				break;
			case 40:
				// down
				tilt = (keyDown) ? 10 : 0;
				break;
			case 37:
				// left
				pan = (keyDown) ? 10 : 0;
				break;
			case 39:
				// right
				pan = (keyDown) ? -10 : 0;
				break;
		}

		// publish the commands
		headControl.publish(new ROSLIB.Message({data:tilt}));
		frontControl.publish(new ROSLIB.Message({data:pan}));
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