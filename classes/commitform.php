<?php
/**
 * Created by PhpStorm.
 * User: andrewhancox
 * Date: 21/11/2018
 * Time: 12:17
 */

namespace local_versioncontrol;

require_once($CFG->libdir . '/formslib.php');

class commitform extends \moodleform {

    /** @var string Persistent class name. */
    protected static $commitclass = 'local_versioncontrol\\commit';

    function definition() {
        $mform = $this->_form;

        $mform->addElement('textarea', 'message', get_string('message', 'local_versioncontrol'), array('rows' => 4, 'cols' => 60));
        $mform->addRule('message', get_string('required'), 'required');
        $mform->setType('message', PARAM_TEXT);

        $this->add_action_buttons();
    }
}
