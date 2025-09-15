<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function eme_new_formfield() {
    $formfield = [
        'field_type'       => 'text',
        'field_name'       => '',
        'field_values'     => '',
        'field_tags'       => '',
        'admin_values'     => '',
        'admin_tags'       => '',
        'field_attributes' => '',
        'field_purpose'    => '',
        'field_condition'  => '',
        'field_required'   => 0,
        'export'           => 0,
        'extra_charge'     => 0,
        'searchable'       => 0,
    ];
    return $formfield;
}

function eme_formfields_page() {
    global $wpdb;

    if ( ! current_user_can( get_option( 'eme_cap_forms' ) ) && isset( $_REQUEST['eme_admin_action'] ) ) {
        $message = __( 'You have no right to update form fields!', 'events-made-easy' );
        eme_formfields_table_layout( $message );
        return;
    }

    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'edit_formfield' ) {
        // edit formfield
        $field_id = intval( $_GET['field_id'] );
        eme_formfields_edit_layout( $field_id );
        return;
    }

    if ( isset( $_POST['eme_admin_action'] ) && $_POST['eme_admin_action'] == 'add_formfield' ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        eme_formfields_edit_layout();
        return;
    }

    if ( isset( $_GET['eme_admin_action'] ) && $_GET['eme_admin_action'] == 'copy_formfield' ) {
        $field_id = intval( $_GET['field_id'] );
        $formfield = eme_get_formfield( $field_id );
        if ( empty( $formfield ) ) {
            eme_formfields_edit_layout();
            return;
        }
        unset( $formfield['field_id'] );
        $formfield['field_name'] .= __( ' (Copy)', 'events-made-easy' );
        eme_formfields_edit_layout( 0, '', $formfield );
        return;
    }

    // Insert/Update/Delete Record
    $formfields_table  = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $validation_result = '';
    $message           = '';
    if ( isset( $_POST['eme_admin_action'] ) ) {
        check_admin_referer( 'eme_admin', 'eme_admin_nonce' );
        if ( $_POST['eme_admin_action'] == 'do_editformfield' ) {
            $formfield                   = [];
            $field_id                    = intval( $_POST['field_id'] );
            $formfield['field_name']     = trim( eme_sanitize_request( $_POST['field_name'] ) );
            $formfield['field_type']     = trim( eme_esc_html( eme_sanitize_request( $_POST['field_type'] ) ) );
            $formfield['extra_charge']   = intval( $_POST['extra_charge'] );
            $formfield['searchable']     = intval( $_POST['searchable'] );
            $formfield['field_required'] = intval( $_POST['field_required'] );
            if ( eme_is_multifield( $formfield['field_type'] ) ) {
                if (eme_is_empty_string($_POST['field_values'] )) {
                    $field_values_arr = array();
                } else {
                    $field_values_arr = eme_sanitize_request( eme_convert_multi2array(eme_convert_array2multi(eme_text_split_newlines( $_POST['field_values'] ) )));
                }
                if (eme_is_empty_string($_POST['field_tags'] )) {
                    $field_tags_arr = array();
                } else {
                    $field_tags_arr = eme_kses( eme_convert_multi2array(eme_convert_array2multi( eme_text_split_newlines( $_POST['field_tags'] ) )));
                }
                if (eme_is_empty_string($_POST['admin_values'] )) {
                    $admin_values_arr = array();
                } else {
                    $admin_values_arr = eme_sanitize_request( eme_convert_multi2array(eme_convert_array2multi( eme_text_split_newlines( $_POST['admin_values'] ) )));
                }
                if (eme_is_empty_string($_POST['admin_tags'] )) {
                    $admin_tags_arr = array();
                } else {
                    $admin_tags_arr = eme_kses( eme_convert_multi2array(eme_convert_array2multi( eme_text_split_newlines( $_POST['admin_tags'] ) )));
                }

                // some sanity checks
                if (empty($field_values_arr)) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: the field value can not be empty for this type of field.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }
                if (eme_array_has_dupes($field_values_arr) || eme_array_has_dupes($admin_values_arr)) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: the field values need to be unique for this type of field.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }
                if (! empty( $field_tags_arr ) && count( $field_values_arr ) != count( $field_tags_arr ) ) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: if you specify field tags, there need to be exact the same amount of tags as values.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }
                if (! empty( $admin_tags_arr ) && count( $admin_values_arr ) != count( $admin_tags_arr ) ) {
                    $message = "<div id='message' class='eme-message-error'>".__( 'Error: if you specify field tags, there need to be exact the same amount of tags as values.', 'events-made-easy' )."</div>";
                    eme_formfields_edit_layout( $field_id, $message, $formfield );
                    return;
                }

                $formfield['field_values'] = eme_convert_array2multi( $field_values_arr );
                $formfield['field_tags'] = eme_convert_array2multi( $field_tags_arr );
                $formfield['admin_values'] = eme_convert_array2multi( $admin_values_arr );
                $formfield['admin_tags'] = eme_convert_array2multi( $admin_tags_arr );
            } else {
                $formfield['field_values'] = trim( eme_sanitize_request( $_POST['field_values'] ) );
                $formfield['field_tags']   = trim( eme_sanitize_request( $_POST['field_tags'] ) );
                $formfield['admin_values'] = trim( eme_sanitize_request( $_POST['admin_values'] ) );
                $formfield['admin_tags']   = trim( eme_sanitize_request( $_POST['admin_tags'] ) );
            }
            $formfield['field_attributes'] = trim( eme_sanitize_request( $_POST['field_attributes'] ) );
            // for updates the field_purpose can be empty, so check for this
            if ( ! empty( $_POST['field_purpose'] ) ) {
                $formfield['field_purpose'] = trim( eme_sanitize_request( $_POST['field_purpose'] ) );
            }
            // condition can be null if there was a group assigned and the group got deleted, so let's check for that too
            // we also remove group:0 from the array in case other groups are choosen too
            if ( ! empty( $_POST['field_condition'] ) && is_array( $_POST['field_condition'] ) ) {
                $condition_arr = eme_sanitize_request( $_POST['field_condition'] );
                //Remove element by value using unset()
                $key = array_search( 'group:0', $condition_arr );
                if ( $key !== false ) {
                    unset( $condition_arr[ $key ] );
                }
                $formfield['field_condition'] = join( ',', eme_sanitize_request( $condition_arr ) );
            }
            if ( empty( $formfield['field_condition'] ) ) {
                $formfield['field_condition'] = 'group:0';
            }
            if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' || $formfield['field_purpose'] != 'people' ) {
                $formfield['export'] = 0;
            } elseif ( isset( $_POST['export'] ) ) {
                $formfield['export'] = intval( $_POST['export'] );
            } else {
                $formfield['export'] = 0;
            }
            if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                // files are not stored in the db, so we can't search on them
                $formfield['searchable'] = 0;
                // for type file, we only accept integers here
                // since we use that as max size
                if ( ! empty( $formfield['admin_values'] ) ) {
                    $formfield['admin_values'] = intval( $formfield['admin_values'] );
                }
                if ( ! empty( $formfield['field_values'] ) ) {
                    $formfield['field_values'] = intval( $formfield['field_values'] );
                }
            }
            if ( $field_id > 0 ) {
                $validation_result = $wpdb->update( $formfields_table, $formfield, [ 'field_id' => $field_id ] );
                if ( $validation_result !== false ) {
                    $message = __( 'Successfully edited the field', 'events-made-easy' );
                } else {
                    $message = __( 'There was a problem editing the field', 'events-made-easy' );
                }
                if ( get_option( 'eme_stay_on_edit_page' ) || $validation_result === false ) {
                    eme_formfields_edit_layout( $field_id, $message );
                    return;
                }
            } else {
                $validation_result = $wpdb->insert( $formfields_table, $formfield );
                if ( $validation_result !== false ) {
                    $new_field_id = $wpdb->insert_id;
                    $message      = __( 'Successfully added the field', 'events-made-easy' );
                } else {
                    $message = __( 'There was a problem adding the field', 'events-made-easy' );
                }
                if ( get_option( 'eme_stay_on_edit_page' ) || $validation_result === false ) {
                    eme_formfields_edit_layout( $new_field_id, $message );
                    return;
                }
            }
        }
    }

    eme_formfields_table_layout( $message );
}

function eme_formfields_table_layout( $message = '' ) {
    global $plugin_page;
    $field_types    = eme_get_fieldtypes();
    $field_purposes = eme_get_fieldpurpose();
    $nonce_field    = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
    $destination    = admin_url( "admin.php?page=$plugin_page" );
    if ( empty( $message ) ) {
        $hidden_class = 'eme-hidden';
    } else {
        $hidden_class = '';
    }
?>
    <div class="wrap nosubsub">
    <div id="poststuff">
    <div id="icon-edit" class="icon32">
    </div>

    <div id="formfields-message" class="notice is-dismissible eme-message-admin <?php echo $hidden_class; ?>">
        <p><?php echo $message; ?></p>
    </div>

    <h1><?php esc_html_e( 'Add custom field', 'events-made-easy' ); ?></h1>

    <div class="wrap">
        <form id="formfields-new" method="post" action="<?php echo $destination; ?>">
            <?php echo $nonce_field; ?>
            <input type="hidden" name="eme_admin_action" value="add_formfield">
            <input type="submit" class="button-primary" name="submit" value="<?php esc_html_e( 'Add custom field', 'events-made-easy' ); ?>">
        </form>
    </div>
    <h1><?php esc_html_e( 'Manage custom fields', 'events-made-easy' ); ?></h1>
    <form action="#" method="post">
    <?php echo eme_ui_select( '', 'search_type', $field_types, __( 'Any', 'events-made-easy' ) ); ?>
    <?php echo eme_ui_select( '', 'search_purpose', $field_purposes, __( 'Any', 'events-made-easy' ) ); ?>
    <input type="search" name="search_name" id="search_name" placeholder="<?php esc_html_e( 'Field name', 'events-made-easy' ); ?>" class="eme_searchfilter" size=10>
    <button id="FormfieldsLoadRecordsButton" class="button-secondary action"><?php esc_html_e( 'Filter fields', 'events-made-easy' ); ?></button>
    </form>

    <div id="bulkactions">
    <form id='formfields-form' action="#" method="post">
    <?php echo $nonce_field; ?>
    <select id="eme_admin_action" name="eme_admin_action">
    <option value="" selected="selected"><?php esc_html_e( 'Bulk Actions', 'events-made-easy' ); ?></option>
    <option value="deleteFormfields"><?php esc_html_e( 'Delete selected fields', 'events-made-easy' ); ?></option>
    </select>
    <button id="FormfieldsActionsButton" class="button-secondary action"><?php esc_html_e( 'Apply', 'events-made-easy' ); ?></button>
    <?php eme_rightclickhint(); ?>
    </form>
    </div>
    <div id="FormfieldsTableContainer"></div>
    </div>
    </div>
<?php
}

function eme_formfields_edit_layout( $field_id = 0, $message = '', $t_formfield = [] ) {
    global $plugin_page;

    $field_types                      = eme_get_fieldtypes();
    $field_purposes                   = eme_get_fieldpurpose();
    $groups                           = eme_get_static_groups();
    $peoplefieldconditions            = [];
 
    //$peoplefieldconditions['group:0'] = __( 'Show for all people', 'events-made-easy' );
    foreach ( $groups as $group ) {
        $peoplefieldconditions[ 'group:' . $group['group_id'] ] = $group['name'];
    }

    $nonce_field = wp_nonce_field( 'eme_admin', 'eme_admin_nonce', false, false );
    if ( $field_id > 0 ) {
        $used          = eme_check_used_formfield( $field_id );
        $formfield     = eme_get_formfield( $field_id );
        $h1_string     = __( 'Edit field', 'events-made-easy' );
        $action_string = __( 'Update field', 'events-made-easy' );
    } else {
        $used          = 0;
        $formfield     = eme_new_formfield();
        $h1_string     = __( 'Create field', 'events-made-easy' );
        $action_string = __( 'Add field', 'events-made-easy' );
    }
    if ( ! empty( $t_formfield ) ) {
        $formfield = array_merge( $formfield, $t_formfield );
    }
    $layout = "
   <div class='wrap'>
      <div id='icon-edit' class='icon32'>
      </div>

      <h1>" . $h1_string . '</h1>';

    if ( $message != '' ) {
        $layout .= "
      <div id='message' class='updated notice notice-success is-dismissible'>
         <p>$message</p>
      </div>";
    }

    if ( $used ) {
        $layout .= "
      <div id='eme_formfield_warning' class='updated below-h1 eme-message-admin'>
         <p>" . __( 'Warning: this field is already used in RSVP replies, member signups, event or location definitions. Changing the field type or values might result in unwanted side effects.', 'events-made-easy' ) . '</p>
      </div>';
    }

    $layout .= "
      <div id='ajax-response'></div>

      <form name='edit_formfield' id='edit_formfield' method='post' action='" . admin_url( "admin.php?page=$plugin_page" ) . "' class='validate'>
      <input type='hidden' name='eme_admin_action' value='do_editformfield'>
      $nonce_field
      <input type='hidden' name='field_id' value='" . $field_id . "'>

      <table class='form-table'>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_name'>" . __( 'Field name', 'events-made-easy' ) . "</label></th>
               <td><input name='field_name' id='field_name' type='text' value='" . eme_esc_html( $formfield['field_name'] ) . "' size='40' required='required'></td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_type'>" . __( 'Field type', 'events-made-easy' ) . '</label></th>
               <td>' . eme_ui_select( $formfield['field_type'], 'field_type', $field_types ) . '
                    <br>' . __( "For the types 'Date (JS)','Datetime (JS)' and 'Time (JS)' you can optionally enter a custom date format in 'HTML Field attributes' to be used when the field is shown.", 'events-made-easy' ) . '
                    <br>' . __( "For the type 'File' you can optionally enter a maximum upload size in MB in 'Field values'.", 'events-made-easy' ) . "
               </td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_purpose'>" . __( 'Field purpose', 'events-made-easy' ) . '</label></th>
               ';
    if ( ! $used || in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'members' ] ) ) {
        if ( $used && in_array( $formfield['field_purpose'], [ 'rsvp', 'members' ] ) ) {
            // for members or rsvp field: allow to change between those and generic
            unset( $field_purposes['events'] );
            unset( $field_purposes['locations'] );
            unset( $field_purposes['memberships'] );
            unset( $field_purposes['people'] );
        }
        $layout .= '
           <td>' . eme_ui_select( $formfield['field_purpose'], 'field_purpose', $field_purposes ) . '
                    <br>' . __( "If you select 'RSVP field', 'People field' or 'Members field', this field will show up as an extra column in the overview table for bookings, people or members. Selecting 'Generic' will cause it to show up in the overview table for bookings or members.", 'events-made-easy' ) . '
                    <br>' . __( "If you select 'People field' you can add a condition to this field, meaning that if the person is in the group you selected in the condition, this is an extra field that will then be available to fill out for that person. This allows you to put people in e.g. a Volunteer group and then ask for more volunteer info.", 'events-made-easy' ) . '
                    <br>' . __( "If you select 'People field' and use this field in a RSVP or membership form, the info will be stored to the person, so you can ask for extra personal info when someone signs up. When editing the person, those fields will then be visible.", 'events-made-easy' ) . '
                    <br>' . __( "If you select 'Events field', 'Locations field' or 'Memberships field', this field will be used in the definition of the event, location or membership. Warning: this is unrelated to the use of custom fields in RSVP forms, so if you don't intend to use this field in the definition of events, locations or memberships, don't select this.", 'events-made-easy' ) . '
               </td>';
    } else {
        $layout .= '<td>' . eme_get_fieldpurpose( $formfield['field_purpose'] );
        $layout .= "<input type='hidden' name='field_purpose' id='field_purpose' value='" . $formfield['field_purpose'] . "'></td>";
    }

    $field_condition_arr = explode( ',', $formfield['field_condition'] );
    $layout             .= "
            </tr>
            <tr id='tr_export' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='export'>" . __( 'Include in CSV export', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['export'], 'export' ) . '
                   <br>' . __( 'Include this field in the CSV export for bookings.', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_field_condition' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_condition'>" . __( 'Field condition', 'events-made-easy' ) . '</label></th>
               <td>' . eme_ui_multiselect( $field_condition_arr, 'field_condition', $peoplefieldconditions, 5, '', 0, ' eme_select2_width50_class' ) . '
                   <br>' . __( 'Only show this field if the person is member of the selected group. Leave empty to add this field to all people.', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_required'>" . __( 'Required field', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['field_required'], 'field_required' ) . '
                  <br>' . __( 'Use this if the field is required to be filled out.', 'events-made-easy' ) . '
                  <br>' . __( 'This overrides the use of "#REQ" when defining a field in a form.', 'events-made-easy' ) . "
            </tr>
            <tr id='tr_searchable' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='searchable'>" . __( 'Searchable or sortable', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['searchable'], 'searchable' ) . '
                  <br>' . __( 'When defining a custom field, it is also used in the administration interface for events, locations, people, members (depending on its purpose).', 'events-made-easy' ) . '
                  <br>' . __( 'However, being able to search or sort on such a field is more heavy on the database, that is why by default this parameter is set to "No".', 'events-made-easy' ) . '
                  <br>' . __( 'If you want to search or sort on such a field, set this parameter to "Yes".', 'events-made-easy' ) . "
            </tr>
            <tr id='tr_extra_charge' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='extra_charge'>" . __( 'Extra charge', 'events-made-easy' ) . '</label></th>
           <td>' . eme_ui_select_binary( $formfield['extra_charge'], 'extra_charge' ) . '
                  <br>' . __( 'Use this if the field indicates an extra charge to the total price (can be negative to indicate a discount), in which case you should also set the field value to the charge.', 'events-made-easy' ) . '
                  <br>' . __( 'For multivalue fields (like e.g. dropdown) the field values should indicate the price for that selection (and the price needs to be unique).', 'events-made-easy' ) . '
                  <br>' . __( "This is ignored for fields with purpose 'Events field', 'Locations field' or 'Memberships field'", 'events-made-easy' ) . "
            </tr>
            <tr id='tr_field_values' class='form-field'>
           <th scope='row' style='vertical-align:top'><label for='field_values'>" . __( 'Field values', 'events-made-easy' ) . '</label></th>';

    $layout .= "<td><div id='field_values_container'><input name='field_values' id='field_values' type='text' value='" . eme_esc_html( $formfield['field_values'] ) . "' size='40'></div>";
    $layout .= '
                  <br>' . __( 'Enter here the default value a field should have, or enter the list of values for fields that support multiple values.', 'events-made-easy' ) . '
                  <br>' . __( 'For fields that support multiple values (like Dropdown or Checkbox), enter one value per line. To include an empty first option (e.g., for a blank default in a dropdown), start with an empty line at the top.', 'events-made-easy' ) . '
                  <br>' . __( "For the types 'Date (Javascript)', 'Datetime (Javascript)' and 'Time (Javascript)' you can optionally enter the word 'NOW' to automatically use the current date and/or time when the field is displayed.", 'events-made-easy' ) . '
                  <br>' . __( "For the type 'File' you can optionally enter a maximum upload size in MB.", 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_field_tags' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_tags'>" . __( 'Field tags', 'events-made-easy' ) . '</label></th>';
    $layout .= "<td><div id='field_tags_container'><input name='field_tags' id='field_tags' type='text' value='" . eme_esc_html( $formfield['field_tags'] ) . "' size='40'></div>";
    $layout .= '
          <br>' . __( 'This option determines the "visible" value people will see for the field.', 'events-made-easy' ) . '
          <br>' . __( 'For multivalue fields, you can here enter the "visible" tag people will see per value (so, if "Field values" contain e.g. "a1||a2||a3", you can use here e.g. "Text a1||Text a2||Text a3").', 'events-made-easy' ) . '
                  <br>' . __( 'If left empty, the field values will be used (so the visible tag equals the value).', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_admin_values' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='admin_values'>" . __( 'Admin Field values', 'events-made-easy' ) . '</label></th>';
    $layout .= "<td><div id='admin_values_container'><input name='admin_values' id='admin_values' type='text' value='" . eme_esc_html( $formfield['admin_values'] ) . "' size='40'></div>";
    $layout .= '
                  <br>' . __( 'If you want a bigger number of choices for e.g. dropdown fields in the admin interface, enter the possible values here', 'events-made-easy' ) . '
                  <br>' . __( "For the type 'File' you can optionally enter a maximum upload size in MB.", 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_admin_tags' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='admin_tags'>" . __( 'Admin Field tags', 'events-made-easy' ) . '</label></th>';
    $layout .= "<td><div id='admin_tags_container'><input name='admin_tags' id='admin_tags' type='text' value='" . eme_esc_html( $formfield['admin_tags'] ) . "' size='40'></div>";
    $layout .= '
                  <br>' . __( 'If you want a bigger number of choices for e.g. dropdown fields in the admin interface, enter the possible tags here', 'events-made-easy' ) . "
               </td>
            </tr>
            <tr id='tr_field_attributes' class='form-field'>
               <th scope='row' style='vertical-align:top'><label for='field_attributes'>" . __( 'HTML field attributes', 'events-made-easy' ) . "</label></th>
               <td><input name='field_attributes' id='field_attributes' type='text' value='" . eme_esc_html( $formfield['field_attributes'] ) . "' size='40'>
                   <br>" . __( 'Here you can specify extra html attributes for your field (like size, maxlength, pattern, ...).', 'events-made-easy' ) . '
                   <br>' . __( "For the types 'Date (Javascript)', 'Datetime (Javascript)' and 'Time (Javascript)' enter a valid PHP-format of the date you like to see when entering/showing the value (unrecognized characters in the format will cause the result to be empty). If left empty, the WordPress settings for date format will be used.", 'events-made-easy' ) . "
               </td>
            </tr>
      </table>
      <p class='submit'><input type='submit' class='button-primary' name='submit' value='" . $action_string . "'></p>
      </form>

   </div>
   <p>" . __( 'For more information about form fields, see ', 'events-made-easy' ) . "<a target='_blank' href='https://www.e-dynamics.be/wordpress/?cat=44'>" . __( 'the documentation', 'events-made-easy' ) . '</a></p>
   ';
    echo $layout;
}

function eme_get_dyndata_conditions() {
        $data = [
            'eq'          => __( 'equal to', 'events-made-easy' ),
            'ne'          => __( 'not equal to', 'events-made-easy' ),
            'lt'          => __( 'lower than', 'events-made-easy' ),
            'gt'          => __( 'greater than', 'events-made-easy' ),
            'ge'          => __( 'greater than or equal to', 'events-made-easy' ),
            'contains'    => __( 'contains', 'events-made-easy' ),
            'notcontains' => __( 'does not contain', 'events-made-easy' ),
            'incsv'       => __( 'CSV list contains', 'events-made-easy' ),
            'notincsv'    => __( 'CSV list does not contain', 'events-made-easy' ),
        ];

        return $data;
}

function eme_get_used_formfield_ids() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    return $wpdb->get_col( "SELECT DISTINCT field_id FROM $table" );
}

function eme_check_used_formfield( $field_id ) {
    global $wpdb;
    $table  = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    $query  = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE field_id=%d", $field_id );
    $count  = $wpdb->get_var( $query );
    return $count;
}

function eme_get_formfields( $ids = '', $purpose = '' ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $where            = '';
    $where_arr        = [];
    if ( ! empty( $ids ) && eme_is_list_of_int( $ids ) ) {
        $where_arr[] = "field_id IN ($ids)";
    }
    if ( ! empty( $purpose ) ) {
        $purposes     = explode( ',', $purpose );
        $purposes_arr = [];
        foreach ( $purposes as $tmp_p ) {
            $purposes_arr[] = "field_purpose='" . esc_sql( $tmp_p ) . "'";
        }
        $where_arr[] = '(' . join( ' OR ', $purposes_arr ) . ')';
    }
    if ( ! empty( $where_arr ) ) {
        $where = 'WHERE ' . join( ' AND ', $where_arr );
    }
    return $wpdb->get_results( "SELECT * FROM $formfields_table $where", ARRAY_A );
}

