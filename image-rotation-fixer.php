<?php
/*
 Plugin Name: Image Rotation Fixer
 Plugin URI: http://www.mertyazicioglu.com/image-rotation-fixer/
 Description: Automatically fixes the rotation of JPEG images using PHP's EXIF extension, immediately after they are uploaded to the server.
 Version: 1.0
 Author: Mert Yazicioglu
 Author URI: http://www.mertyazicioglu.com/
*/

/*  Copyright 2012 Mert Yazicioglu  (email : mert@mertyazicioglu.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

function merty_attachment_uploaded( $id ) {
	
	$attachment = get_post( $id );

	if ( 'image/jpeg' == $attachment->post_mime_type ) {
		merty_fix_rotation( $attachment->guid );
		$attachment_meta = wp_generate_attachment_metadata( $attachment->ID, str_replace( get_bloginfo('url'), ABSPATH, $attachment->guid ) );
		wp_update_attachment_metadata( $attachment->ID, $attachment_meta );
    }
}

function merty_fix_rotation( $source ) {

	$source = str_replace( get_bloginfo('url'), ABSPATH, $source );
	$sourceFile = explode( '/', $source );
	$filename = $sourceFile[5];

	$destination = $source;

	$size = getimagesize( $source );

	$width = $size[0];
	$height = $size[1];

	$sourceImage = imagecreatefromjpeg( $source );

	$destinationImage = imagecreatetruecolor( $width, $height );

	imagecopyresampled( $destinationImage, $sourceImage, 0, 0, 0, 0, $width, $height, $width, $height );

	$exif = exif_read_data( $source );

	$ort = $exif['Orientation'];

	switch ( $ort ) {

		case 2:
			merty_flip_image( $dimg );
			break;
		case 3:
			$destinationImage = imagerotate( $destinationImage, 180, -1 );
			break;
		case 4:
			merty_flip_image( $dimg );
			break;
		case 5:
			merty_flip_image( $destinationImage );
			$destinationImage = imagerotate( $destinationImage, -90, -1 );
			break;
		case 6:
			$destinationImage = imagerotate( $destinationImage, -90, -1 );
			break;
		case 7:
			merty_flip_image( $destinationImage );
			$destinationImage = imagerotate( $destinationImage, -90, -1 );
			break;
		case 8:
			$destinationImage = imagerotate( $destinationImage, 90, -1 );
			break;
	}

	return imagejpeg( $destinationImage, $destination, 100 );
}

function merty_flip_image( &$image ) {

	$x = 0;
	$y = 0;
	$height = null;
	$width = null;

    if ( $width  < 1 )
    	$width  = imagesx( $image );

    if ( $height < 1 )
    	$height = imagesy( $image );

    if ( function_exists('imageistruecolor') && imageistruecolor( $image ) )
        $tmp = imagecreatetruecolor( 1, $height );
    else
        $tmp = imagecreate( 1, $height );

    $x2 = $x + $width - 1;

    for ( $i = (int)floor( ( $width - 1 ) / 2 ); $i >= 0; $i-- ) {
        imagecopy( $tmp, $image, 0, 0, $x2 - $i, $y, 1, $height );
        imagecopy( $image, $image, $x2 - $i, $y, $x + $i, $y, 1, $height );
        imagecopy( $image, $tmp, $x + $i,  $y, 0, 0, 1, $height );
    }

    imagedestroy( $tmp );

    return true;
}

add_action( 'add_attachment', 'merty_attachment_uploaded' );