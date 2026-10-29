<?php
class block_learnpeek_shop extends block_list {

    /**
     * @var bool Flag to indicate whether the header should be hidden or not.
     */
    private $headerhidden = true;

    function init() {
        $this->title = get_string('pluginname', 'block_learnpeek_shop');
    }
}