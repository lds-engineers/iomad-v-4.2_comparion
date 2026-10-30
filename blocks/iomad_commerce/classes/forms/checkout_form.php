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

/**
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\forms;

use \moodleform;
use \context_system;

class checkout_form extends moodleform {
    public function __construct($actionurl) {
        global $CFG;

        parent::__construct($actionurl);
    }

    public function definition() {
        global $CFG, $USER;

        $mform =& $this->_form;

        $mform->addElement('header', 'header', get_string('purchaser_details', 'block_iomad_commerce'));

        $mform->addElement('html', get_string('checkoutpreamble', 'block_iomad_commerce'));

        $strrequired = get_string('required');

        $mform->addElement('text', 'firstname', get_string('firstname'), 'maxlength="100" size="50"');
        $mform->addRule('firstname', $strrequired, 'required', null, 'client');
        $mform->setType('firstname', PARAM_NOTAGS);

        $mform->addElement('text', 'lastname', get_string('lastname'), 'maxlength="100" size="50"');
        $mform->addRule('lastname', $strrequired, 'required', null, 'client');
        $mform->setType('lastname', PARAM_NOTAGS);

        $mform->addElement('text', 'company', get_string('company', 'block_iomad_company_admin'), 'maxlength="40" size="50"');
        // $mform->addRule('company', $strrequired, 'required', null, 'client');
        $mform->setType('company', PARAM_NOTAGS);

        $mform->addElement('text', 'address', get_string('address'), 'maxlength="70" size="50"');
        $mform->addRule('address', $strrequired, 'required', null, 'client');
        $mform->setType('address', PARAM_NOTAGS);

        // uday
        $mform->addElement('text', 'address2', '', 'maxlength="70" size="50"');
        $mform->setType('address2', PARAM_NOTAGS);

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="50"');
        $mform->addRule('city', $strrequired, 'required', null, 'client');
        $mform->setType('city', PARAM_NOTAGS);

        $mform->addElement('text', 'postcode', get_string('postcode', 'block_iomad_commerce'), 'maxlength="20" size="20"');
        $mform->addRule('postcode', $strrequired, 'required', null, 'client');
        $mform->setType('postcode', PARAM_NOTAGS);

        $choices = get_string_manager()->get_list_of_countries();
        $choices = array('' => get_string('selectacountry').'...') + $choices;
        $mform->addElement('select', 'country', get_string('selectacountry'), $choices);
        $mform->addRule('country', $strrequired, 'required', null, 'client');

        // uday
        // State text input (default for non-US)
        $mform->addElement('text', 'state_text', get_string('state', 'block_iomad_commerce'), 'maxlength="20" size="20"');
        $mform->setType('state_text', PARAM_NOTAGS);
        // $mform->addRule('state_text', $strrequired, 'required', null, 'client');

        // State as dropdown (for US).
        $usstates = [
            ''   => get_string('selectastate', 'block_iomad_commerce'),
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming'
        ];
 
        $mform->addElement('select', 'state_select', get_string('state', 'block_iomad_commerce'), $usstates);
        $mform->setType('state_select', PARAM_ALPHANUM);
        // $mform->addRule('state_select', $strrequired, 'required', null, 'client');

        // Show/hide rules.
        $mform->hideIf('state_select', 'country', 'neq', 'US');
        $mform->hideIf('state_text', 'country', 'eq', 'US');

        //end

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="50"');
        $mform->addRule('email', $strrequired, 'required', null, 'client');
        $mform->setType('email', PARAM_NOTAGS);

        $mform->addElement('text', 'phone1', get_string('phone'), 'maxlength="20" size="50"');
        $mform->setType('phone1', PARAM_NOTAGS);
        $mform->addRule('phone1', $strrequired, 'required', null, 'client');

        $mform->addElement('textarea', 'ordernotes', 'Order Notes (optional)', 'wrap="virtual" rows="4" cols="50"');
        $mform->setType('ordernotes', PARAM_TEXT);

        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->addElement('html', '<script>$(`#id_state_select_label ~ .form-label-addon`).html(`<div class="text-danger" title="Required"><i class="icon fa fa-exclamation-circle text-danger fa-fw" title="Required" role="img" aria-label="Required"></i></div>`);</script>');
        $mform->setType('userid', PARAM_INT);

        $this->add_action_buttons(true, get_string('continue'));
    }

    /**
     * Custom validation.
    */
    public function validation($data, $files) {
        $errors = [];
        if($data['country'] == "US"){
            if(empty($data['state_select'])){
                $errors['state_select'] = "Please Select State...";
            }
        }
        return $errors;
    }

    /**
     * Override get_data() to normalize state field.
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }

        if ($data->country === 'US') {
            $data->state = $data->state_select;
        } else {
            $data->state = $data->state_text;
        }

        if(!empty($data->address2)){
            $data->address = $data->address.'<br>'.$data->address2;
        }

        return $data;
    }
}