<?php
require_once('../../config.php');
require_once("locallib.php");
global $DB, $PAGE, $CFG;
if (isloggedin()){
    redirect($CFG->wwwroot.'/my');
    exit();
}
$companyaccess = check_company_access_restriction();
if($companyaccess){
  redirect($CFG->wwwroot . '/login/index.php');
}



echo $OUTPUT->header();

$current_host = $_SERVER['HTTP_HOST'];
$getcompany = $DB->get_record_sql("SELECT * FROM {company} WHERE hostname LIKE ?", array($current_host));
if($getcompany){
  $registrationtitle = get_string('register_your_account', 'local_user_registration');
  echo "<style>
  .individual_registration {
    width: 100%;
    max-width: 100%;
    flex: 1;
  }

  </style>";
}else{
  $registrationtitle = "CHOOSE ACCOUNT TYPE TO CREATE";
}
?>

<style>
  .primary-navigation [data-key='home']{
    display:none;
  }
  .popup-login_cst_box {
    padding: 50px;
    background-color: #d0d9f7;
    border-radius: 10px;
    /* height: 68vh; */
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .register-wrapper h2 {
    color: #192b67;
    margin: 20px 0;
    font-size: 35px;
    text-align: center;
  }

  .register-type {
    padding: 10px;
  }


  .flip-card-front:hover,
  .flip-card-back:hover {
    transform: scale(0.9);
    transition: all .5s ease-in-out;
  }


  .flip-card-front,
  .flip-card-back {
    margin: 0 20px;
    background-color: #839af0;
    border-radius: 10px;
    box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
    transition: all .5s ease-in-out;
  }


  .flip-card-front a:hover,
  .flip-card-back a:hover {
    text-decoration: none;
  }

  .flip-card-front .register-type-title,
  .flip-card-back .register-type-title {
    padding: 10px;
    background-color: #e4e6ef;
    font-size: 18px;
    color: #193570;
    margin: 0 0 10px 0;
    border-radius: 5px;
  }

  .register-type-box {
    padding: 0 20px;
  }

  .register-image {
    width: 80px;
    /* padding: 10px; */
    margin: auto;
    background-color: #d0d9f7;
    /* border-top-right-radius: 5px;
    border-bottom-right-radius: 5px; */
    border-radius: 10px;
    font-size: 50px;
    color: #192b67;
    text-align: center;
    background: linear-gradient(78deg, rgba(68, 102, 235, 1) 0%, rgba(160, 178, 242, 1) 100%) !important;
    color: #fff;
  }

  .register-text {
    font-size: 20px;
    padding: 20px;
    text-align: center;
    color: #fff;
    font-weight: 400;
    font-style: italic;
  }

  .register-wrapper {
    width: 80%;
    margin: auto;
  }

  @media (max-width:1200px) {
    .register-wrapper {
      width: 100%;
    }

    .col-inner {
      justify-content: center;
      align-items: center;
    }

    .register-text {
      font-size: 16px;
    }


    .register-type-box {
      padding: 0;
    }



    .register-wrapper h2 {
      font-size: 25px;
    }
  }

  @media(max-width:767px) {
    .popup-login_cst_box {
      padding: 20px;
    }

    .register-image {
      font-size: 30px;
    }

    .register-wrapper h2 {
      font-size: 16px;
    }
  }

  @media (max-width:500px) {
    .popup-login_cst_box {
      padding: 5px;
    }

    .flip-card-front,
    .flip-card-back {
      margin: 0;
      max-width: 100%;
      flex: 1;
    }

   
  }
</style>

<div class="popup-login_cst_box">
  <div class="popup-container">
    <div class="login-html">
      <div class="popup_register-form">
        <div class="register-type-wrapper">
          <div class="register-wrapper">
          <h2><?php echo $registrationtitle; ?></h2>
            <div class="register-type">
              <div class="flip-card">
                <div class="col-inner flip-card-inner d-flex flex-lg-row flex-column justify-content-center">
                  <div class="flip-card-front col-xl-6 col-lg-6 col-10  my-3  p-0 shadow-md individual_registration">
                    <div class="col-wrap">
                      <a href="/local/user_registration/individual_registration.php">
                        <div class="register-type-title"><?php echo get_string('register_as_individual', 'local_user_registration'); ?></div>
                        <div class="register-image">
                          <i class="fa fa-user"></i>
                        </div>
                        <div class="register-type-box">
                            <div class="register-text"> <?php echo get_string('individual_text', 'local_user_registration'); ?> </div>
                        </div>
                      </a>
                    </div>
                  </div>
                  <?php
                  $allow_brg = get_config(null, "rm_business_registration{$getcompany->id}");
                  if(empty($allow_brg)){ ?>
                  <div class="flip-card-back col-xl-6 col-lg-6 col-10  my-3 p-0 shadow-md business_registration">
                    <div class="col-wrap">
                      <a href="/local/user_registration/business_registration.php">
                        <div class="register-type-title"><?php echo get_string('register_as_business', 'local_user_registration'); ?></div>
                        <div class="register-image">
                          <i class="fa fa-users"></i>
                        </div>
                        <div class="register-type-box">                      
                          <div class="register-text"> <?php echo get_string('business_text', 'local_user_registration'); ?>
                          </div>
                        </div>
                      </a>
                    </div>
                  </div>
                  <?php } ?>

                </div>
              </div>
              <!-- <button id="cst_change">Book as Business</button> -->

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php

echo $OUTPUT->footer();
