// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    mod_capquiz
 * @author     Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright  2018 NTNU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var parameters = {
        capquizId: 0,
    };

    /**
     * Send the new rating for the question to the server.
     * @param {number} questionId
     * @param {number} rating
     * @param {callback} onSuccess
     * @param {callback} onError
     */
    function sendQuestionRating(questionId, rating, onSuccess, onError) {
        $.ajax({
            type: 'post',
            url: 'action.php',
            data: {
                'action': 'set-question-rating',
                'id': parameters.capquizId,
                'question-id': questionId,
                'rating': rating,
            },
            success: onSuccess,
            error: onError
        });
    }

    /**
     * Send the new rating for the question, and avoid race condition.
     * @param $input
     */
    function submitQuestionRating($input) {
        $input.data('saving', true);
        $input.data('dirty', false);
        var $indicator = $input.next();
        $indicator.css('color', 'blue');
        sendQuestionRating($input.data('question-id'), $input.val(), function() {
            if ($input.data('dirty') === true) {
                submitQuestionRating($input);
            } else {
                $indicator.css('color', 'green');
                $input.data('dirty', false);
                $input.data('saving', false);
            }
        }, function() {
            $indicator.css('color', 'red');
        });
    }

    /**
     * Register the input event listener for question rating fields.
     */
    function registerQuestionRatingListeners() {
        $(document).on('input', '.capquiz-question-rating input', function(event) {
            var $input = $(event.target);
            var isBeingSaved = $input.data('saving');
            if (isBeingSaved === true) {
                $input.data('dirty', true);
                return;
            }
            submitQuestionRating($input);
        });
    }

    /**
     * Set the tab indices for the question rating elements to be more user friendly.
     */
    function fixTabIndicesForQuestionRatingInputs() {
        $('.capquiz-question-rating-submit-wrapper button').each(function(index, object) {
            $(object).attr('tabindex', -1);
        });
    }

    return {
        initialize: function(capquizId) {
            parameters.capquizId = capquizId;
            registerQuestionRatingListeners();
            fixTabIndicesForQuestionRatingInputs();
        }
    };

});
