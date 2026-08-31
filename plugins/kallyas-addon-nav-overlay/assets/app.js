(function($){

	$(document).ready(function() {

		var zn_nav_overlay__isActive = false;

		var zn_nav_overlay = function( scope ) {

			var _overlayMenuHolder = $('#zn-nav-overlay'),
				_mainMenu = $('#main-menu.mainnav--overlay > ul');

			if( _mainMenu.length > 0  ){

				var _body = $('body'),
					_pageWrapper = $('#page_wrapper'),
					_responsiveTrigger = $('#zn-res-trigger'),
					_clonedMenu = _mainMenu.clone().attr({id:"zn-overlay-menu", "class":"znNavOvr-menu nav-with-smooth-scroll"}),
					slidingOptions = {};

				// Slide Options
				slidingOptions.duration = 500;
				slidingOptions.easing = 'easeInOutExpo';

				var closeMenu = function(){
					_overlayMenuHolder.removeClass('is-active');
					_responsiveTrigger.removeClass('is-active');
					setTimeout(function(){
						_body.css('overflow','');
					}, 700);
					_clonedMenu.find('ul.sub-menu.is-visible, .zn_mega_container.is-visible').slideUp('fast', function(){
						$(this).removeClass('is-visible');
						$(this).closest('.znNavOvr-menuItemActive').removeClass('znNavOvr-menuItemActive');
					});
				};

				var openMenu = function(){
					_overlayMenuHolder.addClass('is-active');
					_responsiveTrigger.addClass('is-active');
					_body.css('overflow','hidden');
				};

				var toggleOpen = function(){
					if( _overlayMenuHolder.hasClass('is-active') ){
						closeMenu();
					}
					else {
						openMenu();
					}
				};

				var startOverlayMenu = function()
				{
					_clonedMenu
						.appendTo( _overlayMenuHolder.find('.znNavOvr-menuWrapper') )
						.wrap('<div class="znNavOvr-menuWrapper-inner"></div>');

					// Remove Smart area Mega menus
					_clonedMenu.find('div.zn_mega_container.zn-megaMenuSmartArea').remove();

					// TEMP
					// openMenu();

					// Open Levels
					_clonedMenu.find('.menu-item-has-children > a').on('click',function(e){
						e.preventDefault();

						var $t = $(this),
							$parent = $t.parent(),
							$item_submenu = $t.siblings('ul.sub-menu, .zn_mega_container');
							$parentSiblings = $t.parents('.menu-item-has-children').siblings('.menu-item-has-children').find('ul.sub-menu.is-visible, .zn_mega_container.is-visible');

						// Close all other Submenus
						parentSiblings_slideOptions = slidingOptions;
						parentSiblings_slideOptions.complete = function(){
							$(this).removeClass('is-visible');
							$(this).closest('.znNavOvr-menuItemActive').removeClass('znNavOvr-menuItemActive');
						};
						$parentSiblings.slideUp(parentSiblings_slideOptions);

						// Open Submenu
						$parent.toggleClass('znNavOvr-menuItemActive');

						siblings_slideOptions = slidingOptions;
						siblings_slideOptions.complete = function(){
							$(this).toggleClass('is-visible');
						};
						$item_submenu.slideToggle(siblings_slideOptions);

						// Add Depth Class
						_clonedMenu.removeClass('is-depth-0 is-depth-1 is-depth-2 is-depth-3').addClass('is-depth-' + $t.parents('.znNavOvr-menuItemActive').length );
					});

					_clonedMenu.find(".main-menu-link[href*='#']:not([href='#']):only-child").on('click',function(e){
						closeMenu();
					});

					// Open Menu Trigger
					_responsiveTrigger.on('click', function(e){
						e.preventDefault();
						toggleOpen();
					});

					// Close Button
					$('#znNavOvr-close').on('click', function(e){
						e.preventDefault();
						toggleOpen();
					});

					// Close on ESC
					$(document).on('keyup', function(e){
						if ( e.keyCode == 27 && _overlayMenuHolder.hasClass('is-active') ) {
							closeMenu();
						}
					});
				};

				// MAIN TRIGGER FOR ACTIVATING THE RESPONSIVE MENU
				$( window ).on( 'debouncedresize' , function(){
					if ( $(window).width() <= ZnThemeAjax.res_menu_trigger ) {
						if ( !zn_nav_overlay__isActive ){
							startOverlayMenu();
							zn_nav_overlay__isActive = true;
						}
					}
					else{
						// WE SHOULD HIDE THE MENU
						closeMenu();
					}
				// Fix for triggering the responsive menu
				}).trigger('debouncedresize');
			}
		};

		if(typeof $.ZnThemeJs != 'undefined')
			$.extend( true, $.ZnThemeJs.prototype.zinit, zn_nav_overlay( $(document) ) );

		$(window).on('ZnNewContent',function(e){
			zn_nav_overlay( e.content );
		});
	});

})(jQuery);