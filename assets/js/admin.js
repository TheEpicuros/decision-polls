/**
 * Decision Polls Admin JavaScript
 */

(function($) {
    'use strict';

    // Main Decision Polls Admin object
    window.DecisionPollsAdmin = {
        init: function() {
            this.initTabs();
            this.initAnswerFields();
            this.initDatepickers();
        },

        /**
         * Initialize tabs on the settings page
         */
        initTabs: function() {
            $('.decision-polls-tabs-nav li a').on('click', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var target = $this.attr('href');
                
                // Update active tab
                $('.decision-polls-tabs-nav li').removeClass('active');
                $this.parent().addClass('active');
                
                // Show content
                $('.decision-polls-tab-content').removeClass('active');
                $(target).addClass('active');
            });
        },

        /**
         * Initialize answer fields (adding/removing/reordering)
         */
        initAnswerFields: function() {
            var $container = $('.decision-polls-answers');
            
            if (!$container.length) {
                return;
            }
            
            // Add answer button
            $('.decision-polls-add-answer').on('click', function(e) {
                e.preventDefault();
                
                var count = $container.find('.decision-polls-answer').length;
                var html = '<div class="decision-polls-answer">';
                html += '<span class="decision-polls-answer-drag">☰</span>';
                html += '<div class="decision-polls-answer-content">';
                html += '<input type="text" name="answers[]" placeholder="Answer option ' + (count + 1) + '">';
                html += '</div>';
                html += '<div class="decision-polls-answer-actions">';
                html += '<button type="button" class="decision-polls-remove-answer">×</button>';
                html += '</div>';
                html += '</div>';
                
                $container.append(html);
            });
            
            // Remove answer button
            $container.on('click', '.decision-polls-remove-answer', function() {
                $(this).closest('.decision-polls-answer').remove();
            });
            
            // Make answers sortable
            if (typeof $.fn.sortable !== 'undefined') {
                $container.sortable({
                    handle: '.decision-polls-answer-drag',
                    axis: 'y'
                });
            }
        },

        /**
         * Initialize datepickers for start/end dates
         */
        initDatepickers: function() {
            if (typeof $.fn.datepicker !== 'undefined') {
                $('.decision-polls-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd'
                });
            }
        },

        /**
         * Show a preview of poll results
         */
        previewResults: function() {
            var $resultsPreview = $('.decision-polls-results-preview');
            
            if (!$resultsPreview.length) {
                return;
            }
            
            // Get answers
            var answers = [];
            $('.decision-polls-answer input[type="text"]').each(function() {
                var text = $(this).val().trim();
                if (text) {
                    answers.push(text);
                }
            });
            
            if (!answers.length) {
                $resultsPreview.html('<p>Add some answer options to see a preview.</p>');
                return;
            }
            
            // Generate random results
            var results = [];
            var totalVotes = Math.floor(Math.random() * 100) + 20; // 20-120 votes
            
            // Generate random votes for each answer
            for (var i = 0; i < answers.length; i++) {
                var votes = Math.floor(Math.random() * (totalVotes / 2));
                totalVotes -= votes;
                
                results.push({
                    text: answers[i],
                    votes: votes
                });
            }
            
            // Add remaining votes to the first answer
            results[0].votes += totalVotes;
            
            // Calculate percentages
            var totalVotesCount = 0;
            for (var i = 0; i < results.length; i++) {
                totalVotesCount += results[i].votes;
            }
            
            for (var i = 0; i < results.length; i++) {
                results[i].percentage = Math.round((results[i].votes / totalVotesCount) * 100);
            }
            
            // Sort by votes (highest first)
            results.sort(function(a, b) {
                return b.votes - a.votes;
            });
            
            // Render preview
            var html = '<h3>Results Preview</h3>';
            html += '<p>Total votes: ' + totalVotesCount + '</p>';
            
            for (var i = 0; i < results.length; i++) {
                var result = results[i];
                html += '<div class="decision-polls-result">';
                html += '<div class="decision-polls-result-text">';
                html += '<span class="text">' + result.text + '</span>';
                html += '<span class="votes">' + result.votes + ' votes (' + result.percentage + '%)</span>';
                html += '</div>';
                html += '<div class="decision-polls-result-bar">';
                html += '<div class="decision-polls-result-bar-inner" style="width: ' + result.percentage + '%"></div>';
                html += '</div>';
                html += '</div>';
            }
            
            $resultsPreview.html(html);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DecisionPollsAdmin.init();
        
        // Preview button
        $('.decision-polls-preview-results').on('click', function(e) {
            e.preventDefault();
            DecisionPollsAdmin.previewResults();
        });
    });

})(jQuery);
