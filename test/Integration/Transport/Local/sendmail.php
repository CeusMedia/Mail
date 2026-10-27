<?php
/**
 *	Fake sendmail: appends arguments and message to file named by environment variable SENDMAIL_CAPTURE.
 *	Calls are separated by a line "=== CALL ===".
 */
$message	= (string) stream_get_contents( STDIN );
$arguments	= implode( ' ', array_slice( $argv, 1 ) );
file_put_contents( (string) getenv( 'SENDMAIL_CAPTURE' ), "=== CALL ===\n".$arguments."\n".$message, FILE_APPEND );
