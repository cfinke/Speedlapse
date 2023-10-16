<?php

/**
 * A speedup function has a range of (0,1). A return value of zero means
 * no speedup. A return value of 1 means $options['max_speed'] speedup.
 *
 * Technically, the range is unbounded. e.g., A return value of 5 means
 * "speed it up 5x more than the max speed." A return value of -1 means
 * "slow it down to (1 / max_speed)."
 *
 * The default speedup function is a sine curve that is zero at frame zero,
 * resulting in a timelapse that smoothly speeds up to the max speed at the
 * halfway point and then slows back down to the original speed by the end.
 *
 * Define your own function and specify it on the command line with
 * `--function my_custom_function`
 */
function speedup_default( $current_frame, $total_frames ) {
	// How far through the video are we?
	$percentage = $current_frame / $total_frames;

	// What is the period of our function?
	$period = 2 * pi();

	// How far through the function are we?
	$x = $percentage * $period;

	// A sine curve that is zero at zero
	// https://www.wolframalpha.com/input?i=%28sin%28+x+%2B+%28+1.5+*+pi+%29+%29+%2B+1+%29+%2F+2
	return ( sin( $x + ( 1.5 * pi() ) ) + 1 ) / 2;
}