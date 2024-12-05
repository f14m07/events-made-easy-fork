// to avoid the leaflet error 'Map container is already initialized' we use a global var to create the map later on
let map;
// create the tile layer with correct attribution
let osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
let osmAttrib='Map data &copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
let osm = new L.TileLayer(osmUrl, {attribution: osmAttrib});

function eme_displayAddress(ignore_coord){
    if (jQuery('input#location_name').length) {
        eventLocation = jQuery('input#location_name').val() || '';
        eventAddress1 = jQuery('input#location_address1').val() || '';
        eventAddress2 = jQuery('input#location_address2').val() || '';
        eventCity = jQuery('input#location_city').val() || '';
        eventState = jQuery('input#location_state').val() || '';
        eventZip = jQuery('input#location_zip').val() || '';
        eventCountry = jQuery('input#location_country').val() || '';
        map_icon = jQuery('input#eme_loc_prop_map_icon').val() || '';
        if (ignore_coord && !jQuery('input#eme_loc_prop_override_loc').is(':checked')) {
            eventLat = 0;
            eventLong = 0;
        } else {
            eventLat = jQuery('input#location_latitude').val() || 0;
            eventLong = jQuery('input#location_longitude').val() || 0;
        }
        loadMapLatLong(eventLocation, eventAddress1, eventAddress2,eventCity,eventState,eventZip,eventCountry, eventLat,eventLong,map_icon);
    }
}