function eme_get_searchable_formfields( $purpose = '', $include_generic = 0 ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $where            = '';
    $where_arr        = [];
    $where_arr[]      = 'searchable=1';
    $where_arr[]      = "field_type <> 'file' AND field_type <> 'multifile'";
    if ( ! empty( $purpose ) ) {
        if ( $include_generic ) {
            $where_arr[] = "(field_purpose='" . esc_sql( $purpose ) . "' OR field_purpose='generic')";
        } else {
            $where_arr[] = "field_purpose='" . esc_sql( $purpose ) . "'";
        }
    }
    if ( ! empty( $where_arr ) ) {
        $where = 'WHERE ' . join( ' AND ', $where_arr );
    }
    return $wpdb->get_results( "SELECT * FROM $formfields_table $where", ARRAY_A );
}

function eme_get_formfield( $field_info ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    if ( is_numeric( $field_info ) || $field_info == 'performer' ) {
        $formfield = wp_cache_get( "eme_formfield $field_info" );
    } else {
        $formfield = false;
    }
    if ( $formfield === false ) {
        if ( is_numeric( $field_info ) ) {
            $sql = $wpdb->prepare( "SELECT * FROM $formfields_table WHERE field_id=%d", $field_info ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        } else {
            $sql = $wpdb->prepare( "SELECT * FROM $formfields_table WHERE field_name=%s LIMIT 1", $field_info ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
        $formfield = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        if ( is_numeric( $field_info ) || $field_info == 'performer' ) {
            wp_cache_set( "eme_formfield $field_info", $formfield, '', 60 );
        }
    }
    return $formfield;
}

function eme_delete_formfields( $ids_arr ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    if ( ! empty( $ids_arr ) && eme_is_numeric_array( $ids_arr ) ) {
        $ids_list = implode(',', $ids_arr);
        $validation_result = $wpdb->query( "DELETE FROM $formfields_table WHERE field_id IN ($ids_list)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        if ( $validation_result !== false ) {
            $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
            $wpdb->query( "DELETE FROM $answers_table WHERE field_id IN ($ids_list)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function eme_get_fieldpurpose( $purpose = '' ) {
    $uses = [
        'generic'     => __( 'Generic', 'events-made-easy' ),
        'events'      => __( 'Events field', 'events-made-easy' ),
        'locations'   => __( 'Locations field', 'events-made-easy' ),
        'rsvp'        => __( 'RSVP field', 'events-made-easy' ),
        'people'      => __( 'People field', 'events-made-easy' ),
        'members'     => __( 'Members field', 'events-made-easy' ),
        'memberships' => __( 'Memberships field', 'events-made-easy' ),
    ];
    if ( $purpose ) {
        if ( isset( $uses[ $purpose ] ) ) {
            return $uses[ $purpose ];
        } else {
            return $uses['generic'];
        }
    } else {
        return $uses;
    }
}

function eme_get_fieldtypes() {
    $types = [
        'text'              => __( 'Text', 'events-made-easy' ),
        'textarea'          => __( 'Textarea', 'events-made-easy' ),
        'dropdown'          => __( 'Dropdown', 'events-made-easy' ),
        'dropdown_multi'    => __( 'Dropdown (multiple)', 'events-made-easy' ),
        'radiobox'          => __( 'Radiobox', 'events-made-easy' ),
        'radiobox_vertical' => __( 'Radiobox (vertical)', 'events-made-easy' ),
        'checkbox'          => __( 'Checkbox', 'events-made-easy' ),
        'checkbox_vertical' => __( 'Checkbox (vertical)', 'events-made-easy' ),
        'password'          => __( 'Password', 'events-made-easy' ),
        'hidden'            => __( 'Hidden', 'events-made-easy' ),
        'readonly'          => __( 'Readonly', 'events-made-easy' ),
        'file'              => __( 'File upload', 'events-made-easy' ),
        'multifile'         => __( 'Multiple files upload', 'events-made-easy' ),
        'date'              => __( 'Date', 'events-made-easy' ),
        'date_js'           => __( 'Date (Javascript)', 'events-made-easy' ),
        'datetime_js'       => __( 'Datetime (Javascript)', 'events-made-easy' ),
        'datetime-local'    => __( 'Datetime-local', 'events-made-easy' ),
        'month'             => __( 'Month', 'events-made-easy' ),
        'week'              => __( 'Week', 'events-made-easy' ),
        'time'              => __( 'Time', 'events-made-easy' ),
        'time_js'           => __( 'Time (Javascript)', 'events-made-easy' ),
        'color'             => __( 'Color', 'events-made-easy' ),
        'email'             => __( 'Email', 'events-made-easy' ),
        'number'            => __( 'Number', 'events-made-easy' ),
        'range'             => __( 'Range', 'events-made-easy' ),
        'tel'               => __( 'Tel', 'events-made-easy' ),
        'url'               => __( 'Url', 'events-made-easy' ),
        'datalist'          => __( 'Datalist', 'events-made-easy' ),
    ];
    return $types;
}

function eme_get_fieldtype( $type ) {
    $fieldtypes = eme_get_fieldtypes();
    return $fieldtypes[ $type ];
}

function eme_is_multifield( $type ) {
    return in_array( $type, [ 'dropdown', 'dropdown_multi', 'radiobox', 'radiobox_vertical', 'checkbox', 'checkbox_vertical', 'datalist' ] );
}

function eme_get_formfield_html( $formfield, $field_name, $entered_val, $required, $class = '', $ro = 0, $force_single = 0, $force_edit = 0 ) {
    if ( empty( $formfield ) ) {
        return;
    }

    $simple_fieldname = 'FIELD' . $formfield['field_id'];
    if ( empty( $field_name ) ) {
        $field_name = $simple_fieldname;
    }

    $field_name = wp_strip_all_tags( $field_name );
    if ( eme_is_admin_request() && has_filter( 'eme_admin_field_value_filter' ) ) {
        $entered_val = apply_filters( 'eme_admin_field_value_filter', $formfield, $field_name, $entered_val );
    } elseif ( ! eme_is_admin_request() && has_filter( 'eme_field_value_filter' ) ) {
        $entered_val = apply_filters( 'eme_field_value_filter', $formfield, $field_name, $entered_val );
    }

    if ( ! is_array( $entered_val ) && eme_is_multi( $entered_val ) ) {
        $entered_val = eme_convert_multi2array( $entered_val );
    }

    if ( $ro ) {
        $readonly = "readonly='readonly'";
        $disabled = "disabled='disabled'";
    } else {
        $readonly = '';
        $disabled = '';
    }

    if ( $class ) {
        $class_att = "class='$class'";
    } else {
        $class_att = '';
    }
    $field_attributes = eme_merge_classes_into_attrs($class, $formfield['field_attributes']);

    if ( $required ) {
        $required_att = "required='required'";
    } else {
        $required_att = '';
    }

    $field_values = '';
    $field_tags = '';
    if ( (eme_is_admin_request() && isset( $_REQUEST['eme_admin_action'] )) || $force_edit ) {
        // remove some attributes for backend edit (like checked)
        $field_attributes = eme_remove_attrs('checked', $field_attributes);

        // fields can have a different value for front/backend for multi-fields
        if ( ! empty( $formfield['admin_values'] ) ) {
            $field_values = $formfield['admin_values'];
            if ( ! empty( $formfield['admin_tags'] ) ) {
                $field_tags = $formfield['admin_tags'];
            } else {
                $field_tags = $formfield['admin_values'];
            }
        } else {
            $field_values = $formfield['field_values'];
            $field_tags = $formfield['field_tags'];
        }
    } else {
        $field_values = $formfield['field_values'];
        $field_tags   = $formfield['field_tags'];
    }
    if ( empty( $field_tags ) ) {
        $field_tags = $field_values;
    }

    $html = '';
    switch ( $formfield['field_type'] ) {
        case 'text':
        case 'date':
        case 'datetime-local':
        case 'month':
        case 'week':
        case 'time':
        case 'color':
        case 'email':
        case 'number':
        case 'range':
        case 'tel':
        case 'url':
            # for text fields
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = eme_esc_html( $value );
            $html  = "<input $readonly $required_att type='" . $formfield['field_type'] . "' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            break;
        case 'hidden':
            $value = eme_translate( $field_tags );
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = eme_esc_html( $value );
            if ( eme_is_admin_request() ) {
                    $html  = "<input $readonly $required_att type='text' name='$field_name' id='$field_name' value='$value' $field_attributes><br>";
                    $html .= __( 'This is a hidden field, but in the backend it is shown as text so an admin can see its value and optionally change it', 'events-made-easy' );
            } else {
                $html = "<input $readonly $required_att type='hidden' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            }
            break;
        case 'password':
            $value = eme_esc_html( $entered_val );
            $new_attrs = eme_merge_classes_into_attrs('eme_passwordfield', $field_attributes);
            $html = "<input $readonly $required_att type='text' autocomplete='off' name='$field_name' id='$field_name' value='$value' $new_attrs>";
            break;
        case 'readonly':
            $value = eme_esc_html( $entered_val );
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = eme_esc_html( $value );
            $html  = "<input readonly='readonly' $required_att type='text' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            break;
        case 'dropdown':
            # dropdown
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            // since the values for a dropdown field need not be unique, we give them as an array to be built with eme_ui_select
            foreach ( $values as $key => $val ) {
                $tag      = eme_translate( $tags[ $key ] );
                $new_el   = [
                    0 => $val,
                    1 => $tag,
                ];
                $my_arr[] = $new_el;
            }
            $new_attrs = eme_merge_classes_into_attrs('eme_select2', $field_attributes) . ' ' . $disabled;
            $html = eme_ui_select( $entered_val, $field_name, $my_arr, '', $required, '', $new_attrs );
            break;
        case 'dropdown_multi':
            # dropdown, multiselect
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            // since the values for a dropdown field need not be unique, we give them as an array to be built with eme_ui_select
            foreach ( $values as $key => $val ) {
                $tag      = eme_translate( $tags[ $key ] );
                $new_el   = [
                    0 => $val,
                    1 => $tag,
                ];
                $my_arr[] = $new_el;
            }
            // force_single can be 1 (only possible case is in the filterform for now)
            $new_attrs = eme_merge_classes_into_attrs('eme_select2', $field_attributes) . ' ' . $disabled;
            if ( $force_single == 1 ) {
                $html = eme_ui_select( $entered_val, $field_name, $my_arr, '', $required, '', $new_attrs );
            } else {
                $html = eme_ui_multiselect( $entered_val, $field_name, $my_arr, 5, '', $required, '', $new_attrs );
            }
            break;
        case 'textarea':
            # textarea
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            $value = eme_esc_html( $value );
            $html  = "<textarea $required_att name='$field_name' id='$field_name' $field_attributes $readonly>$value</textarea>";
            break;
        case 'radiobox':
            # radiobox
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            $html = eme_ui_radio( $entered_val, $field_name, $my_arr, true, $required, '', $field_attributes . ' ' . $disabled );
            break;
        case 'radiobox_vertical':
            # radiobox, vertical
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            $html = eme_ui_radio( $entered_val, $field_name, $my_arr, false, $required, '', $field_attributes . ' ' . $disabled );
            break;
        case 'checkbox':
            # checkbox
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            // checkboxes can't be made required in the frontend, since that would require all checkboxes to be checked
            // so we use a div+js to accomplish this
            $html = '';
            if ( $required ) {
                $html = '<div class="eme-checkbox-group-required">';
            }
            $html .= eme_ui_checkbox( $entered_val, $field_name, $my_arr, true, 0, '', $field_attributes . ' ' . $disabled );
            if ( $required ) {
                $html .= '</div>';
            }
            break;
        case 'checkbox_vertical':
            # checkbox, vertical
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $values as $key => $val ) {
                $tag            = $tags[ $key ];
                $my_arr[ $val ] = eme_translate( $tag );
            }
            // checkboxes can't be made required in the frontend, since that would require all checkboxes to be checked
            // so we use a div+js to accomplish this
            $html = '';
            if ( $required ) {
                $html = '<div class="eme-checkbox-group-required">';
            }
            $html .= eme_ui_checkbox( $entered_val, $field_name, $my_arr, false, 0, '', $field_attributes . ' ' . $disabled );
            if ( $required ) {
                $html .= '</div>';
            }
            break;
        case 'file':
            // file upload
            // in the admin interface, no upload is required (otherwise edit will never work as well ...)
            if ( eme_is_admin_request() || $force_edit ) {
                $required     = 0;
                $required_att = '';
            }
            // only simple field names accepted, that way the upload code can stay simple and we don't need to worry about arrays and such
            if ( $field_name != $simple_fieldname ) {
                // the field_name can be something like an array name, so we remove redundant info (like the field id in it) and keep integers
                $clean      = preg_replace( "/$simple_fieldname/", '', $field_name );
                $indexes    = preg_replace( '/[^\d]/i', '', $clean );
                $field_name = $simple_fieldname . '_' . $indexes;
            }
            // if the entered_val is not empty it means the file is already uploaded, so we don't show the form
            $html = '<span>';
            if ( ! empty( $entered_val ) ) {
                $showhide_style = "class='eme-hidden'";
            } else {
                $showhide_style = '';
            }
            $html .= "<input type='file' $disabled $required_att name='$field_name' id='$field_name' $showhide_style $field_attributes>";
            if ( ! empty( $entered_val ) ) {
                foreach ( $entered_val as $file ) {
                    $html .= eme_get_uploaded_file_linkdelete( $file );
                }
            }
            if ( empty( $entered_val ) ) {
                $html .= '<br>';
            }
            $html .= '</span>';
            break;
        case 'multifile':
            // file upload
            // in the admin interface, no upload is required (otherwise edit will never work as well ...)
            if ( eme_is_admin_request() || $force_edit ) {
                    $required     = 0;
                    $required_att = '';
            }
            // only simple field names accepted, that way the upload code can stay simple and we don't need to worry about arrays and such
            if ( $field_name != $simple_fieldname ) {
                // the field_name can be something like an array name, so we remove redundant info (like the field id in it) and keep integers
                $clean      = preg_replace( "/$simple_fieldname/", '', $field_name );
                $indexes    = preg_replace( '/[^\d]/i', '', $clean );
                $field_name = $simple_fieldname . '_' . $indexes;
            }
            // if the entered_val is not empty it means the file is already uploaded, so we don't show the form
            $html = '<span>';
            if ( ! empty( $entered_val ) ) {
                $showhide_style = "class='eme-hidden'";
            } else {
                $showhide_style = '';
            }
            $html .= "<input type='file' $disabled $required_att name='{$field_name}[]' id='$field_name' multiple $showhide_style $field_attributes>";
            if ( ! empty( $entered_val ) ) {
                foreach ( $entered_val as $file ) {
                    $html .= eme_get_uploaded_file_linkdelete( $file );
                }
            }
            if ( empty( $entered_val ) ) {
                $html .= '<br>';
            }
            $html .= '</span>';
            break;
        case 'date_js':
            # for date JS field
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            if ( $value == 'NOW' ) {
                $eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
                $value        = $eme_date_obj->getDate();
            }

            $value = eme_esc_html( $value );
            $dateformat = $formfield['field_attributes'];
            if ( empty( $dateformat ) ) {
                $dateformat = EME_WP_DATE_FORMAT;
            }
            $html       = "<input type='hidden' name='$field_name' id='$field_name' value='$value' $class_att>";
            $html      .= "<input $required_att readonly='readonly' $disabled type='text' name='dp_{$field_name}' id='dp_{$field_name}' data-date='$value' data-format='$dateformat' data-alt-field='$field_name' class='eme_formfield_fdate $class'>";
            break;
        case 'datetime_js':
            # for datetime JS field
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            if ( $value == 'NOW' ) {
                $eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
                $value        = $eme_date_obj->getDateTime();
            }
            $value    = eme_esc_html( $value );
            $js_value = eme_js_datetime( $value, EME_TIMEZONE );
            $dateformat = $formfield['field_attributes'];
            if ( empty( $dateformat ) ) {
                $dateformat = EME_WP_DATE_FORMAT .' '. EME_WP_TIME_FORMAT;
            }
            $dateformat = $field_attributes;
            $html       = "<input type='hidden' name='$field_name' id='$field_name' value='$value' $class_att>";
            $html      .= "<input $required_att readonly='readonly' $disabled type='text' name='dp_{$field_name}' id='dp_{$field_name}' data-date='$js_value' data-format='$dateformat' data-alt-field='$field_name' class='eme_formfield_fdatetime $class'>";
            break;
        case 'time_js':
            # for time JS field
            $value = $entered_val;
            if ( empty( $value ) ) {
                $value = eme_translate( $field_tags );
            }
            if ( empty( $value ) ) {
                $value = $field_values;
            }
            if ( $value == 'NOW' ) {
                $eme_date_obj = new emeExpressiveDate( 'now', EME_TIMEZONE );
                $value        = $eme_date_obj->getTime();
            }
            $value    = eme_esc_html( $value );
            $js_value = eme_js_datetime( $value, EME_TIMEZONE );
            $dateformat = $formfield['field_attributes'];
            if ( empty( $dateformat ) ) {
                $dateformat = EME_WP_TIME_FORMAT;
            }
            $html       = "<input type='hidden' name='$field_name' id='$field_name' value='$value' $class_att>";
            $html      .= "<input $required_att readonly='readonly' $disabled type='text' name='dp_{$field_name}' id='dp_{$field_name}' data-date='$js_value' data-format='$dateformat' data-alt-field='$field_name' class='eme_formfield_ftime $class'>";
            break;
        case 'datalist':
            # for text fields
            $value = $entered_val;
            $value = eme_esc_html( $entered_val );
            $html  = "<input $readonly $required_att type='text' list='list_$field_name' name='$field_name' id='$field_name' value='$value' $field_attributes>";
            // now the datalist
            $html  .= "<datalist id='list_$field_name'>";
            $values = eme_convert_multi2array( $field_values );
            $tags   = eme_convert_multi2array( $field_tags );
            foreach ( $values as $key => $val ) {
                $val  = eme_esc_html($val);
                $tag  = eme_esc_html($tags[ $key ]);
                $html .= "<option value='$val'>$tag</option>";
            }
            $html  .= "</datalist>";
            break;
    }
    return $html;
}

function eme_replace_eventtaskformfields_placeholders( $format, $task, $event ) {
    //$used_spaces = eme_count_task_approved_signups( $task['task_id'] );
    if ($event['event_properties']['ignore_pending_tasksignups']) {
        $used_spaces = eme_count_task_approved_signups( $task['task_id'] );
    } else {
        $used_spaces = eme_count_task_signups( $task['task_id'] );
    }
    $free_spaces = $task['spaces'] - $used_spaces;

    $task_ended       = 0;
    $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
    $task_end_obj     = emeExpressiveDate::createFromFormat( 'Y-m-d H:i:s', $task['task_end'], emeExpressiveDate::parseSuppliedTimezone( EME_TIMEZONE ) );
    if ( $task_end_obj < $eme_date_obj_now ) {
        $task_ended = 1;
    }
    $use_radiobox = 0;
    if ( $event['event_properties']['task_only_one_signup_pp'] ) {
        $use_radiobox = 1;
    }

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        if ( preg_match( '/#_TASKSIGNUPCHECKBOX$/', $result ) ) {
            $disabled = '';
            if ( $free_spaces == 0 || $task_ended ) {
                $disabled = 'disabled="disabled"';
            }
            $select_value = $task['task_id'];
            $select_name  = 'eme_task_signups[' . $event['event_id'] . '][]';
            $select_id    = 'eme_task_signups_' . $event['event_id'] . '_' . $select_value;
            if ($use_radiobox) {
                $replacement  = "<input type='radio' name='{$select_name}' id='{$select_id}' value='$select_value' $disabled>";
            } else {
                $replacement  = "<input type='checkbox' name='{$select_name}' id='{$select_id}' value='$select_value' $disabled>";
            }
        } elseif ( preg_match( '/#_TASKHTMLID$/', $result ) ) {
            $replacement    = 'eme_task_signups_' . $event['event_id'] . '_' . $select_value;
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now any leftover task placeholders
    $format = eme_replace_task_placeholders( $format, $task, $event );

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format );

    return $format;
}

function eme_replace_task_signupformfields_placeholders( $form_id, $format ) {
    $eme_is_admin_request = eme_is_admin_request();

    if ( is_user_logged_in() ) {
        $readonly = "readonly='readonly'";
    } else {
        $readonly = '';
    }

    $selected_captcha = '';
    $captcha_set = 0;
    if ( is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ) ) {
        $format = eme_add_captcha_submit( $format );
        } else {
        $configured_captchas = eme_get_configured_captchas();
        if (!empty($configured_captchas) && !$eme_is_admin_request)
            $selected_captcha = array_key_first($configured_captchas);
                $format = eme_add_captcha_submit( $format, $selected_captcha );
        }

    // We need at least #_LASTNAME, #_EMAIL
    $lastname_found = 0;
    $email_found    = 0;

    $bookerLastName  = '';
    $bookerFirstName = '';
    $bookerBirthdate    = '';
    $bookerBirthplace   = '';
    $bookerAddress1     = '';
    $bookerAddress2     = '';
    $bookerCity         = '';
    $bookerZip          = '';
    $bookerState_code   = '';
    $bookerCountry_code = '';
    // if only 1 country, set it as default
    $countries_alpha2 = eme_get_countries_alpha2();
    if ( count( $countries_alpha2 ) == 1 ) {
        $bookerCountry_code = $countries_alpha2[0];
    }
    $bookerEmail   = '';
    $bookerComment = '';
    $bookerPhone   = '';
    $bd_email      = 0;
    $gdpr          = 0;
    if ( is_user_logged_in() ) {
        $current_user       = wp_get_current_user();
        $person             = eme_get_person_by_wp_id( $current_user->ID );
        if ( empty( $person ) ) {
            $person = eme_fake_person_by_wp_id( $current_user->ID );
        }
        $bookerLastName     = eme_esc_html( $person['lastname'] );
        $bookerFirstName    = eme_esc_html( $person['firstname'] );
        $bookerBirthdate    = eme_is_date( $person['birthdate'] ) ? eme_esc_html( $person['birthdate'] ) : '';
        $bookerBirthplace   = eme_esc_html( $person['birthplace'] );
        $bookerAddress1     = eme_esc_html( $person['address1'] );
        $bookerAddress2     = eme_esc_html( $person['address2'] );
        $bookerCity         = eme_esc_html( $person['city'] );
        $bookerZip          = eme_esc_html( $person['zip'] );
        $bookerState_code   = eme_esc_html( $person['state_code'] );
        $bookerCountry_code = eme_esc_html( $person['country_code'] );
        $bookerEmail        = eme_esc_html( $person['email'] );
        $bookerPhone        = eme_esc_html( $person['phone'] );
        $bd_email           = intval( $person['bd_email'] );
        $gdpr               = intval( $person['gdpr'] );
    }

    # we need 3 required fields: #_NAME, #_EMAIL and #_SEATS
    # if these are not present: we don't replace anything and the form is worthless

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';
        $var_prefix  = '';
        $var_postfix = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        // also support RESPNAME, RESPEMAIL, ...
        if ( strstr( $result, '#_RESP' ) ) {
            $result = str_replace( '#_RESP', '#_', $result );
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='task_lastname' id='task_lastname' value='$bookerLastName' $readonly placeholder='$placeholder_text'>";
            if ( wp_script_is( 'eme-autocomplete-form', 'enqueued' ) && get_option( 'eme_autocomplete_sources' ) != 'none' ) {
                $replacement .= "&nbsp;<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . esc_html__( "Notice: since you're logged in as a person with the right to edit or author this event, the 'Last name' field is also an autocomplete field so you can select existing people if desired. Or just clear the field and start typing.", 'events-made-easy' ) . "'>";
            }

            ++$lastname_found;
            // #_NAME is always required
            $required = 1;
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='task_firstname' id='task_firstname' value='$bookerFirstName' $readonly placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='email' name='task_email' id='task_email' value='$bookerEmail' $readonly placeholder='$placeholder_text'>";
            ++$email_found;
            // #_EMAIL is always required
            $required = 1;
        } elseif ( preg_match( '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'task_phone';
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Phone number', 'events-made-easy' );
            }
            $replacement = "<input type='tel' id='$fieldname' name='$fieldname' value='$bookerPhone' placeholder='$placeholder_text' $required_att>";
        } elseif ( preg_match( '/#_COMMENT(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Comment', 'events-made-easy' );
            }
            $replacement = "<textarea name='task_comment' id='task_comment' placeholder='$placeholder_text' ></textarea>";
        } elseif ( preg_match( '/#_REMEMBERME(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __('Remember me?','events-made-easy');
            }
            if ( ! $eme_is_admin_request && ! is_user_logged_in()) {
                $replacement = eme_ui_checkbox_binary( 0, 'eme_rememberme', $label, 0, 'eme-rememberme-field' );
            }
        } elseif ( preg_match( '/#_BIRTHDATE(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Date of birth', 'events-made-easy' );
            }
            $fieldname    = 'task_birthdate';
            $replacement  = "<input type='hidden' name='$fieldname' id='$fieldname' value='$bookerBirthdate'>";
            $replacement .= "<input $required_att readonly='readonly' type='text' name='dp_{$fieldname}' id='dp_{$fieldname}' data-date='$bookerBirthdate' data-format='".EME_WP_DATE_FORMAT."' data-view='years' data-alt-field='birthdate' class='eme_formfield_fdate' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_BIRTHPLACE(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'task_birthplace';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Place of birth', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerBirthplace' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_ADDRESS1(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'task_address1';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address1_string' ) );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerAddress1' placeholder='$placeholder_text' >";
        } elseif ( preg_match( '/#_ADDRESS2(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'task_address2';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address2_string' ) );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerAddress2' placeholder='$placeholder_text' $readonly >";
        } elseif ( preg_match( '/#_CITY(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'task_city';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'City', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerCity' placeholder='$placeholder_text' $readonly >";
        } elseif ( preg_match( '/#_(ZIP|POSTAL)(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'task_zip';
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Postal code', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerZip' placeholder='$placeholder_text' $readonly >";
        } elseif ( preg_match( '/#_STATE$/', $result ) ) {
            $fieldname = 'task_state_code';
            if (!empty($form_id)) {
                $fieldid = $form_id.'-'.$fieldname;
            } else {
                $fieldid = $fieldname;
            }
            if ( ! empty( $bookerState_code ) ) {
                $state_arr = [ $bookerState_code => eme_get_state_name( $bookerState_code, $bookerCountry_code ) ];
            } else {
                $state_arr = [];
            }
            $replacement = eme_form_select( $bookerState_code, $fieldname, $fieldid, $state_arr, '', $required, "eme_select2_state_class" );
        } elseif ( preg_match( '/#_COUNTRY$/', $result ) ) {
            $fieldname = 'task_country_code';
            if (!empty($form_id)) {
                $fieldid = $form_id.'-'.$fieldname;
            } else {
                $fieldid = $fieldname;
            }
            if ( ! empty( $bookerCountry_code ) ) {
                $country_arr = [ $bookerCountry_code => eme_get_country_name( $bookerCountry_code ) ];
            } else {
                $country_arr = [];
            }
            $replacement = eme_form_select( $bookerCountry_code, $fieldname, $fieldid, $country_arr, '', $required, "eme_select2_country_class" );
        } elseif ( preg_match( '/#_BIRTHDAY_EMAIL$/', $result ) ) {
            $replacement = eme_ui_select_binary( $bd_email, 'task_bd_email' );
        } elseif ( preg_match( '/#_OPT_OUT$/', $result ) ) {
            $selected_massmail = ( isset( $massmail ) ) ? $massmail : 1;
            $fieldname         = 'task_massmail';
            $replacement       = eme_ui_select_binary( $selected_massmail, $fieldname, 0 );
            if ( ! $eme_is_admin_request && get_option( 'eme_massmail_popup' ) ) {
                $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                $confirm = esc_html__('Yes','events-made-easy');
                $cancel  = esc_html__('No','events-made-easy');
                if (!eme_is_empty_string($popup))
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
            }
        } elseif ( preg_match( '/#_OPT_IN$/', $result ) ) {
            $selected_massmail = ( isset( $massmail ) ) ? $massmail : 0;
            $fieldname         = 'task_massmail';
            $replacement       = eme_ui_select_binary( $selected_massmail, $fieldname );
            if ( ! $eme_is_admin_request && get_option( 'eme_massmail_popup' ) ) {
                $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                $confirm = esc_html__('Yes','events-made-easy');
                $cancel  = esc_html__('No','events-made-easy');
                if (!eme_is_empty_string($popup))
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
            }
        } elseif ( preg_match( '/#_GDPR(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = '';
            }
            $fieldname = 'task_gdpr';
            if ( ! $eme_is_admin_request ) {
                $replacement = eme_ui_checkbox_binary( $gdpr, $fieldname, $label, 1, 'eme-gdpr-field' );
            }
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) {
            if ( !empty($selected_captcha) && ! $captcha_set ) {
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __( 'Subscribe', 'events-made-easy' );
            }
            $replacement = "<img id='task_loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'tasksignup', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $fieldname      = "{$var_prefix}FIELD" . $field_id . $var_postfix;
                $entered_val    = '';
                if ( $formfield['field_required'] ) {
                    $required = 1;
                }
                $replacement = eme_get_formfield_html( $formfield, $fieldname, '', $required );
            } else {
                $found = 0;
            }
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format );

    // now check we found all the required placeholders for the form to work
    if ( $lastname_found && $email_found ) {
        return $format;
    } else {
        $res = '';
        if ( ! $lastname_found || ! $email_found ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_LASTNAME and #_EMAIL placeholders.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$res</div>";
    }
}
function eme_replace_cancelformfields_placeholders( $event ) {
    // not used from the admin backend, but we check to be sure
    $eme_is_admin_request = eme_is_admin_request();
    if ( $eme_is_admin_request ) {
        return;
    }

    $registration_wp_users_only = $event['registration_wp_users_only'];
    if ( $registration_wp_users_only && ! is_user_logged_in() ) {
        return '';
    }

    if ( $registration_wp_users_only ) {
        $readonly = "readonly='readonly'";
    } else {
        $readonly = '';
    }

    if ( ! eme_is_empty_string( $event['event_cancel_form_format'] ) ) {
        $format = $event['event_cancel_form_format'];
    } elseif ( $event['event_properties']['event_cancel_form_format_tpl'] > 0 ) {
        $format = eme_get_template_format( $event['event_properties']['event_cancel_form_format_tpl'] );
    } else {
        $format = get_option( 'eme_cancel_form_format' );
    }

    $selected_captcha = '';
    $captcha_set = 0;
    if ( is_user_logged_in() && $event['event_properties']['captcha_only_logged_out'] ) {
        $format = eme_add_captcha_submit( $format );
    } else {
        if (!$eme_is_admin_request)
            $selected_captcha = $event['event_properties']['selected_captcha'];
        $format = eme_add_captcha_submit( $format, $selected_captcha );
    }

    // We need at least #_LASTNAME, #_EMAIL
    $lastname_found = 0;
    $email_found    = 0;

    $bookerLastName      = '';
    $bookerFirstName     = '';
    $bookerEmail         = '';
    $bookerCancelComment = '';
    if ( is_user_logged_in() ) {
        $current_user    = wp_get_current_user();
        $person          = eme_get_person_by_wp_id( $current_user->ID );
        if ( empty( $person ) ) {
            $person = eme_fake_person_by_wp_id( $current_user->ID );
        }
        $bookerLastName  = eme_esc_html($person['lastname']);
        $bookerFirstName = eme_esc_html($person['firstname']);
        $bookerEmail     = eme_esc_html($person['email']);
    }

    // the 2 placeholders that can contain extra text are treated separately first
    // the question mark is used for non greedy (minimal) matching
    // the s modifier makes . match newlines as well as all other characters (by default it excludes them)
    if ( preg_match( '/#_CAPTCHAHTML\{.*\}/s', $format ) ) {
        if ( !empty($selected_captcha) ) {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '$1', $format );
        } else {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
        }
    }

    # we need 3 required fields: #_NAME, #_EMAIL and #_SEATS
    # if these are not present: we don't replace anything and the form is worthless

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        // also support RESPNAME, RESPEMAIL, ...
        if ( strstr( $result, '#_RESP' ) ) {
            $result = str_replace( '#_RESP', '#_', $result );
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='lastname' id='lastname' value='$bookerLastName' $readonly placeholder='$placeholder_text'>";
            ++$lastname_found;
            // #_NAME is always required
            $required = 1;
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='firstname' id='firstname' value='$bookerFirstName' $readonly placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='email' name='email' id='email' value='$bookerEmail' $readonly placeholder='$placeholder_text'>";
            ++$email_found;
            // #_EMAIL is always required
            $required = 1;
        } elseif ( preg_match( '/#_CANCELCOMMENT(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Cancel reason', 'events-made-easy' );
            }
            $replacement = "<textarea $required_att name='eme_cancelcomment' placeholder='$placeholder_text'>$bookerCancelComment</textarea>";
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) {
            if ( !empty($selected_captcha) && ! $captcha_set ) {
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = get_option( 'eme_rsvp_delbooking_submit_string' );
            }
            $replacement = "<img id='rsvp_cancel_loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                    $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now any leftover event placeholders
    $format = eme_replace_event_placeholders( $format, $event );

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format );

    // now check we found all the required placeholders for the form to work
    if ( $lastname_found && $email_found ) {
        return $format;
    } else {
        $res = '';
        if ( ! $lastname_found || ! $email_found ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_LASTNAME and #_EMAIL placeholders.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$res</div>";
    }
}

function eme_replace_cancel_payment_placeholders( $format, $person, $booking_ids ) {
    // We need at least #_CANCEL_PAYMENT_LINE
    $line_found = 0;

    $selected_captcha = '';
    $captcha_set = 0;
    if ( is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ) ) {
        $format = eme_add_captcha_submit( $format );
    } else {
        $configured_captchas = eme_get_configured_captchas();
        $eme_is_admin_request = eme_is_admin_request();
        if (!empty($configured_captchas) && !$eme_is_admin_request)
            $selected_captcha = array_key_first($configured_captchas);
        $format = eme_add_captcha_submit( $format, $selected_captcha );
    }

    // the 2 placeholders that can contain extra text are treated separately first
    // the question mark is used for non greedy (minimal) matching
    // the s modifier makes . match newlines as well as all other characters (by default it excludes them)
    if ( preg_match( '/#_CAPTCHAHTML\{.*\}/s', $format ) ) {
        if ( !empty($selected_captcha) ) {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '$1', $format );
        } else {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
        }
    }

    // make sure we set the largest matched placeholders first, otherwise if you found e.g.
    // #_LOCATION, part of #_LOCATIONPAGEURL would get replaced as well ...
    $needle_offset = 0;
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $replacement        = '';
        if ( preg_match( '/#_CANCEL_PAYMENT_LINE$/', $result ) ) {
            $tmp_format = get_option( 'eme_cancel_payment_line_format' );
            ++$line_found;
            $eme_date_obj_now = new emeExpressiveDate( 'now', EME_TIMEZONE );
            foreach ( $booking_ids as $booking_id ) {
                $booking = eme_get_booking( $booking_id );
                $event   = eme_get_event( $booking['event_id'] );
                if ( empty( $event ) ) {
                    continue;
                }
                // first the rsvp cutoff based on event start date
                $cancel_cutofftime    = new emeExpressiveDate( $event['event_start'], EME_TIMEZONE );
                $eme_cancel_rsvp_days = -1 * $event['event_properties']['cancel_rsvp_days'];
                $cancel_cutofftime->modifyDays( $eme_cancel_rsvp_days );
                if ( $cancel_cutofftime < $eme_date_obj_now ) {
                    $no_longer_allowed = eme_translate( get_option( 'eme_rsvp_cancel_no_longer_allowed_string' ) );
                    return "<div class='eme-message-error eme-rsvp-message-error'>" . $no_longer_allowed . '</div>';
                }
                // second the rsvp cutoff based on booking age
                $cancel_cutofftime    = new emeExpressiveDate( $booking['creation_date'], EME_TIMEZONE );
                $eme_cancel_rsvp_days = $event['event_properties']['cancel_rsvp_age'];
                $cancel_cutofftime->modifyDays( $eme_cancel_rsvp_days );
                if ( $eme_cancel_rsvp_days && $cancel_cutofftime < $eme_date_obj_now ) {
                    $no_longer_allowed = eme_translate( get_option( 'eme_rsvp_cancel_no_longer_allowed_string' ) );
                    return "<div class='eme-message-error eme-rsvp-message-error'>" . $no_longer_allowed . '</div>';
                }
                $replacement .= eme_replace_booking_placeholders( $tmp_format, $event, $booking );
            }
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) { 
            if ( !empty($selected_captcha) && ! $captcha_set ) { 
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }       
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = get_option( 'eme_rsvp_delbooking_submit_string' );
            }
            $replacement = "<img id='cancel_loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } else {
            $found = 0;
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now any leftover placeholders
    $format = eme_replace_people_placeholders( $format, $person );

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format );

    // only if #_CANCEL_PAYMENT_LINE is present, we return the format
    if ( $line_found ) {
        return $format;
    } else {
        $res = __( 'Not all required fields are present in the form. We need at least #_CANCEL_PAYMENT_LINE and #_SUBMIT (or similar) placeholders.', 'events-made-easy' ) . '<br>';
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$res</div>";
    }
}

