<?php
require_once($CFG->dirroot . '/lib/formslib.php');
global $DB, $USER;

class manage_company_course extends moodleform {
  //Add elements to form
  public function definition() {
       global $CFG, $DB;
        $id   = optional_param('id', 0, PARAM_INT);
        $selectedcompanies = $DB->get_records_sql("SELECT * FROM {company}  ORDER BY name ASC");
        $selectsmtpsecure = [
                        '' => 'None',
                        'ssl' => 'SSL',
                        'tls' => 'TLS'
                      ];
        $selectsmtpauthtype = [
                          '' => 'None',
                          'LOGIN' => 'LOGIN',
                          'PLAIN' => 'PLAIN',
                          'NTLM' => 'NTLM',
                          'CRAM-MD5' => 'CRAM-MD5'
                      ];
        $selectedcompany  = array();
        foreach($selectedcompanies as $selcompany){
            $selectedcompany[''] = 'Select Company';
            $selectedcompany[$selcompany->id] =    $selcompany->name;
            
        }
        $mform = $this->_form;
        if($id){
          $mform->addElement('select', 'company_id', get_string('selectcompany','block_learnpeek_shop'),$selectedcompany, 'readonly disabled');
        } else {
          $companies = $DB->get_records_sql("SELECT c.* FROM {company} AS c LEFT JOIN {company_category_mapping} AS cmc ON c.id = cmc.company_id AND c.suspended !=1 WHERE cmc.company_id IS NULL AND c.suspended =0");
            $company  = array();
          if($companies){
            foreach($companies as $companylist){
                $company[''] = 'Select Company';
                $company[$companylist->id] =    $companylist->name;
            }

          }else{
             $company[''] = get_string('allcompanyaremapped','block_learnpeek_shop');
             echo "<style>
             .outgoing_mail_section{
               display:none;
             }
             </style>";
          }
          $mform->addElement('select', 'company_id', get_string('selectcompany','block_learnpeek_shop'),$company);
          $mform->addRule('company_id', 'Select Company', 'required', null, 'client', false, false);
        }
        $mform->addElement('static', 'allcategoryele', "", '<div id="allcategory"></div>');

        $mform->addElement('text', 'main_site_url', get_string('mainsiteurl','block_learnpeek_shop'));
        $mform->setType('main_site_url', PARAM_MULTILANG);

        $mform->addElement('advcheckbox', 'cumulative_email', get_string('cumulativeorderemail','block_learnpeek_shop'), get_string('defaultno','block_learnpeek_shop'), array('group' => 1), array(0, 1));

        $mform->addElement('advcheckbox', 'rm_business_registration', get_string('removebusinessuserregistration','block_learnpeek_shop'), get_string('defaultno','block_learnpeek_shop'), array('group' => 1), array(0, 1));

        $mform->addElement('html','<div class="outgoing_mail_section"><div class="mail_heading">'.get_string('SMTPdetailsforoutgoingmailconfiguration','block_learnpeek_shop').'</div>');

        $mform->addElement('text', 'smtphosts', get_string('smtphosts','block_learnpeek_shop'));
        $mform->setType('smtphosts', PARAM_MULTILANG);

        $mform->addElement('text', 'smtpuser', get_string('smtpusername','block_learnpeek_shop'));
        $mform->setType('smtpuser', PARAM_MULTILANG);

        $mform->addElement('text', 'smtppass', get_string('smtppassword','block_learnpeek_shop'));
        $mform->setType('smtppass', PARAM_MULTILANG);

        $mform->addElement('select', 'smtpsecure', get_string('smtpsecurity','block_learnpeek_shop'),$selectsmtpsecure);
        $mform->setDefault('smtpsecure', '');
        // $select->setSelected('0000ff');

        $mform->addElement('select', 'smtpauthtype', get_string('smtpauthtype','block_learnpeek_shop'),$selectsmtpauthtype);
        $mform->setDefault('smtpauthtype', '');

        $mform->addElement('text', 'noreplyaddress', get_string('noreplyaddress','block_learnpeek_shop'));
        $mform->setType('noreplyaddress', PARAM_MULTILANG);

        $mform->addElement('html','<div class="outgoing_mail_section"><div class="mail_heading">'.get_string('googletagmanager','block_learnpeek_shop').'</div>');

        $mform->addElement('text', 'container_id', get_string('containerid','block_learnpeek_shop'));
        $mform->setType('container_id', PARAM_MULTILANG);

        $mform->addElement('html','<div class="mail_heading">'. get_string('testoutgoingmailconfiguration','block_learnpeek_shop').'<br><br>
          <p class="mail_config">'.get_string('notebeforetestingpleasesaveyourconfiguration','block_learnpeek_shop').'<br>
           <a href="' . $CFG->wwwroot . '/local/learnpeek_email_template/testoutgoingmailconf.php?id=' . $id . '" target="_blank">'.get_string('testoutgoingmailconfiguration','block_learnpeek_shop').' </a>
          </p>
          </div>'
        );

        $mform->addElement('html','</div>');


        $mform->addElement('hidden', 'id'); 
        
        $this->add_action_buttons($cancel = true, $submitlabel = 'Assign');
    }

    public function validation($data, $files) {
      $errors = array();

      // If either smtpsecure or smtpauthtype is selected
      if (!empty($data['smtpsecure']) || !empty($data['smtpauthtype'])) {

          // Check if smtpuser is empty
          if (empty($data['smtpuser'])) {
              $errors['smtpuser'] = 'SMTP Username is required when using SMTP Security or Auth Type.';
          }

          // Check if smtppass is empty
          if (empty($data['smtppass'])) {
              $errors['smtppass'] = 'SMTP Password is required when using SMTP Security or Auth Type.';
          }
      }

      return $errors;
  }
}