<?php

class block_iped extends block_base
{
    public function init()
    {
        $this->title = get_string('pluginname', 'block_iped');
    }

    public function get_content()
    {
        global $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = 'block footer';
        $this->content->footer = 'Here is the block contents';


        return $this->content;
    }

    public function instance_allow_multiple()
    {
        return true;
    }

    public function has_config()
    {
        return true;
    }
}