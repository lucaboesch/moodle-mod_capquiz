<?php
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

namespace mod_capquiz\output;

use mod_capquiz\capquiz;
use mod_capquiz\capquiz_user;
use mod_capquiz\capquiz_urls;
use mod_capquiz\capquiz_question;
use mod_capquiz\capquiz_question_attempt;

defined('MOODLE_INTERNAL') || die();

class question_attempt_renderer {

    private $capquiz;
    private $renderer;

    public function __construct(capquiz $capquiz, renderer $renderer) {
        $this->capquiz = $capquiz;
        $this->renderer = $renderer;
    }

    public function render() {
        if (!$this->capquiz->is_published()) {
            return 'Nothing here yet';
        }
        $question_engine = $this->capquiz->question_engine();
        if ($attempt = $question_engine->attempt_for_user($this->capquiz->user())) {
            if ($attempt->is_answered()) {
                return $this->render_review($attempt);
            } else {
                if ($attempt->is_pending()) {
                    return $this->render_attempt($attempt, $this->attempt_display_options());
                }
            }
        } else {
            return 'You have finished this quiz!';
        }
    }

    private function render_attempt(capquiz_question_attempt $attempt, \question_display_options $displayoptions) {
        $user = $this->capquiz->user();
        $html = $this->render_progress($user);
        $html .= $this->render_question_attempt($attempt, $displayoptions);
        $html .= $this->render_metainfo($user, $attempt);
        return $html;
    }

    private function render_review(capquiz_question_attempt $attempt) {
        $html = $this->render_attempt($attempt, $this->review_display_options());
        $html .= $this->render_review_next_button($attempt);
        return $html;
    }

    public function render_review_next_button(capquiz_question_attempt $attempt) {
        return basic_renderer::render_action_button(
            $this->renderer,
            capquiz_urls::response_reviewed_url($attempt),
            get_string('next', 'capquiz')
        );
    }

    private function render_progress(capquiz_user $user) {
        $questionlist = $this->capquiz->question_list();
        $stars = $questionlist->rating_in_stars($user->rating());
        $percent = $questionlist->next_star_percent($user->rating());

        return $this->renderer->render_from_template('capquiz/student_progress', [
            'progress' => [
                'student' => [
                    'percent' => $percent,
                    'stars' => $questionlist->stars_as_array($stars)
                ]
            ]
        ]);
    }

    public function render_question_attempt(capquiz_question_attempt $attempt, \question_display_options $displayoptions) {
        global $PAGE;
        $question_usage = $this->capquiz->question_usage();
        $context = $this->capquiz->context();

        $PAGE->requires->js_module('core_question_engine');
        return $this->renderer->render_from_template('capquiz/student_question_attempt', [
            'attempt' => [
                'url' => capquiz_urls::response_submit_url($attempt)->out_as_local_url(false),
                'body' => $question_usage->render_question($attempt->question_slot(), $displayoptions, $attempt->question_id()),
                'slots' => ''
            ]
        ]);
    }

    public function render_metainfo(capquiz_user $user, capquiz_question_attempt $attempt) {
        $question = capquiz_question::load($attempt->question_id());

        return $this->renderer->render_from_template('capquiz/student_question_metainfo', [
            'metainfo' => [
                'student' => [
                    'rating' => $user->rating(),
                ],
                'question' => [
                    'id' => $question->id(),
                    'rating' => $question->rating(),
                ]
            ]
        ]);
    }

    private function review_display_options() {
        $options = new \question_display_options();
        $options->context = $this->capquiz->context();
        $options->readonly = true;
        $options->flags = \question_display_options::VISIBLE;
        $options->marks = \question_display_options::VISIBLE;
        $options->rightanswer = \question_display_options::VISIBLE;
        $options->numpartscorrect = \question_display_options::VISIBLE;
        $options->manualcomment = \question_display_options::VISIBLE;
        $options->manualcommentlink = \question_display_options::VISIBLE;
        return $options;
    }

    private function attempt_display_options() {
        $options = new \question_display_options();
        $options->context = $this->capquiz->context();
        $options->flags = \question_display_options::HIDDEN;
        $options->marks = \question_display_options::HIDDEN;
        $options->rightanswer = \question_display_options::HIDDEN;
        $options->numpartscorrect = \question_display_options::HIDDEN;
        $options->manualcomment = \question_display_options::HIDDEN;
        $options->manualcommentlink = \question_display_options::HIDDEN;
        return $options;
    }

}
