<?php
define("DA_LOGIN_KEY", "<LOGINKEY>");
define("DA_USERNAME", "<ADMIN>");
define("DA_URL", "https://admin.dapanel.tld:2222");
define("AUTH_CODE", "<AUTHKEY>");
function listMailboxes($domain)
{
  $params = ["action" => "list", "domain" => $domain];
  return callDA("CMD_API_POP", $params)["list"] ?? [];
}
function createMailbox($mailbox, $domain)
{
  $passwd = generatePassword();
  $params = [
    "action" => "create",
    "domain" => $domain,
    "user" => $mailbox,
    "passwd" => $passwd,
    "passwd2" => $passwd,
    "quota" => 100,
    "limit" => 30
  ];
  $result = callDA("CMD_API_POP", $params);
  parse_str($result, $output);
  return $output["error"] === '0' ? [true, $passwd] : [false, urldecode($output["text"])];
}
function generatePassword()
{
  $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
  return substr(str_shuffle($chars), 0, rand(8, 12));
}
function callDA($command, $params)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, DA_URL . "/" . $command);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($ch, CURLOPT_USERPWD, DA_USERNAME . ":" . DA_LOGIN_KEY);
  $result = curl_exec($ch);
  if (curl_errno($ch)) {
    throw new Exception(curl_error($ch));
  }
  curl_close($ch);
  return $result;
}
function sanitizeInput($input)
{
  return htmlspecialchars(stripslashes(trim($input)));
}
function isValidEmail($email)
{
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return false;
  }
  list($localPart) = explode('@', $email);
  return ctype_alnum(str_replace(['-', '_'], '', $localPart));
}
function createForwarder($mailbox, $domain, $forwarding_email)
{
  $params = [
    "action" => "create",
    "user" => $mailbox,
    "domain" => $domain,
    "email" => $forwarding_email
  ];
  $result = callDA("CMD_API_EMAIL_FORWARDERS", $params);
  parse_str($result, $output);
  return $output["error"] === '0' ? [true, $forwarding_email] : [false, urldecode($output["text"])];
}
$message = "";
$status = "error";
$allowed_domains = ["domain1.com", "domain2.com"]; // add your allowed domains here
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $authCode = sanitizeInput($_POST["authCode"]);
  if ($authCode !== AUTH_CODE) {
    $message = "Invalid request.";
  } else {
    $mailbox = sanitizeInput($_POST["mailbox"]);
    $domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_STRING);
    if (!in_array($domain, $allowed_domains)) {
      $message = "Invalid request.";
    } else if (!isValidEmail($mailbox . "@" . $domain)) {
      $message = "Invalid request.";
    } else {
      if (isset($_POST["forwarding"])) {
        $forwarding_email = sanitizeInput($_POST["forwarding-email"]);
        if (empty($forwarding_email)) {
          $message = "No forwarding email supplied.";
        } else if (!isValidEmail($forwarding_email)) {
          $message = "Invalid request.";
        }
      }
      if (empty($message)) {
        $mailboxes = listMailboxes($domain);
        if (in_array($mailbox, $mailboxes)) {
          $message = "Mailbox unavailable. Please try again.";
        } else {
          list($success, $passwd) = createMailbox($mailbox, $domain);
          $message = "Mailbox <b>$mailbox@$domain</b> created successfully with password <b>$passwd</b>";
                    if ($success) {
            if (isset($_POST["forwarding"]) && !empty($forwarding_email) && isValidEmail($forwarding_email)) {
              list($forwarding_success, $forwarding_result) = createForwarder($mailbox, $domain, $forwarding_email);
              if ($forwarding_success) {
                $message .= "<br />Your email will also be forwarded to <b>$forwarding_email</b>.";
                              } else {
                $message .= "<br />Failed to set up forwarding.";
                              }
            }
                      } else {
            $message = "Mailbox unavailable. Please try again.";
          }
          $status = $success ? "success" : "error";
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Create Email</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      background-color: #ffffff;
      font-family: Arial, sans-serif;
      color: #000;
    }
    .box {
      border: 1px solid #e1e4e8;
      border-radius: 6px;
      padding: 20px;
      background-color: #ffffff;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
      width: 400px;
      text-align: center;
    }
    h1 {
      color: #24292e;
      margin-bottom: 20px;
    }
    form div {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      margin-bottom: 10px;
    }
    form div label {
      margin-right: 10px;
    }
    input[type="text"],
    input[type="email"],
    select {
      border: 1px solid #D00E17;
      border-radius: 6px;
      color: #24292e;
      width: 50%;
      padding: 5px;
      margin: 0 5px;
    }
    button {
      background-color: #0A8852;
      color: #fff;
      border: none;
      padding: 10px 16px;
      border-radius: 6px;
      font-weight: 600;
      margin-top: 20px;
      align-self: center;
    }
    .status-message {
      margin-top: 20px;
      padding: 10px;
      border-radius: 5px;
    }
    .success {
      background-color: #0A8852;
      color: #fff;
    }
    .error {
      background-color: #D00E17;
      color: #fff;
    }
    .password-modal-content {
      background-color: #fff;
      color: #24292e;
    }
    .close {
      color: #586069;
    }
    .close:hover,
    .close:focus {
      color: #0366d6;
      text-decoration: none;
      cursor: pointer;
    }
    .forwarding-fields,
    .auth-fields {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }
    .forwarding-fields label,
    .auth-fields label {
      width: auto;
    }
    .forwarding-fields input[type="checkbox"] {
      width: auto;
      margin: 0 10px;
    }
    .auth-fields input[type="text"],
    .forwarding-fields input[type="email"] {
      width: 45%;
    }
  </style>
</head>
<body>
  <div class="box">
    <h1>Create a Mailbox</h1>
    <form action="" method="post">
      <div>
        <label for="mailbox">Mailbox:</label>
        <input type="text" id="mailbox" name="mailbox" required>
        <span>@</span>
        <select id="domain" name="domain">
          <option value="domain1.com">domain1.com</option>
          <option value="domain2.com">domain2.com</option>
        </select>
      </div>
      <div class="forwarding-fields">
        <label for="forwarding">Forward email to:</label>
        <input type="checkbox" id="forwarding" name="forwarding">
        <input type="email" id="forwarding-email" name="forwarding-email" placeholder="Optional" disabled>
      </div>
      <div class="auth-fields">
        <label for="authCode">Authorisation Code:</label>
        <input type="text" id="authCode" name="authCode" required>
      </div>
      <button type="submit">Submit</button>
    </form>
    <?php
    if (!empty($message)) {
      echo "<div class='status-message $status'>$message</div>";
    }
    ?>
  </div>
  <script>
    document.getElementById('forwarding').addEventListener('change', function () {
      var forwardingEmail = document.getElementById('forwarding-email');
      forwardingEmail.required = this.checked;
      forwardingEmail.disabled = !this.checked;
  });
  </script>
</body>
</html>
