<?php
if ( php_sapi_name() != 'cli' ) {
	echo "This script needs to be run in in command-line mode only !!!! \n";

}
//In cli-mode
if ( ! defined( 'ABSPATH' ) ) {
	/** Set up WordPress environment */
	if ( file_exists( __DIR__ . '/../../../wp-load.php' ) ) {
		require_once __DIR__ . '/../../../wp-load.php';
	} else {
		echo "This script needs to be run in the main 'Events Made Easy' plugin directory !!!! \n";
		die();
	}
}
if ( ! function_exists( 'mailparse_msg_create' ) ) {
	echo "This script requires the mailparse php PECL extension\n";
	die();
}

function help( $progname ) {
	echo "For all doc, see https://www.e-dynamics.be/wordpress/category/documentation/21-command-line-mail-script/\n";
	echo "Usage: $progname -d <email>\n";
	echo "Or   : $progname --groupid=<groupid>\n";
	echo "-d <email>\tCauses the email to be forwarded to the EME group with the specified email address\n";
	echo "--groupid=<groupid>\tCauses the email to be forwarded to the EME group with the specified ID\n";
	echo "  Optional provide --allowed_senders, to indicate specific email addresses that can send to the group (comma-separated) By default everyone in that group can send to the group, this option limits it to just the mentioned email addresses or the keyword 'ALL', which allows the world to send.\n";
	echo "  Optional provide --extra_allowed_senders, to allow extra email addresses to send to this group (comma-separated list)\n";
	echo "  Optional provide -f, to indicate the sender email. Normally this is extracted from the email, but programs like postfix can provide this already too and are more suited for email parsing\n";
	echo "  Optional provide -a, to indicate all people in the group should receive the mail, and not only those with the 'massmail' option active.\n";
	echo "  Optional provide --fast: by default the emails are queued inside EME and follow the general queueing rules, if you want these emails to be sent as quickly as possible add --fast\n";
	echo "Examples:\n";
	echo "$progname -d mygroup@email\n";
	echo "==> will forward emails to group with matching email mygroup@email\n\n";
	echo "$progname --groupid=3\n";
	echo "==> will forward emails to group id 3\n\n";
	echo "$progname -d mygroup@email --allowed_senders=only@this.email\n";
	echo "==> will forward emails to group with matching email mygroup@email and allow only only@this.email as sender\n\n";
	echo "$progname --groupid=3 --extra_allowed_senders=also@this.email,another@email \n";
	echo "==> will forward emails to group id 3 and allow all people in that group to email to this group and also accepts also@this.email and another@email as senders\n\n";
	exit;
}

function mailRead( $iKlimit = '' ) {
	// Purpose:
	//   Reads piped mail from STDIN
	//
	// Arguements:
	//   $iKlimit (integer, optional): specifies after how many kilobytes reading of mail should stop
	//   Defaults to 1024k if no value is specified
	//     A value of -1 will cause reading to continue until the entire message has been read
	//
	// Return value:
	//   A string containing the entire email, headers, body and all.

	// Variable perparation
	// Set default limit of 1024k if no limit has been specified
	if ( $iKlimit == '' ) {
		$iKlimit = 1024;
	}

	// Error strings
	$sErrorSTDINFail = 'Error - failed to read mail from STDIN!';

	// Attempt to connect to STDIN
	$fp = fopen( 'php://stdin', 'r' );

	// Failed to connect to STDIN? (shouldn't really happen)
	if ( ! $fp ) {
		echo $sErrorSTDINFail;
		exit();
	}

	// Create empty string for storing message
	$sEmail = '';

	// Read message up until limit (if any)
	if ( $iKlimit == -1 ) {
		while ( ! feof( $fp ) ) {
			$sEmail .= fread( $fp, 1024 );
		}
	} else {
		$i_limit = 0;
		while ( ! feof( $fp ) && $i_limit < $iKlimit ) {
			$sEmail .= fread( $fp, 1024 );
			++$i_limit;
		}
	}

	// Close connection to STDIN
	fclose( $fp );

	// Return message
	return $sEmail;
}

$arguments = getopt( 'ahd:f:', [ 'fast', 'allowed_senders:', 'extra_allowed_senders:', 'groupid:' ] );
if ( ( ! isset( $arguments['groupid'] ) && ! isset( $arguments['d'] ) ) || isset( $arguments['h'] ) ) {
	help( $argv[0] );
}

$group = '';
if ( isset( $arguments['groupid'] ) ) {
	$groupid = intval( $arguments['groupid'] );
	$group   = eme_get_group( $groupid );
} else {
	// in this case argument d is set
	if ( eme_is_email( $arguments['d'] ) ) {
		$group = eme_get_group_by_email( eme_sanitize_email( $arguments['d'] ) );
	}
}
if ( empty( $group ) ) {
	echo "Group doesn't exist\n";
	exit;
}

$email_raw = mailRead();

