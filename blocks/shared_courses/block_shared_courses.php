<?php
class block_shared_courses extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_shared_courses');
    }

    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = 'Shared Courses Block Working';
        $this->content->footer = '';

        return $this->content;
    }

    public function applicable_formats() {
        return array(
            'all' => true
        );
    }
}