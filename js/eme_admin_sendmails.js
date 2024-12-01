jQuery(document).ready( function($) {
    if (typeof getQueryParams === 'undefined') {
        function getQueryParams(qs) {
            qs = qs.split('+').join(' ');
            var params = {},
                tokens,
                re = /[?&]?([^=]+)=([^&]*)/g;

            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            }
            return params;
        }
    }
    var $_GET = getQueryParams(document.location.search);

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($('input[name=chooseperson]').length) {
        let emeadmin_chooseperson_timeout; // Declare a variable to hold the timeout ID
        $("input[name=chooseperson]").on("input", function(e) {
            clearTimeout(emeadmin_chooseperson_timeout); // Clear the previous timeout
            var inputField = $(this);
            var inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_chooseperson_timeout = setTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': emeadmin.translate_adminnonce,
                            'action': 'eme_autocomplete_people',
                            'eme_searchlimit': 'people'
                        },
                        function(data) {
                            var suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'</strong><br /><small>'+eme_htmlDecode(item.email)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=send_previewmailto_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+ememails.translate_nomatchperson+'</strong>')
                                );
                            }
                            inputField.after(suggestions);
                        }, "json");
                }, 500); // Delay of 0.5 second
            }
        });
        $(document).on("click", function() {
            $(".eme-autocomplete-suggestions").remove();
        });

        // if manual input: set the hidden field empty again
        $('input[name=chooseperson]').on("keyup",function() {
            $('input[name=send_previewmailto_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=send_previewmailto_id]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }

    if ($('input[name=eventmail_chooseperson]').length) {
        let emeadmin_eventmailchooseperson_timeout; // Declare a variable to hold the timeout ID
        $("input[name=eventmail_chooseperson]").on("input", function(e) {
            clearTimeout(emeadmin_eventmailchooseperson_timeout); // Clear the previous timeout
            var inputField = $(this);
            var inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                emeadmin_eventmailchooseperson_timeout = setTimeout(function() {
                    $.post(ajaxurl,
                        { 
                            'lastname': inputValue,
                            'eme_admin_nonce': emeadmin.translate_adminnonce,
                            'action': 'eme_autocomplete_people',
                            'eme_searchlimit': 'people'
                        },
                        function(data) {
                            var suggestions = $("<div class='eme-autocomplete-suggestions'></div>");
                            $.each(data, function(index, item) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'</strong><br /><small>'+eme_htmlDecode(item.email)+'</small>')
                                    .on("click", function(e) {
                                        e.preventDefault();
                                        if (item.person_id) {
                                            $('input[name=send_previeweventmailto_id]').val(eme_htmlDecode(item.person_id));
                                            inputField.val(eme_htmlDecode(item.lastname)+' '+eme_htmlDecode(item.firstname)+'  ').attr('readonly', true).addClass('clearable x');
                                        }
                                    })
                                );
                            });
                            if (!data.length) {
                                suggestions.append(
                                    $("<div class='eme-autocomplete-suggestion'></div>")
                                    .html("<strong>"+ememails.translate_nomatchperson+'</strong>')
                                );
                            }
                            inputField.after(suggestions);
                        }, "json");
                }, 500); // Delay of 0.5 second
            }
        });
        $(document).on("click", function() {
            $(".eme-autocomplete-suggestions").remove();
        });

        // if manual input: set the hidden field empty again
        $('input[name=eventmail_chooseperson]').on("keyup",function() {
            $('input[name=send_previeweventmailto_id]').val('');
        }).change(function() {
            if ($(this).val()=='') {
                $('input[name=send_previeweventmailto_id]').val('');
                $(this).attr('readonly', false).removeClass('clearable');
            }
        });
    }

    $('#eventmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmlmail=='yes') {
            var editor = tinymce.get('event_mail_message');
            if ( editor !== null) {
                editor.save();
            }
        }
        var form_id = $(this.form).attr('id');
        var alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', 'eme_eventmail');
        alldata.append('eme_admin_nonce', emeadmin.translate_adminnonce);
        $('#eventmailButton').text(ememails.translate_pleasewait);
        $('#eventmailButton').prop('disabled', true);
        $.ajax({url: ajaxurl, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#eventmail-message').html(data.htmlmessage);
                $('div#eventmail-message').show();
                if (data.Result=='OK') {
                    $('#'+form_id).trigger('reset');
                    // the form reset doesn't reset select2 fields ...
                    // so we call it ourselves
                    $('#event_ids').val(null).trigger("change");
                    $("#eme_eventmail_send_persons").val(null).trigger("change");
                    $("#eme_eventmail_send_groups").val(null).trigger("change");
                    $("#eme_eventmail_send_members").val(null).trigger("change");
                    $("#eme_eventmail_send_membergroups").val(null).trigger("change");
                    $("#eme_eventmail_send_memberships").val(null).trigger("change");
                    $("#eme_mail_type").val(null).trigger("change");
                    $('div#eventmail-message').delay(10000).fadeOut('slow');
                }
                $('#eventmailButton').text(ememails.translate_sendmail);
                $('#eventmailButton').prop('disabled', false);
            });
        return false;
    });

    $('#genericmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmlmail=='yes') {
            var editor = tinymce.get('generic_mail_message');
            if ( editor !== null) {
                editor.save();
            }
        }
        var form_id = $(this.form).attr('id');
        var alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', 'eme_genericmail');
        alldata.append('eme_admin_nonce', emeadmin.translate_adminnonce);
        $('#genericmailButton').text(ememails.translate_pleasewait);
        $('#genericmailButton').prop('disabled', true);
        $.ajax({url: ajaxurl, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#genericmail-message').html(data.htmlmessage);
                $('div#genericmail-message').show();
                if (data.Result=='OK') {
                    $('#'+form_id).trigger('reset');
                    // the form reset doesn't reset select2 fields ...
                    // so we call it ourselves
                    $("#eme_genericmail_send_persons").val(null).trigger("change");
                    $("#eme_genericmail_send_peoplegroups").val(null).trigger("change");
                    $("#eme_genericmail_send_members").val(null).trigger("change");
                    $("#eme_genericmail_send_membergroups").val(null).trigger("change");
                    $("#eme_send_memberships").val(null).trigger("change");
                    // the form reset doesn't reset other show/hide stuff apparently ...
                    // so we call it ourselves
                    $('input#eme_send_all_people').trigger('change');
                    $('div#genericmail-message').delay(5000).fadeOut('slow');
                }
                $('#genericmailButton').text(ememails.translate_sendmail);
                $('#genericmailButton').prop('disabled', false);
            });
        return false;
    });

    $('#previeweventmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmlmail=='yes') {
            var editor = tinymce.get('event_mail_message');
            if ( editor !== null) {
                editor.save();
            }
        }
        var form_id = $(this.form).attr('id');
        var alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', 'eme_previeweventmail');
        alldata.append('eme_admin_nonce', emeadmin.translate_adminnonce);
        $.ajax({url: ajaxurl, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#previeweventmail-message').html(data.htmlmessage);
                $('div#previeweventmail-message').show();
                $('div#previeweventmail-message').delay(5000).fadeOut('slow');
                if (data.Result=='OK') {
                    $('input[name=eventmail_chooseperson]').val('');
                    $('input[name=send_previeweventmailto_id]').val('');
                    $('input[name=eventmail_chooseperson]').attr('readonly', false);
                }
            });
        return false;
    });
    $('#previewmailButton').on("click",function (e) {
        e.preventDefault();
        // if we want html mail, we need to save the html message first, otherwise the mail content is not ok via ajax submit
        if (ememails.translate_htmlmail=='yes') {
            var editor = tinymce.get('generic_mail_message');
            if ( editor !== null) {
                editor.save();
            }
        }
        var form_id = $(this.form).attr('id');
        var alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', 'eme_previewmail');
        alldata.append('eme_admin_nonce', emeadmin.translate_adminnonce);
        $.ajax({url: ajaxurl, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#previewmail-message').html(data.htmlmessage);
                $('div#previewmail-message').show();
                $('div#previewmail-message').delay(5000).fadeOut('slow');
                if (data.Result=='OK') {
                    $('input[name=chooseperson]').val('');
                    $('input[name=send_previewmailto_id]').val('');
                    $('input[name=chooseperson]').attr('readonly', false);
                }
            });
        return false;
    });

    $('#searchmailButton').on("click",function (e) {
        e.preventDefault();
        var form_id = $(this.form).attr('id');
        var alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', 'eme_searchmail');
        alldata.append('eme_admin_nonce', emeadmin.translate_adminnonce);
        $.ajax({url: ajaxurl, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#searchmail-message').html(data.htmlmessage);
                $('div#searchmail-message').show();
            });
        return false;
    });

    $('#testmailButton').on("click",function (e) {
        e.preventDefault();
        var form_id = $(this.form).attr('id');
        var alldata = new FormData($('#'+form_id)[0]);
        alldata.append('action', 'eme_testmail');
        alldata.append('eme_admin_nonce', emeadmin.translate_adminnonce);
        $('#testmailButton').text(ememails.translate_pleasewait);
        $('#testmailButton').prop('disabled', true);
        $.ajax({url: ajaxurl, data: alldata, cache: false, contentType: false, processData: false, type: 'POST', dataType: 'json'})
            .done(function(data){
                $('div#testmail-message').html(data.htmlmessage);
                $('div#testmail-message').show();
                if (data.Result=='OK') {
                    $('#'+form_id).trigger('reset');
                }
                $('#testmailButton').text(ememails.translate_sendmail);
                $('#testmailButton').prop('disabled', false);
            });
        return false;
    });

    // show selected template in form
    $('select#event_subject_template').on("change",function (e) {
        e.preventDefault();
        $.post(ajaxurl,
            { action: 'eme_get_template',
                'eme_admin_nonce': emeadmin.translate_adminnonce,
                template_id: $('select#event_subject_template').val(),
            },
            function(data){
                $('input#event_mail_subject').val(data.htmlmessage);
            }, 'json');

    });

    // show selected template in form
    $('select#event_message_template').on("change",function (e) {
        e.preventDefault();
        $.post(ajaxurl,
            { action: 'eme_get_template',
                'eme_admin_nonce': emeadmin.translate_adminnonce,
                template_id: $('select#event_message_template').val(),
            },
            function(data){
                $('textarea#event_mail_message').val(data.htmlmessage);
                if (ememails.translate_htmlmail=='yes') {
                    var editor = tinymce.get('event_mail_message');
                    if ( editor !== null) {
                        editor.setContent(data.htmlmessage);
                        editor.save();
                    }
                }
            }, 'json');

    });

    // show selected template in form
    //$('select#generic_subject_template').change(function (e) {
    //       e.preventDefault();
    //	  $.post(ajaxurl,
    //		  { action: 'eme_get_template',
    //		   'eme_admin_nonce': emeadmin.translate_adminnonce,
    //		    template_id: $('select#generic_subject_template').val(),
    //		  },
    //		  function(data){
    //		      $('input#generic_mail_subject').val(data.htmlmessage);
    //		  }, "json");
    //  });

    // show selected template in form
    $('select#generic_message_template').on("change",function (e) {
        e.preventDefault();
        $.post(ajaxurl,
            { action: 'eme_get_template',
                'eme_admin_nonce': emeadmin.translate_adminnonce,
                template_id: $('select#generic_message_template').val(),
            },
            function(data){
                $('textarea#generic_mail_message').val(data.htmlmessage);
                if (ememails.translate_htmlmail=='yes') {
                    var editor = tinymce.get('generic_mail_message');
                    if ( editor !== null) {
                        editor.setContent(data.htmlmessage);
                        editor.save();
                    }
                }
            }, 'json');

    });

    function updateShowSendGroups () {
        if ($('input#eme_send_all_people').prop('checked')) {
            $('div#div_eme_send_groups').hide();
            $('div#div_eme_send_all_people').show();
        } else {
            $('div#div_eme_send_groups').show();
            $('div#div_eme_send_all_people').hide();
        }
    }
    $('input#eme_send_all_people').on("change",updateShowSendGroups);
    updateShowSendGroups();

    function updateShowMailTypes () {
        if ($('select[name=eme_mail_type]').val() == 'attendees' || $('select[name=eme_mail_type]').val() == 'bookings') {
            $('tr#eme_pending_approved_row').show();
            $('tr#eme_only_unpaid_row').show();
            if ($('select[name=eme_mail_type]').val() == 'attendees') {
                $('span#span_unpaid_attendees').show();
                $('span#span_unpaid_bookings').hide();
            } else {
                $('span#span_unpaid_attendees').hide();
                $('span#span_unpaid_bookings').show();
            }
            $('tr#eme_exclude_registered_row').hide();
            $('tr#eme_rsvp_status_row').show();
        } else {
            $('tr#eme_pending_approved_row').hide();
            $('tr#eme_only_unpaid_row').hide();
            if ($('select[name=eme_mail_type]').val() != '') {
                $('tr#eme_exclude_registered_row').show();
            } else {
                $('tr#eme_exclude_registered_row').hide();
            }
            $('tr#eme_rsvp_status_row').hide();
        }
        if ($('select[name=eme_mail_type]').val() == 'people_and_groups') {
            $('tr#eme_people_row').show();
            $('tr#eme_groups_row').show();
            $('tr#eme_members_row1').show();
            $('tr#eme_members_row2').show();
            $('tr#eme_members_row3').show();
        } else {
            $('tr#eme_people_row').hide();
            $('tr#eme_groups_row').hide();
            $('tr#eme_members_row1').hide();
            $('tr#eme_members_row2').hide();
            $('tr#eme_members_row3').hide();
        }
    }
    $('select[name=eme_mail_type]').on("change",updateShowMailTypes);
    updateShowMailTypes();

    $('.eme_select2_events_class').select2({
        ajax: {
            url: ajaxurl+'?action=eme_events_select2',
            dataType: 'json',
            delay: 1000,
            data: function (params) {
                var search_all=0;
                if ($('#eventsearch_all').is(':checked')) {
                    search_all=1;
                }
                return {
                    q: params.term, // search term
                    search_all: search_all,
                    eme_admin_nonce: emeadmin.translate_adminnonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                return {
                    results: data.Records,
                };
            },
            cache: true
        },
        placeholder: ememails.translate_selectevents,
        width: '90%'
    });

    //Prepare jtable plugin
    jQuery('#MailingReportTableContainer').jtable({
        title: ememails.translate_mailingreport,
        paging: true,
        sorting: true,
        jqueryuiTheme: true,
        defaultSorting: '',
        selecting: false, //Enable selecting
        multiselect: false, //Allow multiple selecting
        selectingCheckboxes: false, //Show checkboxes on first column
        selectOnRowClick: false, //Enable this to only select using checkboxes
        actions: {
            listAction: ajaxurl+'?action=eme_mailingreport_list&eme_admin_nonce='+emeadmin.translate_adminnonce+'&mailing_id='+$_GET['id'],
        },
        fields: {
            receiveremail: {
                title: ememails.translate_email,
            },
            receivername: {
                title: ememails.translate_name,
            },
            status: {
                title: ememails.translate_status,
            },
            sent_datetime: {
                title: ememails.translate_sentdatetime,
                sorting: true
            },
            first_read_on: {
                title: ememails.translate_first_read_on,
                sorting: true
            },
            last_read_on: {
                title: ememails.translate_last_read_on,
                sorting: true
            },
            read_count: {
                title: ememails.translate_readcount,
            },
            error_msg: {
                title: ememails.translate_errormessage,
                visibility: 'hidden',
                sorting: false
            },
            action: {
                title: ememails.translate_action,
                sorting: false
            }
        }
    });
    if ($('#MailingReportTableContainer').length) {
        $('#MailingReportTableContainer').jtable('load');
    }

    // Re-load records when user click 'load records' button.
    $('#ReportLoadRecordsButton').on("click",function (e) {
        e.preventDefault();
        $('#MailingReportTableContainer').jtable('load', {
            search_name: $('#search_name').val()
        });
        // return false to make sure the real form doesn't submit
        return false;
    });

    $('#eventmail_attach_button').on("click",function(e) {
        e.preventDefault();
        var custom_uploader = wp.media({
            title: ememails.translate_addattachments,
            button: {
                text: ememails.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            var selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // var attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#eventmail_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_eventmail_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_eventmail_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_eventmail_attach_ids').val(tmp_ids_val);
                $('#eventmail_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_eventmail_attach_ids').val() != '') {
        $('#eventmail_remove_attach_button').show();
    } else {
        $('#eventmail_remove_attach_button').hide();
    }
    $('#eventmail_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#eventmail_attach_links').html('');
        $('#eme_eventmail_attach_ids').val('');
        $('#eventmail_remove_attach_button').hide();
    });

    $('#generic_attach_button').on("click",function(e) {
        e.preventDefault();
        var custom_uploader = wp.media({
            title: ememails.translate_addattachments,
            button: {
                text: ememails.translate_addattachments
            },
            multiple: true  // Set this to true to allow multiple files to be selected
        }).on('select', function() {
            var selection = custom_uploader.state().get('selection');
            // using map is not really needed, but this way we can reuse the code if multiple=true
            // var attachment = custom_uploader.state().get('selection').first().toJSON();
            selection.map( function(attach) {
                attachment = attach.toJSON();
                $('#generic_attach_links').append("<a target='_blank' href='"+attachment.url+"'>"+attachment.title+"</a><br />");
                if ($('#eme_generic_attach_ids').val() != '') {
                    tmp_ids_arr=$('#eme_generic_attach_ids').val().split(',');
                } else {
                    tmp_ids_arr=[];
                }
                tmp_ids_arr.push(attachment.id);
                tmp_ids_val=tmp_ids_arr.join(',');
                $('#eme_generic_attach_ids').val(tmp_ids_val);
                $('#generic_remove_attach_button').show();
            });
        }).open();
    });
    if ($('#eme_generic_attach_ids').val() != '') {
        $('#generic_remove_attach_button').show();
    } else {
        $('#generic_remove_attach_button').hide();
    }
    $('#generic_remove_attach_button').on("click",function(e) {
        e.preventDefault();
        $('#generic_attach_links').html('');
        $('#eme_generic_attach_ids').val('');
        $('#generic_remove_attach_button').hide();
    });

    if ($('#eventmail_startdate').length) {
        $('#eventmail_startdate').fdatepicker({
            todayButton: new Date(),
            clearButton: true,
            timepicker: true,
            minutesStep: parseInt(ememails.translate_minutesStep),
            language: ememails.translate_flanguage,
            firstDay: parseInt(ememails.translate_firstDayOfWeek),
            altFieldDateFormat: 'Y-m-d H:i:00',
            dateFormat: ememails.translate_fdateformat,
            timeFormat: ememails.translate_ftimeformat,
            onSelect: function(formattedDate,date,inst) {
                if (!Array.isArray(date)) {
                    $('#eventmail-specificdates').text("");
                    $('#eventmailButton').text(ememails.translate_sendmail);
                } else {
                    $('#eventmail-specificdates').html('<br />'+ememails.translate_selecteddates+'<br />');
                    $.each(date, function( index, value ) {
                        date_formatted = inst.formatDate(ememails.translate_fdatetimeformat,value);
                        $('#eventmail-specificdates').append(date_formatted+'<br />');
                    });
                    $('#eventmailButton').text(ememails.translate_planmail);
                }
            }
        });
    }
    if ($('#genericmail_startdate').length) {
        $('#genericmail_startdate').fdatepicker({
            todayButton: new Date(),
            clearButton: true,
            timepicker: true,
            minutesStep: parseInt(ememails.translate_minutesStep),
            language: ememails.translate_flanguage,
            firstDay: parseInt(ememails.translate_firstDayOfWeek),
            altFieldDateFormat: 'Y-m-d H:i:00',
            dateFormat: ememails.translate_fdateformat,
            timeFormat: ememails.translate_ftimeformat,
            onSelect: function(formattedDate,date,inst) {
                if (!Array.isArray(date)) {
                    $('#genericmail-specificdates').text("");
                    $('#genericmailButton').text(ememails.translate_sendmail);
                } else {
                    $('#genericmail-specificdates').html('<br />'+ememails.translate_selecteddates+'<br />');
                    $.each(date, function( index, value ) {
                        date_formatted = inst.formatDate(ememails.translate_fdatetimeformat,value);
                        $('#genericmail-specificdates').append(date_formatted+'<br />');
                    });
                    $('#genericmailButton').text(ememails.translate_planmail);
                }
            }
        });
    }
});
