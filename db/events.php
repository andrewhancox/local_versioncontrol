<?php
/**
 * Created by PhpStorm.
 * User: andrewhancox
 * Date: 19/02/2019
 * Time: 14:16
 */


$observers = array(
        array(
                'eventname'   => '\core\event\course_module_updated',
                'callback'  => 'local_versioncontrol\eventhandlers::course_module_updated',
                'priority'    => 9999,
        ),
);