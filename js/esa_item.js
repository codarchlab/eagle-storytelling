/**
 * @package 	enhanced-storytelling
 * @subpackage	Search in Datasources | esa_item Javascript
 * @link 		https://github.com/dainst/wordpress-storytelling
 * @author 		Philipp Franck
 *
 * 
 * Some Javascript functionality of the esa_items
 * 
 *
 */

(function ($) {
    $.fn.esa_item = function(options) {
                
        return this.each(function() {
            /*
            var settings = $.extend({
            }, options);
            */
            var this_esa_item = this;
            this_esa_item.map = false;
            var is_in_editor = (typeof window.tinymce !== 'undefined');
            
            //console.log('init', this_esa_item);

            var collapsible = jQuery(this_esa_item).hasClass('esa_item_no_collapse');

            $(this_esa_item).on('mouseenter', function() {
                jQuery(this_esa_item).find('.esa_item_tools').fadeIn('slow');
                if (!collapsible) {
                    jQuery(this_esa_item).find('.esa_item_resizebar').fadeIn('slow');
                }
            });

            $(this_esa_item).on('mouseleave', function() {
                jQuery(this_esa_item).find('.esa_item_tools').fadeOut('slow');
                if (!collapsible) {
                    jQuery(this_esa_item).find('.esa_item_resizebar').fadeOut('slow');
                }
            });


            $(this_esa_item).on('click', '.esa_item_resizebar, .esa_item_tools_expand', function() {

                if (!collapsible) {
                    return;
                }

                var thisItem = $(this_esa_item);
                thisItem.toggleClass('esa_item_collapsed');

                // on Expand

                function reArrangeMediaboxes() {
                    var mediaBoxes = thisItem.find('.esa_item_media_box');
                    //console.log('rearragane', mediaBoxes);

                    if (mediaBoxes.length === 0) {
                        return;
                    }

                    var itmWidth = thisItem.width();

                    thisItem.removeClass('esa_item_media_size_1');
                    thisItem.removeClass('esa_item_media_size_2');
                    thisItem.removeClass('esa_item_media_size_3');
                    thisItem.removeClass('esa_item_media_size_4');

                    var b = Math.min(mediaBoxes.length, 4);
                    var p = Math.min(Math.floor(itmWidth / 150), 4);
                    var s = Math.min(b,  p);
                    //console.log(itmWidth, b, p, s);

                    thisItem.addClass('esa_item_media_size_' + s);
                }

                if (!thisItem.hasClass('esa_item_collapsed')) {

                    //load fullres images of not allready  (because good guy me always tries to save some traffic)
                    $.each(thisItem.find('.esa_item_fullres'), function(i, item) {
                        if (typeof $(item).src === 'undefined') {
                            if (!$(item).attr('src')) {
                                $(item).on('error', function() {
                                    $(item).parents('.esa_item_media_box').remove();
                                    reArrangeMediaboxes();
                                });
                                $(item).attr('src', $(item).data('fullsize'));
                            }
                        }
                        //console.log(jQuery(item).src, jQuery(item).data('fullsize'));
                    });

                    reArrangeMediaboxes();


                }

                // map
                if (this_esa_item.map) {
                    this_esa_item.map.invalidateSize();
                    //console.log('invalidateSize', this_esa_item.map);
                }

            });
            
            // thickbox
            if (!is_in_editor) {
                $(this_esa_item).on('click', '.esa_item_media_box', function() {
                    var thickboxObj = $(this).find('.esa_thickbox');

                    var thickboxId = thickboxObj.attr('id');
                    if (!thickboxObj) {
                        return;
                    }
                    var fullsizeObj = $(this).find('.esa_item_fullres');
                    var fullsize = fullsizeObj.data('fullsize');

                    function esa_tb() {
                        if (!thickboxId) {
                            return;
                        }
                        var width = Math.min($(window).width() - 55, fullsizeObj.get(0).naturalWidth);
                        var height = Math.min($(window).height() - 55, fullsizeObj.get(0).naturalHeight);
                        var title = $(this_esa_item).find('h4').text();
                        tb_show(title, '#TB_inline?inlineId=' + thickboxId + '&width=' + width + '&height=' + height);
                    }
                    fullsizeObj.load(esa_tb);
                    if (!fullsizeObj.attr('src')) {
                        fullsizeObj.attr('src', fullsize);
                    } else {
                        esa_tb();
                    }

                });
            }
            
            // load leaflet if needed
            if (!is_in_editor && $(this_esa_item).find('.esa_item_map').length) {

                // draw maps
                $(this_esa_item).find('.esa_item_map').each(function(k, mapDiv) {

                    var mapId = $(mapDiv).attr('id');
                    var lat   = parseFloat($(mapDiv).data('latitude'));
                    var long  = parseFloat($(mapDiv).data('longitude'));
                    var mapType = $(mapDiv).data('layer') || "osm";
                    var shape  = $(mapDiv).data('shape');

                    window.leaflet_document = $(mapDiv).context.ownerDocument;

                    var map = this_esa_item.map = L.map(mapId).setView([lat, long], 13);

                    L.tileLayer(esa_map_layers[mapType].url, esa_map_layers[mapType].opts).addTo(map);

                    if (typeof shape !== "undefined") {
                        //console.log(shape);
                        var poly = L.polygon(shape).addTo(map);
                        map.fitBounds(poly.getBounds());
                    } else {
                        L.marker([lat, long]).addTo(map);
                    }

                })

            }
        })
    };
}(jQuery));

