<?php

echo '<a href="index.php">Home<br /></a>';

$content = file_get_contents('php://input'); //Receives the JSON Result from safaricom
$res = json_decode($content, true); //Convert the json to an array

$dataToLog = array(
    date("Y-m-d H:i:s"), //Date and time
    " MerchantRequestID: " . $res['Body']['stkCallback']['MerchantRequestID'],
    " CheckoutRequestID: " . $res['Body']['stkCallback']['CheckoutRequestID'],
    " ResultCode: " . $res['Body']['stkCallback']['ResultCode'],
    " ResultDesc: " . $res['Body']['stkCallback']['ResultDesc'],
);

$data = implode(" - ", $dataToLog);
$data .= PHP_EOL;
file_put_contents('transaction_log', $data, FILE_APPEND); //Logs the results to our log file

//Saves the result to the database
$conn = new PDO("mysql:host=localhost;dbname=shakingmachine_onlinefoodphp", "shakingmachine_iorder", "icb*lGIIq;Q5");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $ID = $row['id'];

    if ($res['Body']['stkCallback']['ResultCode'] == '1032') {
        $sql = $conn->query("UPDATE `orders` SET `status` = 'CANCELLED' WHERE `orders`.`id` = $ID");
        $rs = $sql->execute();
    } else {
        $sql = $conn->query("UPDATE `orders` SET `status` = 'SUCCESS' WHERE `orders`.`id` = $ID");
        $rs = $sql->execute();
    }

    if ($rs) {
        file_put_contents('error_log', "Records Inserted", FILE_APPEND);;
    } else {
        file_put_contents('error_log', "Failed to insert Records", FILE_APPEND);
    }
    // insert the data into payment table
    /* 
    uid` int(11) NOT NULL,
    `order_id` int(11) NOT NULL,
    `total` int(11) NOT NULL,
    `MerchantRequestID` varchar(50) NOT NULL,
    `CheckoutRequestID` varchar(100) NOT NULL,
    `ResponseCode` int(2) NOT NULL,
    `ResponseDescription` varchar(255) NOT NULL,
    `CustomerMessage` varchar(255) NOT NULL */
    $uid = $_SESSION['uid'];
    $order_id = $_SESSION['order_no'];
    $total = $_SESSION['amount'];
    $MerchantRequestID = $res['Body']['stkCallback']['MerchantRequestID'];
    $CheckoutRequestID = $res['Body']['stkCallback']['CheckoutRequestID'];
    $ResponseCode = $res['Body']['stkCallback']['ResultCode'];
    $ResponseDescription = $res['Body']['stkCallback']['ResultDesc'];
    $CustomerMessage = $res['Body']['stkCallback']['ResultDesc'];

    $sql = $conn->query("INSERT INTO `payments` (`uid`, `order_id`, `total`, `MerchantRequestID`, `CheckoutRequestID`, `ResponseCode`, `ResponseDescription`, `CustomerMessage`) VALUES ('$uid', '$order_id', '$total', '$MerchantRequestID', '$CheckoutRequestID', '$ResponseCode', '$ResponseDescription', '$CustomerMessage')");
    $rs = $sql->execute();
    if ($rs) {
        file_put_contents('error_log', "Records Inserted", FILE_APPEND);;
    } else {
        file_put_contents('error_log', "Failed to insert Records", FILE_APPEND);
    }
}
