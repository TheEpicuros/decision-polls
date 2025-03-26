/**
 * Decision Polls Frontend JavaScript
 * 
 * Handles loading polls, submitting votes, and displaying results.
 */

(function($) {
    'use strict';

    // Main Decision Polls object
    window.DecisionPolls = {
        init: function() {
            this.initPolls();
            this.initResults();
            this.initForms();
        },

        /**
         * Initialize polls
         */
        initPolls: function() {
            $('.decision-polls-container').each(function() {
                var $container = $(this);
                var pollId = $container.data('poll-id');
                var showResults = $container.data('show-results');

                if (!pollId) {
                    $container.html('<div class="decision-polls-error">Error: No poll ID specified.</div>');
                    return;
                }

                // Simulate loading from API
                DecisionPolls.loadPoll($container, pollId);
            });
        },

        /**
         * Load poll from API
         * 
         * @param {jQuery} $container Poll container
         * @param {number} pollId Poll ID
         */
        loadPoll: function($container, pollId) {
            // This would be an actual API call in the future
            // For now, simulate with setTimeout to show loading state
            setTimeout(function() {
                // Example poll data (placeholder)
                var pollData = {
                    id: pollId,
                    title: 'Example Poll #' + pollId,
                    description: 'This is a placeholder poll that would normally be loaded from the API.',
                    type: 'standard',
                    answers: [
                        { id: 1, text: 'Option 1' },
                        { id: 2, text: 'Option 2' },
                        { id: 3, text: 'Option 3' }
                    ]
                };

                // Render poll
                DecisionPolls.renderPoll($container, pollData);
            }, 1000);
        },

        /**
         * Render poll
         * 
         * @param {jQuery} $container Poll container
         * @param {object} pollData Poll data
         */
        renderPoll: function($container, pollData) {
            var html = '';
            
            html += '<div class="decision-polls-question">' + pollData.title + '</div>';
            
            if (pollData.description) {
                html += '<div class="decision-polls-description">' + pollData.description + '</div>';
            }
            
            html += '<div class="decision-polls-options" data-poll-type="' + pollData.type + '">';
            
            if (pollData.type === 'ranked') {
                // Render ranked choice poll
                html += '<ul class="decision-polls-ranked-list">';
                for (var i = 0; i < pollData.answers.length; i++) {
                    var answer = pollData.answers[i];
                    html += '<li class="decision-polls-ranked-item" data-answer-id="' + answer.id + '">';
                    html += '<span class="handle">☰</span>';
                    html += '<span class="rank">' + (i + 1) + '</span>';
                    html += '<span class="text">' + answer.text + '</span>';
                    html += '</li>';
                }
                html += '</ul>';
            } else if (pollData.type === 'multiple') {
                // Render multiple choice poll
                for (var i = 0; i < pollData.answers.length; i++) {
                    var answer = pollData.answers[i];
                    html += '<div class="decision-polls-option" data-answer-id="' + answer.id + '">';
                    html += '<label><input type="checkbox" name="poll_' + pollData.id + '[]" value="' + answer.id + '"> ' + answer.text + '</label>';
                    html += '</div>';
                }
            } else {
                // Render standard poll
                for (var i = 0; i < pollData.answers.length; i++) {
                    var answer = pollData.answers[i];
                    html += '<div class="decision-polls-option" data-answer-id="' + answer.id + '">';
                    html += '<label><input type="radio" name="poll_' + pollData.id + '" value="' + answer.id + '"> ' + answer.text + '</label>';
                    html += '</div>';
                }
            }
            
            html += '</div>';
            html += '<button class="decision-polls-submit" data-poll-id="' + pollData.id + '">Vote</button>';
            
            $container.html(html);
            
            // Attach event handlers
            DecisionPolls.attachPollEvents($container, pollData);
        },

        /**
         * Attach event handlers to poll
         * 
         * @param {jQuery} $container Poll container
         * @param {object} pollData Poll data
         */
        attachPollEvents: function($container, pollData) {
            var $submit = $container.find('.decision-polls-submit');
            
            // Vote button click
            $submit.on('click', function(e) {
                e.preventDefault();
                
                var selectedAnswers = [];
                
                if (pollData.type === 'ranked') {
                    // Get ranked answers
                    $container.find('.decision-polls-ranked-item').each(function(index) {
                        selectedAnswers.push({
                            id: $(this).data('answer-id'),
                            rank: index + 1
                        });
                    });
                } else if (pollData.type === 'multiple') {
                    // Get multiple selected answers
                    $container.find('input[type="checkbox"]:checked').each(function() {
                        selectedAnswers.push(parseInt($(this).val(), 10));
                    });
                } else {
                    // Get single selected answer
                    var selectedValue = $container.find('input[type="radio"]:checked').val();
                    if (selectedValue) {
                        selectedAnswers.push(parseInt(selectedValue, 10));
                    }
                }
                
                if (selectedAnswers.length === 0) {
                    alert('Please select an option.');
                    return;
                }
                
                // Submit vote
                DecisionPolls.submitVote($container, pollData.id, selectedAnswers);
            });
            
            // Make ranked items sortable (placeholder functionality)
            if (pollData.type === 'ranked' && typeof $.fn.sortable !== 'undefined') {
                $container.find('.decision-polls-ranked-list').sortable({
                    handle: '.handle',
                    update: function() {
                        // Update rank numbers after sorting
                        $(this).find('.decision-polls-ranked-item').each(function(index) {
                            $(this).find('.rank').text(index + 1);
                        });
                    }
                });
            }
        },

        /**
         * Submit vote to API
         * 
         * @param {jQuery} $container Poll container
         * @param {number} pollId Poll ID
         * @param {array} answers Selected answers
         */
        submitVote: function($container, pollId, answers) {
            // Show loading state
            $container.addClass('loading');
            $container.find('.decision-polls-submit').prop('disabled', true).text('Submitting...');
            
            // This would be an actual API call in the future
            // For now, simulate with setTimeout
            setTimeout(function() {
                // Simulate successful vote
                $container.removeClass('loading');
                
                // Show results
                DecisionPolls.showResults($container, pollId);
            }, 1000);
        },

        /**
         * Show poll results
         * 
         * @param {jQuery} $container Poll container
         * @param {number} pollId Poll ID
         */
        showResults: function($container, pollId) {
            // This would load results from API
            // For now, use placeholder results
            var resultsData = {
                total_votes: 42,
                answers: [
                    { id: 1, text: 'Option 1', votes: 18, percentage: 42.86 },
                    { id: 2, text: 'Option 2', votes: 15, percentage: 35.71 },
                    { id: 3, text: 'Option 3', votes: 9, percentage: 21.43 }
                ]
            };
            
            var html = '<div class="decision-polls-results">';
            html += '<h3>Results</h3>';
            html += '<p>Total votes: ' + resultsData.total_votes + '</p>';
            
            for (var i = 0; i < resultsData.answers.length; i++) {
                var answer = resultsData.answers[i];
                html += '<div class="decision-polls-result-item">';
                html += '<div class="decision-polls-result-text">' + answer.text + ' (' + answer.votes + ' votes, ' + answer.percentage + '%)</div>';
                html += '<div class="decision-polls-result-bar" style="width: ' + answer.percentage + '%;"></div>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $container.html(html);
        },

        /**
         * Initialize results displays
         */
        initResults: function() {
            $('.decision-polls-results-container').each(function() {
                var $container = $(this);
                var pollId = $container.data('poll-id');
                var chartType = $container.data('chart-type');

                if (!pollId) {
                    $container.html('<div class="decision-polls-error">Error: No poll ID specified.</div>');
                    return;
                }

                // Load results
                DecisionPolls.loadResults($container, pollId, chartType);
            });
        },

        /**
         * Load results from API
         * 
         * @param {jQuery} $container Results container
         * @param {number} pollId Poll ID
         * @param {string} chartType Chart type
         */
        loadResults: function($container, pollId, chartType) {
            // This would be an actual API call in the future
            // For now, simulate with setTimeout
            setTimeout(function() {
                // Example results data (placeholder)
                var resultsData = {
                    poll: {
                        id: pollId,
                        title: 'Example Poll #' + pollId
                    },
                    total_votes: 42,
                    answers: [
                        { id: 1, text: 'Option 1', votes: 18, percentage: 42.86 },
                        { id: 2, text: 'Option 2', votes: 15, percentage: 35.71 },
                        { id: 3, text: 'Option 3', votes: 9, percentage: 21.43 }
                    ]
                };

                // Render results
                DecisionPolls.renderResults($container, resultsData, chartType);
            }, 1000);
        },

        /**
         * Render results
         * 
         * @param {jQuery} $container Results container
         * @param {object} resultsData Results data
         * @param {string} chartType Chart type
         */
        renderResults: function($container, resultsData, chartType) {
            var html = '';
            
            html += '<div class="decision-polls-results-title">' + resultsData.poll.title + '</div>';
            html += '<div class="decision-polls-results-total">Total votes: ' + resultsData.total_votes + '</div>';
            
            html += '<div class="decision-polls-results-chart" data-chart-type="' + chartType + '">';
            
            for (var i = 0; i < resultsData.answers.length; i++) {
                var answer = resultsData.answers[i];
                html += '<div class="decision-polls-result-item">';
                html += '<div class="decision-polls-result-text">' + answer.text + ' (' + answer.votes + ' votes, ' + answer.percentage + '%)</div>';
                html += '<div class="decision-polls-result-bar" style="width: ' + answer.percentage + '%; background-color: ' + DecisionPolls.getBarColor(i) + '"></div>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $container.html(html);
        },

        /**
         * Get color for result bar
         * 
         * @param {number} index Answer index
         * @return {string} Color
         */
        getBarColor: function(index) {
            var colors = [
                '#0073aa',
                '#00a0d2',
                '#39b54a',
                '#72aee6',
                '#d94f4f',
                '#ea9142'
            ];
            
            return colors[index % colors.length];
        },

        /**
         * Initialize poll creation forms
         */
        initForms: function() {
            $('.decision-polls-form-container').each(function() {
                var $container = $(this);
                var pollType = $container.data('poll-type');
                var redirect = $container.data('redirect');

                // Render form
                DecisionPolls.renderForm($container, pollType, redirect);
            });
        },

        /**
         * Render poll creation form
         * 
         * @param {jQuery} $container Form container
         * @param {string} pollType Poll type
         * @param {string} redirect Redirect URL
         */
        renderForm: function($container, pollType, redirect) {
            var html = '';
            
            html += '<form class="decision-polls-form" data-poll-type="' + pollType + '">';
            
            html += '<div class="decision-polls-form-field">';
            html += '<label for="poll-title">Question</label>';
            html += '<input type="text" id="poll-title" name="title" placeholder="Enter your question" required>';
            html += '</div>';
            
            html += '<div class="decision-polls-form-field">';
            html += '<label for="poll-description">Description (optional)</label>';
            html += '<textarea id="poll-description" name="description" placeholder="Add more details about your question"></textarea>';
            html += '</div>';
            
            html += '<div class="decision-polls-form-field">';
            html += '<label>Poll Type</label>';
            html += '<div class="decision-polls-form-radio">';
            html += '<label><input type="radio" name="type" value="standard" ' + (pollType === 'standard' ? 'checked' : '') + '> Standard (single choice)</label>';
            html += '</div>';
            html += '<div class="decision-polls-form-radio">';
            html += '<label><input type="radio" name="type" value="multiple" ' + (pollType === 'multiple' ? 'checked' : '') + '> Multiple choice</label>';
            html += '</div>';
            html += '<div class="decision-polls-form-radio">';
            html += '<label><input type="radio" name="type" value="ranked" ' + (pollType === 'ranked' ? 'checked' : '') + '> Ranked choice</label>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="decision-polls-form-field">';
            html += '<label>Answers</label>';
            html += '<div class="decision-polls-form-answers">';
            
            // Add 3 empty answer fields by default
            for (var i = 0; i < 3; i++) {
                html += '<div class="decision-polls-form-answer">';
                html += '<input type="text" name="answers[]" placeholder="Answer option ' + (i + 1) + '" required>';
                if (i > 1) {
                    html += '<button type="button" class="decision-polls-remove-answer">×</button>';
                }
                html += '</div>';
            }
            
            html += '</div>';
            html += '<button type="button" class="decision-polls-form-add-answer">+ Add Another Answer</button>';
            html += '</div>';
            
            html += '<div class="decision-polls-form-field">';
            html += '<button type="submit" class="decision-polls-submit">Create Poll</button>';
            html += '</div>';
            
            html += '</form>';
            
            $container.html(html);
            
            // Attach form events
            DecisionPolls.attachFormEvents($container);
        },

        /**
         * Attach event handlers to form
         * 
         * @param {jQuery} $container Form container
         */
        attachFormEvents: function($container) {
            var $form = $container.find('.decision-polls-form');
            
            // Add answer button
            $container.on('click', '.decision-polls-form-add-answer', function() {
                var $answers = $container.find('.decision-polls-form-answers');
                var count = $answers.find('.decision-polls-form-answer').length;
                
                var $newAnswer = $('<div class="decision-polls-form-answer"></div>');
                $newAnswer.append('<input type="text" name="answers[]" placeholder="Answer option ' + (count + 1) + '" required>');
                $newAnswer.append('<button type="button" class="decision-polls-remove-answer">×</button>');
                
                $answers.append($newAnswer);
            });
            
            // Remove answer button
            $container.on('click', '.decision-polls-remove-answer', function() {
                $(this).parent('.decision-polls-form-answer').remove();
            });
            
            // Form submission
            $form.on('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                if (!$form[0].checkValidity()) {
                    $form[0].reportValidity();
                    return;
                }
                
                // Get form data
                var formData = {
                    title: $form.find('[name="title"]').val(),
                    description: $form.find('[name="description"]').val(),
                    type: $form.find('[name="type"]:checked').val(),
                    answers: []
                };
                
                $form.find('[name="answers[]"]').each(function() {
                    var value = $(this).val().trim();
                    if (value) {
                        formData.answers.push(value);
                    }
                });
                
                // Submit form
                DecisionPolls.submitForm($container, formData);
            });
        },

        /**
         * Submit form to API
         * 
         * @param {jQuery} $container Form container
         * @param {object} formData Form data
         */
        submitForm: function($container, formData) {
            // Show loading state
            $container.addClass('loading');
            $container.find('.decision-polls-submit').prop('disabled', true).text('Creating...');
            
            // This would be an actual API call in the future
            // For now, simulate with setTimeout
            setTimeout(function() {
                // Simulate successful creation
                $container.removeClass('loading');
                
                // Show success message
                var html = '<div class="decision-polls-success">';
                html += '<h3>Poll Created Successfully!</h3>';
                html += '<p>Your poll has been created. You can use this shortcode to display it:</p>';
                html += '<div class="decision-polls-shortcode">[decision_poll id="123"]</div>';
                html += '<p>To display just the results:</p>';
                html += '<div class="decision-polls-shortcode">[decision_poll_results id="123"]</div>';
                
                // Add link to view the poll if redirect is specified
                var redirect = $container.data('redirect');
                if (redirect) {
                    html += '<p><a href="' + redirect + '" class="decision-polls-view-poll">View Your Poll</a></p>';
                }
                
                html += '</div>';
                
                $container.html(html);
            }, 1500);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DecisionPolls.init();
    });

})(jQuery);
