<?php
// Send the vote response.
if($data['arg'] == "new") {
    send_response_vote($update, $data,false,false,true);
}else if ($config->RAID_PICTURE){
   send_response_vote($update, $data,false,false); 
} else {
   send_response_vote($update, $data); 
}
exit();
