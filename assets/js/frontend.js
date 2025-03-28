/**
 * Decision Polls Frontend JavaScript
 *
 * @package Decision_Polls
 */

(function($) {
	'use strict';

	// Initialize all polls on the page.
	function initPolls() {
		// Initialize standard polls.
		$('.decision-poll-standard').each(function() {
			initStandardPoll($(this));
		});

		// Initialize multiple choice polls.
		$('.decision-poll-multiple').each(function() {
			initMultiplePoll($(this));
		});

		// Initialize ranked choice polls.
		$('.decision-poll-ranked').each(function() {
			initRankedPoll($(this));
		});

		// Initialize poll creator if present.
		if ($('.decision-poll-creator').length) {
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
		
		// Initialize sortable for drag and drop ranking.
		$sortable.sortable({
			handle: '.decision-poll-drag-handle',
			axis: 'y',
			containment: 'parent',
			update: function(event, ui) {
				// Update rank numbers and hidden inputs.
				updateRanks($sortable);
			}
		});
		
		// Handle form submission.
		$form.on('submit', function(e) {
			e.preventDefault();
			
			var $message = $form.find('.decision-poll-message');
			var $submit = $form.find('.decision-poll-submit');
			var pollId = $form.find('input[name="poll_id"]').val();
			var rankedAnswers = $form.find('input[name="ranked_answers[]"]').map(function() {
				return $(this).val();
			}).get();
			
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
		$sortable.find('.decision-poll-option').each(function(index) {
			var $option = $(this);
			var answerId = $option.data('answer-id');
			
			// Update visible rank number.
			$option.find('.decision-poll-option-rank').text(index + 1);
			
			// Update hidden input value.
			$option.find('input[name="ranked_answers[]"]').val(answerId);
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
	
	// Get the current URL to determine if we're using the clean URL format or not
	var currentUrl = window.location.href;
	var pollUrl;
	
	// Create the proper poll URL based on the current site structure
	if (currentUrl.indexOf('/poll/') !== -1) {
		// We're using the clean URL format
		pollUrl = window.location.protocol + '//' + window.location.host + '/poll/' + pollId + '/';
	} else {
		// We might be using query parameters or shortcodes
		// Try to keep the same URL structure but add a results parameter
		pollUrl = currentUrl.split('?')[0] + '?poll_id=' + pollId + '&show_results=1';
	}
	
	console.log('Poll URL for redirection: ' + pollUrl);
	
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
		data.answers = data.ranked_answers;
		delete data.ranked_answers;
	}
	
	$.ajax({
		url: decisionPollsAPI.url + '/polls/' + pollId + '/vote',
		method: 'POST',
		beforeSend: function( xhr ) {
			xhr.setRequestHeader( 'X-WP-Nonce', decisionPollsAPI.nonce );
		},
		data: data,
			success: function(response) {
				// Show success message.
				$message.html('<p class="success">' + decisionPollsL10n.voteSuccess + '</p>').fadeIn();
				
				// Process all poll types, with special handling for ranked choice
				
				// If results are available, update the display.
				if (response.data && response.data.results) {
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
							// Sort results by votes before displaying
							results.results.sort(function(a, b) {
								return b.votes - a.votes;
							});
							
							// Add results for each option with rank indicators
							$.each(results.results, function(index, result) {
								var rankClass = '';
								var rankLabel = '';
								
								// Add rank indicators for top choices
								if (index === 0) {
									rankClass = 'rank-first';
									rankLabel = '<span class="rank-indicator rank-first">1st</span> ';
								} else if (index === 1) {
									rankClass = 'rank-second';
									rankLabel = '<span class="rank-indicator rank-second">2nd</span> ';
								} else if (index === 2) {
									rankClass = 'rank-third';
									rankLabel = '<span class="rank-indicator rank-third">3rd</span> ';
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
					// Navigate to the poll results page instead of just reloading
					setTimeout(function() {
						// Try direct navigation to ensure we're showing results
						window.location.href = pollUrl;
					}, 1500);
				}
			},
			error: function(xhr) {
				// Re-enable submit button.
				$submit.prop('disabled', false);
				
				// Show error message.
				var errorMessage = xhr.responseJSON && xhr.responseJSON.message 
					? xhr.responseJSON.message 
					: decisionPollsL10n.voteError;
				
				$message.html('<p class="error">' + errorMessage + '</p>').fadeIn();
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
		
		// The click handler for add button is defined in the template's inline script
		// We don't need to attach it here to avoid duplicates
		
		// Remove an option field.
		$optionsContainer.on('click', '.decision-poll-creator-remove-option', function() {
			$(this).parent('.decision-poll-creator-option').remove();
			
			// Update placeholders for remaining options.
			$optionsContainer.find('.decision-poll-creator-option').each(function(index) {
				$(this).find('input').attr('placeholder', decisionPollsL10n.option + ' ' + (index + 1));
			});
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
				$message.html('<p class="error">' + decisionPollsL10n.pollCreateError + ': ' + 
					 'At least two options are required.</p>').fadeIn();
				$submit.prop('disabled', false);
				return;
			}
			
			data.answers = options;
			
			// Add multiple choices limit if applicable.
			if (data.type === 'multiple') {
				data.multiple_choices = parseInt(formData.get('poll_max_choices'), 10) || 0;
			}
			
			$.ajax({
				url: decisionPollsAPI.adminUrl || ajaxurl,
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
		initPolls();
	});

})(jQuery);