$mime_resource = mailparse_msg_create();
mailparse_msg_parse( $mime_resource, $email_raw );
$structure = mailparse_msg_get_structure( $mime_resource ); // Ex. ["1", "1.1", "1.2"]
if ( isset( $arguments['f'] ) && eme_is_email( $arguments['f'] ) ) {
	$from_email = eme_sanitize_email( $arguments['f'] );
} else {
	$from_email = '';
}
$subject        = '';
$plain_contents = '';
$html_contents  = '';
$on_behalf_of   = '';
foreach ( $structure as $part_label ) { // Search among each e-mail part
	$part      = mailparse_msg_get_part( $mime_resource, $part_label ); // Parse a specified part
	$part_data = mailparse_msg_get_part_data( $part ); // Get parsed part data, header and meta values
	// the from/subject is in the first part
	if ( $part_label == 1 ) {
		if ( isset( $part_data['headers']['to'] ) ) {
			$replyto       = $part_data['headers']['to'];
			$replyto_email = strpos( $replyto, '<' ) ? substr( $replyto, strpos( $replyto, '<' ) + 1, -1 ) : $replyto;
		}
		if ( empty( $from_email ) && isset( $part_data['headers']['from'] ) ) {
			$from         = $part_data['headers']['from'];
			$from_email   = strpos( $from, '<' ) ? substr( $from, strpos( $from, '<' ) + 1, -1 ) : $from;
			$on_behalf_of = strpos( $from, '<' ) ? substr( $from, 0, strpos( $from, '<' ) ) : $from;
			$on_behalf_of = trim( eme_sanitize_request( $on_behalf_of ) );
			if ( ! empty( $on_behalf_of ) ) {
				$on_behalf_of = ' (' . sprintf(
					/* translators: %s: original email sender name */
					__( 'on behalf of %s', 'events-made-easy' ),
				    $on_behalf_of
				) . ')';
			}
		}
		if ( isset( $part_data['headers']['subject'] ) ) {
			$subject = eme_sanitize_request( $part_data['headers']['subject'] );
		}
	}
	if ( empty( $plain_contents ) && $part_data['content-type'] == 'text/plain' ) {
		$plain_contents = mailparse_msg_extract_part( $part, $email_raw, null ); // null for returning content
	}
	if ( empty( $html_contents ) && $part_data['content-type'] == 'text/html' ) {
		$html_contents = mailparse_msg_extract_part( $part, $email_raw, null ); // null for returning content
	}
	if ( ! empty( $plain_contents ) && ! empty( $html_contents ) ) {
		// we now have the needed info, so quit the loop
		break;
	}
}
mailparse_msg_free( $mime_resource );
if ( ! eme_is_email( $from_email ) || ! eme_is_email( $replyto_email ) || empty( $subject ) || ( empty( $plain_contents ) && empty( $html_contents ) ) ) {
	exit();
}

if (isset( $arguments['a'] )) {
	$names_emails = eme_get_groups_person_emails( $group['group_id'], 0 );
} else {
	$names_emails = eme_get_groups_person_emails( $group['group_id'], 1 );
}
// empty list of emails? Then do nothing
if ( empty( $names_emails ) ) {
	exit();
}

$emails = [];
foreach ( $names_emails as $entry ) {
	$emails[] = $entry['email'];
}

// if only a specific sender is allowed, check it
if ( isset( $arguments['allowed_senders'] ) ) {
	if ($arguments['allowed_senders'] != "ALL" ) {
		$senders = explode( ',', $arguments['allowed_senders'] );
		if ( ! in_array( $from_email, $senders ) ) {
			exit();
		}
	}
} elseif ( isset( $arguments['extra_allowed_senders'] ) ) {
	$extra_senders = explode( ',', $arguments['extra_allowed_senders'] );
	if ( ! in_array( $from_email, $emails ) && ! in_array( $from_email, $extra_senders ) ) {
		exit();
	}
} elseif ( ! in_array( $from_email, $emails ) ) {
		exit();
}

// ok, conditions met, send it
$eme_mail_send_html = get_option( 'eme_mail_send_html' );
if ( $eme_mail_send_html ) {
	$body           = ( ! empty( $html_contents ) ) ? $html_contents : $plain_contents;
	$mail_text_html = 'html';
} else {
	$body           = ( ! empty( $plain_contents ) ) ? $plain_contents : $html_contents;
	$mail_text_html = 'text';
}

// empty subject/body? Then exit
if ( empty( $subject ) || empty( $body ) ) {
	exit();
}

// now create the mailing
$mailing_name = "Forwarding mail from $from_email to $replyto_email to group " . $group['name'];
$conditions   = [
	'eme_genericmail_send_peoplegroups' => $group['group_id'],
	'action'                            => 'genericmail',
];

if ( get_option( 'eme_mail_force_from' ) ) {
	// by setting the from address to the forced address, we avoid the sender name from being changed in eme_queue_mail later on (which is needed in this case, to keep the "on behalf of"
	$from_email = get_option( 'eme_mail_sender_address' );
} else {
	$from_email = $replyto_email;
}

$mailing_id = eme_db_insert_ongoing_mailing( $mailing_name, $subject, $body, $from_email, $group['name'] . $on_behalf_of, $replyto_email, $group['name'], $mail_text_html, $conditions );
// even if we fail to create a mailing, we'll continue
if ( ! $mailing_id ) {
	$mailing_id = 0;
}
foreach ( $names_emails as $entry ) {
	$person_name = eme_format_full_name( $entry['firstname'], $entry['lastname'], $entry['email'] );
	if ( isset( $arguments['fast'] ) ) {
		eme_queue_fastmail( $subject, $body, $from_email, $group['name'] . $on_behalf_of, $entry['email'], $person_name, $replyto_email, $group['name'], $mailing_id, add_listhdrs: 1 );
	} else {
		eme_queue_mail( $subject, $body, $from_email, $group['name'] . $on_behalf_of, $entry['email'], $person_name, $replyto_email, $group['name'], $mailing_id, add_listhdrs: 1 );
	}
}


