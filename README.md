Speedlapse
==========
Speed up videos with ffmpeg according to a curve (or any function).

You define a PHP function that specifies what the speed of the video should be at any given point, and the script will do the rest.

Usage
=====
`php speedlapse.php --input video.mp4 --output video-speedlapse.mp4`

Defining the Speedup Function
=============================
See the example function in `speedup-functions.php` and write your own function
that takes two arguments: the current frame and the total number of frames and
returns the multiplier that should be applied to the maximum speed at this point
in the video.  Generally, the return value is expected to be between zero and one.

All Parameters
==============
```
--input [file path]      The video to use as source material.
--output [file path]     Save the resulting video to this location.
--skip_opening [float]   Skip this many seconds at the beginning of the original video.
--skip_closing [float]   Skip this many seconds at the end of the original video.
--max_speed [float]      When the speedup function returns 1, the amount that the original video will be sped up.
--function [string]      Use this speedup function.
--debug                  Show debugging information as the script runs.
```

Examples
=============
**Match the speed of a video input.mp4 to a sine curve, with the maximum speed being 20x the original video speed, but skip the first 3 seconds and the last 7 seconds of the original video.**

`php speedlapse.php --input input.mp4 --output output.mp4 --skip_opening 3 --skip_closing 7 --max_speed 20 --function my_sine_speedup_function`

Put `my_sine_speed_function` in `speedup-functions.php`:

```
function my_sine_speed_function( $current_frame, $total_frames ) {
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
```

Note that `my_sine_speed_function` is identical to the default speedup function, `speedup_default`.

**Speed up just the second half of a video to 5x the original speed:**

`php speedlapse.php --input input.mp4 --output output.mp4 --max_speed 5 --function fast_second_half`

```
function fast_second_half(  $current_frame, $total_frames ) {
	if ( $current_frame > $total_frames / 2 ) {
		return 1;
	}

	return 0;
}
```