// the event param in eme_replace_extra_multibooking_formfields_placeholders
// is only there for generic replacements, like e.g. currency
function eme_replace_extra_multibooking_formfields_placeholders( $form_id, $format, $event ) {
    $bookerLastName     = '';
    $bookerFirstName    = '';
    $bookerBirthdate    = '';
    $bookerBirthplace   = '';
    $bookerAddress1     = '';
    $bookerAddress2     = '';
    $bookerCity         = '';
    $bookerZip          = '';
    $bookerState_code   = '';
    $bookerCountry_code = '';
    // if only 1 country, set it as default
    $countries_alpha2 = eme_get_countries_alpha2();
    if ( count( $countries_alpha2 ) == 1 ) {
        $bookerCountry_code = $countries_alpha2[0];
    }
    $bookerEmail   = '';
    $bookerComment = '';
    $bookerPhone   = '';
    $bd_email      = 0;
    $gdpr          = 0;

    $eme_is_admin_request = eme_is_admin_request();

    $allow_clear = 0;
    if ( is_user_logged_in() ) {
        $current_user       = wp_get_current_user();
        $person             = eme_get_person_by_wp_id( $current_user->ID );
        if ( empty( $person ) ) {
            $person = eme_fake_person_by_wp_id( $current_user->ID );
        }
        $bookerLastName     = eme_esc_html( $person['lastname'] );
        $bookerFirstName    = eme_esc_html( $person['firstname'] );
        $bookerBirthdate    = eme_is_date( $person['birthdate'] ) ? eme_esc_html( $person['birthdate'] ) : '';
        $bookerBirthplace   = eme_esc_html( $person['birthplace'] );
        $bookerAddress1     = eme_esc_html( $person['address1'] );
        $bookerAddress2     = eme_esc_html( $person['address2'] );
        $bookerCity         = eme_esc_html( $person['city'] );
        $bookerZip          = eme_esc_html( $person['zip'] );
        $bookerState_code   = eme_esc_html( $person['state_code'] );
        $bookerCountry_code = eme_esc_html( $person['country_code'] );
        $bookerEmail        = eme_esc_html( $person['email'] );
        $bookerPhone        = eme_esc_html( $person['phone'] );
        $bd_email           = intval( $person['bd_email'] );
        $gdpr               = intval( $person['gdpr'] );

        if ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ) {
            $allow_clear = 1;
        }
    }

    $selected_captcha = '';
    $captcha_set = 0;
    if ( is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ) ) {
        $selected_captcha = '';
        } else {
        $configured_captchas = eme_get_configured_captchas();
        if (!empty($configured_captchas) && !$eme_is_admin_request)
            $selected_captcha = array_key_first($configured_captchas);
        }

    // the 2 placeholders that can contain extra text are treated separately first
    // the question mark is used for non greedy (minimal) matching
    // the s modifier makes . match newlines as well as all other characters (by default it excludes them)
    if ( preg_match( '/#_CAPTCHAHTML\{.*\}/s', $format ) ) {
        if ( !empty($selected_captcha) ) {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '$1', $format );
        } else {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
        }
    }

    # we need 3 required fields: #_NAME, #_EMAIL and #_SEATS
    # if these are not present: we don't replace anything and the form is worthless

    // let us always set the dynamic price class, since the js checks if the dynamic price html-span exists anyway
    $dynamic_price_class_basic = 'dynamicprice';

    $needle_offset = 0;
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        // also support RESPNAME, RESPEMAIL, ...
        if ( strstr( $result, '#_RESP' ) ) {
            $result = str_replace( '#_RESP', '#_', $result );
        }
        // also support CONSENT
        if ( strstr( $result, '#_CONSENT' ) ) {
            $result = str_replace( '#_CONSENT', '#_GDPR', $result );
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            $this_readonly = '';
            if ( is_user_logged_in() ) {
                // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                $this_readonly = "readonly='readonly'";
                if ( $allow_clear ) {
                    $this_readonly .= " data-clearable='true'";
                }
            }
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='lastname' id='lastname' value='$bookerLastName' $this_readonly placeholder='$placeholder_text'>";
            // #_NAME is always required
            $required = 1;
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            $this_readonly = '';
            if ( is_user_logged_in() ) {
                // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                $this_readonly = "readonly='readonly'";
            }
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='firstname' id='firstname' value='$bookerFirstName' $this_readonly placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_BIRTHDATE(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Date of birth', 'events-made-easy' );
            }
            $replacement  = "<input type='hidden' name='birthdate' id='birthdate' value='$bookerBirthdate'>";
            $replacement .= "<input $required_att readonly='readonly' type='text' name='dp_birthdate' id='dp_birthdate' data-date='$bookerBirthdate' data-format='".EME_WP_DATE_FORMAT."' data-alt-field='birthdate' data-view='years' class='eme_formfield_fdate' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_BIRTHPLACE(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Place of birth', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='birthplace' id='birthplace' value='$bookerBirthplace' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_ADDRESS1(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address1_string' ) );
            }
            $replacement = "<input $required_att type='text' name='address1' id='address1' value='$bookerAddress1' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_ADDRESS2(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address2_string' ) );
            }
            $replacement = "<input $required_att type='text' name='address2' id='address2' value='$bookerAddress2' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_CITY(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'City', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='city' id='city' value='$bookerCity' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_STATE$/', $result ) ) {
            $fieldname = 'state_code';
            if (!empty($form_id)) {
                $fieldid = $form_id.'-'.$fieldname;
            } else {
                $fieldid = $fieldname;
            }
            if ( ! empty( $bookerState_code ) ) {
                $state_arr = [ $bookerState_code => eme_get_state_name( $bookerState_code, $bookerCountry_code ) ];
            } else {
                $state_arr = [];
            }
            $replacement = eme_form_select( $bookerState_code, $fieldname, $fieldid, $state_arr, '', $required, 'eme_select2_state_class' );
        } elseif ( preg_match( '/#_(ZIP|POSTAL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Postal code', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='zip' id='zip' value='$bookerZip' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_COUNTRY\{(.+)\}$/', $result, $matches ) ) {
            $fieldname = 'country_code';
            if (!empty($form_id)) {
                $fieldid = $form_id.'-'.$fieldname;
            } else {
                $fieldid = $fieldname;
            }
            if ( ! empty( $bookerCountry_code ) ) {
                $country_arr = [ $bookerCountry_code => eme_get_country_name( $bookerCountry_code ) ];
                $replacement = eme_ui_select( $bookerCountry_code, $fieldname, $fieldid, $country_arr, '', $required, 'eme_select2_country_class' );
            } else {
                $country_code = $matches[1];
                $country_name = eme_get_country_name( $country_code );
                if ( ! empty( $country_name ) ) {
                    $country_arr = [ $country_code => $country_name ];
                    $replacement = eme_ui_select( $country_code, $fieldname, $fieldid, $country_arr, '', $required, 'eme_select2_country_class' );
                }
            }
        } elseif ( preg_match( '/#_COUNTRY$/', $result ) ) {
            $fieldname = 'country_code';
            if (!empty($form_id)) {
                $fieldid = $form_id.'-'.$fieldname;
            } else {
                $fieldid = $fieldname;
            }
            if ( ! empty( $bookerCountry_code ) ) {
                $country_arr = [ $bookerCountry_code => eme_get_country_name( $bookerCountry_code ) ];
            } else {
                $country_arr = [];
            }
            $replacement = eme_ui_select( $bookerCountry_code, $fieldname, $fieldid, $country_arr, '', $required, 'eme_select2_country_class' );
        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            $this_readonly = '';
            if ( is_user_logged_in() ) {
                // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                $this_readonly = "readonly='readonly'";
            }
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='email' name='email' id='email' value='$bookerEmail' $this_readonly placeholder='$placeholder_text'>";
            // #_EMAIL is always required
            $required = 1;
        } elseif ( preg_match( '/#_BIRTHDAY_EMAIL$/', $result ) ) {
            $replacement = eme_ui_select_binary( $bd_email, 'bd_email' );
        } elseif ( preg_match( '/#_OPT_OUT$/', $result ) ) {
            $selected_massmail = 1;
            $replacement = eme_ui_select_binary( $selected_massmail, 'massmail' );
            if ( get_option( 'eme_massmail_popup' ) ) {
                $popup       = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
            }
        } elseif ( preg_match( '/#_OPT_IN$/', $result ) ) {
            $selected_massmail = 0;
            $replacement = eme_ui_select_binary( $selected_massmail, 'massmail' );
            if ( get_option( 'eme_massmail_popup' ) ) {
                $popup       = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
            }
        } elseif ( preg_match( '/#_GDPR(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = '';
            }
            if ( ! $eme_is_admin_request ) {
                $replacement = eme_ui_checkbox_binary( $gdpr, 'gdpr', $label, 1, 'eme-gdpr-field nodynamicupdates' );
            }
        } elseif ( preg_match( '/#_REMEMBERME(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __('Remember me?','events-made-easy');
            }
            if ( ! $eme_is_admin_request && ! is_user_logged_in()) {
                $replacement = eme_ui_checkbox_binary( 0, 'eme_rememberme', $label, 0, 'eme-rememberme-field nodynamicupdates' );
            }
        } elseif ( preg_match( '/#_SUBSCRIBE_TO_GROUP\{(.+?)\}(\{.+?\})?/', $result, $matches ) ) {
            if ( is_numeric( $matches[1] ) ) {
                    $group = eme_get_group( $matches[1] );
            } else {
                $group = eme_get_group_by_name( eme_sanitize_request( $matches[1] ) );
            }
            if ( ! empty( $group ) ) {
                if ( ! $group['public'] ) {
                    $replacement = __( 'Group is not public', 'events-made-easy' );
                } else {
                    $group_id = $group['group_id'];
                    if ( isset( $matches[2] ) ) {
                        // remove { and } (first and last char of second match)
                        $label = substr( $matches[2], 1, -1 );
                    } else {
                        $label = $group['name'];
                    }
                    $replacement = "<input id='subscribe_groups_$group_id' name='subscribe_groups[]' value='$group_id' type='checkbox' class='nodynamicupdates'>";
                    if ( ! empty( $label ) ) {
                        $replacement .= "<label for='subscribe_groups_$group_id'>" . eme_esc_html( $label ) . '</label>';
                    }
                }
            } else {
                $replacement = __( 'Group does not exist', 'events-made-easy' );
            }
        } elseif ( preg_match( '/#_PASSWORD(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! empty( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Password', 'events-made-easy' );
                }
                $fieldname   = 'rsvp_password';
                $replacement = "<input required='required' type='text' name='$fieldname' value='' class='eme_passwordfield' autocomplete='off' placeholder='$placeholder_text'>";
                $required    = 1;
            }
        } elseif ( preg_match( '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Phone number', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='tel' name='phone' id='phone' value='$bookerPhone' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_COMMENT(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Comment', 'events-made-easy' );
            }
            $replacement = "<textarea $required_att name='eme_rsvpcomment' placeholder='$placeholder_text' >$bookerComment</textarea>";
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) { 
            if ( !empty($selected_captcha) && ! $captcha_set ) { 
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }       
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = get_option( 'eme_rsvp_addbooking_submit_string' );
            }
            $replacement = "<img id='rsvp_add_loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } elseif ( preg_match( '/#_DYNAMICPRICE$/', $result ) ) {
            $replacement = "<span id='eme_calc_bookingprice'></span>";
        } elseif ( preg_match( '/#_DYNAMICPRICE_PER_PG|#_DYNAMICPRICE_DETAILED$/', $result ) ) {
            $replacement = "<span id='eme_calc_bookingprice_detail'></span>";
        } elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $postfield_name = 'FIELD' . $field_id;
                $entered_val    = '';
                if ( $formfield['field_required'] ) {
                    $required = 1;
                }
                if ( $formfield['extra_charge'] ) {
                    $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $dynamic_price_class_basic );
                } else {
                    $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required );
                }
            } else {
                $found = 0;
            }
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                    $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now any leftover event placeholders
    $format = eme_replace_event_placeholders( $format, $event );

    // now, replace any language tags found in the format itself
    $format = eme_translate( $format );
    return $format;
}

