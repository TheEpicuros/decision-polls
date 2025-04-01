/**
 * Decision Polls Frontend JavaScript
 *
 * @package Decision_Polls
 */

(function($) {
	'use strict';

	// For debugging
	var DEBUG = true;
	
	function log(message) {
		if (DEBUG && console && console.log) {
			console.log("Decision Polls: " + message);
		}
	}

	// Initialize all polls on the page.
	function initPolls() {
		log("Initializing polls");
		
		// Initialize standard polls.
		$('.decision-poll-standard').each(function() {
			log("Found standard poll - initializing");
			initStandardPoll($(this));
		});

		// Initialize multiple choice polls.
		$('.decision-poll-multiple').each(function() {
			log("Found multiple choice poll - initializing");
			initMultiplePoll($(this));
		});

		// Initialize ranked choice polls.
		$('.decision-poll-ranked').each(function() {
			log("Found ranked choice poll - initializing");
			initRankedPoll($(this));
		});

		// Initialize poll creator if present.
		if ($('.decision-poll-creator').length) {
			log("Found poll creator - initializing");
			initPollCreator();
		}
	}

	/**
	 * Initialize a standard (single choice) poll.
	 *
	 * @param {Object} $container The poll container jQuery object.
	 */
	function initStandardPoll($container) {
		var $form = $container.find('.decision-poll-form');

		// Handle form submission.
		$form.on('submit', function(e) {
			e.preventDefault();
			
			var $message = $form.find('.decision-poll-message');
			var $submit = $form.find('.decision-poll-submit');
			var pollId = $form.find('input[name="poll_id"]').val();
			var answerId = $form.find('input[name="poll_answer"]:checked').val();
			
			// Clear previous messages.
			$message.empty().hide();
			
			// Disable submit button.
			$submit.prop('disabled', true);
			
			// Check if an option is selected.
			if (!answerId) {
				$message.html('<p class="error">' + decisionPollsL10n.selectOptionError + '</p>').fadeIn();
				$submit.prop('disabled', false);
				return;
			}
			
			// Submit vote via AJAX.
			submitVote({
				poll_id: pollId,
				answer_id: answerId,
				poll_type: 'standard'
			}, $form);
		});
	}

	/**
	 * Initialize a multiple choice poll.
	 *
	 * @param {Object} $container The poll container jQuery object.
	 */
	function initMultiplePoll($container) {
		var $form = $container.find('.decision-poll-form');
		var maxChoices = parseInt($form.find('input[name="max_choices"]').val(), 10);
		
		// Validate multiple choices.
		$form.find('.decision-poll-checkbox').on('change', function() {
			var $checkboxes = $form.find('.decision-poll-checkbox:checked');
			var $message = $form.find('.decision-poll-message');
			
			// If max choices is set and exceeded.
			if (maxChoices > 0 && $checkboxes.length > maxChoices) {
				// Uncheck the current checkbox.
				$(this).prop('checked', false);
				
				// Show message.
				$message.html('<p class="error">' + 
					decisionPollsL10n.maxChoicesError.replace('{max}', maxChoices) + 
					'</p>').fadeIn();
				
				// Hide message after 3 seconds.
				setTimeout(function() {
					$message.fadeOut();
				}, 3000);
			}
		});
		
		// Handle form submission.
		$form.on('submit', function(e) {
			e.preventDefault();
			
			var $message = $form.find('.decision-poll-message');
			var $submit = $form.find('.decision-poll-submit');
			var pollId = $form.find('input[name="poll_id"]').val();
			var $checkboxes = $form.find('.decision-poll-checkbox:checked');
			var answerIds = $checkboxes.map(function() {
				return $(this).val();
			}).get();
			
			// Clear previous messages.
			$message.empty().hide();
			
			// Disable submit button.
			$submit.prop('disabled', true);
			
			// Check if at least one option is selected.
			if (answerIds.length === 0) {
				$message.html('<p class="error">' + decisionPollsL10n.selectOptionError + '</p>').fadeIn();
				$submit.prop('disabled', false);
				return;
			}
			
			// Submit vote via AJAX.
			submitVote({
				poll_id: pollId,
				answer_ids: answerIds,
				poll_type: 'multiple'
			}, $form);
		});
	}

	/**
	 * Initialize a ranked choice poll.
	 *
	 * @param {Object} $container The poll container jQuery object.
	 */
	function initRankedPoll($container) {
		var $form = $container.find('.decision-poll-form');
		var $sortable = $container.find('.decision-poll-sortable');
		
		log("Setting up sortable for poll: " + $container.attr('id'));
		log("Sortable element found: " + ($sortable.length > 0 ? "Yes" : "No"));
		
		if (!$sortable.length) {
			log("ERROR: Sortable container not found!");
			return;
		}
		
		// Verify jQuery UI is available
		if (!$.fn.sortable) {
			log("ERROR: jQuery UI Sortable not available!");
			console.error("jQuery UI Sortable is required for ranked polls but is not loaded.");
			return;
		}
		
		try {
			// Initialize sortable for drag and drop ranking
			$sortable.sortable({
				handle: '.decision-poll-drag-handle',
				axis: 'y',
				cursor: 'move',
				forcePlaceholderSize: true,
				placeholder: 'ui-state-highlight',
				create: function(event, ui) {
					log("Sortable created successfully");
					// Ensure initial ranks are set correctly
					updateRanks($sortable);
				},
				start: function(event, ui) {
					log("Drag started");
					// Add class for styling
					ui.item.addClass('sorting');
				},
				stop: function(event, ui) {
					log("Drag stopped");
					// Remove class
					ui.item.removeClass('sorting');
				},
				update: function(event, ui) {
					log("Order updated");
					// Update rank numbers and hidden inputs
					updateRanks($sortable);
				}
			}).disableSelection();
			
			log("Sortable initialization completed");
		} catch (e) {
			log("ERROR initializing sortable: " + e.message);
			console.error("Failed to initialize sortable:", e);
		}
		
		// Handle form submission.
		$form.on('submit', function(e) {
			e.preventDefault();
			
			var $message = $form.find('.decision-poll-message');
			var $submit = $form.find('.decision-poll-submit');
			var pollId = $form.find('input[name="poll_id"]').val();
			
			// Get ranked answers in current order
			var rankedAnswers = [];
			$sortable.find('.decision-poll-option').each(function() {
				rankedAnswers.push($(this).data('answer-id'));
			});
			
			log("Submitting ranked answers: " + JSON.stringify(rankedAnswers));
			
			// Clear previous messages.
			$message.empty().hide();
			
			// Disable submit button.
			$submit.prop('disabled', true);
			
			// Submit vote via AJAX.
			submitVote({
				poll_id: pollId,
				ranked_answers: rankedAnswers,
				poll_type: 'ranked'
			}, $form);
		});
	}

	/**
	 * Update rank numbers and hidden inputs after sorting.
	 *
	 * @param {Object} $sortable The sortable container jQuery object.
	 */
	function updateRanks($sortable) {
		log("Updating ranks");
		$sortable.find('.decision-poll-option').each(function(index) {
			var $option = $(this);
			var answerId = $option.data('answer-id');
			
			log("Item " + index + ": answer_id = " + answerId);
			
			// Update visible rank number.
			$option.find('.decision-poll-option-rank').text(index + 1);
			
			// Update hidden input value.
			var $input = $option.find('input[name="ranked_answers[]"]');
			if ($input.length) {
				$input.val(answerId);
				log("Updated input for " + answerId);
			} else {
				log("WARNING: Could not find hidden input for answer ID: " + answerId);
			}
		});
	}

	/**
	 * Submit a vote via AJAX.
	 *
	 * @param {Object} data     The data to submit.
	 * @param {Object} $form    The form jQuery object.
	 */
	function submitVote(data, $form) {
		var $message = $form.find('.decision-poll-message');
		var $submit = $form.find('.decision-poll-submit');
		var pollId = data.poll_id;
		var pollType = data.poll_type || '';
		
		log("Submitting vote for poll " + pollId + " (type: " + pollType + ")");
		
		// Get site URL
		var siteUrl = window.location.protocol + '//' + window.location.host;
		
		// Construct the canonical URL for displaying results for this poll
		// This is using the same format as WordPress expects for this poll view
		var pollUrl = '';
		
		// Use the format consistent with how WordPress permalinks are set up
		if (window.location.href.indexOf('/poll/') !== -1) {
			// Clean URLs are enabled, so use the /poll/{id}/results/ format
			pollUrl = siteUrl + '/poll/' + pollId + '/results/';
		} else {
			// No clean URLs, so use query parameters
			pollUrl = siteUrl + '/index.php?poll_id=' + pollId + '&show_results=1';
		}
		
		// Force a cache-busting parameter - essential for browser to get a fresh page
		pollUrl += (pollUrl.indexOf('?') !== -1 ? '&' : '?') + '_=' + new Date().getTime();
		
		log("Redirection target URL: " + pollUrl);
		
		// Add nonce to data.
		data.nonce = $('#decision_polls_nonce').val();
		
		// Remove poll_id from data as it's in the URL
		delete data.poll_id;
		
		// Convert answer_id/answer_ids/ranked_answers to answers format
		if (data.answer_id) {
			data.answers = [data.answer_id];
			delete data.answer_id;
		} else if (data.answer_ids) {
			data.answers = data.answer_ids;
			delete data.answer_ids;
		} else if (data.ranked_answers) {
			// For ranked polls, we need to maintain the order of answers
			// The position in the array indicates the rank (index 0 = rank 1)
			data.answers = data.ranked_answers;
			delete data.ranked_answers;
		}
		
		log("Sending data to API: " + JSON.stringify(data));
		
		// Check if the API URL is available
		if (!decisionPollsAPI || !decisionPollsAPI.url) {
			log("ERROR: decisionPollsAPI not available!");
			$message.html('<p class="error">API configuration missing. Please reload the page and try again.</p>').fadeIn();
			$submit.prop('disabled', false);
			return;
		}
		
		$.ajax({
			url: decisionPollsAPI.url + '/polls/' + pollId + '/vote',
			method: 'POST',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', decisionPollsAPI.nonce );
			},
			data: data,
			success: function(response) {
				log("Vote submitted successfully");
				// Show success message.
				$message.html('<p class="success">' + decisionPollsL10n.voteSuccess + '</p>').fadeIn();
				
				// If results are available, update the display.
				if (response.data && response.data.results) {
					log("Results received, updating display");
					// Delay slightly to let the user see the success message.
					setTimeout(function() {
						// Replace poll form with results.
						var results = response.data.results;
						var resultsHtml = '<div class="decision-poll-results-list">';
						
						// Add total votes.
						resultsHtml += '<div class="decision-poll-total-votes">' + 
							decisionPollsL10n.totalVotes.replace('{total}', results.total_votes) + 
							'</div>';
						
						// Special handling for ranked choice polls
						if (pollType === 'ranked') {
							log("Generating ranked poll results display");
							// Use rank order from server - DO NOT re-sort! The server already sorted by rank
							// Add results for each option with rank indicators
							$.each(results.results, function(index, result) {
								var rankClass = '';
								var rankLabel = '';
								var rank = result.rank || (index + 1);
								
								// Add rank indicators for top choices
								if (rank === 1) {
									rankClass = 'rank-first';
									rankLabel = '<span class="rank-indicator rank-first">1st choice</span> ';
								} else if (rank === 2) {
									rankClass = 'rank-second';
									rankLabel = '<span class="rank-indicator rank-second">2nd choice</span> ';
								} else if (rank === 3) {
									rankClass = 'rank-third';
									rankLabel = '<span class="rank-indicator rank-third">3rd choice</span> ';
								} else {
									rankClass = 'rank-other';
									rankLabel = '<span class="rank-indicator rank-other">' + rank + 'th choice</span> ';
								}
								
								resultsHtml += '<div class="decision-poll-result">' +
									'<div class="decision-poll-result-text">' + rankLabel + result.text + '</div>' +
									'<div class="decision-poll-result-bar-container">' +
										'<div class="decision-poll-result-bar decision-poll-ranked-bar ' + rankClass + '" style="width: ' + result.percentage + '%;">' +
											'<span class="decision-poll-result-percentage">' + Math.round(result.percentage * 10) / 10 + '%</span>' +
										'</div>' +
									'</div>' +
									'<div class="decision-poll-result-votes">' + 
										decisionPollsL10n.votes.replace('{votes}', result.votes) + 
									'</div>' +
								'</div>';
							});
						} else {
							// Standard display for other poll types
							$.each(results.results, function(index, result) {
								resultsHtml += '<div class="decision-poll-result">' +
									'<div class="decision-poll-result-text">' + result.text + '</div>' +
									'<div class="decision-poll-result-bar-container">' +
										'<div class="decision-poll-result-bar" style="width: ' + result.percentage + '%;">' +
											'<span class="decision-poll-result-percentage">' + Math.round(result.percentage * 10) / 10 + '%</span>' +
										'</div>' +
									'</div>' +
									'<div class="decision-poll-result-votes">' + 
										decisionPollsL10n.votes.replace('{votes}', result.votes) + 
									'</div>' +
								'</div>';
							});
						}
						
						resultsHtml += '</div>';
						
						// Add footer with timestamp.
						resultsHtml += '<div class="decision-poll-footer">' +
							'<div class="decision-poll-last-updated">' +
								decisionPollsL10n.lastUpdated.replace('{time}', new Date().toLocaleString()) +
							'</div>' +
						'</div>';
						
						// Replace form with results.
						$form.fadeOut(300, function() {
							$(this).replaceWith(resultsHtml);
						});
					}, 1000);
				} else {
					log("No results in response, redirecting to: " + pollUrl);
					// Show a clear indication we're about to redirect
					$message.html('<p class="success">' + decisionPollsL10n.voteSuccess + ' Redirecting to results...</p>').fadeIn();
					
					// IMMEDIATE REDIRECT - don't wait
					console.log('Redirecting to: ' + pollUrl);
					
					// Try multiple redirect methods
					try {
						// Method 1: Replace current page (cleanest)
						window.location.replace(pollUrl);
						
						// Method 2: Backup with regular navigation
						setTimeout(function() {
							window.location.href = pollUrl;
						}, 500);
						
						// Method 3: Last resort, reload entire page
						setTimeout(function() {
							window.location = pollUrl;
						}, 1000);
					} catch(e) {
						console.error('Redirect error: ' + e.message);
						// Final fallback - just reload
						window.location.reload();
					}
				}
			},
			error: function(xhr) {
				// Re-enable submit button.
				$submit.prop('disabled', false);
				
				// Show error message.
				var errorMessage = xhr.responseJSON && xhr.responseJSON.message 
					? xhr.responseJSON.message 
					: decisionPollsL10n.voteError;
				
				log("AJAX error: " + errorMessage);
				$message.html('<p class="error">' + errorMessage + '</p>').fadeIn();
				
				console.error('AJAX error: ', xhr);
			}
		});
	}

	/**
	 * Initialize poll creator form.
	 */
	function initPollCreator() {
		// Make sure we only initialize once to prevent duplicate event bindings
		if ($('.decision-poll-creator').data('initialized')) {
			return;
		}
		
		var $creator = $('.decision-poll-creator');
		var $form = $creator.find('.decision-poll-creator-form');
		var $optionsContainer = $creator.find('.decision-poll-creator-options');
		var $addButton = $creator.find('.decision-poll-creator-add-option');
		var $typeSelect = $creator.find('select[name="poll_type"]');
		var $multipleOptions = $creator.find('.decision-poll-multiple-options');
		var $message = $creator.find('.decision-poll-message');
		
		// Mark as initialized to prevent duplicate initialization
		$creator.data('initialized', true);
		
		// Initially hide or show multiple choice options based on selected type.
		toggleMultipleOptions();
		
		// Remove an option field.
		$optionsContainer.on('click', '.decision-poll-creator-remove-option', function() {
			if ($(this).prop('disabled')) {
				return;
			}
			
			$(this).parent('.decision-poll-creator-option').remove();
			
			// Update placeholders for remaining options.
			$optionsContainer.find('.decision-poll-creator-option').each(function(index) {
				$(this).find('input').attr('placeholder', decisionPollsL10n.option + ' ' + (index + 1));
			});
			
			// Disable remove buttons if we have 2 or fewer options.
			if ($optionsContainer.find('.decision-poll-creator-option').length <= 2) {
				$optionsContainer.find('.decision-poll-creator-remove-option').prop('disabled', true);
			}
		});
		
		// Add a new option field.
		$addButton.on('click', function(e) {
			e.preventDefault();
			
			var optionCount = $optionsContainer.find('.decision-poll-creator-option').length + 1;
			var optionHtml = '<div class="decision-poll-creator-option">' +
				'<input type="text" name="poll_option[]" placeholder="' + decisionPollsL10n.option + ' ' + optionCount + '" required>' +
				'<button type="button" class="decision-poll-creator-remove-option">' + decisionPollsL10n.remove + '</button>' +
				'</div>';
			
			$optionsContainer.append(optionHtml);
			
			// Enable all remove buttons if we have more than 2 options.
			if (optionCount > 2) {
				$optionsContainer.find('.decision-poll-creator-remove-option').prop('disabled', false);
			}
		});
		
		// Toggle multiple choice options when type changes.
		$typeSelect.on('change', toggleMultipleOptions);
		
		// Function to toggle multiple choices options visibility.
		function toggleMultipleOptions() {
			if ($typeSelect.val() === 'multiple') {
				$multipleOptions.show();
			} else {
				$multipleOptions.hide();
			}
		}
		
		// Handle form submission.
		$form.on('submit', function(e) {
			e.preventDefault();
			
			var $submit = $form.find('button[type="submit"]');
			
			// Check if form is already being submitted to prevent duplicates
			if ($submit.prop('disabled')) {
				return false;
			}
			
			// Clear previous messages.
			$message.empty().hide();
			
			// Disable submit button.
			$submit.prop('disabled', true);
			
			// Show loading indicator
			$message.html('<p class="info">Creating your poll...</p>').fadeIn();
			
			// Get form data.
			var formData = new FormData(this);
			var data = {
				title: formData.get('poll_title'),
				description: formData.get('poll_description') || '',
				type: formData.get('poll_type'),
				status: 'published',
				is_private: formData.get('poll_private') === 'on',
				nonce: $('#decision_polls_creator_nonce').val()
			};
			
			// Get options.
			var options = [];
			$optionsContainer.find('input[name="poll_option[]"]').each(function() {
				var value = $(this).val().trim();
				if (value) {
					options.push(value);
				}
			});
			
			// Validate options.
			if (options.length < 2) {
				$message.html('<p class="error">At least two poll options are required.</p>').fadeIn();
				$submit.prop('disabled', false);
				return;
			}
			
			data.answers = options;
			
			// Add multiple choices limit if applicable.
			if (data.type === 'multiple') {
				data.multiple_choices = parseInt(formData.get('poll_max_choices'), 10) || 0;
			}
			
			var ajaxUrl = decisionPollsAPI && decisionPollsAPI.adminUrl ? 
				decisionPollsAPI.adminUrl : (window.ajaxurl || '/wp-admin/admin-ajax.php');
			
			$.ajax({
				url: ajaxUrl,
				method: 'POST',
				data: {
					action: 'decision_polls_create_poll',
					...data
				},
				success: function(response) {
					if (response.success) {
						// Show brief success message
						$message.html('<p class="success">' + decisionPollsL10n.pollCreated + '</p>').fadeIn();
						
						// Get the URL for the new poll using clean URL format
						var pollUrl = window.location.protocol + '//' + window.location.host + '/poll/' + response.data.poll.id + '/';
						
						// Automatically redirect to the new poll after a short delay
						setTimeout(function() {
							window.location.href = pollUrl;
						}, 1000);
					} else {
						$message.html('<p class="error">' + decisionPollsL10n.pollCreateError + 
							(response.data && response.data.message ? ': ' + response.data.message : '') + 
							'</p>').fadeIn();
						$submit.prop('disabled', false);
					}
				},
				error: function() {
					$message.html('<p class="error">' + decisionPollsL10n.pollCreateError + '</p>').fadeIn();
					$submit.prop('disabled', false);
				}
			});
		});
	}

	// Initialize on document ready.
	$(document).ready(function() {
		log("Document ready, initializing Decision Polls");
		initPolls();
	});

})(jQuery);
