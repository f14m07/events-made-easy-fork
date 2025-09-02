<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function eme_db_insert_attendance( $type, $person_id, $attendance_date = '', $related_id = 0 ) {
	global $wpdb;
	$table_name = EME_DB_PREFIX . EME_ATTENDANCES_TBNAME;

	$line              = [];
	$line['type']      = esc_sql( $type );
	$line['person_id'] = intval( $person_id );
	if ( ! empty( $attendance_date ) && ( eme_is_date( $attendance_date ) || eme_is_datetime( $attendance_date ) ) ) {
		$line['creation_date'] = $attendance_date;
	} else {
		$line['creation_date'] = current_time( 'mysql', false );
	}
	$line['related_id'] = intval( $related_id );
	if ( $wpdb->insert( $table_name, $line ) ) {
		return true;
	} else {
		return false;
	}
}

// for GDPR cron
function eme_delete_old_attendances() {
	global $wpdb;
	$table_name                  = EME_DB_PREFIX . EME_ATTENDANCES_TBNAME;
	$eme_date_obj                = new emeExpressiveDate( 'now', EME_TIMEZONE );
	$remove_old_attendances_days = get_option( 'eme_gdpr_remove_old_attendances_days' );
	if ( empty( $remove_old_attendances_days ) ) {
		return;
	} else {
		$remove_old_attendances_days = abs( $remove_old_attendances_days );
	}

	$old_date = $eme_date_obj->minusDays( $remove_old_attendances_days )->getDateTime();
	$wpdb->query( $wpdb->prepare("DELETE FROM $table_name WHERE creation_date<%s", $old_date) );
}