function eme_get_dyndata_people_fields( $condition ) {
    global $wpdb;
    $formfields_table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $sql              = $wpdb->prepare( "SELECT * FROM $formfields_table where field_purpose='people' AND FIND_IN_SET(%s,field_condition)", $condition );
    return $wpdb->get_results( $sql, ARRAY_A );
}

function eme_replace_dynamic_rsvp_formfields_placeholders( $event, $booking, $format, $grouping, $i = 0 ) {
    $eme_is_admin_request = eme_is_admin_request();
    $event_id             = $event['event_id'];
    $dynamic_price_class  = 'dynamicprice';
    // the dynamic field class is used to indicate this is a dynamically added field and we don't allow extra dynamic actions on it (except the price)
    // this helps limitting the amount of ajax requests
    $dynamic_field_class = 'nodynamicupdates dynamicfield';
    if ( $eme_is_admin_request && ! empty( $booking['booking_id'] ) ) {
        $editing_booking_from_backend = 1;
        $dyn_answers                  = eme_get_dyndata_booking_answer( $booking['booking_id'], $grouping, $i );
        $files1                       = eme_get_uploaded_files( $booking['person_id'], 'people' );
        $files2                       = eme_get_uploaded_files( $booking['booking_id'], 'bookings' );
        $files                        = array_merge( $files1, $files2 );
    } else {
        $editing_booking_from_backend = 0;
        $dyn_answers                  = [];
        $files                        = [];
    }

    $needle_offset = 0;
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';

        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }
        if ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $var_prefix     = "dynamic_bookings[$event_id][$grouping][$i][";
                $var_postfix    = ']';
                $postfield_name = "{$var_prefix}FIELD" . $field_id . $var_postfix;
                $postvar_arr    = [ 'dynamic_bookings', $event_id, $grouping, $i, 'FIELD' . $field_id ];

                // when we edit a booking, there's nothing in $_POST until a field condition changes
                // so the first time entered_val=''
                $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
                // if from backend and entered_val ===false, then get it from the stored answer
                if ( $editing_booking_from_backend && $entered_val === false ) {
                    foreach ( $dyn_answers as $answer ) {
                        if ( $answer['field_id'] == $field_id ) {
                            $entered_val = $answer['answer'];
                        }
                    }
                }
                if ( $editing_booking_from_backend ) {
                    if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                        $entered_files = [];
                        foreach ( $files as $file ) {
                            if ( $file['field_id'] == $field_id && $file['extra_id'] == "$event_id$grouping$i" ) {
                                $entered_files[] = $file;
                            }
                        }
                        $entered_val = $entered_files;
                    }
                }

                if ( $formfield['field_required'] ) {
                    $required = 1;
                }
                if ( $formfield['extra_charge'] ) {
                    $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $dynamic_price_class . ' ' . $dynamic_field_class );
                } else {
                    $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $dynamic_field_class );
                }
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELDCOUNTER$/', $result, $matches ) ) {
            $replacement = intval( $i ) + 1;
        } elseif ( preg_match( '/#_FIELDGROUPINDEX$/', $result, $matches ) ) {
            $replacement = intval( $grouping ) + 1;
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }
    // now any leftover event placeholders
    $format = eme_replace_event_placeholders( $format, $event );
    return $format;
}

function eme_replace_dynamic_membership_formfields_placeholders( $membership, $member, $format, $grouping, $i = 0 ) {
    $membership_id        = $membership['membership_id'];
    $dynamic_price_class  = 'dynamicprice';
    // the dynamic field class is used to indicate this is a dynamically added field and we don't allow extra dynamic actions on it (except the price)
    // this helps limitting the amount of ajax requests
    $dynamic_field_class = 'nodynamicupdates dynamicfield';
    if ( ! empty( $member['member_id'] ) && current_user_can(get_option( 'eme_cap_edit_members' ) ) ) {
        $dyn_answers = eme_get_dyndata_member_answer( $member['member_id'], $grouping, $i );
        $files1      = eme_get_uploaded_files( $member['person_id'], 'people' );
        $files2      = eme_get_uploaded_files( $member['member_id'], 'members' );
        $files       = array_merge( $files1, $files2 );
        $member_edit = 1;
    } else {
        $dyn_answers = [];
        $files       = [];
        $member_edit = 0;
    }

    $needle_offset = 0;
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';

        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }
        if ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'members', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $var_prefix     = "dynamic_member[$membership_id][$grouping][$i][";
                $var_postfix    = ']';
                $postfield_name = "{$var_prefix}FIELD" . $field_id . $var_postfix;
                $postvar_arr    = [ 'dynamic_member', $membership_id, $grouping, $i, 'FIELD' . $field_id ];

                // when we edit a booking, there's nothing in $_POST until a field condition changes
                // so the first time entered_val=''
                $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
                // if entered_val ===false, then get it from the stored answer
                if ( $entered_val === false ) {
                    foreach ( $dyn_answers as $answer ) {
                        if ( $answer['field_id'] == $field_id ) {
                            $entered_val = $answer['answer'];
                        }
                    }
                }

                if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                    $entered_files = [];
                    foreach ( $files as $file ) {
                        if ( $file['field_id'] == $field_id && $file['extra_id'] == "$membership_id$grouping$i" ) {
                            $entered_files[] = $file;
                        }
                    }
                    $entered_val = $entered_files;
                }

                if ( $formfield['field_required'] ) {
                    $required = 1;
                }
                if ( $formfield['extra_charge'] ) {
                    $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $dynamic_price_class . ' ' . $dynamic_field_class, 0, 0, $member_edit );
                } else {
                    $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $dynamic_field_class, 0, 0, $member_edit );
                }
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELDCOUNTER$/', $result, $matches ) ) {
            $replacement = intval( $i ) + 1;
        } elseif ( preg_match( '/#_FIELDGROUPINDEX$/', $result, $matches ) ) {
            $replacement = intval( $grouping ) + 1;
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }
    $format = eme_replace_membership_placeholders( $format, $membership );
    return $format;
}