var esa_map_layers = {
    "osm": {
        "url":  'https://{s}.tile.osm.org/{z}/{x}/{y}.png',
        "opts": {
            attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
        }
    },
    "stamen-toner": {
        "url":  'https://stamen-tiles-{s}.a.ssl.fastly.net/toner/{z}/{x}/{y}.{ext}',
        "opts": {
            attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            subdomains: 'abcd',
            minZoom: 0,
            maxZoom: 20,
            ext: 'png'
        }
    },
    "stamen-watercolor": {
        "url":  'https://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.{ext}',
        "opts": {
            attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            subdomains: 'abcd',
            minZoom: 0,
            maxZoom: 20,
            ext: 'png'
        }
    },
    "stamen-terrain": {
        "url":  'https://stamen-tiles-{s}.a.ssl.fastly.net/terrain/{z}/{x}/{y}.{ext}',
        "opts": {
            attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            subdomains: 'abcd',
            minZoom: 0,
            maxZoom: 20,
            ext: 'png'
        }
    },
    "landsat": {
        "url":   "https://map1.vis.earthdata.nasa.gov/wmts-webmerc/{layer}/default/{time}/{tileMatrixSet}/{z}/{y}/{x}.{format}",
        "opts": {
            layer: "MODIS_Terra_CorrectedReflectance_TrueColor",
            tileMatrixSet: "GoogleMapsCompatible_Level9",
            maxZoom: 18,
            maxNativeZoom: 9,
            time: "2015-08-31",
            tileSize: 256,
            subdomains: "abc",
            noWrap: true,
            continuousWorld: true,
            bounds: [[-85.0511287776, -179.999999975], [85.0511287776, 179.999999975]],
            format: "jpg",
            attribution: "NASA MODIS"
        }
    }
};

(function ($) {
    $.fn.esa_items_overview_map = function(options) {

        var clusterOptions = {
            maxClusterRadius: 30
        };

        return this.each(function(counter, mapDiv) {

            var mapId = $(mapDiv).attr('id');
            var map = L.map(mapDiv).setView([12.483333, 41.883333], 13);
            var mapType = (typeof esa_map_layers[$(mapDiv).data("type")] === "undefined") ? "osm" : $(mapDiv).data("type");

            L.tileLayer(esa_map_layers[mapType].url, esa_map_layers[mapType].opts).addTo(map);

            $.ajax({
                url: esa.ajax_url,
                type: 'post',
                data: {
                    action: 'esa_get_overview_map',
                    display: $(mapDiv).data('display')
                },
                success: function(response) {
                    response = JSON.parse(response);
                    if ((response.length === 0) || (!response.length)) {
                        map.remove();
                        $(mapDiv).hide();
                        return;
                    }
                    var markers = response.length > 35
                        ? L.markerClusterGroup(clusterOptions)
                        : L.featureGroup();
                    $.each(response, function(k, item) {
                        L.circleMarker(
                            [parseFloat(item.latitude), parseFloat(item.longitude)],
                            {
                                'fillColor': '#004242',
                                'color': '#004242',
                                'radius': 8,
                                'weight': (item.selected === "1") ? 4 : 2,
                                'opacity': (item.selected === "1") ? 1 : 0.8,
                                'fillOpacity': (item.selected === "1") ? 0.8 : 0.6
                            })
                            .bindPopup(item.textbox)
                            .addTo(markers);
                    });
                    map.addLayer(markers);

                    map.fitBounds(markers.getBounds());
                },
                error: function(exception) {
                    console.log(exception);
                    map.remove();
                    $(mapDiv).hide();
                }
            });

        })
    };
}(jQuery));

var scriptLoadingTimeout; // timeout id is a global variable

jQuery(document).ready(function($){
    window.tagBox && window.tagBox.init();
    scriptLoadingTimeout = window.setTimeout(function() {
        console.warn("Leaflet could ne be loaded.");
        $('.esa_item_map').addClass("esa_map_error");
        $('.esa_items_overview_map').addClass("esa_map_error");
    }, 2000);
    $.getScript("https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.3.4/leaflet.js", function() {
        $.getScript("https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/leaflet.markercluster.js", function() {
            $("head").append($("<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/MarkerCluster.css' type='text/css' media='screen' />"));
            $('.esa_item').esa_item();
            $('.esa_items_overview_map').esa_items_overview_map();
            window.clearTimeout(scriptLoadingTimeout);
        });
    });
});


