<?php
// Send the vote response.
if($data['arg'] == "new") {
    send_response_vote($update, $data,false,false,true);
}else {
    send_response_vote($update, $data,false,false);
}
exit();