function eme_replace_rsvp_formfields_placeholders( $form_id, $event, $booking, $format = '', $is_multibooking = 0 ) {
    $eme_is_admin_request = eme_is_admin_request();
    // the next can happen if we would be editing a booking where the event has been deleted but somehow the booking remains
    if ( isset( $event['event_id'] ) ) {
        $event_id = $event['event_id'];
    } else {
        $event_id = 0;
    }
    if (!empty($event['location_id'] )) {
        $location = eme_get_location( $event['location_id'] );
    } else {
        $location = [];
    }
    $registration_wp_users_only = $event['registration_wp_users_only'];
    if ( $registration_wp_users_only && ! is_user_logged_in() ) {
        return '';
    }

    $allow_clear  = 0;
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        if ( current_user_can( get_option( 'eme_cap_edit_events' ) ) ||
            ( current_user_can( get_option( 'eme_cap_author_event' ) ) && ( $event['event_author'] == $current_user->ID || $event['event_contactperson_id'] == $current_user->ID ) ) ) {
            $allow_clear = 1;
        } elseif ( ! $registration_wp_users_only ) {
            $allow_clear = 1;
        }
    } else {
        $current_user = 0;
    }
    if ( !$eme_is_admin_request && empty( $booking['booking_id'] ) ) {
        $new_booking_in_frontend = 1;
    } else {
        $new_booking_in_frontend = 0;
    }
    if ( $eme_is_admin_request && ! empty( $booking['booking_id'] ) ) {
        $editing_booking_from_backend = 1;
    } else {
        $editing_booking_from_backend = 0;
    }
    if ( $eme_is_admin_request && get_option( 'eme_rsvp_admin_allow_overbooking' ) ) {
        $allow_overbooking = 1;
    } else {
        $allow_overbooking = 0;
    }

    $bookerLastName     = '';
    $bookerFirstName    = '';
    $bookerBirthdate    = '';
    $bookerBirthplace   = '';
    $bookerAddress1     = '';
    $bookerAddress2     = '';
    $bookerCity         = '';
    $bookerZip          = '';
    $bookerState_code   = '';
    $bookerCountry_code = '';
    // if only 1 country, set it as default
    $countries_alpha2 = eme_get_countries_alpha2();
    if ( count( $countries_alpha2 ) == 1 ) {
        $bookerCountry_code = $countries_alpha2[0];
    }
    $bookerEmail      = '';
    $bookerComment    = '';
    $bookerPhone      = '';
    $bookedSeats      = 0;
    $booking_seats_mp = [];
    $massmail         = null;
    $bd_email         = 0;
    $gdpr             = 0;

    // don't fill out the basic info if in the backend, but do it only if in the frontend
    if ( is_user_logged_in() && ! $eme_is_admin_request ) {
        $person             = eme_get_person_by_wp_id( $current_user->ID );
        if ( empty( $person ) ) {
            $person = eme_fake_person_by_wp_id( $current_user->ID );
        }
        $bookerLastName     = eme_esc_html( $person['lastname'] );
        $bookerFirstName    = eme_esc_html( $person['firstname'] );
        $bookerBirthdate    = eme_is_date( $person['birthdate'] ) ? eme_esc_html( $person['birthdate'] ) : '';
        $bookerBirthplace   = eme_esc_html( $person['birthplace'] );
        $bookerAddress1     = eme_esc_html( $person['address1'] );
        $bookerAddress2     = eme_esc_html( $person['address2'] );
        $bookerCity         = eme_esc_html( $person['city'] );
        $bookerZip          = eme_esc_html( $person['zip'] );
        $bookerState_code   = eme_esc_html( $person['state_code'] );
        $bookerCountry_code = eme_esc_html( $person['country_code'] );
        $bookerEmail        = eme_esc_html( $person['email'] );
        $bookerPhone        = eme_esc_html( $person['phone'] );
        $massmail           = intval( $person['massmail'] );
        $bd_email           = intval( $person['bd_email'] );
        $gdpr               = intval( $person['gdpr'] );
    }

    if ( $editing_booking_from_backend ) {
        if ( ! empty( $booking['person_id'] ) ) {
            $person = eme_get_person( $booking['person_id'] );
            // when editing a booking
            $bookerLastName     = eme_esc_html( $person['lastname'] );
            $bookerFirstName    = eme_esc_html( $person['firstname'] );
            $bookerBirthdate    = eme_is_date( $person['birthdate'] ) ? eme_esc_html( $person['birthdate'] ) : '';
            $bookerBirthplace   = eme_esc_html( $person['birthplace'] );
            $bookerAddress1     = eme_esc_html( $person['address1'] );
            $bookerAddress2     = eme_esc_html( $person['address2'] );
            $bookerCity         = eme_esc_html( $person['city'] );
            $bookerZip          = eme_esc_html( $person['zip'] );
            $bookerState_code   = eme_esc_html( $person['state_code'] );
            $bookerCountry_code = eme_esc_html( $person['country_code'] );
            $bookerEmail        = eme_esc_html( $person['email'] );
            $bookerPhone        = eme_esc_html( $person['phone'] );
            $massmail           = intval( $person['massmail'] );
            $bd_email           = intval( $person['bd_email'] );
            $gdpr               = intval( $person['gdpr'] );
        }
        $bookerComment = eme_esc_html( $booking['booking_comment'] );
        $bookedSeats   = eme_esc_html( $booking['booking_seats'] );
        if ( $booking['booking_seats_mp'] ) {
            $booking_seats_mp = eme_convert_multi2array( $booking['booking_seats_mp'] );
        }
    }

    // if not in the backend and wp membership is required
    // or when editing an existing booking via backend (not a new)
    $disabled = '';
    if ( $editing_booking_from_backend ) {
        $readonly = "readonly='readonly'";
        $disabled = "disabled='disabled'";
    } else {
        $readonly = '';
        $disabled = '';
    }

    // now one final case: if invite url is followed, use the set email from the invite link and make the email field readonly
    if ( eme_check_invite_url( $event['event_id'] ) && ! $eme_is_admin_request ) {
        if ( ! empty( $_GET['eme_email'] ) ) {
            $bookerEmail = eme_sanitize_email( $_GET['eme_email'] );
        }
        if ( ! empty( $_GET['eme_ln'] ) ) {
            $bookerLastName = eme_sanitize_request( $_GET['eme_ln'] );
        }
        if ( ! empty( $_GET['eme_fn'] ) ) {
            $bookerFirstName = eme_sanitize_request( $_GET['eme_fn'] );
        }
        $invite_readonly = "readonly='readonly'";
    } else {
        $invite_readonly = '';
    }

    if ( eme_is_empty_string( $format ) ) {
        if ( ! eme_is_empty_string( $event['event_registration_form_format'] ) ) {
            $format = $event['event_registration_form_format'];
        } elseif ( $event['event_properties']['event_registration_form_format_tpl'] > 0 ) {
            $format = eme_get_template_format( $event['event_properties']['event_registration_form_format_tpl'] );
        } else {
            $format = get_option( 'eme_registration_form_format' );
        }
    }

    // check which fields are used in the event definition for dynamic data
    $eme_dyndatafields = [];
    if ( isset( $event['event_properties']['rsvp_dyndata'] ) ) {
        foreach ( $event['event_properties']['rsvp_dyndata'] as $dynfield ) {
            $eme_dyndatafields[] = $dynfield['field'];
        }
    }
    if ( ! empty( $eme_dyndatafields ) ) {
        $add_dyndata = 1;
    } else {
        $add_dyndata = 0;
    }

    $selected_captcha = '';
    $captcha_set = 0;
    if ( ! $is_multibooking ) {
        if ( is_user_logged_in() && $event['event_properties']['captcha_only_logged_out'] ) {
            $format = eme_add_captcha_submit( $format );
        } else {
            if (!$eme_is_admin_request)
                $selected_captcha = $event['event_properties']['selected_captcha'];
            $format = eme_add_captcha_submit( $format, $selected_captcha, $add_dyndata );
        }
    }

    $min_allowed = $event['event_properties']['min_allowed']; // it is a string (can be a number or some multi-format)
    $max_allowed = $event['event_properties']['max_allowed']; // it is a string (can be a number or some multi-format)
    //if ($event['event_properties']['take_attendance']) {
    //   $min_allowed = 0;
    //   $max_allowed = 1;
    //}

    $waitinglist       = 0;
    $waitinglist_seats = $event['event_properties']['waitinglist_seats'];
    $event_seats       = eme_get_total( $event['event_seats'] );
    if ( $allow_overbooking ) {
        // allowing overbooking
        // then the avail seats are the total seats
        $avail_seats = $event_seats;
    } else {
        // the next gives the number of available seats excluding waitinglist, even for multiprice
        $avail_seats = eme_get_available_seats( $event_id, 1 );
        if ( $waitinglist_seats > 0 && $avail_seats <= 0 && ! eme_is_multi( $event['event_seats'] ) ) {
            $waitinglist = 1;
            $avail_seats = eme_get_available_seats( $event_id );
        }
    }

    $booked_seats_options = [];
    if ( eme_is_multi( $max_allowed ) ) {
        $multi_max_allowed    = eme_convert_multi2array( $max_allowed );
        $max_allowed_is_multi = 1;
    } else {
        $max_allowed_is_multi = 0;
    }
    if ( eme_is_multi( $min_allowed ) ) {
        $multi_min_allowed    = eme_convert_multi2array( $min_allowed );
        $min_allowed_is_multi = 1;
    } else {
        $min_allowed_is_multi = 0;
    }
    if ( eme_is_multi( $event['event_seats'] ) ) {
        // if allowing overbooking
        // then the avail seats are the total seats
        $event_multiseats = eme_convert_multi2array( $event['event_seats'] );
        if ( $allow_overbooking ) {
            $multi_avail = $event_multiseats;
        } else {
            $multi_avail = eme_get_available_multiseats( $event_id );
        }

        foreach ( $multi_avail as $key => $avail_seats ) {
            $booked_seats_options[ $key ] = [];
            if ( $max_allowed_is_multi ) {
                $real_max_allowed = (int) $multi_max_allowed[ $key ];
            } else {
                $real_max_allowed = (int) $max_allowed;
            }

            // don't let people choose more seats than available
            if ( $event_multiseats[ $key ] > 0 && ( $real_max_allowed > $avail_seats || $real_max_allowed == 0 ) ) {
                $real_max_allowed = $avail_seats;
            }

            if ( !empty($location) && !empty($location['location_properties']['max_capacity'])) {
                $used_capacity = eme_get_event_location_used_capacity( $event );
                $free_location_capacity = $location['location_properties']['max_capacity'] - $used_capacity;
                if ($free_location_capacity < 0) {
                    $free_location_capacity=0;
                }
                if ($real_max_allowed > $free_location_capacity) {
                    $real_max_allowed = $free_location_capacity;
                }
            }

            // 0 means no limit, but we need a sensible max to show ...
            if ( $event_multiseats[ $key ] == 0 && $real_max_allowed == 0 ) {
                $real_max_allowed = 10;
            }

            if ( $editing_booking_from_backend && isset( $booking_seats_mp[ $key ] ) ) {
                // when editing a booking in the backend, the number available seats are in fact the number of free seats+number of booked seat
                $real_max_allowed += intval( $booking_seats_mp[ $key ] );
                // now also respect the set max for the event
                if ( $max_allowed_is_multi && $real_max_allowed > intval( $multi_max_allowed[ $key ] ) && intval( $multi_max_allowed[ $key ] ) > 0 ) {
                    $real_max_allowed = intval( $multi_max_allowed[ $key ] );
                } elseif ( $real_max_allowed > $max_allowed && $max_allowed > 0 ) {
                    $real_max_allowed = $max_allowed;
                }
            }

            if ( $min_allowed_is_multi ) {
                $real_min_allowed = $multi_min_allowed[ $key ];
            } else {            // it's no use to have a non-multi minimum for multiseats
                $real_min_allowed = 0;
            }

            for ( $i = $real_min_allowed; $i <= $real_max_allowed; $i++ ) {
                $booked_seats_options[ $key ][ $i ] = $i;
            }
        }
    } elseif ( eme_is_multi( $event['price'] ) ) {
        // we just need to loop through the same amount of seats as there are prices
        foreach ( eme_convert_multi2array( $event['price'] ) as $key => $value ) {
            $booked_seats_options[ $key ] = [];
            if ( $max_allowed_is_multi ) {
                $real_max_allowed = (int) $multi_max_allowed[ $key ];
            } else {
                $real_max_allowed = (int) $max_allowed;
            }

            // don't let people choose more seats than available
            if ( $event_seats > 0 && ( $real_max_allowed > $avail_seats || $real_max_allowed == 0 ) ) {
                $real_max_allowed = $avail_seats;
            }

            // limit to free location capacity too
            if ( !empty($location) && !empty($location['location_properties']['max_capacity'])) {
                $used_capacity = eme_get_event_location_used_capacity( $event );
                $free_location_capacity = $location['location_properties']['max_capacity'] - $used_capacity;
                if ($free_location_capacity < 0) {
                    $free_location_capacity=0;
                }
                if ($real_max_allowed > $free_location_capacity) {
                    $real_max_allowed = $free_location_capacity;
                }
            }

            // 0 means no limit, but we need a sensible max to show ...
            if ( $event_seats == 0 && $real_max_allowed == 0 ) {
                $real_max_allowed = 10;
            }

            if ( $editing_booking_from_backend && isset( $booking_seats_mp[ $key ] ) ) {
                // when editing a booking in the backend, the number available seats are in fact the number of free seats+number of booked seat
                $real_max_allowed += $booking_seats_mp[ $key ];
                // now also respect the set max for the event
                if ( $max_allowed_is_multi && $real_max_allowed > intval( $multi_max_allowed[ $key ] ) && intval( $multi_max_allowed[ $key ] ) > 0 ) {
                    $real_max_allowed = intval( $multi_max_allowed[ $key ] );
                } elseif ( $real_max_allowed > $max_allowed && $max_allowed > 0 ) {
                    $real_max_allowed = $max_allowed;
                }
            }

            if ( $min_allowed_is_multi ) {
                $real_min_allowed = intval( $multi_min_allowed[ $key ] );
            } else {            // it's no use to have a non-multi minimum for multiseats/multiprice
                $real_min_allowed = 0;
            }

            for ( $i = $real_min_allowed; $i <= $real_max_allowed; $i++ ) {
                $booked_seats_options[ $key ][ $i ] = $i;
            }
        }
    } else {
        if ( $max_allowed_is_multi ) {
            $real_max_allowed = $multi_max_allowed[0];
        } else {
            $real_max_allowed = $max_allowed;
        }

        // don't let people choose more seats than available
        if ( $event_seats > 0 && ( $real_max_allowed > $avail_seats || $real_max_allowed == 0 ) ) {
            $real_max_allowed = $avail_seats;
        }

        // limit to free location capacity too
        if ( !empty($location) && !empty($location['location_properties']['max_capacity'])) {
                        $used_capacity = eme_get_event_location_used_capacity( $event );
            $free_location_capacity = $location['location_properties']['max_capacity'] - $used_capacity;
            if ($free_location_capacity < 0) {
                $free_location_capacity=0;
            }
                        if ($real_max_allowed > $free_location_capacity) {
                $real_max_allowed = $free_location_capacity;
                        }
                }

        // 0 means no limit, but we need a sensible max to show ...
        if ( $event_seats == 0 && $real_max_allowed == 0 ) {
            $real_max_allowed = 10;
        }

        // let's make sure that when editing a booking in the backend, at least the same amount of seats are shown as there were booked seats
        if ( $editing_booking_from_backend && $real_max_allowed < $bookedSeats ) {
            $real_max_allowed += $bookedSeats;
            if ( $max_allowed_is_multi && $real_max_allowed > $multi_max_allowed[0] ) {
                $real_max_allowed = $multi_max_allowed[0];
            } elseif ( $real_max_allowed > $max_allowed ) {
                $real_max_allowed = $max_allowed;
            }
        }

        if ( $min_allowed_is_multi ) {
            $real_min_allowed = $multi_min_allowed[0];
        } else {
            $real_min_allowed = $min_allowed;
        }

        for ( $i = $real_min_allowed; $i <= $real_max_allowed; $i++ ) {
            $booked_seats_options[ $i ] = $i;
        }
    }

    $discount_fields_count = 0;
    $error_msg             = '';
    // We need at least #_LASTNAME, #_EMAIL, #_SEATS and #_SUBMIT
    $lastname_found = 0;
    $email_found    = 0;
    $seats_found    = 0;

    if ( ! empty( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request ) {
        $password_found = 0;
    } else {
        $password_found = 1;
    }

    // for multi booking forms, let's fake that all are present
    if ( ! $eme_is_admin_request && $is_multibooking ) {
        $lastname_found = 1;
        $email_found    = 1;
        $seats_found    = 1;
    }

    // first we do the custom attributes, since these can contain other placeholders
    preg_match_all( '/#(ESC|URL)?_ATT\{.+?\}(\{.+?\})?/', $format, $results );
    foreach ( $results[0] as $resultKey => $result ) {
        $need_escape    = 0;
        $need_urlencode = 0;
        $orig_result    = $result;
        if ( strstr( $result, '#ESC' ) ) {
            $result      = str_replace( '#ESC', '#', $result );
            $need_escape = 1;
        } elseif ( strstr( $result, '#URL' ) ) {
            $result         = str_replace( '#URL', '#', $result );
            $need_urlencode = 1;
        }
        $replacement = '';
        //Strip string of placeholder and just leave the reference
        $attRef = substr( substr( $result, 0, strpos( $result, '}' ) ), 6 );
        if ( isset( $event['event_attributes'][ $attRef ] ) ) {
            $replacement = $event['event_attributes'][ $attRef ];
        }
        if ( trim( $replacement ) == ''
            && isset( $results[2][ $resultKey ] )
            && $results[2][ $resultKey ] != '' ) {
            //Check to see if we have a second set of braces;
            $replacement = substr( $results[2][ $resultKey ], 1, strlen( trim( $results[2][ $resultKey ] ) ) - 2 );
        }

        if ( $need_escape ) {
            $replacement = eme_esc_html( preg_replace( '/\n|\r/', '', $replacement ) );
        }
        if ( $need_urlencode ) {
            $replacement = rawurlencode( $replacement );
        }
        $format = str_replace( $orig_result, $replacement, $format );
    }

    // the 2 placeholders that can contain extra text are treated separately first
    // the question mark is used for non greedy (minimal) matching
    // the s modifier makes . match newlines as well as all other characters (by default it excludes them)
    if ( preg_match( '/#_CAPTCHAHTML\{.*\}/s', $format ) ) {
        // only show the captcha when booking via the frontend, not the admin backend
        if ( $is_multibooking ) {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
        } elseif ( !empty($selected_captcha) ) {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '$1', $format );
        } else {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
        }
    }

    # we need 3 required fields: #_NAME, #_EMAIL and #_SEATS
    # if these are not present: we don't replace anything and the form is worthless

    // let us always set the dynamic price class, since the js checks if the dynamic price html-span exists anyway
    $dynamic_price_class       = "class='dynamicprice'";
    $dynamic_price_class_basic = 'dynamicprice';

    # check if dynamic data is requested
    if ( ( strstr( $format, '#_DYNAMICDATA' ) && ! empty( $eme_dyndatafields ) ) || $event['event_properties']['dyndata_all_fields'] ) {
        $dynamic_data_wanted = 1;
    } else {
        $dynamic_data_wanted = 0;
    }
    # the next is to make sure we render #_DYNAMICDATA only once
    $dynamic_data_rendered = 0;

    $needle_offset = 0;
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        // also support RESPNAME, RESPEMAIL, ...
        if ( strstr( $result, '#_RESP' ) ) {
            $result = str_replace( '#_RESP', '#_', $result );
        }

        // check for dynamic field class for this field
        if ( $dynamic_data_wanted && ! $is_multibooking && ( in_array( $result, $eme_dyndatafields ) || $event['event_properties']['dyndata_all_fields'] ) ) {
            $dynamic_field_class       = "class='dynamicupdates'";
            $dynamic_field_class_basic = 'dynamicupdates';
        } else {
            $dynamic_field_class       = "class='nodynamicupdates'";
            $dynamic_field_class_basic = 'nodynamicupdates';
        }

        if ( $is_multibooking ) {
            $var_prefix  = "bookings[$event_id][";
            $var_postfix = ']';
        } else {
            $var_prefix  = '';
            $var_postfix = '';
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'lastname';
                if ( is_user_logged_in() && ! $eme_is_admin_request ) {
                    // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                    $this_readonly = "readonly='readonly'";
                    if ( $allow_clear ) {
                        $this_readonly .= " data-clearable='true'";
                    }
                } elseif ( ! empty( $invite_readonly ) && ! empty( $bookerLastName ) ) {
                    $this_readonly = $invite_readonly;
                } else {
                    $this_readonly = $readonly;
                }
                if ( isset( $matches[2] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[2], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
                }
                $replacement = "<input required='required' type='text' name='$fieldname' id='$fieldname' value='$bookerLastName' $this_readonly $dynamic_field_class placeholder='$placeholder_text'>";
                if ( wp_script_is( 'eme-autocomplete-form', 'enqueued' ) && get_option( 'eme_autocomplete_sources' ) != 'none' ) {
                    $replacement .= "&nbsp;<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . esc_html__( "Notice: since you're logged in as a person with the right to edit or author this event, the 'Last name' field is also an autocomplete field so you can select existing people if desired. Or just clear the field and start typing.", 'events-made-easy' ) . "'>";
                }

                ++$lastname_found;
                // #_NAME is always required
                $required = 1;
            }
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'firstname';
                if ( is_user_logged_in() && ! $eme_is_admin_request ) {
                    // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                    $this_readonly = "readonly='readonly'";
                } elseif ( ! empty( $invite_readonly ) && ! empty( $bookerFirstName ) ) {
                    $this_readonly = $invite_readonly;
                } else {
                    $this_readonly = $readonly;
                }
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
                }
                $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerFirstName' $this_readonly $dynamic_field_class placeholder='$placeholder_text'>";
            }
        } elseif ( preg_match( '/#_BIRTHDATE(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Date of birth', 'events-made-easy' );
                }
                $fieldname    = 'birthdate';
                $replacement  = "<input type='hidden' name='$fieldname' id='$fieldname' value='$bookerBirthdate'>";
                $replacement .= "<input $required_att readonly='readonly' $disabled type='text' name='dp_{$fieldname}' id='dp_{$fieldname}' data-date='$bookerBirthdate' data-format='".EME_WP_DATE_FORMAT."' data-view='years' data-alt-field='birthdate' class='eme_formfield_fdate' placeholder='$placeholder_text'>";
            }
        } elseif ( preg_match( '/#_BIRTHPLACE(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'birthplace';
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Place of birth', 'events-made-easy' );
                }
                $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerBirthplace' placeholder='$placeholder_text'>";
            }
        } elseif ( preg_match( '/#_ADDRESS1(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'address1';
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = eme_trans_esc_html( get_option( 'eme_address1_string' ) );
                }
                $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerAddress1' placeholder='$placeholder_text' $readonly $dynamic_field_class>";
            }
        } elseif ( preg_match( '/#_ADDRESS2(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'address2';
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = eme_trans_esc_html( get_option( 'eme_address2_string' ) );
                }
                $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerAddress2' placeholder='$placeholder_text' $readonly $dynamic_field_class>";
            }
        } elseif ( preg_match( '/#_CITY(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'city';
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'City', 'events-made-easy' );
                }
                $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerCity' placeholder='$placeholder_text' $readonly $dynamic_field_class>";
            }
        } elseif ( preg_match( '/#_(ZIP|POSTAL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'zip';
                if ( isset( $matches[2] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[2], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Postal code', 'events-made-easy' );
                }
                $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerZip' placeholder='$placeholder_text' $readonly $dynamic_field_class>";
            }
        } elseif ( preg_match( '/#_STATE$/', $result ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'state_code';
                if (!empty($form_id)) {
                    $fieldid = $form_id.'-'.$fieldname;
                } else {
                    $fieldid = $fieldname;
                }
                if ( ! empty( $bookerState_code ) ) {
                    $state_arr = [ $bookerState_code => eme_get_state_name( $bookerState_code, $bookerCountry_code ) ];
                } else {
                    $state_arr = [];
                }
                $replacement = eme_form_select( $bookerState_code, $fieldname, $fieldid, $state_arr, '', $required, "eme_select2_state_class $dynamic_field_class_basic", $disabled );
            }
        } elseif ( preg_match( '/#_COUNTRY$/', $result ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'country_code';
                if (!empty($form_id)) {
                    $fieldid = $form_id.'-'.$fieldname;
                } else {
                    $fieldid = $fieldname;
                }
                if ( ! empty( $bookerCountry_code ) ) {
                        $country_arr = [ $bookerCountry_code => eme_get_country_name( $bookerCountry_code ) ];
                } else {
                    $country_arr = [];
                }
                $replacement = eme_form_select( $bookerCountry_code, $fieldname, $fieldid, $country_arr, '', $required, "eme_select2_country_class $dynamic_field_class_basic", $disabled );
            }
        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'email';
                if ( is_user_logged_in() && ! $eme_is_admin_request ) {
                    // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                    $this_readonly = "readonly='readonly'";
                } elseif ( ! empty( $invite_readonly ) && ! empty( $bookerEmail ) ) {
                    $this_readonly = $invite_readonly;
                } else {
                    $this_readonly = $readonly;
                }
                // there still exist people without email, so in the backend we allow it ...
                if ( isset( $matches[2] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[2], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
                }
                if ( $eme_is_admin_request ) {
                    $replacement = "<input type='email' name='$fieldname' value='$bookerEmail' $this_readonly $dynamic_field_class placeholder='$placeholder_text'>";
                } else {
                    $replacement = "<input required='required' type='email' name='$fieldname' value='$bookerEmail' $this_readonly $dynamic_field_class placeholder='$placeholder_text'>";
                }
                ++$email_found;
                // #_EMAIL is always required
                $required = 1;
            }
        } elseif ( preg_match( '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'phone';
                if ( isset( $matches[2] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[2], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Phone number', 'events-made-easy' );
                }
                $replacement = "<input $required_att type='tel' name='$fieldname' value='$bookerPhone' $readonly $dynamic_field_class placeholder='$placeholder_text'>";
            }
        } elseif ( preg_match( '/#_BIRTHDAY_EMAIL$/', $result ) ) {
            $replacement = eme_ui_select_binary( $bd_email, 'bd_email' );
        } elseif ( preg_match( '/#_OPT_OUT$/', $result ) ) {
            if ( ! $is_multibooking ) {
                $selected_massmail = ( isset( $massmail ) ) ? $massmail : 1;
                $fieldname         = 'massmail';
                $replacement       = eme_ui_select_binary( $selected_massmail, $fieldname, 0, $dynamic_field_class_basic, $disabled );
                if ( ! $eme_is_admin_request && get_option( 'eme_massmail_popup' ) ) {
                    $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                    $confirm = esc_html__('Yes','events-made-easy');
                    $cancel  = esc_html__('No','events-made-easy');
                    if (!eme_is_empty_string($popup))
                        $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
                }
            }
        } elseif ( preg_match( '/#_OPT_IN$/', $result ) ) {
            if ( ! $is_multibooking ) {
                $selected_massmail = ( isset( $massmail ) ) ? $massmail : 0;
                $fieldname         = 'massmail';
                $replacement       = eme_ui_select_binary( $selected_massmail, $fieldname, 0, $dynamic_field_class_basic, $disabled );
                if ( ! $eme_is_admin_request && get_option( 'eme_massmail_popup' ) ) {
                    $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                    $confirm = esc_html__('Yes','events-made-easy');
                    $cancel  = esc_html__('No','events-made-easy');
                    if (!eme_is_empty_string($popup))
                        $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
                }
            }
        } elseif ( preg_match( '/#_GDPR(\{.+?\})?/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $label = substr( $matches[1], 1, -1 );
                } else {
                    $label = '';
                }
                $fieldname = 'gdpr';
                if ( ! $eme_is_admin_request ) {
                    $replacement = eme_ui_checkbox_binary( $gdpr, $fieldname, $label, 1, 'eme-gdpr-field nodynamicupdates', $disabled );
                }
            }
        } elseif ( preg_match( '/#_REMEMBERME(\{.+?\})?/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $label = substr( $matches[1], 1, -1 );
                } else {
                    $label = __('Remember me?','events-made-easy');
                }
                if ( ! $eme_is_admin_request && ! is_user_logged_in()) {
                    $replacement = eme_ui_checkbox_binary( 0, 'eme_rememberme', $label, 0, 'eme-rememberme-field nodynamicupdates' );
                }
            }
        } elseif ( preg_match( '/#_SUBSCRIBE_TO_GROUP\{(.+?)\}(\{.+?\})?/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                if ( is_numeric( $matches[1] ) ) {
                    $group = eme_get_group( $matches[1] );
                } else {
                    $group = eme_get_group_by_name( eme_sanitize_request( $matches[1] ) );
                }
                if ( ! empty( $group ) ) {
                    if ( ! $group['public'] ) {
                        $replacement = __( 'Group is not public', 'events-made-easy' );
                    } else {
                        $group_id = $group['group_id'];
                        if ( isset( $matches[2] ) ) {
                            // remove { and } (first and last char of second match)
                            $label = substr( $matches[2], 1, -1 );
                        } else {
                            $label = $group['name'];
                        }
                        $replacement = "<input id='subscribe_groups_$group_id' name='subscribe_groups[]' value='$group_id' type='checkbox' class='nodynamicupdates'>";
                        if ( ! empty( $label ) ) {
                            $replacement .= "<label for='subscribe_groups_$group_id'>" . eme_esc_html( $label ) . '</label>';
                        }
                    }
                } else {
                    $replacement = __( 'Group does not exist', 'events-made-easy' );
                }
            }
        } elseif ( preg_match( '/#_PASSWORD(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! empty( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request && ! $is_multibooking ) {
                $fieldname = 'rsvp_password';
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
                }
                $placeholder_text = esc_html__( 'Password', 'events-made-easy' );
                $replacement      = "<input required='required' type='text' class='eme_passwordfield' autocomplete='off' name='$fieldname' value='' $dynamic_field_class placeholder='$placeholder_text'>";
                ++$password_found;
                $required = 1;
            }
        } elseif ( preg_match( '/#_DYNAMICPRICE$/', $result ) ) {
            if ( ! $is_multibooking ) {
                $replacement = "<span id='eme_calc_bookingprice'></span>";
            }
        } elseif ( preg_match( '/#_DYNAMICPRICE_PER_PG|#_DYNAMICPRICE_DETAILED$/', $result ) ) {
            if ( ! $is_multibooking ) {
                $replacement = "<span id='eme_calc_bookingprice_detail'></span>";
            }
        } elseif ( preg_match( '/#_DYNAMICDATA$/', $result ) ) {
            if ( !$dynamic_data_rendered && ! empty( $event['event_properties']['rsvp_dyndata'] ) ) {
                $replacement = "<div id='eme_dyndata'></div>";
                $dynamic_data_rendered = 1;
            }
        } elseif ( preg_match( '/#_SEATS$|#_SPACES$/', $result ) ) {
            $var_prefix  = "bookings[$event_id][";
            $var_postfix = ']';
            $fieldname   = "{$var_prefix}bookedSeats{$var_postfix}";
            if ( $editing_booking_from_backend && isset( $bookedSeats ) ) {
                $entered_val = $bookedSeats;
            } else {
                $entered_val = 0;
            }

            if ( $event['event_properties']['take_attendance'] ) {
                // if we require 1 seat at the minimum, we set it to that and hide it for take_attendance
                if ( ! $min_allowed_is_multi && $min_allowed > 0 ) {
                    $replacement = "<input type='hidden' name='$fieldname' value='1'>";
                } else {
                    if ($new_booking_in_frontend) {
                        $entered_val = 1; // by default people attend :-)
                    }
                    $replacement = eme_ui_checkbox_binary( $entered_val, $fieldname, '', 0, '', "class='eme-attendance-field $dynamic_price_class_basic $dynamic_field_class_basic'" );
                }
                ++$seats_found;
            } else {
                if ( ! $min_allowed_is_multi && $min_allowed > 0 && $min_allowed == $max_allowed ) {
                    $replacement = "<input type='hidden' name='$fieldname' value='$min_allowed'>";
                } else {
                    $replacement = eme_ui_select( $entered_val, $fieldname, $booked_seats_options, '', 0, $dynamic_price_class_basic . ' ' . $dynamic_field_class_basic );
                }
                if ( $waitinglist && !$editing_booking_from_backend ) {
                    $replacement .= "<span id='eme_waitinglist'><br>" . eme_translate( get_option( 'eme_rsvp_on_waiting_list_string' ) ) . '</span>';
                }
                ++$seats_found;
            }
        } elseif ( preg_match( '/#_(SEATS|SPACES)\{(\d+)\}/', $result, $matches ) ) {
            $field_id    = intval( $matches[2] );
            $var_prefix  = "bookings[$event_id][";
            $var_postfix = ']';
            $fieldname   = "{$var_prefix}bookedSeats" . $field_id . $var_postfix;

            // for multiseats, the index starts at 1 (#_SEATS1, #_SEATS2, etc ...) but in booking_seats_mp the index starts at 0, so we do -1
            if ( $editing_booking_from_backend && $field_id > 0 && isset( $booking_seats_mp[ $field_id - 1 ] ) ) {
                $entered_val = intval( $booking_seats_mp[ $field_id - 1 ] );
            } else {
                $entered_val = 0;
            }

            if ( ! eme_is_multi( $event['price'] ) ) {
                // this will show if people mix #_SEATS and #_SEATS{xx}
                $error_msg = __( 'By using #_SEATS{xx}, you are using multiple seat categories in your RSVP template, but you have not defined a price for each category in your event RSVP settings. Please correct the event RSVP settings.', 'events-made-easy' );
            } elseif ( $event['event_properties']['take_attendance'] ) {
                // if we require 1 seat at the minimum, we set it to that and hide it for take_attendance
                if ( $min_allowed_is_multi && $multi_min_allowed[ $field_id - 1 ] > 0 ) {
                    $replacement = "<input type='hidden' name='$fieldname' value='1'>";
                } else {
                    $replacement = eme_ui_select_binary( $entered_val, $fieldname, 0, $dynamic_price_class_basic . ' ' . $dynamic_field_class_basic );
                }
                ++$seats_found;
            } else {
                if ( $min_allowed_is_multi && $multi_min_allowed[ $field_id - 1 ] > 0 && $multi_min_allowed[ $field_id - 1 ] == $multi_max_allowed[ $field_id - 1 ] ) {
                    $replacement = "<input type='hidden' name='$fieldname' value='" . $multi_min_allowed[ $field_id - 1 ] . "'>";
                } else {
                    $replacement = eme_ui_select( $entered_val, $fieldname, $booked_seats_options[ $field_id - 1 ], '', 0, $dynamic_price_class_basic . ' ' . $dynamic_field_class_basic );
                }
                ++$seats_found;
            }
        } elseif ( preg_match( '/#_COMMENT(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                $fieldname = 'eme_rsvpcomment';
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Comment', 'events-made-easy' );
                }
                $replacement = "<textarea $required_att name='$fieldname' $dynamic_field_class placeholder='$placeholder_text' >$bookerComment</textarea>";
            }
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) { 
            if ( !empty($selected_captcha) && ! $captcha_set ) { 
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }       
        } elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'rsvp', 'people' ] ) ) {
                if ( ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) && $is_multibooking ) {
                    // for file uploads we expect just FIELDxx as name (also for members, see function eme_upload_files), so not allowed for multibooking
                    $replacement = '';
                } else {
                    $field_id       = $formfield['field_id'];
                    $fieldname      = "{$var_prefix}FIELD" . $field_id . $var_postfix;
                    $entered_val    = '';
                    $field_readonly = 0;
                    if ( $editing_booking_from_backend ) {
                        if ( $formfield['field_purpose'] == 'people' ) {
                            $answers        = eme_get_person_answers( $booking['person_id'] );
                            $field_readonly = 1;
                        } else {
                            $answers        = eme_get_nodyndata_booking_answers( $booking['booking_id'] );
                            $field_readonly = 0;
                        }
                        foreach ( $answers as $answer ) {
                            if ( $answer['field_id'] == $field_id ) {
                                $entered_val = $answer['answer'];
                                break;
                            }
                        }
                        $files1 = eme_get_uploaded_files( $booking['person_id'], 'people' );
                        $files2 = eme_get_uploaded_files( $booking['booking_id'], 'bookings' );
                        $files  = array_merge( $files1, $files2 );
                        if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                            $entered_files = [];
                            foreach ( $files as $file ) {
                                if ( $file['field_id'] == $field_id ) {
                                    $entered_files[] = $file;
                                }
                            }
                            $entered_val = $entered_files;
                        }
                    } elseif ( $formfield['field_purpose'] == 'people' && is_user_logged_in() && ! empty( $person['person_id'] ) ) {
                            $answers = eme_get_person_answers( $person['person_id'] );
                            $files   = eme_get_uploaded_files( $booking['person_id'], 'people' );
                        foreach ( $answers as $answer ) {
                            if ( $answer['field_id'] == $field_id ) {
                                $entered_val = $answer['answer'];
                                break;
                            }
                        }
                        if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                            $entered_files = [];
                            foreach ( $files as $file ) {
                                if ( $file['field_id'] == $field_id ) {
                                    $entered_files[] = $file;
                                }
                            }
                            $entered_val = $entered_files;
                        }
                    }
                    if ( $formfield['field_required'] ) {
                        $required = 1;
                    }
                    if ( $formfield['extra_charge'] ) {
                        $replacement = eme_get_formfield_html( $formfield, $fieldname, $entered_val, $required, $dynamic_price_class_basic . ' ' . $dynamic_field_class_basic, $field_readonly );
                    } else {
                        $replacement = eme_get_formfield_html( $formfield, $fieldname, $entered_val, $required, $dynamic_field_class_basic, $field_readonly );
                    }
                }
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_DISCOUNT(\{.+?\})?$/', $result, $matches ) ) {
            if ( $event['event_properties']['rsvp_discount'] || $event['event_properties']['rsvp_discountgroup'] ) {
                // we need an ID to have a unique name per DISCOUNT input field
                ++$discount_fields_count;
                if ( ! $eme_is_admin_request ) {
                    $var_prefix     = "bookings[$event_id][";
                    $var_postfix    = ']';
                    $postfield_name = "{$var_prefix}DISCOUNT{$discount_fields_count}{$var_postfix}";
                    $entered_val    = '';
                    if ( isset( $matches[1] ) ) {
                        // remove { and } (first and last char of second match)
                        $placeholder_text = substr( $matches[1], 1, -1 );
                        $placeholder_text = eme_trans_esc_html( $placeholder_text );
                    } else {
                        $placeholder_text = esc_html__( 'Discount code', 'events-made-easy' );
                    }
                    $replacement = "<input $dynamic_price_class type='text' name='$postfield_name' value='$entered_val' $required_att placeholder='$placeholder_text'>";
                } elseif ( $eme_is_admin_request && $discount_fields_count == 1 ) {
                    $postfield_name = 'DISCOUNT';
                    if ( $booking['discount'] ) {
                        $replacement = "<input $dynamic_price_class type='text' name='$postfield_name' value='" . $booking['discount'] . "'><br>" . sprintf( __( 'Enter a new fixed discount value if wanted, or leave as is to keep the calculated value %s based on the following applied discounts:', 'events-made-easy' ), eme_localized_price( $booking['discount'], $event['currency'] ) );
                    } else {
                        $replacement = "<input $dynamic_price_class type='text' name='$postfield_name' value=''><br>" . __( 'Enter a fixed discount value if wanted', 'events-made-easy' );
                    }
                    if ( $booking['dgroupid'] ) {
                        $dgroup = eme_get_discountgroup( $booking['dgroupid'] );
                        if ( $dgroup && isset( $dgroup['name'] ) ) {
                            $replacement .= '<br>' . sprintf( __( 'Discountgroup %s', 'events-made-easy' ), eme_esc_html( $dgroup['name'] ) );
                        } else {
                            $replacement .= '<br>' . sprintf( __( 'Applied discount group %d no longer exists', 'events-made-easy' ), $booking['dgroupid'] );
                        }
                    }
                    if ( ! empty( $booking['discountids'] ) ) {
                        $discount_ids = explode( ',', $booking['discountids'] );
                        foreach ( $discount_ids as $discount_id ) {
                            $discount = eme_get_discount( $discount_id );
                            if ( $discount && isset( $discount['name'] ) ) {
                                $replacement .= '<br>' . eme_esc_html( $discount['name'] );
                            } else {
                                $replacement .= '<br>' . sprintf( __( 'Applied discount %d no longer exists', 'events-made-easy' ), $discount_id );
                            }
                        }
                    }
                    $replacement .= '<br>' . __( 'Only one discount field can be used in the admin backend, the others are not rendered', 'events-made-easy' ) . '<br>';
                }
            }
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( ! $is_multibooking ) {
                if ( $editing_booking_from_backend ) {
                    $label = __( 'Update booking', 'events-made-easy' );
                } elseif ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $label = substr( $matches[1], 1, -1 );
                } else {
                    $label = get_option( 'eme_rsvp_addbooking_submit_string' );
                }
                $replacement .= "<img id='rsvp_add_loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
            }
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now any leftover event placeholders
    $format = eme_replace_event_placeholders( $format, $event );

    if ( eme_is_multi( $event['price'] ) ) {
        $matches     = eme_convert_multi2array( $event['price'] );
        $seats_count = count( $matches );
    } else {
        $seats_count = 1;
    }

    // now check we found all the required placeholders for the form to work
    if ( ! empty( $error_msg ) ) {
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$error_msg</div>";
    } elseif ( $lastname_found && $email_found && $password_found && $seats_found >= $seats_count ) {
        return $format;
    } else {
        $res = '';
        if ( ! $lastname_found || ! $email_found || $seats_found < $seats_count ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_LASTNAME, #_EMAIL, #_SEATS and #_SUBMIT (or similar) placeholders.', 'events-made-easy' ) . '<br>';
        }
        if ( eme_is_multi( $event['price'] ) ) {
            $res .= __( "Since this is a multiprice event, make sure you changed the setting 'Booking Form' for the event to include #_SEAT{xx} placeholders for each price.", 'events-made-easy' ) . '<br>';
        }
        if ( ! empty( $event['event_properties']['rsvp_password'] ) && ! $eme_is_admin_request && ! $password_found ) {
                $res .= __( 'Check that the placeholder #_PASSWORD is present in the form.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$res</div>";
    }
}

function eme_replace_membership_familyformfields_placeholders( $format, $counter ) {
    $lastname_found  = 0;
    $firstname_found = 0;

    // all these are dynamic fields
    $dynamic_field_class       = "class='nodynamicupdates dynamicfield'";
    $dynamic_field_class_basic = 'nodynamicupdates dynamicfield';

    $needle_offset = 0;
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $replacement        = '';
        $required           = 0;
        $required_att       = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            $postvar_arr = [ 'familymember', $counter, 'lastname' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $entered_val = '';
            }
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='familymember[$counter][lastname]' id='familymember[$counter][lastname]' value='$entered_val' $dynamic_field_class placeholder='$placeholder_text'>";
            ++$lastname_found;
            $required = 1;
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            $postvar_arr = [ 'familymember', $counter, 'firstname' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $entered_val = '';
            }
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='familymember[$counter][firstname]' id='familymember[$counter][firstname]' value='$entered_val' $dynamic_field_class placeholder='$placeholder_text'>";
            ++$firstname_found;
            $required = 1;
        } elseif ( preg_match( '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/', $result, $matches ) ) {
            $postvar_arr = [ 'familymember', $counter, 'phone' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $entered_val = '';
            }
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Phone number', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='tel' name='familymember[$counter][phone]' id='familymember[$counter][phone]' value='$entered_val' $dynamic_field_class placeholder='$placeholder_text'>";
            $required    = 1;
        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            $postvar_arr = [ 'familymember', $counter, 'email' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $entered_val = '';
            }
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='email' name='familymember[$counter][email]' id='familymember[$counter][email]' value='$entered_val' $dynamic_field_class placeholder='$placeholder_text'>";
            $required    = 1;
        } elseif ( preg_match( '/#_BIRTHDAY_EMAIL$/', $result ) ) {
            $postvar_arr = [ 'familymember', $counter, 'bd_email' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $selected_bd_email = 1;
            } else {
                $selected_bd_email = $entered_val;
            }
            $replacement = eme_ui_select_binary( $selected_bd_email, "familymember[$counter][bd_email]", 0, $dynamic_field_class_basic );
        } elseif ( preg_match( '/#_OPT_OUT/', $result ) ) {
            $postvar_arr = [ 'familymember', $counter, 'massmail' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $selected_massmail = 1;
            } else {
                $selected_massmail = $entered_val;
            }
            $replacement = eme_ui_select_binary( $selected_massmail, "familymember[$counter][massmail]", 0, $dynamic_field_class_basic );
        } elseif ( preg_match( '/#_OPT_IN$/', $result ) ) {
            if ( $entered_val === false ) {
                $selected_massmail = 0;
            } else {
                $selected_massmail = $entered_val;
            }
            $replacement = eme_ui_select_binary( $selected_massmail, "familymember[$counter][massmail]", 0, $dynamic_field_class_basic );
            $required     = 1;
        } elseif ( preg_match( '/#_BIRTHPLACE(\{.+?\})?/', $result, $matches ) ) {
            $postvar_arr = [ 'familymember', $counter, 'birthplace' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $entered_val = '';
            }
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Place of birth', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='familymember[$counter][birthplace]' id='familymember[$counter][birthplace]' value='$entered_val' $dynamic_field_class placeholder='$placeholder_text'>";
            $required    = 1;
        } elseif ( preg_match( '/#_BIRTHDATE(\{.+?\})?/', $result, $matches ) ) {
            $fieldname   = "familymember[$counter][birthdate]";
            $postvar_arr = [ 'familymember', $counter, 'birthdate' ];
            $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
            if ( $entered_val === false ) {
                $entered_val = '';
            }
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Date of birth', 'events-made-easy' );
            }
            $replacement  = "<input type='hidden' name='$fieldname' id='$fieldname' value='$entered_val'>";
            $replacement .= "<input required='required' readonly='readonly' type='text' name='dp_{$fieldname}' id='dp_{$fieldname}' data-date='$entered_val' data-format='".EME_WP_DATE_FORMAT."' data-view='years' data-alt-field='$fieldname' class='eme_formfield_fdate $dynamic_field_class_basic' placeholder='$placeholder_text'>";
            $required     = 1;
        } elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'members', 'people' ] ) ) {
                $field_id = $formfield['field_id'];
                // all the people fields are dynamic fields in the backend, and the function eme_store_person_answers searches for that, so we need that name again
                $var_prefix     = "familymember[$counter][";
                $var_postfix    = ']';
                $postfield_name = "{$var_prefix}FIELD" . $field_id . $var_postfix;
                $postvar_arr    = [ 'familymember', $counter, 'FIELD' . $field_id ];
                $entered_val    = eme_getValueFromPath( $_POST, $postvar_arr );
                if ( $formfield['field_required'] ) {
                    $required = 1;
                }
                $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required, $dynamic_field_class );
            } else {
                $found = 0;
            }
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                    $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }
        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now check we found all the required placeholders for the form to work
    if ( $lastname_found && $firstname_found ) {
        return $format;
    } else {
        $res = '';
        if ( ! $lastname_found || ! $firstname_found ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_LASTNAME and #_FIRSTNAME placeholders.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-family-message-error'>$res</div>";
    }
}

