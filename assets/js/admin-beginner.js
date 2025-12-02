/**
 * Admin Beginner-Friendly JavaScript
 *
 * Handles wizard navigation, validation, and interactive elements.
 *
 * @package WritgoCMS
 */

(function($) {
	'use strict';

	/**
	 * Setup Wizard Handler
	 */
	const WritgoWizard = {
		init: function() {
			this.bindEvents();
			this.initTooltips();
		},

		bindEvents: function() {
			// Wizard navigation
			$(document).on('click', '.wizard-next', this.handleNext.bind(this));
			$(document).on('click', '.wizard-back', this.handleBack.bind(this));
			$(document).on('click', '.wizard-skip', this.handleSkip.bind(this));
			$(document).on('click', '.wizard-complete', this.handleComplete.bind(this));

			// License validation
			$(document).on('click', '#validate-license-btn', this.validateLicense.bind(this));

			// Authentication
			$(document).on('submit', '#wizard-login-form', this.handleLogin.bind(this));

			// Example cards selection
			$(document).on('click', '.example-card', this.selectExample.bind(this));

			// Analysis actions
			$(document).on('click', '#start-analysis-btn', this.startAnalysis.bind(this));
			$(document).on('click', '.skip-analysis-btn', this.skipAnalysis.bind(this));

			// Auto-save on input change
			$(document).on('change', '.wizard-content input, .wizard-content select, .wizard-content textarea', this.autoSave.bind(this));
		},

		initTooltips: function() {
			// Tooltips are handled via CSS :hover
			// This is a placeholder for more advanced tooltip functionality if needed
		},

		handleNext: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const step = $button.data('step');

			// Validate current step
			if (!this.validateStep(step)) {
				return;
			}

			// Save step data
			this.saveStepData(step, function(success) {
				if (success) {
					// Navigate to next step
					const nextStep = step + 1;
					window.location.href = adminUrl + '?page=writgoai-setup-wizard&step=' + nextStep;
				}
			});
		},

		handleBack: function(e) {
			// Let the link handle navigation naturally
		},

		handleSkip: function(e) {
			e.preventDefault();
			
			if (!confirm(writgoaiAdmin.i18n.skipConfirm || 'Weet je zeker dat je de setup wilt overslaan?')) {
				return;
			}

			$.ajax({
				url: writgoaiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'writgoai_skip_wizard',
					nonce: writgoaiAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						window.location.href = response.data.redirect_url;
					}
				}
			});
		},

		handleComplete: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const step = $button.data('step');

			this.saveStepData(step, function(success) {
				if (success) {
					window.location.href = adminUrl + '?page=writgoai-ai';
				}
			});
		},

		validateStep: function(step) {
			const $step = $('.writgo-wizard-step-' + step);
			let isValid = true;

			// Step-specific validation
			switch(step) {
				case 1:
					// Optional: validate license if entered
					break;
				case 2:
					const theme = $('#website-theme').val();
					const customTheme = $('#custom-theme').val();
					if (!theme && !customTheme) {
						alert('Selecteer een thema of vul een eigen thema in.');
						isValid = false;
					}
					break;
				case 3:
					const audience = $('#target-audience').val();
					if (!audience || audience.trim() === '') {
						alert('Beschrijf je doelgroep.');
						isValid = false;
					}
					break;
				case 4:
					// Analysis step - check if analysis is complete
					const analysisComplete = $('#analysis-results').is(':visible');
					if (!analysisComplete) {
						alert('Start de analyse of klik op "Analyse overslaan".');
						isValid = false;
					}
					break;
			}

			return isValid;
		},

		saveStepData: function(step, callback) {
			const $step = $('.writgo-wizard-step-' + step);
			const data = {};

			// Collect form data from current step
			$step.find('input, select, textarea').each(function() {
				const $input = $(this);
				const name = $input.attr('name');
				
				if (name) {
					if ($input.attr('type') === 'checkbox') {
						if (!data[name]) {
							data[name] = [];
						}
						if ($input.is(':checked')) {
							data[name].push($input.val());
						}
					} else {
						data[name] = $input.val();
					}
				}
			});

			$.ajax({
				url: writgoaiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'writgoai_save_wizard_step',
					nonce: writgoaiAdmin.nonce,
					step: step,
					data: data
				},
				success: function(response) {
					if (callback) {
						callback(response.success);
					}
				},
				error: function() {
					alert(writgoaiAdmin.i18n.error);
					if (callback) {
						callback(false);
					}
				}
			});
		},

		autoSave: function(e) {
			// Debounced auto-save could be implemented here
			// For now, we save on next button click
		},

		validateLicense: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const licenseKey = $('#wizard-license-key').val();

			if (!licenseKey) {
				alert('Voer een licentie code in.');
				return;
			}

			$button.prop('disabled', true).text(writgoaiAdmin.i18n.validating);

			// This would call the existing license validation endpoint
			$.ajax({
				url: writgoaiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'writgoai_validate_license',
					license_key: licenseKey,
					nonce: writgoaiAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						$('.license-status').remove();
						$('.license-input-group').after(
							'<div class="license-status license-valid">' +
							'<span class="dashicons dashicons-yes-alt"></span>' +
							'Licentie is geldig en actief!' +
							'</div>'
						);
					} else {
						alert('Ongeldige licentie code. Probeer het opnieuw.');
					}
				},
				error: function() {
					alert(writgoaiAdmin.i18n.error);
				},
				complete: function() {
					$button.prop('disabled', false).text('Valideren');
				}
			});
		},

		handleLogin: function(e) {
			e.preventDefault();
			const $form = $(e.currentTarget);
			const $button = $('#wizard-login-btn');
			const $message = $('.auth-status-message');
			const email = $('#wizard-email').val();
			const password = $('#wizard-password').val();

			if (!email || !password) {
				$message.removeClass('success').addClass('error').text('Vul alle velden in.');
				return;
			}

			$button.prop('disabled', true).text('Inloggen...');
			$message.removeClass('success error').text('');

			// Use auth nonce if available, otherwise fall back to admin nonce
			const authNonce = (typeof writgoaiAuth !== 'undefined' && writgoaiAuth.nonce) 
				? writgoaiAuth.nonce 
				: writgoaiAdmin.nonce;

			$.ajax({
				url: writgoaiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'writgoai_login',
					nonce: authNonce,
					email: email,
					password: password
				},
				success: function(response) {
					if (response.success) {
						$message.addClass('success').text(response.data.message || 'Login succesvol!');
						// Reload page to show logged-in state
						setTimeout(function() {
							window.location.reload();
						}, 1000);
					} else {
						$message.addClass('error').text(response.data.message || 'Login mislukt.');
						$button.prop('disabled', false).text('Inloggen');
					}
				},
				error: function() {
					$message.addClass('error').text('Er is een fout opgetreden.');
					$button.prop('disabled', false).text('Inloggen');
				}
			});
		},

		selectExample: function(e) {
			const $card = $(e.currentTarget);
			const example = $card.data('example');
			
			// Toggle selection
			$('.example-card').removeClass('selected');
			$card.addClass('selected');

			// Fill in the textarea with example text
			const examples = {
				professionals: 'Professionals tussen 25-45 jaar die geïnteresseerd zijn in carrière ontwikkeling en productiviteit',
				parents: 'Ouders met jonge kinderen die op zoek zijn naar opvoedingstips en gezinsactiviteiten',
				students: 'Studenten en leerlingen die hulp zoeken bij studeren en leren',
				hobbyists: 'Hobbyisten en enthousiastelingen die hun passies willen verdiepen'
			};

			if (examples[example]) {
				$('#target-audience').val(examples[example]);
			}
		},

		startAnalysis: function(e) {
			e.preventDefault();
			const $button = $(e.currentTarget);
			const $progress = $('#analysis-progress');
			const $results = $('#analysis-results');
			const $nextButton = $('.wizard-next');

			$button.prop('disabled', true).hide();
			$progress.show();

			// Simulate progress
			let progress = 0;
			const progressInterval = setInterval(function() {
				progress += Math.random() * 15;
				if (progress > 100) {
					progress = 100;
					clearInterval(progressInterval);
				}
				$('.progress-fill').css('width', progress + '%');
			}, 500);

			// This would call the actual analysis endpoint
			// For now, simulate with a timeout
			setTimeout(function() {
				clearInterval(progressInterval);
				$('.progress-fill').css('width', '100%');
				
				setTimeout(function() {
					$progress.hide();
					$results.show();
					
					// Populate results with dummy data
					$('#insight-posts').text('42');
					$('#insight-score').text('78/100');
					$('#insight-opportunities').text('12');
					
					// Enable next button
					$nextButton.prop('disabled', false);
				}, 500);
			}, 3000);
		},

		skipAnalysis: function(e) {
			e.preventDefault();
			
			// Just enable the next button
			$('.wizard-next').prop('disabled', false);
			$('#analysis-results').show();
			$('#insight-posts').text('--');
			$('#insight-score').text('--');
			$('#insight-opportunities').text('--');
		}
	};

	/**
	 * Dashboard Enhancements
	 */
	const WritgoDashboard = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Toggle details sections
			$(document).on('click', '.toggle-details', this.toggleDetails.bind(this));
			
			// Quick action buttons
			$(document).on('click', '.quick-action-btn', this.handleQuickAction.bind(this));
		},

		toggleDetails: function(e) {
			e.preventDefault();
			const $toggle = $(e.currentTarget);
			const target = $toggle.data('target');
			
			$(target).slideToggle(300);
			$toggle.toggleClass('open');
		},

		handleQuickAction: function(e) {
			// Handle quick action button clicks
			// Implementation depends on specific actions
		}
	};

	/**
	 * Settings Tabs Handler
	 */
	const WritgoSettings = {
		init: function() {
			this.bindEvents();
			this.initTabs();
		},

		bindEvents: function() {
			$(document).on('click', '.settings-tab', this.switchTab.bind(this));
		},

		initTabs: function() {
			// Show first tab by default
			$('.settings-tab-content').hide();
			$('.settings-tab-content:first').show();
			$('.settings-tab:first').addClass('active');
		},

		switchTab: function(e) {
			e.preventDefault();
			const $tab = $(e.currentTarget);
			const target = $tab.data('tab');

			$('.settings-tab').removeClass('active');
			$tab.addClass('active');

			$('.settings-tab-content').hide();
			$('#' + target).fadeIn(200);
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		// Admin URL should be passed via wp_localize_script
		const adminUrl = writgoaiAdmin.ajaxUrl.replace('/admin-ajax.php', '') + '/admin.php';
		window.adminUrl = adminUrl;

		// Initialize components
		WritgoWizard.init();
		WritgoDashboard.init();
		WritgoSettings.init();

		// Smooth scroll for anchor links
		$('a[href^="#"]').on('click', function(e) {
			const target = $(this).attr('href');
			if (target !== '#' && $(target).length) {
				e.preventDefault();
				$('html, body').animate({
					scrollTop: $(target).offset().top - 100
				}, 500);
			}
		});

		// Add loading states to buttons
		$(document).on('click', '.button-primary:not(.no-loading)', function() {
			const $button = $(this);
			if (!$button.prop('disabled')) {
				$button.addClass('is-loading');
			}
		});
	});

})(jQuery);
