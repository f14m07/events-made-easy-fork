jQuery(document).ready( function($) {
    function eme_tasklastname_clearable() {
        if ($('input[name=task_lastname]').val()=='') {
            $('input[name=task_lastname]').attr('readonly', false).removeClass('clearable');
            $('input[name=task_firstname]').val('').attr('readonly', false);
            $('input[name=task_address1]').val('').attr('readonly', false);
            $('input[name=task_address2]').val('').attr('readonly', false);
            $('input[name=task_city]').val('').attr('readonly', false);
            $('input[name=task_state]').val('').attr('readonly', false);
            $('input[name=task_zip]').val('').attr('readonly', false);
            $('input[name=task_country]').val('').attr('readonly', false);
            $('input[name=task_email]').val('').attr('readonly', false);
            $('input[name=task_phone]').val('').attr('readonly', false);
        }
        if ($('input[name=task_lastname]').val()!='') {
            $('input[name=task_lastname]').addClass('clearable x');
        }
    }

    // for autocomplete to work, the element needs to exist, otherwise JS errors occur
    // we check for that using length
    if ($("input[name=lastname]").length) {
        let frontend_lastname_timeout; // Declare a variable to hold the timeout ID
        $("input[name=lastname]").on("input", function() {
            clearTimeout(frontend_lastname_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                let requestData = inputField.parents('form:first').serializeArray();
                requestData.push({name: 'eme_ajax_action', value: 'rsvp_autocomplete_people'});
                frontend_lastname_timeout = setTimeout(function() {
                    $.post(self.location.href, requestData, function(data) {
                        suggestions = $("<div class='eme-autocomplete-suggestions'></div>");

                        $.each(data, function(index, item) {
                            suggestions.append(
                                $("<div class='eme-autocomplete-suggestion'></div>")
                                .html("<strong>" + eme_htmlDecode(item.lastname) + ' ' + eme_htmlDecode(item.firstname) + "</strong><br><small>" + eme_htmlDecode(item.email) + ' - ' + eme_htmlDecode(item.phone) + "</small>")
                                .on("click", function(e) {
                                    e.preventDefault();
                                    $('input[name=lastname]').val(item.lastname).prop('readonly', true);
                                    $('input[name=firstname]').val(item.firstname).prop('readonly', true);
                                    $('input[name=address1]').val(item.address1).prop('readonly', true).prop('required',false);
                                    $('input[name=address2]').val(item.address2).prop('readonly', true).prop('required',false);
                                    $('input[name=city]').val(item.city).prop('readonly', true).prop('required',false);
                                    $('input[name=state]').val(item.state).prop('readonly', true).prop('required',false);
                                    $('input[name=zip]').val(item.zip).prop('readonly', true).prop('required',false);
                                    $('input[name=country]').val(item.country).prop('readonly', true).prop('required',false);
                                    $('input[name=email]').val(item.email).prop('readonly', true).prop('required',false);
                                    $('input[name=phone]').val(item.phone).prop('readonly', true).prop('required',false);
                                    $('input[name=wp_id]').val(item.wp_id).prop('readonly', true);
                                    $('input[name=person_id]').val(item.person_id).prop('readonly', true);
                                })
                            );
                        });
                        $(".eme-autocomplete-suggestions").remove();
                        inputField.after(suggestions);
                    }, "json");
                }, 500); // Delay of 0.5 second
            }
        });

        $(document).on("click", function() {
            $(".eme-autocomplete-suggestions").remove();
        });

        // if this js gets loaded, the lastname is always clearable, so call those functions
        $('input[name=lastname]').on("change",eme_lastname_clearable);
        eme_lastname_clearable();
    }

    if ($("input[name=task_lastname]").length) {
        let frontend_tasklastname_timeout; // Declare a variable to hold the timeout ID
        $("input[name=task_lastname]").on("input", function() {
            clearTimeout(frontend_tasklastname_timeout); // Clear the previous timeout
            let suggestions;
            let inputField = $(this);
            let inputValue = inputField.val();
            $(".eme-autocomplete-suggestions").remove();
            if (inputValue.length >= 2) {
                let requestData = inputField.parents('form:first').serializeArray();
                requestData.push({name: 'eme_ajax_action', value: 'task_autocomplete_people'});
                frontend_tasklastname_timeout = setTimeout(function() {
                    $.post(self.location.href, requestData, function(data) {
                        suggestions = $("<div class='eme-autocomplete-suggestions'></div>");

                        $.each(data, function(index, item) {
                            suggestions.append(
                                $("<div class='eme-autocomplete-suggestion'></div>")
                                .html("<strong>" + eme_htmlDecode(item.lastname) + ' ' + eme_htmlDecode(item.firstname) + "</strong><br><small>" + eme_htmlDecode(item.email) + "</small>")
                                .on("click", function(e) {
                                    e.preventDefault();
                                    $('input[name=task_lastname]').val(eme_htmlDecode(item.lastname)).attr('readonly', true);
                                    $('input[name=task_firstname]').val(eme_htmlDecode(item.firstname)).attr('readonly', true);
                                    $('input[name=task_email]').val(eme_htmlDecode(item.email)).attr('readonly', true);
                                    $('input[name=task_phone]').val(eme_htmlDecode(item.phone)).attr('readonly', true);
                                })
                            );
                        });
                        $('.eme-autocomplete-suggestions').remove();
                        inputField.after(suggestions);
                    }, "json");
                }, 500); // Delay of 0.5 second
            }
        });

        $(document).on("click", function() {
            $(".eme-autocomplete-suggestions").remove();
        });

        // if this js gets loaded, the lastname is always clearable, so call those functions
        $('input[name=task_lastname]').on("change",eme_tasklastname_clearable);
        eme_tasklastname_clearable();
    }
});
