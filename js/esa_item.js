/**
 * @package 	eagle-storytelling
 * @subpackage	Search in Datasources | esa_item Javascript
 * @link 		http://www.eagle-network.eu/stories/
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
            var is_in_editor = (typeof window.tinymce !== 'undefined');
            
            //console.log('init', this_esa_item);
     
            $(this_esa_item).on('mouseenter', function() {
        		jQuery(this_esa_item).find('.esa_item_resizebar').fadeIn('slow');		
            });

            $(this_esa_item).on('mouseleave', function() {
            	jQuery(this_esa_item).find('.esa_item_resizebar').fadeOut('slow');
            });
            
            
            $(this_esa_item).on('mouseenter', '.esa_item_tools a', function(e) {		
            	var tooltip = jQuery('<div>', {
            		class: 'esa_item_tooltip'
            	}).text(jQuery(this).attr('title'));
            	jQuery(this).after(tooltip);
            	
            	var fullwidth = tooltip.width() + 14;
            	tooltip.css({
            		display: 'block',
            		width : '0px'
            	});
            	tooltip.animate({
            		width: fullwidth
            	}, 'slow');
            });

            $(this_esa_item).on('mouseleave', '.esa_item_tools a', function(e) {
            	
            	$(this_esa_item).find('.esa_item_tooltip').toggle('fast', function() {
            		jQuery(this).remove();
            	});
            	
            });
            
            $(this_esa_item).on('click', '.esa_item_resizebar, .esa_item_tools_expand', function() {
            	var thisItem = $(this_esa_item);
            	thisItem.toggleClass('esa_item_collapsed');
            	
            	// map
            	if (mapDiv = thisItem.find('.esa_item_map')[0]) {
            		this_esa_item.map.invalidateSize();
            	}
            	
            	// on Expand
            	var isExpanding = false;
            	if (!thisItem.hasClass('esa_item_collapsed')) {
            		
            		var mediaBoxes = thisItem.find('.esa_item_media_box');
            		//console.log('expansion', mediaBoxes.length);
            		
            		if (mediaBoxes.length == 0) {
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

            		//load fullres images of not allready  (because good guy me always tries to save some traffic)
            		$.each(thisItem.find('.esa_item_fullres'), function(i, item) {
            			if (typeof $(item).src === 'undefined') {
            				$(item).attr('src', $(item).data('fullsize'));
            			}
            			//console.log(jQuery(item).src, jQuery(item).data('fullsize'));

            		});
            		
            		
            	}
            	

            });
            
            // load leaflet if needed           
            
            
            console.log(is_in_editor);
        	if (!is_in_editor && $(this_esa_item).find('.esa_item_map').length) {
        		$.getScript("http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.js")
	       			.done(function() {
	        			
	        			// draw maps
	        			$(this_esa_item).find('.esa_item_map').each(function(k, mapDiv) {

	        				var mapId = $(mapDiv).attr('id');
	        				var lat   = parseFloat($(mapDiv).data('latitude'));
	        				var long  = parseFloat($(mapDiv).data('longitude'));
	        				
	        				var shape  = $(mapDiv).data('shape');
	        			
	        				window.leaflet_document = $(mapDiv).context.ownerDocument;
	        				//console.log(window.leaflet_document);
	        				
	        				var map = this_esa_item.map = L.map(mapId).setView([lat, long], 13);
	        				
	        				L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
	        				    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
	        				}).addTo(map);
	        	
	        				if (typeof shape !== "undefined") {
	        					//console.log(shape);
	        					var poly = L.multiPolygon(shape).addTo(map);
	        					map.fitBounds(poly.getBounds());
	        				} else {
	        					L.marker([lat, long]).addTo(map);
	        				}
	        				
	        			})
	        		})
        		
	        		.fail(function(jqxhr, settings, exception) {
	        			console.log(exception)
	        		});
        	}
        })
    };
}(jQuery));

jQuery(document).ready(function(){
	jQuery('.esa_item').esa_item();	
});