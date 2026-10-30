<?php

require_once(__DIR__ . '/../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT;

// Page setup.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/learnpeek_shop/stop_registration.php'));
$PAGE->set_title('Stop Registration');
$PAGE->set_heading('Stop Registration');

// $companies = $DB->get_records('company', null, 'shortname ASC');
$companies = $DB->get_records('company', ['suspended' => 0], 'shortname ASC');


if (optional_param('save', 0, PARAM_INT)) {

    require_sesskey();

    $selectedcompanies = optional_param_array('companies', [], PARAM_TEXT);
    $jsoncompanies = json_encode(array_values($selectedcompanies));

    // We always keep only one record.
    $record = $DB->get_record_sql(
        "SELECT *
           FROM {registration_settings}
       ORDER BY id ASC
          LIMIT 1"
    );

    if ($record) {

        $record->companies = $jsoncompanies;
        $record->timemodified = time();

        $DB->update_record('registration_settings', $record);

    } else {

        $record = new stdClass();
        $record->companies = $jsoncompanies;
        $record->timecreated = time();
        $record->timemodified = time();

        $DB->insert_record('registration_settings', $record);
    }

    redirect(
        new moodle_url('/blocks/learnpeek_shop/stop_registration.php'),
        'Settings saved successfully.',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// LOAD SAVED DATA
$savedrecord = $DB->get_record_sql(
    "SELECT *
       FROM {registration_settings}
   ORDER BY id ASC
      LIMIT 1"
);

$selectedcompanies = [];

if ($savedrecord && !empty($savedrecord->companies)) {
    $selectedcompanies = json_decode($savedrecord->companies, true);

    if (!is_array($selectedcompanies)) {
        $selectedcompanies = [];
    }
}

echo $OUTPUT->header();

?>

<div class="container-fluid">

    <form method="post">

        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
        <input type="hidden" name="save" value="1">

        <table class="generaltable table table-striped">

            <thead>

                <tr>
                    <th style="width:80px;">Select</th>
                    <th>Company Short Name</th>
                </tr>

            </thead>

            <tbody>

            <?php foreach ($companies as $company) { ?>

                <tr>

                    <td>

                        <input
                            type="checkbox"
                            name="companies[]"
                            value="<?php echo s($company->shortname); ?>"
                            <?php echo in_array($company->shortname, $selectedcompanies) ? 'checked' : ''; ?>
                        >

                    </td>

                    <td>

                        <?php echo s($company->name); ?>

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

        <br>

        <button type="submit" class="btn btn-primary">
            Save
        </button>

    </form>

</div>

<?php

echo $OUTPUT->footer();