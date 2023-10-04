<?php

require 'speedup-functions.php';

$options = getopt( "", array(
	"input:",
	"output:",
	"skip_opening:",
	"skip_closing:",
	"max_speed:",
	"function:",
	"debug",
) );

if ( empty( $options['input'] ) ) {
	die( "You must provide an input file.\n" );
}

if ( ! file_exists( $options['input'] ) ) {
	die( "Video file " . $options['input'] . " does not exist.\n" );
}

if ( empty( $options['output'] ) ) {
	// The default output file is the input file with '(speedlapse)' added.
	$options['output'] = preg_replace( '/\.[^\.]+$/', '', $options['input'] ) . ' (speedlapse).mp4';
}

if ( ! isset( $options['skip_opening'] ) ) {
	$options['skip_opening'] = 0;
}

if ( ! isset( $options['skip_closing'] ) ) {
	$options['skip_closing'] = 0;
}

if ( ! isset( $options['max_speed'] ) ) {
	$options['max_speed'] = 5;
}

if ( ! isset( $options['function'] ) ) {
	$options['function'] = 'speedup_default';
}

$duration = duration( $options['input'] );
$frame_count = frame_count( $options['input'] );

debug( "Video duration: " . $duration );
debug( "Video frame count: " . $frame_count );

$tmp_dir = sys_get_temp_dir();

$frame_file_prefix = microtime( true );

// Generate individual files of all the frames.
shell_exec( "ffmpeg -y -i " . escapeshellarg( $options['input'] ) . " -qmin 1 -qmax 1 -q:v 1 " . escapeshellarg( $tmp_dir . '/' . $frame_file_prefix . '-frame%03d.jpg' ) );

// Now make a list of which frames should appear in our output. If the speed function speeds up the video,
// we'll skip frames. If it slows it down, we'll repeat frames.
$frames_to_keep = array();

// Keep track of the "ideal" frame that we currently want to keep. This will almost never be an integer.
// For example, for a speed of 1.2x, a series of ideal frames might be 0, 1.2, 2.4, 3.6, 4.8, 6.0.
// We can't keep frame 2.4, so the frames we actually use will be 0, 1, 2, 4, 5, 6.

$ideal_i = 0;

do {
	if ( $ideal_i < ( $options['skip_opening'] * 30 ) ) {
		$ideal_i++;
	} else if ( $ideal_i >= ( $frame_count - ( $options['skip_closing'] * 30 ) ) ) {
		break;
	} else {
		$frames_to_keep[] = 1 + round( $ideal_i ); // I count at zero, ffmpeg counts at 1.

		$current_speed = 1 + ( call_user_func( $options['function'], $ideal_i, $frame_count ) * ( $options['max_speed'] - 1 ) );

		debug( "Current speed is " . $current_speed );

		// If the current speed is 1x, we advance 1 frame. If the speed is 10x, we advance 10 frames.
		$ideal_i += $current_speed;

		debug( "Keep frame " . round( $ideal_i ) . " (ideally " . $ideal_i . ")" );
	}
} while ( round( $ideal_i ) < ( $frame_count - 1 ) );

debug( "Kept " . count( $frames_to_keep ) . " out of " . $frame_count );

// Make a list of the frames we want to feed to ffmpeg.
$concat_file = tempnam( sys_get_temp_dir(), 'speedlapse-concat-' );

foreach ( $frames_to_keep as $frame_to_keep ) {
	file_put_contents( $concat_file, "file '" . ( $tmp_dir . '/' . $frame_file_prefix . '-frame' . sprintf( '%03d', $frame_to_keep ) . ".jpg" ) . "'\n", FILE_APPEND );
}

// Now assemble those frames into a video.
shell_exec( 'ffmpeg -y -f concat -safe 0 -i ' . escapeshellarg( $concat_file ) . ' ' . escapeshellarg( $options['output'] ) );

// Clean up.
unlink( $concat_file );

// Delete frame JPEGs.
for ( $i = 1; $i <= $frame_count; $i++ ) {
	unlink( $tmp_dir . '/' . $frame_file_prefix . '-frame' . sprintf( '%03d', $i ) . ".jpg" );
}

function duration( $video ) {
	return floatval( trim( shell_exec( "ffprobe -v 0 -show_entries format=duration -of compact=p=0:nk=1 " . escapeshellarg( $video ) ) ) );
}

function frame_count( $video ) {
	return intval( trim( shell_exec( "ffprobe -v error -select_streams v:0 -show_entries stream=nb_frames -of default=nokey=1:noprint_wrappers=1 " . escapeshellarg( $video ) ) ) );
}

function debug( $log_str ) {
	global $options;

	if ( isset( $options['debug'] ) ) {
		echo "LOG: " . $log_str . "\n";
	}
}