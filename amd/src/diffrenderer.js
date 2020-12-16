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
 * Adds support for confirmation via JS modal for some management actions at the Manage policies page.
 *
 * @module      local_versioncontrol/diffrenderer
 * @package     local_versioncontrol
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['Diff2Html'
], function(Diff2Html) {

    "use strict";

    return {
        /**
         * Factory method returning instance of the formenhancements
         *
         * @return {formenhancements}
         */
        init: function(diffString) {

            var diffHtml = Diff2Html.html(diffString, {
                drawFileList: true,
                matching: 'lines',
                outputFormat: 'side-by-side',
            });

            document.getElementById('myDiffElement').innerHTML = diffHtml;
        }
    };
});
