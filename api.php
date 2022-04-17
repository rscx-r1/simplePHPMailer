<?php

require '/var/www/html/vendor/phpmailer/phpmailer/src/Exception.php';
require '/var/www/html/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '/var/www/html/vendor/phpmailer/phpmailer/src/SMTP.php';

$servername = "servername";
$usernameb = "usernameb";
$passwordb = "passwordb";
$dbname = "dbname";

function sendMessage($chatID, $messaggio, $token) {
  $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chatID . "&parse_mode=markdown";
  $url = $url . "&text=" . urlencode($messaggio);
  $ch = curl_init();
  $optArray = array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true
  );
  curl_setopt_array($ch, $optArray);
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

if ($_REQUEST["use"] == "savedb") {
  $conn = new mysqli($servername, $usernameb, $passwordb, $dbname);

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $result = $conn->query("SELECT * FROM settings");

  $rows = $result->fetch_all(MYSQLI_ASSOC);
  foreach ($rows as $row) {
    if (!empty($_POST["SMTPSecure"])) { $SMTPSecure = $_POST["SMTPSecure"]; } else { $SMTPSecure = $row["SMTPSecure"]; }
    if (!empty($_POST["Host"])) { $SMTPSecure = $_POST["Host"]; } else { $SMTPSecure = $row["Host"]; }
    if (!empty($_POST["Port"])) { $SMTPSecure = $_POST["Port"]; } else { $SMTPSecure = $row["Port"]; }
    if (!empty($_POST["Username"])) { $SMTPSecure = $_POST["Username"]; } else { $SMTPSecure = $row["Username"]; }
    if (!empty($_POST["Password"])) { $SMTPSecure = $_POST["Password"]; } else { $SMTPSecure = $row["Password"]; }
    if (!empty($_POST["setFrom"])) { $SMTPSecure = $_POST["setFrom"]; } else { $SMTPSecure = $row["setFrom"]; }
    
    $sql = "UPDATE settings SET `SMTPSecure`='$SMTPSecure', `Host`='$Host', `Port`=$Port, `Username`='$Username', `Password`='$Password', `setFrom`='$setFrom'";

    header("Location: /other/admin/panel");
  }
}
elseif ($_REQUEST["use"] == "send") {
  if (isset($_REQUEST["apiKey"]) && isset($_REQUEST["service"]) && isset($_REQUEST["to"]) && isset($_REQUEST["price"]) && isset($_REQUEST["item"]) && isset($_REQUEST["url"])) {
    $api_key = trim($_REQUEST["apiKey"]);
    $service = trim($_REQUEST["service"]);
    $to = trim($_REQUEST["to"]);
    $price = trim($_REQUEST["price"]);
    $item = trim($_REQUEST["item"]);
    $date = date("Y-m-d H:i:s");
    $url = trim($_REQUEST["url"]);
    $number = random_int(1, 9999); // random num
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM api_keys WHERE api_key = '$api_key'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
      $result = $conn->query("SELECT * FROM settings");

      $rows = $result->fetch_all(MYSQLI_ASSOC);
      foreach ($rows as $row) {
        $SMTPSecure = $row["SMTPSecure"];
        $Host = $row["Host"];
        $Port = $row["Port"];
        $Username = $row["Username"];
        $Password = $row["Password"];
        $setFrom = $row["setFrom"];
      }
      $mail = new PHPMailer\PHPMailer\PHPMailer();
      $mail->CharSet = 'UTF-8';
      
      // Settings SMTP
      $mail->isSMTP();
      $mail->SMTPAuth = true;
      $mail->SMTPDebug = 0;

      $mail->SMTPSecure = $SMTPSecure;
      $mail->Host = $Host;
      $mail->Port = $Port;
      $mail->Username = $Username;
      $mail->Password = $Password;
      //$mail->trackopens = true; // tracking module
      $mail->setFrom($setFrom);		

      $mail->addAddress($to);

      if ($service == "service") {
        $mail->Subject = 'Test service message.';
        $body = """HTML_BODY""";
      } 
      
      $mail->msgHTML($body);

      if($mail->send()) {
        $sql = "INSERT INTO logs(logs) VALUES ('$date => Service: $service => To: $to | Item: $item | Price: $price | Url: $url | Key: $api_key')";
        $result = $conn->query($sql);
        $sql = "UPDATE api_keys SET `uses`=`uses` + 1 WHERE `api_key`='$api_key'";
        $result = $conn->query($sql);
        $msg = "📤 *eMail sending*\n\n🔑 ApiKEY: `$api_key`\n\n📫 Service: `$service`\nℹ️ Item Name: *$item*\n🔗 Link: $url\n🖌 Cost: $price €\n\n🕛 Time: *$date*\n📥 Mailing to: $to\n";
        $result = sendMessage($tgid, $msg, $tgbottoken);
        echo 'Mail sent.';
      }
    } else {
      echo "Key unavailable.";
    }
  }
}