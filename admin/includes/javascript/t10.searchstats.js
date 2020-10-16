/*-----------------------------------------------------------------
*  __           __             ___     _     __
* /\ \__       /\ \__         /\_ \  /' \  /'__`\
* \ \ ,_\   ___\ \ ,_\    __  \//\ \/\_, \/\ \/\ \
*  \ \ \/  / __`\ \ \/  /'__`\  \ \ \/_/\ \ \ \ \ \
*   \ \ \_/\ \L\ \ \ \_/\ \L\.\_ \_\ \_\ \ \ \ \_\ \
*    \ \__\ \____/\ \__\ \__/.\_\/\____\\ \_\ \____/
*     \/__/\/___/  \/__/\/__/\/_/\/____/ \/_/\/___/
*     
* 
* t10.de
* 
******************************************************************
              e46d0505ce4c61e03062d256a9ea109d 
******************************************************************
*
* Date:       07.03.2014
* Author:     total10 UG / Niels Heberlein
*
* total10 UG (haftungsbeschränkt)
* Gänsemarkt 43
* 20354 Hamburg
*
* http://www.t10.de
* info@t10.de
* +49 (0)40 4191 3355
*
* Copyright (c) 2014 total10 UG (haftungsbeschränkt)
* 
* Released under the GNU General Public License
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* http://j.mp/1fLHb9V
*
* ------------------------------------------------------------------
*/

$(function() {
	 	
	// make all these links open in the same (new) window
	$(document).on('click', '.viewNewTab', function (e) {
		e.preventDefault();
		window.open($(this).attr('href'), 't10stats');
	});

	// truncate search
	$('.truncate').click(function (e) {
		e.preventDefault();

		var $this 	= $(this),
			x 		= confirm($this.data('msg'));

		if (x == true)
			$this.closest('form').submit();

	});

	// product search
	var productSearch 	= $('div.productSearch');
	
	if (productSearch.length > 0) {

		// actual search form
		var q 			= $('input[name=q]', productSearch),		// the product search
			query 		= $('input[name=query]', productSearch), 	// the search query from db
			form		= $('form.productSearch', productSearch),	// the actual search form
			LPMInfo 	= $('div.LPMInfo'),			
			go 			= $('.go', productSearch),
			result 		= $('.search-result');


		// toggle search form per row
		$('.toggleSearch').click(function (e) {
			e.preventDefault();

			var $this 		= $(this);

			// find the closest search form container
			var row 		= $this.closest('tr'),
				container 	= $this.closest('td', row);
			
			// make some modifications to the search container
			// keyword change
			query.val($this.data('query'));
			
			// remove all highlights form rows and highlight current
			$('table.stats tr').removeClass('current');
			row.addClass('current');

			// append the search form to the current container and 
			// show it (has display none per default)
			productSearch.appendTo(container).show();

			// trigger the search to update all form parts
			$(document).trigger('submitsearch');

			// give focus to the search field
			q.focus();

		});
		
		// position info layer
		LPMInfo.css({'left': ($(window).width() / 2 - LPMInfo.innerWidth() / 2), 'top': ($(window).height() / 2 - LPMInfo.innerHeight() / 2)});
		
		$('.close', LPMInfo).click(function(e) {
			e.preventDefault();
			LPMInfo.fadeOut('fast');
		});
		
		$('.toggleLPMInfo').click(function (e) {
			e.preventDefault();
			LPMInfo.fadeToggle('fast');
		});

		// checkbox stuff
		$(result).on('change', 'input[type=checkbox]', function (e, x) {
				
			var currentTarget = $(e.target);

			if (currentTarget.hasClass('toggle')) {
				// set the new status of the toggle checkbox to all other checkboxes
				$('input.product').prop('checked', currentTarget.prop('checked'));
			
			} else {
				// check if all checkboxes have been activated
				// if so, activate the toggle checkbox too
				$('input.toggle', result).prop('checked', ($('input.product:not(:checked)', result).length == 0) ? true : false );
			}

		});

		// search button clicked
		go.click(function(e) {
			e.preventDefault();
			$(document).trigger('submitsearch');
		});

		var timer;

		q.keyup(function(e) {

			// make sure not to request the same query twice in a row 
			// and delay request by 250ms
			if (q.data('lastvalue') != q.val())
				timer = window.setTimeout("$(document).trigger('submitsearch')", 250); 

		});

		q.keydown(function(e) {
			// save last value in order to prevent double requests
			q.data('lastvalue', q.val());

			// kill the timer
			clearTimeout(timer);
		});

		// global custom events
		$(document).on('submitsearch', function () {
			
			// do not search if q's value isn't long enough
			if (q.val().length < 3)
				return;

			result.addClass('loading');

			$.ajax({
				url: 		form.attr('action'),
				data: 		form.serialize(),
				dataType: 	'html'
			}).done(function (e) {
				result.html(e).removeClass('loading');
			});

		});

		

		productSearch.on('click', '#submitProductAssignment', function(e) {
			
			e.preventDefault();

			var $this 	= $('#productAssignment', productSearch),
				button 	= $(this);

			// disable button 
			button.prop('disabled', true);
				
			$.ajax({
				  url: 		$this.attr('action'),
				  data: 	$this.serialize(),
				  type: 	'POST',
				  cache: 	false,

				}).done(function (r) {
					
					var msg = $('.message', productSearch);

					msg.removeClass('success fail');
					$('.products-name', productSearch).removeClass('success fail');

					$.each(r, function(i,e) {
						
						var container 			= $('label[data-products-id=' + i + ']', productSearch),
							productsName 		= $('.products-name', container),
							keywordsDisplay 	= $('.keywords', container);

						// global success
						if (r.success == true) {
							msg.addClass('success');

							// success per product
							if (e.success == true) {
								productsName.addClass('success');
								keywordsDisplay.html(e.keywordString);

								// remove the checkbox check from this product.
								// By triggering the click event, we also get rid
								// of the "all" checkmark
								$('input[type=checkbox]', container).click();


							} else {
								productsName.addClass('fail');
								keywordsDisplay.html(e.keywordString);
							}

						} else {
							msg.addClass('fail');
						}

						// set message returned from server
						msg.html(r.msg);

					});

				}).always(function () {
					// and enable the button again after the request is finished
					// whether successful or not
					button.prop('disabled', false);
				});
			
			
		});

	}

});