function eme_delete_attendances( $ids ) {
	global $wpdb;
	$attendances_table = EME_DB_PREFIX . EME_ATTENDANCES_TBNAME;
	if (eme_is_list_of_int( $ids ) ) {
		$wpdb->query( "DELETE FROM $attendances_table WHERE id IN ($ids)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

function eme_delete_person_attendances( $ids ) {
	global $wpdb;
	$attendances_table = EME_DB_PREFIX . EME_ATTENDANCES_TBNAME;
	if (eme_is_list_of_int( $ids ) ) {
		$wpdb->query( "DELETE FROM $attendances_table WHERE person_id IN ($ids)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

function eme_delete_event_attendances( $event_id ) {
	global $wpdb;
	$attendances_table = EME_DB_PREFIX . EME_ATTENDANCES_TBNAME;
	$sql = $wpdb->prepare( "DELETE FROM $attendances_table WHERE type='event' AND related_id=%d", $event_id);
	$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_delete_membership_attendances( $membership_id ) {
	global $wpdb;
	$attendances_table = EME_DB_PREFIX . EME_ATTENDANCES_TBNAME;
	$sql = $wpdb->prepare( "DELETE FROM $attendances_table WHERE type='membership' AND related_id=%d", $membership_id);
	$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_attendances_page() {
	eme_attendances_table_layout();
}

function eme_attendance_types() {
	$att_types               = [];
	$att_types['any']        = __( 'Any', 'events-made-easy' );
	$att_types['event']      = __( 'Events', 'events-made-easy' );
	$att_types['membership'] = __( 'Memberships', 'events-made-easy' );
	$att_types['manual']     = __( 'Manual', 'events-made-easy' );
	return $att_types;
}

function eme_attendances_table_layout( $message = '' ) {
	if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_attendance' && ! empty( $_POST['person_id'] ) ) {
		check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
		if ( current_user_can( get_option( 'eme_cap_manage_attendances' ) ) ) {
			$person_id       = intval( $_POST['person_id'] );
			$attendance_date = eme_sanitize_request( $_POST['attendance_actualdate'] );
			eme_db_insert_attendance( 'manual', $person_id, $attendance_date );
			$message = __( 'Attendance record added', 'events-made-easy' );
		} else {
			$message = __( 'You have no right to add attendance records!', 'events-made-easy' );
		}
	}
	$nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
	echo "
      <div class='wrap nosubsub'>
      <div id='poststuff'>
         <div id='icon-edit' class='icon32'>
         </div>
         <h1>" . esc_html__( 'Manually add an attendance record', 'events-made-easy' ) . "</h1>
	 <form action='#' method='post'>$nonce_field
         <input type='hidden' name='eme_admin_action' value='add_attendance'>
         <input type='hidden' name='person_id' value=''>
         <input type='search' id='chooseperson' name='chooseperson' placeholder='" . esc_html__( 'Start typing a name', 'events-made-easy' ) . "'>
         " . esc_html__( 'Optional attendance date and time: ', 'events-made-easy' ) . "
         <input type='hidden' name='attendance_actualdate' id='attendance_actualdate' value=''>
         <input type='text' readonly='readonly' name='attendance_date' id='attendance_date' data-date='' data-alt-field='attendance_actualdate' data-multiple='false' class='eme_formfield_fdatetime'><br>
         <input type='submit' class='button-primary' name='submit' value='" . esc_attr__( 'Add attendance', 'events-made-easy' ) . "'>
         </form>
         <h1>" . esc_html__( 'Consult attendances', 'events-made-easy' ) . '</h1>
         <p>' . esc_html__( 'If a RSVP or member QRCODE is scanned by someone with sufficent rights, an attendance record will be added in this table.', 'events-made-easy' ) . '</p>';

	if ( $message != '' ) {
			echo "
            <div id='message' class='updated notice is-dismissible eme-message-admin'>
               <p>$message</p>
            </div>";
	}
	$att_types = eme_attendance_types();
	?>
	<form action="#" method="post">
	<select id="search_type" name="search_type">
	<?php
	foreach ( $att_types as $key => $value ) {
		echo "<option value='" . eme_esc_html( $key ) . "'>" . eme_esc_html( $value ) . '</option>';
	}
	?>
	</select>
	<input id="search_start_date" type="hidden" name="search_start_date" value="">
	<input id="eme_localized_search_start_date" type="text" name="eme_localized_search_start_date" value="" readonly="readonly" placeholder="<?php esc_attr_e( 'Filter on start date', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='search_start_date' class='eme_formfield_fdate'>
	<input id="search_end_date" type="hidden" name="search_end_date" value="">
	<input id="eme_localized_search_end_date" type="text" name="eme_localized_search_end_date" value="" readonly="readonly" placeholder="<?php esc_attr_e( 'Filter on end date', 'events-made-easy' ); ?>" size=15 data-date='' data-alt-field='search_end_date' class='eme_formfield_fdate'>
	<button id="AttendancesLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter attendances', 'events-made-easy' ); ?></button>
	</form>

    <div id="bulkactions">
    <?php eme_rightclickhint(); ?>
    </div>
	<div id="AttendancesTableContainer"></div>
	</div>
	</div>
	<?php
}

add_action( 'wp_ajax_eme_attendances_list', 'eme_ajax_attendances_list' );
add_action( 'wp_ajax_eme_manage_attendances', 'eme_ajax_manage_attendances' );
function eme_ajax_attendances_list() {
	global $wpdb;
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	$table             = EME_DB_PREFIX . EME_ATTENDANCES_TBNAME;
	$fTableResult      = [];
	$search_type       = isset( $_POST['search_type'] ) ? esc_sql( eme_sanitize_request( $_POST['search_type'] ) ) : '';
	$search_start_date = isset( $_POST['search_start_date'] ) && eme_is_date( $_POST['search_start_date'] ) ? esc_sql( $_POST['search_start_date'] ) : '';
	$search_end_date   = isset( $_POST['search_end_date'] ) && eme_is_date( $_POST['search_end_date'] ) ? esc_sql( $_POST['search_end_date'] ) : '';

	$att_types = eme_attendance_types();

	$where     = '';
	$where_arr = [];
	if ( ! empty( $search_start_date ) && ! empty( $search_end_date ) ) {
		$where_arr[] = "creation_date >= '$search_start_date'";
		$where_arr[] = "creation_date <= '$search_end_date'";
	} elseif ( ! empty( $search_start_date ) ) {
		$where_arr[] = "creation_date >= '$search_start_date 00:00:00'";
		$where_arr[] = "creation_date <= '$search_start_date 23:59:59'";
	} elseif ( ! empty( $search_end_date ) ) {
		$where_arr[] = "creation_date >= '$search_end_date 00:00:00'";
		$where_arr[] = "creation_date <= '$search_end_date 23:59:59'";
	}

	if ( ! empty( $search_type ) && $search_type != 'any' ) {
		$where_arr[] = "(type = '$search_type')";
	}
	if ( $where_arr ) {
		$where = 'WHERE ' . implode( ' AND ', $where_arr );
	}

	if ( current_user_can( get_option( 'eme_cap_list_attendances' ) ) ) {
		$sql         = "SELECT COUNT(*) FROM $table $where";
		$recordCount = $wpdb->get_var( $sql );
        $limit       = eme_get_datatables_limit();
		$orderby     = eme_get_datatables_orderby();
		$sql         = "SELECT * FROM $table $where $orderby $limit";
		$rows        = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $key => $row ) {
				$rows[ $key ]['type']      = $att_types[ $row['type'] ];
			$rows[ $key ]['creation_date'] = eme_localized_datetime( $row['creation_date'], EME_TIMEZONE, 1 );
			$person                        = eme_get_person( $row['person_id'] );
			if ( empty( $person ) ) {
				$person = eme_new_person();
			}
			$person_info_shown = $person['lastname'];
			if ( ! empty( $person['firstname'] ) ) {
				$person_info_shown .= ' ' . $person['firstname'];
			}
			$person_info_shown           .= ' (' . $person['email'] . ')';
			$rows[ $key ]['person']       = "<a href='" . admin_url( 'admin.php?page=eme-people&amp;eme_admin_action=edit_person&amp;person_id=' . $person['person_id'] ) . "' title='" . __( 'Edit person', 'events-made-easy' ) . "'>" . eme_esc_html( $person_info_shown ) . '</a>';
			$rows[ $key ]['related_name'] = '';
			if ( $row['type'] == 'event' ) {
				$event = eme_get_event( $row['related_id'] );
				if ( ! empty( $event ) ) {
					$localized_start_date = eme_localized_date( $event['event_start'], EME_TIMEZONE, $time_format );
					$localized_start_time = eme_localized_time( $event['event_start'], EME_TIMEZONE, $time_format );
					$localized_end_date   = eme_localized_date( $event['event_end'], EME_TIMEZONE, $time_format );
					$localized_end_time   = eme_localized_time( $event['event_end'], EME_TIMEZONE, $time_format );
					$datetime             = $localized_start_date;
					if ( $localized_end_date != '' && $localized_end_date != $localized_start_date ) {
						$datetime .= ' - ' . $localized_end_date;
					}
					$datetime .= '<br>';
					if ( $event['event_properties']['all_day'] == 1 ) {
						$datetime .= __( 'All day', 'events-made-easy' );
					} else {
						$datetime .= "$localized_start_time - $localized_end_time";
					}

					$rows[ $key ]['related_name'] = eme_trans_esc_html( $event['event_name'] ) . "<br>$datetime";
				}
			} elseif ( $row['type'] == 'membership' ) {
				$membership = eme_get_membership( $row['related_id'] );
				if ( $membership ) {
					$rows[ $key ]['related_name'] = eme_trans_esc_html( $membership['name'] );
				}
			} else {
				$rows[ $key ]['related_name'] = __( 'Manual entry', 'events-made-easy' );
			}
		}
		$fTableResult['Result']           = 'OK';
		$fTableResult['Records']          = $rows;
		$fTableResult['TotalRecordCount'] = $recordCount;
	} else {
		$fTableResult['Result']  = 'Error';
		$fTableResult['Message'] = __( 'Access denied!', 'events-made-easy' );
	}
	print wp_json_encode( $fTableResult );
	wp_die();
}

function eme_ajax_manage_attendances() {
	check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
	if ( ! current_user_can( get_option( 'eme_cap_list_attendances' ) ) ) {
		wp_die();
	}
	if ( isset( $_REQUEST['do_action'] ) ) {
		$do_action = eme_sanitize_request( $_REQUEST['do_action'] );
		switch ( $do_action ) {
			case 'deleteAttendances':
				eme_ajax_record_delete( EME_ATTENDANCES_TBNAME, 'eme_cap_manage_attendances', 'id' );
				break;
		}
	}
	wp_die();
}

function eme_ajax_action_attendances_delete( $ids ) {
	eme_delete_events( $ids );
	$ajaxResult            = [];
	$ajaxResult['Result']  = 'OK';
	$ajaxResult['Message'] = __( 'Events deleted', 'events-made-easy' );
	print wp_json_encode( $ajaxResult );
}
?>
