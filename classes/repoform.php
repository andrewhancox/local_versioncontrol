<?php
/**
 * Created by PhpStorm.
 * User: andrewhancox
 * Date: 21/11/2018
 * Time: 12:17
 */

namespace local_versioncontrol;

use core\form\persistent;

class repoform extends persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'local_versioncontrol\\repo';

    function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'instancetype');
        $mform->setType('instancetype', PARAM_INT);

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);

        $mform->addElement('select', 'trackingtype', get_string('trackingtype', 'local_versioncontrol'), repo::gettrackingtypes(),
                ['required' => 'required']);

        $this->add_action_buttons();
    }

    public function handlepostback() {
        global $USER;

        $datacleaned = $this->get_data();

        if (!$datacleaned) {
            return false;
        }

        if (empty($data->id)) {
            $persistent = new repo();
            $persistent->from_record($datacleaned);
            $persistent->create();

            if ($persistent->get('trackingtype') == repo::TRACKINGTYPE_AUTOMATIC) {
                $persistent->commitchanges($USER->id, time());
            }
        } else {
            $persistent = $this->get_persistent();
            $persistent->from_record($datacleaned);
            $persistent->update();
        }

        return true;
    }
}
