<?php
class block_custom_upcoming_events extends block_base {
    public function init() {
        // $this->title = get_string('pluginname', 'block_custom_upcoming_events');
        $this->title = '';
    }

    public function get_content() {
        global $PAGE, $USER, $DB, $CFG;
        $this->content = new stdClass();

        $html = '
         <div class="row p-3 bg_custom1 rounded rounded-3 my-3" id="upevent">
            <div class="col-md-12 table-responsive">
            <h5>' . get_string('upcomingevents', 'block_custom_upcoming_events') . '</h5>
                <table class="table">
                    <thead>
                      <tr>
                        <th></th>
                            <th>' . get_string('events', 'block_custom_upcoming_events') . '</th>
                            <th>' . get_string('starttime', 'block_custom_upcoming_events') . '</th>
                            <th>' . get_string('endtime', 'block_custom_upcoming_events') . '</th>
                            <!--<th>Location</th>-->
                            <th>' . get_string('eventlinks', 'block_custom_upcoming_events') . '</th>
                      </tr>
                    </thead>
                    <tbody class="evtlist" >
                    </tbody>
                </table>
            </div>
         </div>

        <script>
            $(document).ready(function(){
                console.log("aaaaaa");
                $.ajax({
                    type:"POST",
                    url:"'.$CFG->wwwroot.'/blocks/custom_upcoming_events/ajax.php",
                    success:function(response){
                        response = JSON.parse(response);
                        $(".evtlist").html(response);

                    }
                });
            });

        </script>


        ';
        $this->content->text = $html;

        return $this->content;
    }
}
