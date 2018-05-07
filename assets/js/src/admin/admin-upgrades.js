jQuery(document).ready(function ($) {

	const WPUM_Selector_Cache = {
		collection: {},

		get: function (selector, parent, refresh_cache) {
			if (!jQuery) {
				return -1;
			}

			// Set default parent.
			parent = (undefined !== parent) ? parent : undefined;
			refresh_cache = (refresh_cache === true);

			if ((undefined === this.collection[selector]) || refresh_cache) {
				if (undefined !== parent) {
					this.collection[selector] = jQuery(selector, parent);
				} else {
					this.collection[selector] = jQuery(selector);
				}
			}

			return this.collection[selector];
		}
	};

	window.WPUM_Selector_Cache = WPUM_Selector_Cache;

	/**
	 * Updates screen JS
	 */
	var WPUM_Updates = {
		el: {},

		init: function () {
			this.submit();
			this.dismiss_message();
		},

		submit: function () {
			var $self = this,
				step = 1,
				resume_update_step = 0;

			$self.el.main_container = WPUM_Selector_Cache.get('#wpum-db-updates');
			$self.el.update_link = WPUM_Selector_Cache.get('.wpum-update-now', $self.el.main_container);
			$self.el.run_upload_container = WPUM_Selector_Cache.get('.wpum-run-database-update', $self.el.progress_main_container);
			$self.el.progress_main_container = WPUM_Selector_Cache.get('.progress-container', $self.el.main_container);
			$self.el.heading = WPUM_Selector_Cache.get('.update-message', $self.el.progress_main_container);
			$self.el.progress_container = WPUM_Selector_Cache.get('.progress-content', $self.el.progress_main_container);
			$self.el.update_progress_counter = WPUM_Selector_Cache.get($('.wpum-update-progress-count'));

			if ($self.el.main_container.data('resume-update')) {
				$self.el.update_link.addClass('active').hide().removeClass('wpum-hidden');

				if (!$('#wpum-restart-upgrades').length) {
					window.setTimeout(WPUM_Updates.get_db_updates_info, 1000, $self);
				}
			}

			// Bailout.
			if ($self.el.update_link.hasClass('active')) {
				return;
			}

			$self.el.update_link.on('click', '', function (e) {
				e.preventDefault();

				$self.el.run_upload_container.find('.notice').remove();
				$self.el.run_upload_container.append('<div class="notice notice-error non-dismissible wpum-run-update-containt"><p> <a href="#" class="wpum-run-update-button button">' + wpum_vars.db_update_confirmation_msg_button + '</a> ' + wpum_vars.db_update_confirmation_msg + '</p></div>');
			});

			$('#wpum-db-updates').on('click', 'a.wpum-run-update-button', function (e) {
				e.preventDefault();

				if ($(this).hasClass('active')) {
					return false;
				}

				$(this).addClass('active').fadeOut();
				$self.el.update_link.addClass('active').fadeOut();
				$('#wpum-db-updates .wpum-run-update-containt').slideUp();

				$self.el.progress_container.find('.notice-wrap').remove();
				$self.el.progress_container.append('<div class="notice-wrap wpum-clearfix"><span class="spinner is-active"></span><div class="wpum-progress"><div></div></div></div>');
				$self.el.progress_main_container.removeClass('wpum-hidden');

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'wpum_run_db_updates',
						run_db_update: 1
					},
					dataType: 'json',
					success: function (response) {

					}
				});

				window.setTimeout(WPUM_Updates.get_db_updates_info, 500, $self);

				return false;
			});
		},

		get_db_updates_info: function ($self) {
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'wpum_db_updates_info',
				},
				dataType: 'json',
				success: function (response) {
					// We need to get the actual in progress form, not all forms on the page.
					var notice_wrap = WPUM_Selector_Cache.get('.notice-wrap', $self.el.progress_container, true);

					if (-1 !== $.inArray('success', Object.keys(response))) {
						if (response.success) {
							if ($self.el.update_progress_counter.length) {
								$self.el.update_progress_counter.text('100%');
							}

							// Update steps info.
							if (-1 !== $.inArray('heading', Object.keys(response.data))) {
								$self.el.heading.html('<strong>' + response.data.heading + '</strong>');
							}

							$self.el.update_link.closest('p').remove();
							notice_wrap.html('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p><button type="button" class="notice-dismiss"></button></div>');

						} else {
							// Update steps info.
							if (-1 !== $.inArray('heading', Object.keys(response.data))) {
								$self.el.heading.html('<strong>' + response.data.heading + '</strong>');
							}

							if (response.data.message) {
								$self.el.update_link.closest('p').remove();
								notice_wrap.html('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p><button type="button" class="notice-dismiss"></button></div>');
							} else {
								setTimeout(function () {
									$self.el.update_link.removeClass('active').show();
									$self.el.progress_main_container.addClass('wpum-hidden');
								}, 1000);
							}
						}
					} else {
						if (response && -1 !== $.inArray('percentage', Object.keys(response.data))) {
							if ($self.el.update_progress_counter.length) {
								$self.el.update_progress_counter.text(response.data.total_percentage + '%');
							}

							// Update steps info.
							if (-1 !== $.inArray('heading', Object.keys(response.data))) {
								$self.el.heading.html('<strong>' + response.data.heading + '</strong>');
							}

							// Update progress.
							$('.wpum-progress div', '#wpum-db-updates').animate({
								width: response.data.percentage + '%',
							}, 50, function () {
								// Animation complete.
							});

							window.setTimeout(WPUM_Updates.get_db_updates_info, 1000, $self);
						} else {
							notice_wrap.html('<div class="notice notice-error"><p>' + wpum_vars.updates.ajax_error + '</p></div>');

							setTimeout(function () {
								$self.el.update_link.removeClass('active').show();
								$self.el.progress_main_container.addClass('wpum-hidden');
							}, 1000);
						}
					}
				}
			});
		},

		process_step: function (step, update, $self) {

			wpum_setting_edit = true;

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'wpum_do_ajax_updates',
					step: parseInt(step),
					update: parseInt(update)
				},
				dataType: 'json',
				success: function (response) {
					wpum_setting_edit = false;

					// We need to get the actual in progress form, not all forms on the page.
					var notice_wrap = WPUM_Selector_Cache.get('.notice-wrap', $self.el.progress_container, true);

					if (-1 !== $.inArray('success', Object.keys(response))) {
						if (response.success) {
							// Update steps info.
							if (-1 !== $.inArray('heading', Object.keys(response.data))) {
								$self.el.heading.html('<strong>' + response.data.heading + '</strong>');
							}

							$self.el.update_link.closest('p').remove();
							notice_wrap.html('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p><button type="button" class="notice-dismiss"></button></div>');

						} else {
							// Update steps info.
							if (-1 !== $.inArray('heading', Object.keys(response.data))) {
								$self.el.heading.html('<strong>' + response.data.heading + '</strong>');
							}

							notice_wrap.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');

							setTimeout(function () {
								$self.el.update_link.removeClass('active').show();
								$self.el.progress_main_container.addClass('wpum-hidden');
							}, 5000);
						}
					} else {
						if (response && -1 !== $.inArray('percentage', Object.keys(response.data))) {
							// Update progress.
							$('.wpum-progress div', '#wpum-db-updates').animate({
								width: response.data.percentage + '%',
							}, 50, function () {
								// Animation complete.
							});

							// Update steps info.
							if (-1 !== $.inArray('heading', Object.keys(response.data))) {
								$self.el.heading.html('<strong>' + response.data.heading.replace('{update_count}', $self.el.heading.data('update-count')) + '</strong>');
							}

							$self.process_step(parseInt(response.data.step), response.data.update, $self);
						} else {
							notice_wrap.html('<div class="notice notice-error"><p>' + wpum_vars.updates.ajax_error + '</p></div>');

							setTimeout(function () {
								$self.el.update_link.removeClass('active').show();
								$self.el.progress_main_container.addClass('wpum-hidden');
							}, 5000);
						}
					}

				}
			}).fail(function (response) {

				wpum_setting_edit = false;

				if (window.console && window.console.log) {
					console.log(response);
				}

				WPUM_Selector_Cache.get('.notice-wrap', self.el.progress_container).append(response.responseText);

			}).always(function () {});

		},

		dismiss_message: function () {
			$('body').on('click', '#poststuff .notice-dismiss', function () {
				$(this).parent().slideUp('fast');
			});
		}

	};

	WPUM_Updates.init();

	var WPUM_Upgrades = {

		init: function () {
			this.restartUpgrade();
			this.stopUpgrade();
			this.restartUpdater();
		},

		/**
		 * Function to restart the upgrade process.
		 */
		restartUpgrade: function () {
			jQuery('#wpum-restart-upgrades').click('click', function (e) {
				var vthat = this;
				e.preventDefault();
				jQuery('.wpum-doing-update-text-p').show();
				jQuery('.wpum-update-paused-text-p').hide();
				window.location.assign(jQuery(vthat).data('redirect-url'));
				return;
			});
		},

		/**
		 * Function to pause the upgrade process.
		 */
		stopUpgrade: function () {
			jQuery('#wpum-pause-upgrades').click('click', function (e) {
				var vthat = this;
				e.preventDefault();
				jQuery('.wpum-doing-update-text-p').hide();
				jQuery('.wpum-update-paused-text-p').show();
				window.location.assign(jQuery(vthat).data('redirect-url'));
				return;
			});
		},

		/**
		 * Function to restart the update process.
		 */
		restartUpdater: function () {
			jQuery('.wpum-restart-updater-btn,.wpum-run-update-now').click('click', function (e) {
				var vthat = this;
				e.preventDefault();
				window.location.assign(jQuery(vthat).attr('href'));
				return;
			});
		}
	};

	WPUM_Upgrades.init()

});