function eme_replace_membership_formfields_placeholders( $form_id, $membership, $member, $format ) {
    $eme_is_admin_request = eme_is_admin_request();
    $membership_id        = $membership['membership_id'];

    // check if logged in if required
    $registration_wp_users_only = $membership['properties']['registration_wp_users_only'];
    if ( !$eme_is_admin_request && $registration_wp_users_only && ! is_user_logged_in() ) {
        return '';
    }

    // format is required
    if ( eme_is_empty_string( $format ) ) {
        return;
    }

    $allow_clear    = 0;
    $editing_member = 0;
    $current_userid = get_current_user_id();
    if ( !empty($current_userid) ) {
        if (current_user_can( get_option( 'eme_cap_edit_members' ) ) && ! empty( $member['member_id'] ) && !empty( $member['person_id'] )) {
            $editing_member = 1;
        }
        if ( !$editing_member && (! $registration_wp_users_only || current_user_can( get_option( 'eme_cap_edit_members' ))) ) {
            $allow_clear = 1;
        }
    }

    $bookerLastName     = '';
    $bookerFirstName    = '';
    $bookerBirthdate    = '';
    $bookerBirthplace   = '';
    $bookerAddress1     = '';
    $bookerAddress2     = '';
    $bookerCity         = '';
    $bookerZip          = '';
    $bookerState_code   = '';
    $bookerCountry_code = '';
    // if only 1 country, set it as default
    $countries_alpha2 = eme_get_countries_alpha2();
    if ( count( $countries_alpha2 ) == 1 ) {
        $bookerCountry_code = $countries_alpha2[0];
    }
    $bookerEmail = '';
    $bookerPhone = '';
    $massmail    = null;
    $bd_email    = 0;
    $gdpr        = 0;

    // don't fill out the basic info if in the backend, but do it only if in the frontend
    $readonly = '';
    $disabled = '';
    $person = [];
    if ($editing_member) {
        $person = eme_get_person( $member['person_id'] );
    } elseif (! empty( $current_userid ) && !$eme_is_admin_request) {
        // this will also fill person with wp info if logged in and person doesn't exist in EME
        $person = eme_get_person_by_wp_id( $current_userid );
        if ( empty( $person ) ) {
            $person = eme_fake_person_by_wp_id( $current_userid );
        }
    }
    if ( ! empty( $person ) ) {
        $bookerLastName     = eme_esc_html( $person['lastname'] );
        $bookerFirstName    = eme_esc_html( $person['firstname'] );
        $bookerBirthdate    = eme_is_date( $person['birthdate'] ) ? eme_esc_html( $person['birthdate'] ) : '';
        $bookerBirthplace   = eme_esc_html( $person['birthplace'] );
        $bookerAddress1     = eme_esc_html( $person['address1'] );
        $bookerAddress2     = eme_esc_html( $person['address2'] );
        $bookerCity         = eme_esc_html( $person['city'] );
        $bookerZip          = eme_esc_html( $person['zip'] );
        $bookerState_code   = eme_esc_html( $person['state_code'] );
        $bookerCountry_code = eme_esc_html( $person['country_code'] );
        $bookerEmail        = eme_esc_html( $person['email'] );
        $bookerPhone        = eme_esc_html( $person['phone'] );
        $massmail           = intval( $person['massmail'] );
        $bd_email           = intval( $person['bd_email'] );
        $gdpr               = intval( $person['gdpr'] );
    }

    if ( $editing_member ) {
        // when editing an existing member (not a new)
        // we disable the editing of person info completely
        // we also set the width to 100% because otherwise the size of the placeholder is used to render the width of readonly fields ...
        $readonly = "readonly='readonly' style='width: 100%;'";
        $disabled = "disabled='disabled'";
    }

    // check which fields are used in the event definition for dynamic data
    $eme_dyndatafields = [];
    if ( isset( $membership['properties']['dyndata'] ) ) {
        foreach ( $membership['properties']['dyndata'] as $dynfield ) {
            $eme_dyndatafields[] = $dynfield['field'];
        }
    }
    if ( ! empty( $eme_dyndatafields ) ) {
        $add_dyndata = 1;
    } else {
        $add_dyndata = 0;
    }

    if ( $membership['properties']['family_membership'] && ! preg_match( '/#_FAMILYCOUNT/', $format ) ) {
        $text = '#_FAMILYCOUNT';
        if ( preg_match( '/#_SUBMIT/', $format ) ) {
            $format = preg_replace( '/#_SUBMIT/', "$text<br>#_SUBMIT", $format );
        } else {
            $format .= $text;
        }
    }
    if ( $membership['properties']['family_membership'] && ! preg_match( '/#_FAMILYMEMBERS/', $format ) ) {
        $text = '#_FAMILYMEMBERS';
        if ( preg_match( '/#_SUBMIT/', $format ) ) {
            $format = preg_replace( '/#_SUBMIT/', "$text<br>#_SUBMIT", $format );
        } else {
            $format .= $text;
        }
    }

    $selected_captcha = '';
    $captcha_set = 0;
    if ( is_user_logged_in() && $membership['properties']['captcha_only_logged_out'] ) {
        $format = eme_add_captcha_submit( $format, '', $add_dyndata );
    } else {
        if (!$eme_is_admin_request)
            $selected_captcha = $membership['properties']['selected_captcha'];
        $format = eme_add_captcha_submit( $format, $selected_captcha, $add_dyndata  );
    }

    // the placeholders that can contain extra text are treated separately first
    // the question mark is used for non greedy (minimal) matching
    // the s modifier makes . match newlines as well as all other characters (by default it excludes them)
    if ( preg_match( '/#_CAPTCHAHTML\{.*\}/s', $format ) ) {
        // only show the captcha when booking via the frontend, not the admin backend
        if ( !empty($selected_captcha) ) {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '$1', $format );
        } else {
            $format = preg_replace( '/#_CAPTCHAHTML\{(.*?)\}/s', '', $format );
        }
    }

    // We need at least #_LASTNAME, #_FIRSTNAME, #_EMAIL and #_SUBMIT
    $lastname_found  = 0;
    $firstname_found = 0;
    $email_found     = 0;
    // #_FAMILYCOUNT and #_FAMILYMEMBERS can only be present once
    $familycount_found   = 0;
    $familymembers_found = 0;

    # we need 3 required fields: #_NAME, #_EMAIL and #_SEATS
    # if these are not present: we don't replace anything and the form is worthless

    # first we check if people desire dynamic pricing on it's own, if not: we set the relevant price class to empty
    if ( str_contains( $format, '#_DYNAMICPRICE' ) ) {
        $dynamic_price_class       = "class='dynamicprice'";
        $dynamic_price_class_basic = 'dynamicprice';
    } else {
        $dynamic_price_class       = '';
        $dynamic_price_class_basic = '';
    }

    # check also if dynamic data is requested
    if ( ( strstr( $format, '#_DYNAMICDATA' ) && ! empty( $eme_dyndatafields ) ) || $membership['properties']['dyndata_all_fields'] ) {
        $dynamic_data_wanted = 1;
    } else {
        $dynamic_data_wanted = 0;
    }
    # the next is to make sure we render #_DYNAMICDATA only once
    $dynamic_data_rendered = 0;

    $personal_info_class   = 'personal_info';
    $discount_fields_count = 0;

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $required           = 0;
        $required_att       = '';
        $replacement        = '';
        if ( strstr( $result, '#REQ' ) ) {
            $result = str_replace( '#REQ', '#', $result );
            if ( ! $eme_is_admin_request ) {
                $required     = 1;
                $required_att = "required='required'";
            }
        }

        // check for dynamic field class
        if ( $dynamic_data_wanted && ( in_array( $result, $eme_dyndatafields ) || $membership['properties']['dyndata_all_fields'] ) ) {
            $dynamic_field_personal_info_class = "class='dynamicupdates $personal_info_class'";
            $dynamic_field_class_basic         = "dynamicupdates $personal_info_class";
        } else {
            $dynamic_field_personal_info_class = "class='nodynamicupdates $personal_info_class'";
            $dynamic_field_class_basic         = "nodynamicupdates $personal_info_class";
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'lastname';
            if ( is_user_logged_in() && ! $eme_is_admin_request ) {
                // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                $this_readonly = "readonly='readonly'";
                if ( $allow_clear ) {
                    $this_readonly .= " data-clearable='true'";
                }
            } else {
                $this_readonly = $readonly;
            }

            if ( ! $eme_is_admin_request ) {
                $required_att = "required='required'";
                $required     = 1;
            }
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerLastName' $this_readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
            if ( wp_script_is( 'eme-autocomplete-form', 'enqueued' ) && get_option( 'eme_autocomplete_sources' ) != 'none' ) {
                $replacement .= "&nbsp;<img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . esc_html__( "Notice: since you're logged in as a person with the right to manage members and memberships, the 'Last name' field is also an autocomplete field so you can select existing people if desired. Or just clear the field and start typing.", 'events-made-easy' ) . "'>";
            }
            ++$lastname_found;
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'firstname';
            if ( is_user_logged_in() && ! $eme_is_admin_request ) {
                // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                $this_readonly = "readonly='readonly'";
            } else {
                $this_readonly = $readonly;
            }
            if ( ! $eme_is_admin_request ) {
                $required_att = "required='required'";
                $required     = 1;
            }
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerFirstName' $this_readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
            ++$firstname_found;
        } elseif ( preg_match( '/#_BIRTHDATE(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'birthdate';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Date of birth', 'events-made-easy' );
            }
            $replacement  = "<input type='hidden' name='$fieldname' id='$fieldname' value='$bookerBirthdate'>";
            $replacement .= "<input $required_att readonly='readonly' $disabled type='text' name='dp_{$fieldname}' id='dp_{$fieldname}' data-date='$bookerBirthdate' data-format='".EME_WP_DATE_FORMAT."' data-view='years' data-alt-field='birthdate' class='eme_formfield_fdate $personal_info_class' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_BIRTHPLACE(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'birthplace';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Place of birth', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerBirthplace' $readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_ADDRESS1(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'address1';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address1_string' ) );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerAddress1' $readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_ADDRESS2(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'address2';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address2_string' ) );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerAddress2' $readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_CITY(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'city';
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'City', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerCity' $readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_(ZIP|POSTAL)(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'zip';
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Postal code', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='$fieldname' id='$fieldname' value='$bookerZip' $readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_STATE$/', $result ) ) {
            $fieldname = 'state_code';
            if (!empty($form_id)) {
                $fieldid = $form_id.'-'.$fieldname;
            } else {
                $fieldid = $fieldname;
            }
            if ( ! empty( $bookerState_code ) ) {
                $state_arr = [ $bookerState_code => eme_get_state_name( $bookerState_code, $bookerCountry_code ) ];
            } else {
                $state_arr = [];
            }
            $replacement = "<div class=$personal_info_class>" . eme_form_select( $bookerState_code, $fieldname, $fieldid, $state_arr, '', $required, "eme_select2_state_class $dynamic_field_class_basic", $disabled ) . '</div>';
        } elseif ( preg_match( '/#_COUNTRY$/', $result ) ) {
            $fieldname = 'country_code';
            if (!empty($form_id)) {
                $fieldid = $form_id.'-'.$fieldname;
            } else {
                $fieldid = $fieldname;
            }
            if ( ! empty( $bookerCountry_code ) ) {
                $country_arr = [ $bookerCountry_code => eme_get_country_name( $bookerCountry_code ) ];
            } else {
                $country_arr = [];
            }
            $replacement = "<div class=$personal_info_class>" . eme_form_select( $bookerCountry_code, $fieldname, $fieldid, $country_arr, '', $required, "eme_select2_country_class $dynamic_field_class_basic", $disabled ) . '</div>';
        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'email';
            if ( is_user_logged_in() && ! $eme_is_admin_request ) {
                // in the frontend and logged in, so this info comes from the wp profile, so make it readonly
                $this_readonly = "readonly='readonly'";
            } else {
                $this_readonly = $readonly;
            }

            // there still exist people without email, so in the backend we allow it ...
            if ( ! $eme_is_admin_request ) {
                $required_att = "required='required'";
                $required     = 1;
            }
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='email' id='$fieldname' name='$fieldname' value='$bookerEmail' $this_readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
            ++$email_found;
        } elseif ( preg_match( '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/', $result, $matches ) ) {
            $fieldname = 'phone';
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Phone number', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='tel' id='$fieldname' name='$fieldname' value='$bookerPhone' $readonly $dynamic_field_personal_info_class placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_BIRTHDAY_EMAIL$/', $result ) ) {
            $replacement = eme_ui_select_binary( $bd_email, 'bd_email', 0, "$dynamic_field_personal_info_class", $disabled );
        } elseif ( preg_match( '/#_OPT_OUT$/', $result ) ) {
            $selected_massmail = ( isset( $massmail ) ) ? $massmail : 1;
            $fieldname         = 'massmail';
            $replacement       = eme_ui_select_binary( $selected_massmail, $fieldname, 0, $dynamic_field_class_basic, $disabled );
            if ( ! $eme_is_admin_request && get_option( 'eme_massmail_popup' ) ) {
                $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                $confirm = esc_html__('Yes','events-made-easy');
                $cancel  = esc_html__('No','events-made-easy');
                if (!eme_is_empty_string($popup))
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
            }
        } elseif ( preg_match( '/#_OPT_IN$/', $result ) ) {
            $selected_massmail = ( isset( $massmail ) ) ? $massmail : 0;
            $fieldname         = 'massmail';
            $replacement       = eme_ui_select_binary( $selected_massmail, $fieldname, 0, $dynamic_field_class_basic, $disabled );
            if ( ! $eme_is_admin_request && get_option( 'eme_massmail_popup' ) ) {
                $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                $confirm = esc_html__('Yes','events-made-easy');
                $cancel  = esc_html__('No','events-made-easy');
                if (!eme_is_empty_string($popup))
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
            }
        } elseif ( preg_match( '/#_GDPR(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = '';
            }
            $fieldname = 'gdpr';
            if ( ! $eme_is_admin_request ) {
                $replacement = eme_ui_checkbox_binary( $gdpr, $fieldname, $label, 1, "eme-gdpr-field nodynamicupdates $personal_info_class", $disabled );
            }
        } elseif ( preg_match( '/#_REMEMBERME(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __('Remember me?','events-made-easy');
            }
            if ( ! $eme_is_admin_request && ! is_user_logged_in()) {
                $replacement = eme_ui_checkbox_binary( 0, 'eme_rememberme', $label, 0, 'eme-rememberme-field nodynamicupdates' );
            }
        } elseif ( preg_match( '/#_SUBSCRIBE_TO_GROUP\{(.+?)\}(\{.+?\})?/', $result, $matches ) ) {
            if ( is_numeric( $matches[1] ) ) {
                $group = eme_get_group( $matches[1] );
            } else {
                $group = eme_get_group_by_name( eme_sanitize_request( $matches[1] ) );
            }
            if ( ! empty( $group ) ) {
                if ( ! $group['public'] ) {
                    $replacement = __( 'Group is not public', 'events-made-easy' );
                } else {
                    $group_id = $group['group_id'];
                    if ( isset( $matches[2] ) ) {
                        // remove { and } (first and last char of second match)
                        $label = substr( $matches[2], 1, -1 );
                    } else {
                        $label = $group['name'];
                    }
                    $replacement = "<input id='subscribe_groups_$group_id' name='subscribe_groups[]' value='$group_id' type='checkbox' class='nodynamicupdates'>";
                    if ( ! empty( $label ) ) {
                        $replacement .= "<label for='subscribe_groups_$group_id'>" . eme_esc_html( $label ) . '</label>';
                    }
                }
            } else {
                $replacement = __( 'Group does not exist', 'events-made-easy' );
            }
        } elseif ( preg_match( '/#_DISCOUNT(\{.+?\})?$/', $result, $matches ) ) {
            if ( $membership['properties']['discount'] || $membership['properties']['discountgroup'] ) {
                // we need an ID to have a unique name per DISCOUNT input field
                ++$discount_fields_count;
                if ( ! $eme_is_admin_request ) {
                    $var_prefix     = "members[$membership_id][";
                    $var_postfix    = ']';
                    $postfield_name = "{$var_prefix}DISCOUNT{$discount_fields_count}{$var_postfix}";
                    $entered_val    = '';
                    if ( isset( $matches[1] ) ) {
                        // remove { and } (first and last char of second match)
                        $placeholder_text = substr( $matches[1], 1, -1 );
                        $placeholder_text = eme_trans_esc_html( $placeholder_text );
                    } else {
                        $placeholder_text = esc_html__( 'Discount code', 'events-made-easy' );
                    }
                    $replacement = "<input $dynamic_price_class type='text' name='$postfield_name' value='$entered_val' $required_att placeholder='$placeholder_text'>";
                } elseif ( $eme_is_admin_request && $discount_fields_count == 1 ) {
                    $postfield_name = 'DISCOUNT';
                    if ( $member['discount'] ) {
                        $replacement = "<input $dynamic_price_class type='text' name='$postfield_name' value='" . $member['discount'] . "'><br>" . sprintf( __( 'Enter a new fixed discount value if wanted, or leave as is to keep the calculated value %s based on the following applied discounts:', 'events-made-easy' ), eme_localized_price( $member['discount'], $membership['properties']['currency'] ) );
                    } else {
                        $replacement = "<input $dynamic_price_class type='text' name='$postfield_name' value=''><br>" . __( 'Enter a fixed discount value if wanted', 'events-made-easy' );
                    }
                    if ( $member['dgroupid'] ) {
                        $dgroup = eme_get_discountgroup( $member['dgroupid'] );
                        if ( $dgroup && isset( $dgroup['name'] ) ) {
                            $replacement .= '<br>' . sprintf( __( 'Discountgroup %s', 'events-made-easy' ), eme_esc_html( $dgroup['name'] ) );
                        } else {
                            $replacement .= '<br>' . sprintf( __( 'Applied discount group %d no longer exists', 'events-made-easy' ), $member['dgroupid'] );
                        }
                    }
                    if ( ! empty( $member['discountids'] ) ) {
                        $discount_ids = explode( ',', $member['discountids'] );
                        foreach ( $discount_ids as $discount_id ) {
                            $discount = eme_get_discount( $discount_id );
                            if ( $discount && isset( $discount['name'] ) ) {
                                $replacement .= '<br>' . eme_esc_html( $discount['name'] );
                            } else {
                                $replacement .= '<br>' . sprintf( __( 'Applied discount %d no longer exists', 'events-made-easy' ), $discount_id );
                            }
                        }
                    }
                    $replacement .= '<br>' . __( 'Only one discount field can be used in the admin backend, the others are not rendered', 'events-made-easy' ) . '<br>';
                }
            }
        } elseif ( preg_match( '/#_DYNAMICPRICE$/', $result ) ) {
            $replacement = "<span id='eme_calc_memberprice'></span>";
        } elseif ( preg_match( '/#_DYNAMICPRICE_PER_PG|#_DYNAMICPRICE_DETAILED$/', $result ) ) {
            $replacement = "<span id='eme_calc_memberprice_detail'></span>";
        } elseif ( preg_match( '/#_DYNAMICDATA$/', $result ) ) {
            if ( !$dynamic_data_rendered && ! empty( $membership['properties']['dyndata'] ) ) {
                $replacement = "<div id='eme_dyndata'></div>";
                $dynamic_data_rendered = 1;
            }
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) { 
            if ( !empty($selected_captcha) && ! $captcha_set ) { 
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }       
        } elseif ( preg_match( '/#_FAMILYCOUNT/', $result, $matches ) ) {
            $fieldname = 'familycount';
            if ( ! $eme_is_admin_request ) { 
                if ( ! $familycount_found ) {
                    $range_arr = [];
                    for ( $i = 0;$i <= $membership['properties']['family_maxmembers'];$i++ ) {
                        $range_arr[ $i ] = $i;
                    }
                    $replacement = eme_ui_select( 1, 'familycount', $range_arr );
                    #$replacement= "<input type='number' name='familycount' id='familycount' value='' min='1' max='50'>";
                    $familycount_found = 1;
                }
            } else {
                $replacement = __( "In the backend you can't add or edit family member info, use the frontend form for that.", 'events-made-easy' );
            }
        } elseif ( preg_match( '/#_FAMILYMEMBERS/', $result, $matches ) ) {
            if ( ! $eme_is_admin_request ) {
                if ( ! $familymembers_found ) {
                    $replacement = "<div id='eme_dyndata_family'></div>";
                    $familymembers_found = 1;
                }
            }
        } elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && in_array( $formfield['field_purpose'], [ 'generic', 'members', 'people' ] ) ) {
                $field_id       = $formfield['field_id'];
                $fieldname      = 'FIELD' . $field_id;
                $entered_val    = '';
                $field_readonly = 0;
                if ( $editing_member ) {
                    if ( $formfield['field_purpose'] == 'people' ) {
                        $answers        = eme_get_person_answers( $member['person_id'] );
                        $field_readonly = 1;
                    } else {
                        $answers        = eme_get_nodyndata_member_answers( $member['member_id'] );
                        $field_readonly = 0;
                    }
                    foreach ( $answers as $answer ) {
                        if ( $answer['field_id'] == $field_id ) {
                            $entered_val = $answer['answer'];
                            break;
                        }
                    }
                } elseif ( $formfield['field_purpose'] == 'people' && is_user_logged_in() && ! empty( $person['person_id'] ) ) {
                    $answers = eme_get_person_answers( $person['person_id'] );
                    foreach ( $answers as $answer ) {
                        if ( $answer['field_id'] == $field_id ) {
                            $entered_val = $answer['answer'];
                            break;
                        }
                    }
                }
                // people fields can be hidden for members in the admin backend
                if ( $formfield['field_required'] && ( ! $eme_is_admin_request || ( $eme_is_admin_request && $formfield['field_purpose'] != 'people' ) ) ) {
                    $required = 1;
                }
                $class = $dynamic_field_class_basic;
                if ( $formfield['field_purpose'] == 'people' ) {
                    $class .= " $personal_info_class";
                }
                if ( $formfield['extra_charge'] ) {
                    $class .= " $dynamic_price_class_basic";
                }
                $replacement = eme_get_formfield_html( $formfield, $fieldname, $entered_val, $required, $class, $field_readonly, 0, $editing_member );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( $editing_member ) {
                $label = __( 'Update member', 'events-made-easy' );
            } elseif ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __( 'Become member', 'events-made-easy' );
            }
            $replacement = "<img id='member_loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    $format = eme_replace_membership_placeholders( $format, $membership );

    // now check we found all the required placeholders for the form to work
    if ( $lastname_found && $firstname_found && $email_found ) {
        return $format;
    } else {
        $res = '';
        if ( ! $lastname_found || ! $firstname_found || ! $email_found ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_LASTNAME, #_FIRSTNAME, #_EMAIL and #_SUBMIT (or similar) placeholders.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$res</div>";
    }
}