function eme_SelectdisplayAddress(){
    if (jQuery('input[name="location-select-name"]').length) {
        eventLocation = jQuery('input[name="location-select-name"]').val() || '';
        eventAddress1 = jQuery('input[name="location-select-address1"]').val() || '';
        eventAddress2 = jQuery('input[name="location-select-address2"]').val() || '';
        eventCity = jQuery('input[name="location-select-city"]').val() || '';
        eventState = jQuery('input[name="location-select-state"]').val() || '';
        eventZip = jQuery('input[name="location-select-zip"]').val() || '';
        eventCountry = jQuery('input[name="location-select-country"]').val() || '';
        eventLat = jQuery('input[name="location-select-latitude"]').val() || 0;
        eventLong = jQuery('input[name="location-select-longitude"]').val() || 0;
        map_icon = jQuery('input#eme_loc_prop_map_icon').val() || '';
        loadMapLatLong(eventLocation, eventAddress1, eventAddress2,eventCity,eventState,eventZip,eventCountry, eventLat,eventLong,map_icon);
    }
}
function loadMap(loc_name, address1, address2, city, state, zip, country, map_icon) {
    if (map_icon === undefined) {
        map_icon = '';
    }
    let myOptions = {
        zoom: 13,
        scrollWheelZoom: emeeditmaps.translate_map_zooming,
        doubleClickZoom: false
    }
    // to avoid the leaflet error 'Map container is already initialized'
    if (map) {
        map.off();
        map.remove();
    }
    // first we show the map, so leaflet can check the size
    jQuery('#eme-edit-location-map').show();
    map = L.map('eme-edit-location-map', myOptions);
    map.addLayer(osm);
    // to account for a hidden layer where leaflet doesn't read the dimensions correctly, we call invalidateSize
    //map.invalidateSize(false);
    let searchKey_arr = [];
    if (address1) {
        searchKey_arr.push(address1);
    }
    if (address2) {
        searchKey_arr.push(address2);
    }
    if (city) {
        searchKey_arr.push(city);
    }
    if (state) {
        searchKey_arr.push(state);
    }
    if (zip) {
        searchKey_arr.push(zip);
    }
    if (country) {
        searchKey_arr.push(country);
    }
    searchKey = searchKey_arr.join(', ');
    if (!searchKey && !(jQuery('input#eme_loc_prop_online_only').prop('checked')) ) {
        searchKey=loc_name;
    }

    if (searchKey) {
        let geocode_url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1';
        jQuery.getJSON( geocode_url, { 'q': searchKey}, function(data) {
            if (data.length===0) {
                jQuery('#eme-edit-location-map').hide();
            } else {
                map.panTo([data[0].lat, data[0].lon]);
                let myIcon;
                if (map_icon!='') {
                    myIcon = L.icon({iconUrl: map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
                } else if (emeeditmaps.translate_default_map_icon!='') {
                    myIcon = L.icon({iconUrl: emeeditmaps.translate_default_map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
                } else {
                    myIcon = new L.Icon.Default();
                }
                let marker = L.marker([data[0].lat, data[0].lon], {icon: myIcon}).addTo(map);
                let pop_content='<div class=\"eme-location-balloon\"><strong>' + loc_name +'</strong><p>' + address1 + ' ' + address2 + '<br />' + city + ' ' + state + ' ' + zip + ' ' + country + '</p></div>';
                marker.bindPopup(pop_content).openPopup();
                jQuery('input#location_latitude').val(data[0].lat);
                jQuery('input#location_longitude').val(data[0].lon);
                jQuery('div#eme-location-changed').show();
                jQuery('#eme-edit-location-map').show();
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            jQuery('#eme-edit-location-map').hide();
        });
    } else {
        jQuery('#eme-edit-location-map').hide();
        jQuery('div#eme-location-changed').hide();
    }

}

function loadMapLatLong(loc_name, address1, address2, city, state, zip, country, lat, lng, map_icon) {
    if (lat === undefined) {
        lat = 0;
    }
    if (lng === undefined) {
        lng = 0;
    }
    if (map_icon === undefined) {
        map_icon = '';
    }

    if (lat != 0 && lng != 0) {
        let latlng = L.latLng(lat, lng);
        let myOptions = {
            zoom: 13,
            center: latlng,
            scrollWheelZoom: emeeditmaps.translate_map_zooming,
            doubleClickZoom: false
        }
        // to avoid the leaflet error 'Map container is already initialized'
        if (map) {
            map.off();
            map.remove();
        }
        // first we show the map, so leaflet can check the size
        jQuery('#eme-edit-location-map').show();
        map = L.map('eme-edit-location-map', myOptions);
        map.addLayer(osm);
        // to account for a hidden layer where leaflet doesn't read the dimensions correctly, we call invalidateSize
        //map.invalidateSize(false);
        //map.panTo(latlng);
        let myIcon;
        if (map_icon!='') {
            myIcon = L.icon({iconUrl: map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
        } else if (emeeditmaps.translate_default_map_icon!='') {
            myIcon = L.icon({iconUrl: emeeditmaps.translate_default_map_icon, iconSize:[32,32],iconAnchor:[16,32],popupAnchor:[1,-28],tooltipAnchor:[16,-24]});
        } else {
            myIcon = new L.Icon.Default();
        }
        let marker = L.marker(latlng, {icon: myIcon}).addTo(map);
        let pop_content='<div class=\"eme-location-balloon\"><strong>' + loc_name +'</strong><p>' + address1 + ' ' + address2 + '<br />' + city + ' ' + state + ' ' + zip + ' ' + country + '</p></div>';
        marker.bindPopup(pop_content).openPopup();
    } else {
        loadMap(loc_name, address1, address2, city, state, zip, country, map_icon);
    }
}

jQuery(document).ready(function($) {
    function updateOnlineOnly() {
        if ($('input#eme_loc_prop_online_only').prop('checked')) {
            $('input#location_address1').val('').attr('readonly', true);
            $('input#location_address2').val('').attr('readonly', true);
            $('input#location_city').val('').attr('readonly', true);
            $('input#location_state').val('').attr('readonly', true);
            $('input#location_zip').val('').attr('readonly', true);
            $('input#location_country').val('').attr('readonly', true);
            $('input#eme_loc_prop_map_icon').val('').attr('readonly', true);
            $('input#location_latitude').val('').attr('readonly', true);
            $('input#location_longitude').val('').attr('readonly', true);
            $('#eme-edit-location-map').hide();
            $("input#location_url").prop('required',true);
        } else {
            $('input#location_address1').attr('readonly', false);
            $('input#location_address2').attr('readonly', false);
            $('input#location_city').attr('readonly', false);
            $('input#location_state').attr('readonly', false);
            $('input#location_zip').attr('readonly', false);
            $('input#location_country').attr('readonly', false);
            $('input#eme_loc_prop_map_icon').attr('readonly', false);
            $('input#location_latitude').attr('readonly', false);
            $('input#location_longitude').attr('readonly', false);
            $("input#location_url").prop('required',false);
            eme_displayAddress(0);
        }
    }
    function updateOverrideLoc() {
        if ($('input#eme_loc_prop_override_loc').is(':checked')) {
            $('input#location_latitude').attr('readonly', false);
            $('input#location_longitude').attr('readonly', false);
        } else {
            $('input#location_latitude').attr('readonly', true);
            $('input#location_longitude').attr('readonly', true);
        }
    }
    $('#eme-edit-location-map').hide();
    eme_displayAddress(0);
    $('input[name="eme_loc_prop_map_icon"]').on("change",function(){
        eme_displayAddress(0);
    });
    // the location name change only needs to be trapped when not in frontend form
    // in the frontend form, this is already handled
    if (!$('form[name=eme-fs-form]').length) {
        $('input#location_name').on("change",function(){
            eme_displayAddress(0);
        });
    }
    $('input#location_city').on("change",function(){
        eme_displayAddress(1);
    });
    $('input#location_state').on("change",function(){
        eme_displayAddress(1);
    });
    $('input#location_zip').on("change",function(){
        eme_displayAddress(1);
    });
    $('input#location_country').on("change",function(){
        eme_displayAddress(1);
    });
    $('input#location_address1').on("change",function(){
        eme_displayAddress(1);
    });
    $('input#location_address2').on("change",function(){
        eme_displayAddress(1);
    });
    $('input#location_latitude').on("change",function(){
        eme_displayAddress(0);
    });
    $('input#location_longitude').on("change",function(){
        eme_displayAddress(0);
    });
    $('input#eme_loc_prop_online_only').on("change",updateOnlineOnly);
    updateOnlineOnly();
    $('input#eme_loc_prop_override_loc').on("change",updateOverrideLoc);
    updateOverrideLoc();
}); 
