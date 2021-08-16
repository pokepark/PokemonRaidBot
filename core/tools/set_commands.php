<html>

<head>
    <title>Set Telegram bot commands</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css" />
</head>

<body>
<form action="?post=1" method="post">
<table style="width:70%;margin-left:auto;margin-right:auto;margin-top:2%;" class="table is-bordered">
    <thead>
    <tr>
        <th colspan="3">
            API key: <input type="text" style="width:100%" name="API_KEY" class="input" value="<?php echo isset($_POST['API_KEY'])?$_POST['API_KEY']:'';?>" placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11">
        <?php if(isset($_GET['post']) && $_GET['post'] == 1 && (!isset($_POST['API_KEY']) || empty($_POST['API_KEY']))) echo 'ERROR! API key missing!';?>
        </th>
    </tr>
    </thead>
    <tr>
        <td style="width:33%;">
            <table style="width:100%" class="table">
                <thead>
                    <tr>
                        <th colspan="2">Current commands:</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $scopes = ['default','all_private_chats','all_group_chats','all_chat_administrators'];
                    if(isset($_GET['post']) && $_GET['post'] == 1 && isset($_POST['API_KEY']) && !empty($_POST['API_KEY'])) {
                        if($_POST['action'] == 'Set' && $_POST['setscope'] != 'none') {
                            $commands = [];
                            for($i=0;$i<8;$i++) {
                                if(!empty($_POST['c'.$i]) && !empty($_POST['d'.$i])) {
                                    $commands[] = ['command' => $_POST['c'.$i], 'description' => $_POST['d'.$i]];
                                }
                            }
                            $scope = ['type'=>$_POST['setscope']];
                            $request = json_encode([
                                            'method'=>'setMyCommands',
                                            'commands'=>$commands,
                                            'scope'=>$scope,
                                            ]);
                            $response = curl_json_request($request);
                        }
                        if($_POST['action'] == 'Delete' && $_POST['delscope'] != 'none') {
                            $scope = ['type'=>$_POST['delscope']];
                            $request = json_encode([
                                            'method'=>'deleteMyCommands',
                                            'scope'=>$scope,
                                            
                                            ]);
                            $response = curl_json_request($request);
                        }
                        foreach($scopes as $scope) {
                            $request = json_encode(['method'=>'getMyCommands','scope'=>['type'=>$scope]]);
                            $response = curl_json_request($request);
                            $result= json_decode($response, true);
                            if($result['ok'] == true) {
                                echo '<tr><th colspan="2">'.$scope.'</th></tr>';
                                $row = '';
                                foreach($result['result'] as $id=>$row) {
                                    echo '<tr><td>'.$row['command'].'</td><td>'.$row['description'].'</td></tr>';
                                }
                                if(empty($row))  echo '<tr><td colspan="2"><i>None</i></td></tr>';
                            }else {
                                echo '<tr><td colspan="2">ERROR: '.$response.'</td></tr>';
                            }
                        }
                    }
                    ?>
                    </tbody>
            </table>
        </td>
        <td style="width:33%;">
            <table>
                <thead>
                    <tr>
                        <th colspan="2">Set commands:</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for($i=0;$i<8;$i++) {
                        echo '<tr><td><input type="text" name="c'.$i.'" placeholder="Command" class="input is-primary"></td><td><input type="text" name="d'.$i.'" placeholder="Description" class="input is-primary"></td></tr>';
                    }
                    ?>
                    <tr>
                        <td colspan="2" style="vertical-align:middle">
                            Scope:
                            <div class="select">
                            <select name="setscope">
                                <option value="none">--- Select ---</option>
                                <?php foreach($scopes as $scope) { echo '<option value="'.$scope.'">'.$scope.'</option>\r\n';}?>
                            </select>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
        <td style="width:33%;">
            <table>
                <thead>
                    <tr>
                        <th>Delete commands:</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Scope:
                            <div class="select">
                            <select name="delscope">
                            <option value="none">--- Select ---</option>
                            <?php foreach($scopes as $scope) { echo '<option value="'.$scope.'">'.$scope.'</option>';}?>
                            </select></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td><input type="submit" name="action" class="button is-link" value="Get"></td>
        <td><input type="submit" name="action" class="button is-primary" value="Set"></td>
        <td><input type="submit" name="action" class="button is-danger" value="Delete"></td>
    </tr>
</table>
</form>

<?php
function curl_json_request($json)
{
    // Telegram
    $URL = 'https://api.telegram.org/bot' . $_POST['API_KEY'] . '/';
    $curl = curl_init($URL);

    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    // Execute curl request.
    $json_response = curl_exec($curl);

    // Close connection.
    curl_close($curl);

    // Return response.
    return $json_response;
}
?>