function eme_replace_subscribeform_placeholders( $format, $unsubscribe = 0 ) {
    $eme_is_admin_request = eme_is_admin_request();
    $selected_captcha = '';
    $captcha_set = 0;
    if ( is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ) ) {
        $format = eme_add_captcha_submit( $format );
    } else {
        $configured_captchas = eme_get_configured_captchas();
        if (!empty($configured_captchas) && !$eme_is_admin_request)
            $selected_captcha = array_key_first($configured_captchas);
        $format = eme_add_captcha_submit( $format, $selected_captcha );
    }

    $tmp_groups = eme_get_subscribable_groups();
    if ( ! empty( $tmp_groups ) ) {
        $subscribable_groups = [ '' => esc_html__( 'All', 'events-made-easy' ) ];
    } else {
        $subscribable_groups = [];
    }
    if ( wp_next_scheduled( 'eme_cron_send_new_events' ) ) {
        $subscribable_groups['-1'] = esc_html__( 'Newsletter concerning new events', 'events-made-easy' );
    }
    foreach ( $tmp_groups as $group ) {
        $subscribable_groups[ $group['group_id'] ] = eme_esc_html( $group['name'] );
    }

    $bookerLastName  = '';
    $bookerFirstName = '';
    $bookerEmail     = '';
    $readonly        = '';
    $gdpr            = 0;
    if ( is_user_logged_in() ) {
        $readonly        = "readonly='readonly'";
        $current_user    = wp_get_current_user();
        $person          = eme_get_person_by_wp_id( $current_user->ID );
        if ( empty( $person ) ) {
            $person = eme_fake_person_by_wp_id( $current_user->ID );
        }
        $bookerLastName  = $person['lastname'];
        $bookerFirstName = $person['firstname'];
        $bookerEmail     = $person['email'];
        $gdpr            = intval( $person['gdpr'] );
    } elseif ( isset( $_GET['eme_email'] ) ) {
        $bookerEmail = eme_esc_html( eme_sanitize_email( $_GET['eme_email'] ) );
    }

    // We need at least #_EMAIL
    $email_found   = 0;
    $needle_offset = 0;
    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );

        $found        = 1;
        $required     = 0;
        $required     = 0;
        $required_att = '';
        $replacement  = '';

        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $unsubscribe ) {
                if ( isset( $matches[2] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[2], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
                }
                $replacement = "<input $required_att type='text' name='lastname' id='lastname' value='$bookerLastName' $readonly placeholder='$placeholder_text'>";
                if ( ! empty( $readonly ) ) {
                    $replacement .= "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'You can change your last name in your WP profile.', 'events-made-easy' ) . '</div>';
                }
            }
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            if ( ! $unsubscribe ) {
                if ( isset( $matches[1] ) ) {
                    // remove { and } (first and last char of second match)
                    $placeholder_text = substr( $matches[1], 1, -1 );
                    $placeholder_text = eme_trans_esc_html( $placeholder_text );
                } else {
                    $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
                }
                $replacement = "<input $required_att type='text' name='firstname' id='firstname' value='$bookerFirstName' $readonly placeholder='$placeholder_text'>";
                if ( ! empty( $readonly ) ) {
                    $replacement .= "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'You can change your first name in your WP profile.', 'events-made-easy' ) . '</div>';
                }
            }
        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='email' name='email' value='$bookerEmail' $readonly placeholder='$placeholder_text'>";
            if ( ! empty( $readonly ) ) {
                $replacement .= "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'You can change your email in your WP profile.', 'events-made-easy' ) . '</div>';
            }
            ++$email_found;
        } elseif ( preg_match( '/#_MAILGROUPS(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of match)
                $group_ids = substr( $matches[1], 1, -1 );
                if ( eme_is_list_of_int( $group_ids ) ) {
                    $groups  = eme_get_subscribable_groups( $group_ids );
                    $ids_arr = explode( ',', $group_ids );
                    if (in_array('-1',$ids_arr) && wp_next_scheduled( 'eme_cron_send_new_events' ) ) {
                        $groups[] = [ 'group_id'=>-1, 'name' => __( 'Newsletter concerning new events', 'events-made-easy' )];
                    }
                    if ( count( $ids_arr ) == 1 ) {
                        // if only 1 group id, take that one and no need for multiselect
                        $selected_value = $ids_arr[0];
                        $replacement    = eme_ui_select_key_value( $selected_value, 'email_group', $groups, 'group_id', 'name', '', 1 );
                    } else {
                        $replacement = eme_ui_multiselect_key_value( '', 'email_groups', $groups, 'group_id', 'name', 5, '', 1 );
                    }
                }
            } elseif ( ! empty( $subscribable_groups ) ) {
                if ( count( $subscribable_groups ) == 1 ) {
                    $selected_value = $subscribable_groups[0];
                    $replacement    = eme_ui_select( '', 'email_group', $subscribable_groups, '', 1 );
                } else {
                    $replacement = eme_ui_multiselect( '', 'email_groups', $subscribable_groups, 5, '', 1 );
                }
            }
        } elseif ( preg_match( '/#_GDPR(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = '';
            }
            $replacement = eme_ui_checkbox_binary( $gdpr, 'gdpr', $label, 1, 'eme-gdpr-field' );
        } elseif ( preg_match( '/#_REMEMBERME(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __('Remember me?','events-made-easy');
            }
            if ( ! is_user_logged_in()) {
                $replacement = eme_ui_checkbox_binary( 0, 'eme_rememberme', $label, 0, 'eme-rememberme-field nodynamicupdates' );
            }
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) { 
            if ( !empty($selected_captcha) && ! $captcha_set ) { 
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }       
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } elseif ( $unsubscribe ) {
                $label = __( 'Unsubscribe', 'events-made-easy' );
            } else {
                $label = __( 'Subscribe', 'events-made-easy' );
            }
            $replacement = "<img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } else {
            $found = 0;
        }

        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now check we found all the required placeholders for the form to work
    if ( $email_found ) {
        return $format;
    } else {
        $res = '';
        if ( ! $email_found ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_EMAIL and #_SUBMIT (or similar) placeholders.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-rsvp-message-error'>$res</div>";
    }
}

function eme_replace_cpiform_placeholders( $format, $person ) {
    $eme_is_admin_request = eme_is_admin_request();

    $selected_captcha = '';
    $captcha_set = 0;
    if ( is_user_logged_in() && get_option( 'eme_captcha_only_logged_out' ) ) {
        $format = eme_add_captcha_submit( $format );
    } else {
        $configured_captchas = eme_get_configured_captchas();
        if (!empty($configured_captchas) && !$eme_is_admin_request)
            $selected_captcha = array_key_first($configured_captchas);
        $format = eme_add_captcha_submit( $format, $selected_captcha );
    }

    $bookerLastName     = eme_esc_html( $person['lastname'] );
    $bookerFirstName    = eme_esc_html( $person['firstname'] );
    $bookerBirthdate    = eme_is_date( $person['birthdate'] ) ? eme_esc_html( $person['birthdate'] ) : '';
    $bookerBirthplace   = eme_esc_html( $person['birthplace'] );
    $bookerAddress1     = eme_esc_html( $person['address1'] );
    $bookerAddress2     = eme_esc_html( $person['address2'] );
    $bookerCity         = eme_esc_html( $person['city'] );
    $bookerZip          = eme_esc_html( $person['zip'] );
    $bookerState_code   = eme_esc_html( $person['state_code'] );
    $bookerCountry_code = eme_esc_html( $person['country_code'] );
    $bookerEmail        = eme_esc_html( $person['email'] );
    $bookerPhone        = eme_esc_html( $person['phone'] );
    $massmail           = intval( $person['massmail'] );
    $bd_email           = intval( $person['bd_email'] );

    // We need at least #_EMAIL and #_SUBMIT
    $email_found     = 0;
    $lastname_found  = 0;
    $firstname_found = 0;

    $current_userid = get_current_user_id();

    preg_match_all( '/#(REQ)?@?_?[A-Za-z0-9_]+(\{(?>[^{}]+|(?2))*\})*+/', $format, $placeholders, PREG_OFFSET_CAPTURE );
    $needle_offset = 0;
    foreach ( $placeholders[0] as $orig_result ) {
        $result             = $orig_result[0];
        $orig_result_needle = $orig_result[1] - $needle_offset;
        $orig_result_length = strlen( $orig_result[0] );
        $found              = 1;
        $replacement        = '';
        $required           = 0;
        $required_att       = '';
        if ( is_user_logged_in() && $person['wp_id'] == $current_userid ) {
            $readonly = "readonly='readonly'";
        } else {
            $readonly = '';
        }
        if ( strstr( $result, '#REQ' ) ) {
            $result       = str_replace( '#REQ', '#', $result );
            $required     = 1;
            $required_att = "required='required'";
        }

        if ( preg_match( '/#_(NAME|LASTNAME)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Last name', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='lastname' id='lastname' value='$bookerLastName' $readonly placeholder='$placeholder_text'>";
            if ( ! empty( $readonly ) ) {
                $replacement .= "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'You can change your last name in your WP profile.', 'events-made-easy' ) . '</div>';
            }
            // #_NAME is always required
            ++$lastname_found;
            $required = 1;
        } elseif ( preg_match( '/#_FIRSTNAME(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'First name', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='text' name='firstname' id='firstname' value='$bookerFirstName' $readonly placeholder='$placeholder_text'>";
            if ( ! empty( $readonly ) ) {
                $replacement .= "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'You can change your first name in your WP profile.', 'events-made-easy' ) . '</div>';
            }
            ++$firstname_found;
            $required = 1;
        } elseif ( preg_match( '/#_BIRTHDATE(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Date of birth', 'events-made-easy' );
            }
            $replacement  = "<input type='hidden' name='birthdate' id='birthdate' value='$bookerBirthdate'>";
            $replacement .= "<input $required_att readonly='readonly' type='text' name='dp_birthdate' id='dp_birthdate' data-date='$bookerBirthdate' data-format='".EME_WP_DATE_FORMAT."' data-view='years' data-alt-field='birthdate' class='eme_formfield_fdate' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_BIRTHPLACE(\{.+?\})?$/', $result, $matches ) ) {
            $replacement = "<input $required_att type='text' name='birthplace' id='birthplace' value='$bookerBirthplace' placeholder='$placeholder_text'>";
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Place of birth', 'events-made-easy' );
            }
        } elseif ( preg_match( '/#_ADDRESS1(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address1_string' ) );
            }
            $replacement = "<input $required_att type='text' name='address1' id='address1' value='$bookerAddress1' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_ADDRESS2(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = eme_trans_esc_html( get_option( 'eme_address2_string' ) );
            }
            $replacement = "<input $required_att type='text' name='address2' id='address2' value='$bookerAddress2' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_CITY(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[1], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'City', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='city' id='city' value='$bookerCity' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_STATE$/', $result ) ) {
            if ( ! empty( $bookerState_code ) ) {
                $state_arr = [ $bookerState_code => eme_get_state_name( $bookerState_code, $bookerCountry_code ) ];
            } else {
                $state_arr = [];
            }
            $replacement = eme_ui_select( $bookerState_code, 'state_code', $state_arr, '', $required, 'eme_select2_state_class' );
        } elseif ( preg_match( '/#_(ZIP|POSTAL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Postal code', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='text' name='zip' id='zip' value='$bookerZip' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_COUNTRY\{(.+)\}$/', $result, $matches ) ) {
            if ( ! empty( $bookerCountry_code ) ) {
                $country_arr = [ $bookerCountry_code => eme_get_country_name( $bookerCountry_code ) ];
                $replacement = eme_ui_select( $bookerCountry_code, 'country_code', $country_arr, '', $required, 'eme_select2_country_class' );
            } else {
                $country_code = $matches[1];
                $country_name = eme_get_country_name( $country_code );
                if ( ! empty( $country_name ) ) {
                    $country_arr = [ $country_code => $country_name ];
                    $replacement = eme_ui_select( $country_code, 'country_code', $country_arr, '', $required, 'eme_select2_country_class' );
                }
            }
        } elseif ( preg_match( '/#_COUNTRY$/', $result ) ) {
            if ( ! empty( $bookerCountry_code ) ) {
                $country_arr = [ $bookerCountry_code => eme_get_country_name( $bookerCountry_code ) ];
            } else {
                $country_arr = [];
            }
            $replacement = eme_ui_select( $bookerCountry_code, 'country_code', $country_arr, '', $required, 'eme_select2_country_class' );

        } elseif ( preg_match( '/#_(EMAIL|HTML5_EMAIL)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Email', 'events-made-easy' );
            }
            $replacement = "<input required='required' type='email' id='email' name='email' value='$bookerEmail' $readonly placeholder='$placeholder_text'>";
            if ( ! empty( $readonly ) ) {
                $replacement .= "<br><div class='eme_warning_wp_profile'><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning'>" . esc_html__( 'You can change your email in your WP profile.', 'events-made-easy' ) . '</div>';
            }
            ++$email_found;
            $required = 1;
        } elseif ( preg_match( '/#_(PHONE|HTML5_PHONE)(\{.+?\})?$/', $result, $matches ) ) {
            if ( isset( $matches[2] ) ) {
                // remove { and } (first and last char of second match)
                $placeholder_text = substr( $matches[2], 1, -1 );
                $placeholder_text = eme_trans_esc_html( $placeholder_text );
            } else {
                $placeholder_text = esc_html__( 'Phone number', 'events-made-easy' );
            }
            $replacement = "<input $required_att type='tel' id='phone' name='phone' value='$bookerPhone' placeholder='$placeholder_text'>";
        } elseif ( preg_match( '/#_IMAGE/', $result ) ) {
            // add the 1 as second argument to have a relative positioned div, by default it is absolute
            $replacement = eme_person_replace_image_input_div( $person, 1 );
        } elseif ( preg_match( '/#_BIRTHDAY_EMAIL$/', $result ) ) {
            $replacement = eme_ui_select_binary( $bd_email, 'bd_email' );
        } elseif ( preg_match( '/#_MASSMAIL$/', $result ) ) {
            $replacement = eme_ui_select_binary( $massmail, 'massmail' );
            if ( get_option( 'eme_massmail_popup' ) ) {
                $popup       = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                $confirm = esc_html__('Yes','events-made-easy');
                $cancel = esc_html__('No','events-made-easy');
                if (!eme_is_empty_string($popup))
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
            }
        } elseif ( preg_match( '/#_OPT_OUT$/', $result ) ) {
            $selected_massmail = ( isset( $massmail ) ) ? $massmail : 1;
            $replacement       = eme_ui_select_binary( $selected_massmail, 'massmail' );
            if ( get_option( 'eme_massmail_popup' ) ) {
                $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                $confirm = esc_html__('Yes','events-made-easy');
                $cancel  = esc_html__('No','events-made-easy');
                if (!eme_is_empty_string($popup))
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
            }
        } elseif ( preg_match( '/#_OPT_IN$/', $result ) ) {
            $selected_massmail = ( isset( $massmail ) ) ? $massmail : 0;
            $replacement       = eme_ui_select_binary( $selected_massmail, 'massmail' );
            if ( get_option( 'eme_massmail_popup' ) ) {
                $popup   = eme_esc_html( get_option( 'eme_massmail_popup_text' ) );
                $confirm = esc_html__('Yes','events-made-easy');
                $cancel  = esc_html__('No','events-made-easy');
                if (!eme_is_empty_string($popup))
                    $replacement .= "<dialog id='MassMailDialog' style='border: solid 1px #ccc;'><p>$popup</p><button id='dialog-confirm'>$confirm</button> <button id='dialog-cancel'>$cancel</button></dialog>";
            }
        } elseif ( preg_match( '/#_CFCAPTCHA|#_HCAPTCHA|#_RECAPTCHA|#_CAPTCHA$/', $result ) ) {
            if ( !empty($selected_captcha) && ! $captcha_set ) {
                $replacement = eme_generate_captchas_html($selected_captcha);
                if (!empty($replacement))
                    $captcha_set = 1;
            }
        } elseif ( preg_match( '/#_FIELDNAME\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) ) {
                    $replacement = eme_trans_esc_html( $formfield['field_name'] );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_FIELD\{(.+)\}/', $result, $matches ) ) {
            $field_key = $matches[1];
            $formfield = eme_get_formfield( $field_key );
            if ( ! empty( $formfield ) && $formfield['field_purpose'] == 'people' ) {
                    $field_id = $formfield['field_id'];
                    // all the people fields are dynamic fields in the backend, and the function eme_store_person_answers searches for that, so we need that name again
                    $person_id      = $person['person_id'];
                    $var_prefix     = "dynamic_personfields[$person_id][";
                    $var_postfix    = ']';
                    $postfield_name = "{$var_prefix}FIELD" . $field_id . $var_postfix;
                    $postvar_arr    = [ 'dynamic_personfields', $person_id, 'FIELD' . $field_id ];
                    // the first time there's no $_POST yet
                if ( ! empty( $_POST ) ) {
                    $entered_val = eme_getValueFromPath( $_POST, $postvar_arr );
                } else {
                    $entered_val = false;
                }
                // if entered_val ===false, then get it from the stored answer
                if ( $entered_val === false ) {
                    $answers = eme_get_person_answers( $person['person_id'] );
                    foreach ( $answers as $answer ) {
                        if ( $answer['field_id'] == $field_id ) {
                            $entered_val = $answer['answer'];
                            break;
                        }
                    }
                }
                $files = eme_get_uploaded_files( $person['person_id'], 'people' );
                if ( $formfield['field_type'] == 'file' || $formfield['field_type'] == 'multifile' ) {
                    $entered_files = [];
                    foreach ( $files as $file ) {
                        if ( $file['field_id'] == $field_id ) {
                            $entered_files[] = $file;
                        }
                    }
                    $entered_val = $entered_files;
                }

                if ( $formfield['field_required'] ) {
                    $required = 1;
                }
                $replacement = eme_get_formfield_html( $formfield, $postfield_name, $entered_val, $required );
            } else {
                $found = 0;
            }
        } elseif ( preg_match( '/#_SUBMIT(\{.+?\})?/', $result, $matches ) ) {
            if ( isset( $matches[1] ) ) {
                // remove { and } (first and last char of second match)
                $label = substr( $matches[1], 1, -1 );
            } else {
                $label = __( 'Save personal info', 'events-made-easy' );
            }
            $replacement = "<img id='loading_gif' alt='loading' src='" . esc_url(EME_PLUGIN_URL) . "images/spinner.gif' class='eme-hidden'><input name='eme_submit_button' class='eme_submit_button' type='submit' value='" . eme_trans_esc_html( $label ) . "'>";
        } else {
            $found = 0;
        }

        if ( $required ) {
            $eme_form_required_field_string = eme_translate( get_option( 'eme_form_required_field_string' ) );
            if ( ! empty( $eme_form_required_field_string ) ) {
                $replacement .= "<div class='eme-required-field'>$eme_form_required_field_string</div>";
            }
        }
        if ( $found ) {
            // to be sure
            if (is_null($replacement)) {
                $replacement = "";
            }
            $format         = substr_replace( $format, $replacement, $orig_result_needle, $orig_result_length );
            $needle_offset += $orig_result_length - strlen( $replacement );
        }
    }

    // now check we found all the required placeholders for the form to work
    if ( $lastname_found && $firstname_found && $email_found ) {
        return $format;
    } else {
        $res = '';
        if ( ! $lastname_found || ! $firstname_found || ! $email_found ) {
            $res .= __( 'Not all required fields are present in the form. We need at least #_LASTNAME, #_FIRSTNAME, #_EMAIL and #_SUBMIT (or similar) placeholders.', 'events-made-easy' ) . '<br>';
        }
        return "<div id='message' class='eme-message-error eme-cpi-message-error'>$res</div>";
    }
}

function eme_find_required_formfields( $format ) {
    preg_match_all( '/#REQ_?[A-Za-z0-9_]+(\{.*?\})?/', $format, $placeholders );
    usort( $placeholders[0], 'eme_sort_stringlenth' );
    $result = [];
    foreach ( $placeholders[0] as $placeholder ) {
        // #_NAME and #REQ_NAME should be using _LASTNAME
        $res = preg_replace( '/_NAME/', '_LASTNAME', $placeholder );
        if ( preg_match( '/#REQ_FIELD/', $res ) ) {
            // We just want the fieldnames: FIELD1, FIELD2, ... like they are POST'd via the form
            // But there are 3 possible notations in the format: FIELD1, FIELD{1}, FIELD{fieldname}
            $res       = preg_replace( '/#REQ_FIELD|\{|\}/', '', $res );
            $formfield = eme_get_formfield( $res );
            if ( ! empty( $formfield ) ) {
                $res = 'FIELD' . $formfield['field_id'];
            } else {
                $res = '';
            }
        } else {
            $res = preg_replace( '/#REQ_|\{|\}/', '', $res );
        }
        if ( ! empty( $res ) ) {
            $result[] = $res;
        }
    }

    // formfields can be required in their definition too, so lets check those too
    preg_match_all( '/#_[A-Za-z0-9_]+(\{.*?\})?/', $format, $placeholders );
    usort( $placeholders[0], 'eme_sort_stringlenth' );
    foreach ( $placeholders[0] as $placeholder ) {
        if ( preg_match( '/#_FIELD/', $placeholder ) ) {
            // We just want the fieldnames: FIELD1, FIELD2, ... like they are POST'd via the form
            // But there are 3 possible notations in the format: FIELD1, FIELD{1}, FIELD{fieldname}
            $res       = preg_replace( '/#_FIELD|\{|\}/', '', $placeholder );
            $formfield = eme_get_formfield( $res );
            if ( ! empty( $formfield ) && $formfield['field_required'] ) {
                $res      = 'FIELD' . $formfield['field_id'];
                $result[] = $res;
            }
        }
    }
    return $result;
}

function eme_answer2readable( $answer, $formfield=[], $convert_val = 1, $sep = '||', $target = 'html', $from_backend = 0 ) {
    if (empty($formfield)) {
        $formfield=eme_get_formfield($answer['field_id']);
    }
    $field_values = $formfield['field_values'];
    $field_tags   = $formfield['field_tags'];

    if ( eme_is_multifield( $formfield['field_type'] ) ) {
        if ( $convert_val ) {
            $answers = eme_convert_multi2array( $answer );
            $values  = eme_convert_multi2array( $field_values );
            if ( empty( $field_tags ) ) {
                return eme_convert_array2multi( $answers, $sep );
            }
            $tags   = eme_convert_multi2array( $field_tags );
            $my_arr = [];
            foreach ( $answers as $ans ) {
                foreach ( $values as $key => $val ) {
                    if ( $val === $ans ) {
                        if ( $target == 'html' ) {
                            $my_arr[] = eme_esc_html( $tags[ $key ] );
                        } else {
                            $my_arr[] = $tags[ $key ];
                        }
                    }
                }
            }
            return eme_convert_array2multi( $my_arr, $sep );
        } else {
            $answers = eme_convert_multi2array( $answer );
            if ( $target == 'html' ) {
                $answers = eme_esc_html( $answers );
            }
            return eme_convert_array2multi( $answers, $sep );
        }
    } else {
        if ( ! isset( $formfield['field_attributes'] ) ) {
            $formfield['field_attributes'] = '';
        }
        if ( $formfield['field_type'] == 'date' ) { // for type DATE
            return eme_localized_date( $answer, EME_TIMEZONE, $from_backend );
        } elseif ( $formfield['field_type'] == 'date_js' ) { // for type DateJS
            if ( $from_backend ) {
                return eme_localized_date( $answer, EME_TIMEZONE, $from_backend );
            } else {
                return eme_localized_date( $answer, EME_TIMEZONE, $formfield['field_attributes'] );
            }
        } elseif ( $formfield['field_type'] == 'datetime_js' ) { // for type DateJS
            if ( $from_backend ) {
                return eme_localized_datetime( $answer, EME_TIMEZONE, $from_backend );
            } else {
                return eme_localized_datetime( $answer, EME_TIMEZONE, $formfield['field_attributes'] );
            }
        } elseif ( $formfield['field_type'] == 'time_js' ) { // for type DateJS
            if ( $from_backend ) {
                return eme_localized_time( $answer, EME_TIMEZONE, $from_backend );
            } else {
                return eme_localized_time( $answer, EME_TIMEZONE, $formfield['field_attributes'] );
            }
        } elseif ( $formfield['extra_charge'] && $target == 'html' ) {
            //return eme_convert_answer_price($answer);
            return $answer;
        } else {
            return $answer;
        }
    }
}

function eme_convert_answer_price( $answer ) {
    if ( $answer['type'] == 'booking' ) { // for fields with answers that are an extra charge
        $event = eme_get_event_by_booking_id( $answer['related_id'] );
        return eme_localized_price( $answer, $event['currency'] );
    } elseif ( $answer['type'] == 'member' ) { // for fields with answers that are an extra charge
        $member     = eme_get_member( $answer['related_id'] );
        $membership = eme_get_membership( $member['membership_id'] );
        return eme_localized_price( $answer, $membership['properties']['currency'] );
    } else {
        return $answer;
    }
}

function eme_get_answer_fieldids( $ids_arr ) {
    return eme_get_booking_answers_fieldids( $ids_arr );
}

function eme_get_booking_answers_fieldids( $ids_arr ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    # use ORDER BY to get a predictable list of field ids (otherwise result could be different for each event/booking)
    if (!empty($ids_arr) &&  eme_is_numeric_array( $ids_arr ) ) {
        $ids_list = implode(',', $ids_arr);
        return $wpdb->get_col( "SELECT DISTINCT field_id FROM $answers_table WHERE type='booking' AND eme_grouping=0 AND related_id IN ($ids_list) ORDER BY field_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    } else {
        return [];
    }
}

function eme_get_tasksignups_answers_fieldids( $ids_arr ) {
    global $wpdb;
    $answers_table = EME_DB_PREFIX . EME_ANSWERS_TBNAME;
    # use ORDER BY to get a predictable list of field ids (otherwise result could be different for each event/booking)
    if (!empty($ids_arr) &&  eme_is_numeric_array( $ids_arr ) ) {
        $ids_list = implode(',', $ids_arr);
        return $wpdb->get_col( "SELECT DISTINCT field_id FROM $answers_table WHERE type='tasksignup' AND eme_grouping=0 AND related_id IN ($ids_list) ORDER BY field_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    } else {
        return [];
    }
}

function eme_get_people_export_fieldids() {
    global $wpdb;
    $table = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    # use ORDER BY to get a predictable list of field ids (otherwise result could be different for each run)
    $sql = "SELECT field_id FROM $table WHERE export=1 AND field_purpose='people' ORDER BY field_id";
    return $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

function eme_dyndata_adminform( $eme_data, $templates_array, $used_groupingids ) {
    $eme_dyndata_conditions = eme_get_dyndata_conditions();
    ?>
    <div id="div_dyndata">
        <b><?php esc_html_e( 'Dynamically show fields based on a number of conditions', 'events-made-easy' ); ?></b>
        <table class="eme_dyndata">
        <thead>
            <tr>
                <th></th>
                <th><strong><?php esc_html_e( 'Index', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Field condition', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Templates', 'events-made-easy' ); ?></strong></th>
                <th><strong><?php esc_html_e( 'Repeat', 'events-made-easy' ); ?></strong></th>
                <th></th>
            </tr>
        </thead>    
        <tbody id="eme_dyndata_tbody" class="eme_dyndata_tbody">
            <?php
            // if there are no entries in the eme_data array, make 1 empty entry in it, so it renders at least 1 row
            if ( ! is_array( $eme_data ) || count( $eme_data ) == 0 ) {
                $info     = [
                    'field'              => '',
                    'condition'          => '',
                    'condval'            => '',
                    'template_id_header' => 0,
                    'template_id'        => 0,
                    'template_id_footer' => 0,
                    'repeat'             => 0,
                    'grouping'           => 1,
                ];
                $eme_data = [ $info ];
                $required = '';
                $dyn_count_total = 0;
            } else {
                $required = "required='required'";
                $dyn_count_total = count( $eme_data);
            }
            foreach ( $eme_data as $count => $info ) {
                $grouping_used = in_array( $info['grouping'], $used_groupingids ) ? 1 : 0;
                ?>
                    <tr id="eme_dyndata_<?php echo $count; ?>">
                    <td>
                <?php echo "<img class='eme-sortable-handle' src='" . esc_url(EME_PLUGIN_URL) . "images/reorder.png' alt='" . esc_attr__( 'Reorder', 'events-made-easy' ) . "'>"; ?>
                    </td>
                    <td>
            <!-- the grouping index parameter should be a unique index per condition. This is used to set/retrieve all the entered info based on this condition in the database (so once set, always keep it to the same value for that condition) -->
            <!-- Since it is too complicated to explain that, but we still need it: keep it a hidden field if possible, the value for new rows is set via php anyway -->
                        <?php if ($dyn_count_total>0 && $grouping_used==0) : ?>
                        <input type='text' id="eme_dyndata[<?php echo $count; ?>][grouping]" name="eme_dyndata[<?php echo $count; ?>][grouping]" aria-label="hidden grouping index" size="5" maxlength="5" value="<?php echo $info['grouping']; ?>">
                        <?php else : ?>
                        <?php if ($dyn_count_total>0) echo $info['grouping']; ?>
                        <input type='hidden' id="eme_dyndata[<?php echo $count; ?>][grouping]" name="eme_dyndata[<?php echo $count; ?>][grouping]" aria-label="hidden grouping index" value="<?php echo $info['grouping']; ?>">
                        <?php endif; ?>
                    </td>
                    <td><table style="">
                        <tr><td><?php esc_html_e( 'Field', 'events-made-easy' ); ?></td><td><input <?php echo $required; ?> id="eme_dyndata[<?php echo $count; ?>][field]" name="eme_dyndata[<?php echo $count; ?>][field]" size="12" aria-label="field" value="<?php echo $info['field']; ?>"></td></tr>
                        <tr><td><?php esc_html_e( 'Condition', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['condition'], 'eme_dyndata[' . $count . '][condition]', $eme_dyndata_conditions, '', 0, '', "aria-label='condition'" ); ?></td></tr>
                        <tr><td><?php esc_html_e( 'Condition value', 'events-made-easy' ); ?></td><td><input <?php echo $required; ?> id="eme_dyndata[<?php echo $count; ?>][condval]" name="eme_dyndata[<?php echo $count; ?>][condval]" aria-label="condition value" size="12" value="<?php echo $info['condval']; ?>"></td></tr>
                    </table>
                    </td>
                    <td><table style="">
                        <tr><td><?php esc_html_e( 'Header template', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['template_id_header'], 'eme_dyndata[' . $count . '][template_id_header]', $templates_array, '', 0, '', "aria-label='template_id_header'" ); ?></td></tr>
                        <tr><td><?php esc_html_e( 'Template', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['template_id'], 'eme_dyndata[' . $count . '][template_id]', $templates_array, '', 0, '', "aria-label='template_id'" ); ?></td></tr>
                        <tr><td><?php esc_html_e( 'Footer template', 'events-made-easy' ); ?></td><td><?php echo eme_ui_select( $info['template_id_footer'], 'eme_dyndata[' . $count . '][template_id_footer]', $templates_array, '', 0, '', "aria-label='template_id_footer'" ); ?></td></tr>
                    </table>
                    </td>
                    <td>
                <?php echo eme_ui_select_binary( $info['repeat'], 'eme_dyndata[' . $count . '][repeat]', 0, '', "aria-label='repeat'" ); ?>
                    </td>
                    <td>
                        <a href="#" class='eme_remove_dyndatacondition'><?php echo "<img class='eme_remove_dyndatacondition' src='" . esc_url(EME_PLUGIN_URL) . "images/cross.png' alt='" . esc_attr__( 'Remove', 'events-made-easy' ) . "' title='" . esc_attr__( 'Remove', 'events-made-easy' ) . "'>"; ?></a><a href="#" class="eme_dyndata_add_tag"><?php echo "<img class='eme_dyndata_add_tag' src='" . esc_url(EME_PLUGIN_URL) . "images/plus_16.png' alt='" . esc_attr__( 'Add new condition', 'events-made-easy' ) . "' title='" . esc_attr__( 'Add new condition', 'events-made-easy' ) . "'>"; ?></a>
                <?php
                if ( $grouping_used ) {
                    echo "<br><img style='vertical-align: middle;' src='" . esc_url(EME_PLUGIN_URL) . "images/warning.png' alt='warning' title='" . esc_attr__( 'Warning: there are already answers entered based on this condition, changing or removing this condition might lead to unwanted side effects.', 'events-made-easy' ) . "'>";
                }
                ?>
                    </td>
                    </tr>
                <?php
            }
            ?>
        </tbody>
        </table>
        <p class='eme_smaller'>
        <?php esc_html_e( 'This will additionally show the selected template in the form if the condition is met.', 'events-made-easy' ); ?>
        <br>
        <?php esc_html_e( "The 'Field' parameter is to be filled out with any valid placeholder allowed in the form.", 'events-made-easy' ); ?>
        <br>
        <?php esc_html_e( "The selected template will be shown several times if the repeat option is used (based on the number of times the field is different from the condition value. This is not used for the 'equal to' condition selector.", 'events-made-easy' ); ?>
        <br>
        <?php esc_html_e( 'The selected template can contain html and also have placeholders for custom form fields (no other placeholders allowed).', 'events-made-easy' ); ?>
        <?php esc_html_e( 'Use the placeholder #_DYNAMICDATA to show the dynamic forms in your form.', 'events-made-easy' ); ?>
        </p>
    </div>
    <?php
}

function eme_handle_dyndata_post_adminform() {
    $eme_dyndata           = [];
    $biggest_grouping_seen = 0;
    $groupings_seen        = [];
    $eme_dyndata_arr       = [];
    if ( empty( $_POST['eme_dyndata'] ) ) {
        return $eme_dyndata_arr;
    }
    foreach ( $_POST['eme_dyndata'] as $eme_dyndata ) {
        if ( $eme_dyndata['template_id'] > 0 && isset( $eme_dyndata['grouping'] ) ) {
            $grouping = intval( $eme_dyndata['grouping'] );
            if ( $biggest_grouping_seen < $grouping ) {
                $biggest_grouping_seen = $grouping;
            }
        }
    }
    foreach ( $_POST['eme_dyndata'] as $eme_dyndata ) {
        if ( $eme_dyndata['template_id'] > 0 ) {
            $eme_dyndata['template_id'] = intval( $eme_dyndata['template_id'] );
            if ( isset( $eme_dyndata['repeat'] ) && $eme_dyndata['repeat'] == 1 ) {
                $eme_dyndata['repeat']  = intval( $eme_dyndata['repeat'] );
                $eme_dyndata['condval'] = intval( $eme_dyndata['condval'] );
            } else {
                $eme_dyndata['repeat']  = 0;
                $eme_dyndata['condval'] = $eme_dyndata['condval'];
            }
            if ( isset( $eme_dyndata['template_id_header'] ) ) {
                $eme_dyndata['template_id_header'] = intval( $eme_dyndata['template_id_header'] );
            } else {
                $eme_dyndata['template_id_header'] = 0;
            }
            if ( isset( $eme_dyndata['template_id_footer'] ) ) {
                $eme_dyndata['template_id_footer'] = intval( $eme_dyndata['template_id_footer'] );
            } else {
                $eme_dyndata['template_id_footer'] = 0;
            }
            if ( isset( $eme_dyndata['grouping'] ) ) {
                // to make sure people don't use 2 times the same id
                $grouping = intval( $eme_dyndata['grouping'] );
                if ( in_array( $grouping, $groupings_seen ) ) {
                    $eme_dyndata['grouping'] = $biggest_grouping_seen + 1;
                    ++$biggest_grouping_seen;
                    $groupings_seen[] = $biggest_grouping_seen;
                } else {
                    $eme_dyndata['grouping'] = $grouping;
                    $groupings_seen[]        = $grouping;
                }
            } else {
                        $eme_dyndata['grouping'] = $biggest_grouping_seen + 1;
                ++$biggest_grouping_seen;
                $groupings_seen[] = $biggest_grouping_seen;
            }
            $eme_dyndata_arr[] = $eme_dyndata;
        }
    }
    return $eme_dyndata_arr;
}

add_action( 'wp_ajax_eme_formfields_list', 'eme_ajax_formfields_list' );
add_action( 'wp_ajax_eme_manage_formfields', 'eme_ajax_manage_formfields' );

function eme_ajax_formfields_list() {
    global $wpdb;

    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    header( 'Content-type: application/json; charset=utf-8' );
    if ( ! current_user_can( get_option( 'eme_cap_list_events' ) ) ) {
        $ajaxResult            = [];
        $ajaxResult['Result']  = 'Error';
        $ajaxResult['Message'] = __( 'Access denied!', 'events-made-easy' );
        print wp_json_encode( $ajaxResult );
        wp_die();
    }

    $table              = EME_DB_PREFIX . EME_FORMFIELDS_TBNAME;
    $used_formfield_ids = eme_get_used_formfield_ids();
    $fTableResult       = [];
    $search_type        = isset( $_POST['search_type'] ) ? esc_sql( eme_sanitize_request( $_POST['search_type'] ) ) : '';
    $search_purpose     = isset( $_POST['search_purpose'] ) ? esc_sql( eme_sanitize_request( $_POST['search_purpose'] ) ) : '';
    $search_name        = isset( $_POST['search_name'] ) ? esc_sql( $wpdb->esc_like( eme_sanitize_request( $_POST['search_name'] ) ) ) : '';
    $where              = '';
    $where_arr          = [];
    if ( ! empty( $search_name ) ) {
        $where_arr[] = "field_name like '%" . $search_name . "%'";
    }
    if ( ! empty( $search_type ) ) {
        $where_arr[] = "(field_type = '$search_type')";
    }
    if ( ! empty( $search_purpose ) ) {
        $where_arr[] = "(field_purpose = '$search_purpose')";
    }
    if ( $where_arr ) {
        $where = 'WHERE ' . implode( ' AND ', $where_arr );
    }

    if ( current_user_can( get_option( 'eme_cap_forms' ) ) ) {
        $sql         = "SELECT COUNT(*) FROM $table $where";
        $recordCount = $wpdb->get_var( $sql );
        $limit       = eme_get_datatables_limit();
        $orderby     = eme_get_datatables_orderby();
        $sql         = "SELECT * FROM $table $where $orderby $limit";
        $rows        = $wpdb->get_results( $sql, ARRAY_A );
        $res         = [];
        foreach ( $rows as $key => $formfield ) {
            if ( empty( $formfield['field_name'] ) ) {
                $row['field_name'] = __( 'No name', 'events-made-easy' );
            }
            $rows[ $key ]['field_type']     = eme_get_fieldtype( $formfield['field_type'] );
            $rows[ $key ]['field_required'] = ( $formfield['field_required'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['field_purpose']  = eme_get_fieldpurpose( $formfield['field_purpose'] );
            $rows[ $key ]['extra_charge']   = ( $formfield['extra_charge'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['searchable']     = ( $formfield['searchable'] == 1 ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['used']           = in_array( $formfield['field_id'], $used_formfield_ids ) ? __( 'Yes', 'events-made-easy' ) : __( 'No', 'events-made-easy' );
            $rows[ $key ]['field_name']     = "<a href='" . admin_url( 'admin.php?page=eme-formfields&amp;eme_admin_action=edit_formfield&amp;field_id=' . $formfield['field_id'] ) . "'>" . $formfield['field_name'] . '</a>';

            $copy_link='window.location.href="'.admin_url( 'admin.php?page=eme-formfields&amp;eme_admin_action=copy_formfield&amp;field_id=' . $formfield['field_id'] ).'";';
            $rows[ $key ][ 'copy'] = "<button onclick='$copy_link' title='" . __( 'Copy', 'events-made-easy' ) . "' class='ftable-command-button eme-copy-button'><span>copy</span></a>";

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

function eme_ajax_manage_formfields() {
    check_ajax_referer( 'eme_admin', 'eme_admin_nonce' );
    $fTableResult=[];
    if (! current_user_can( get_option( 'eme_cap_forms' ) ) || !isset( $_REQUEST['field_id'] ) ) {
        $fTableResult['Result']      = 'Error';
        $fTableResult['Message']     = __( 'Access denied!', 'events-made-easy' );
    }
    if ( isset( $_REQUEST['do_action'] ) ) {
        $do_action = eme_sanitize_request( $_REQUEST['do_action'] );
        switch ( $do_action ) {
            case 'deleteFormfield':
                // validation happens in the eme_delete_formfields function
                eme_delete_formfields( [ intval($_REQUEST['field_id']) ] );
                $fTableResult['Result']      = 'OK';
                $fTableResult['Message'] = __( 'Records deleted!', 'events-made-easy' );
                print wp_json_encode( $fTableResult );
                wp_die();
                break;
            case 'deleteFormfields':
                $field_ids = explode( ',', eme_sanitize_request($_REQUEST['field_id']) );
                if (eme_is_numeric_array( $field_ids)) {
                    // validation happens in the eme_delete_formfields function
                    eme_delete_formfields( $field_ids );
                    $fTableResult['Result']      = 'OK';
                    $fTableResult['Message'] = __( 'Records deleted!', 'events-made-easy' );
                } else {
                    $fTableResult['Result']      = 'Error';
                    $fTableResult['Message']     = __( 'Access denied!', 'events-made-easy' );
                }
                print wp_json_encode( $fTableResult );
                wp_die();
                break;
        }
    }
    wp_die();
}
