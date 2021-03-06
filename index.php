<!DOCTYPE html>
<html>
<head>
    <link href="../css/style.css" rel=stylesheet />
    <title>Infini</title>
</head>

<body class='index is__background--color--blue'>

<?php
    define('MAX_LENGTH',6);
    define('SIZE', mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB));
    $db_host = "104.130.32.112";
    $db_user = "adminDB";
    $db_pass = "t0p\$ecret";
    $db_name = "infinitesting";
    // Intialize the database connection
    $link = mysqli_connect ($db_host, $db_user, $db_pass, $db_name);
    // Verify that we have a valid connection
    if (!$link) {
      echo "Connection Error: " . mysqli_connect_error();
      die();
    }
    function sanitize($data) {
        global $link;
        return htmlentities(strip_tags(mysqli_real_escape_string($link, $data)));
    }
    function encrypt($data){
        // encrypting data based on the Initialization Vector (iv) and a random "SALT".
        // must return in an array for effective access.
        $iv = mcrypt_create_iv(SIZE, MCRYPT_DEV_RANDOM);
        $intermediateSalt = md5(uniqid(rand(),true));
        $salt = substr($intermediateSalt,0,MAX_LENGTH);
        $hash = hash("md5", $salt . $data);
        $encryptData = mcrypt_encrypt(MCRYPT_CAST_256, $hash, $data, MCRYPT_MODE_CFB, $iv);
        // it is necessary to encode into base 64
        return array("encrypt" => base64_encode($iv . $encryptData), "hash" => $hash);
    }
    function decrypt($hash, $data){
        // decrypting encrypted data based on the hash and a IV which was a part of the data.
        // separating the encrypted data from the iv that was appended together.
        $data = base64_decode($data);
        $iv = substr($data, 0, SIZE);
        $encryptedData = substr($data,SIZE);
        return mcrypt_decrypt(MCRYPT_CAST_256, $hash, $encryptedData, MCRYPT_MODE_CFB,$iv);
    }
    function formatDate($tempDate){
        $date = date_create($tempDate);
        $date = date_format($date, '\S\u\b\m\i\t\t\e\d \o\n F d, Y \a\t h:i:s a');
        return $date;
    }
    $months = array(1 => "January", 2 =>"February",3 =>"March", 4 =>"April",5 => "May",6 => "June",7=>"July", 8 => "August",9 => "September", 10 => "October", 11 => "November", 12 => "December");
     $errorFirst = $errorLast = $errorZip = $errorPosition = $errorCVN = $errorLocation = "";
    if(isset($_POST["submit"])) {
        
        if(empty($_POST["fName"])) {
            $errorFirst = "<span class='error'>Please enter first name</span>";
        }
        if(empty($_POST["lName"])) {
            $errorLast = "<span class='error'>Please enter first name</span>";
        }
        if(empty($_POST["zipcode"])) {
            $errorZip = "<span class='error'>Please enter the billing zip code</span>";
        }
        if(empty($_POST["ccNum"])) {
            $errorPosition = "<span class='error'>Please enter the CC number</span>";
        }
        if(empty($_POST["month"]) && empty($_POST["year"])) {
            $errorLocation = "<span class='error'>Please enter the expiration date </span>";
        }
        if(empty($_POST["cvn"])) {
            $errorCVN = "<span class='error'>Please enter the CV number</span>";
        }
        if($errorFirst == "" && $errorLast == "" &&  $errorCVN == "" && $errorPosition == "" && $errorLocation == ""){
            global $link;
            $stmt = $link->prepare("CALL insert_cc_data(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $fName, $lName, $zipcode, $encryptCC, $encryptExpDate, $encryptCVN, $encryptCCHash, $encryptExpDateHash, $encryptCVNHash);
            // sanitizing inputs
            $fName = sanitize($_POST["fName"]);
            $lName = sanitize($_POST["lName"]);
            $zipcode = sanitize($_POST["zipcode"]);
            $ccNum = sanitize($_POST["ccNum"]);
            $expDate = sanitize($_POST["month"] . "/" . $_POST["year"]);
            $cvn = sanitize($_POST["cvn"]);
            // encrypted cc
            $encryptArray       = encrypt($ccNum);
            $encryptCC          = $encryptArray["encrypt"];
            $encryptCCHash      = $encryptArray["hash"];
            // encrypted expiration date
            $encryptArray       = encrypt($expDate);
            $encryptExpDate     = $encryptArray["encrypt"];
            $encryptExpDateHash = $encryptArray["hash"];
            // encrypted CVN
            $encryptArray       = encrypt($cvn);
            $encryptCVN         = $encryptArray["encrypt"];
            $encryptCVNHash     = $encryptArray ["hash"];
          
            $stmt->execute();
            //echo "<h2 class='headerPages'>The credit card information was added to database successfully!</h2>";
            //die();
        }
    }
?>

    <h3 class='index__splash--description--secondary is__text--centered is__text--darker puffer puffer--top'>
       CREDIT CARD FORM
    </h3>
    <style>
           form{
                margin-left: 120px;
           }
           input{
               display: inline-block;
                float: right;
                margin-right: 60%;
            }
            #month{
               margin-left: 93px;
            }
            label{
                clear: both;
            }
           select{
                width: 9.5%;
                margin-bottom: -30px;
            }
        
        #view{
            background-color: white;
            width: 900px;
            margin-top: 100px;
            margin-left: 250px;
        }
        
        p{
            padding-left: 20px;
        }
        
        #submission p{
            font-size: 10pt; 
            padding-left:  600px;
            padding-bottom: 10px;
        }
        
        #ccInfo p{
            font-size: 14pt;
            padding-top: 10px;
        }
        
       </style>
    <div class="container">
        <form action="index.php" method="post">
            <br />
            <h2>Fill out the form for credit card payment</h2>
            <label>First Name: </label>
            <input type=text name="fName" placeholder="First Name" />
            <br />
                Last Name:
            <input type=text name="lName" placeholder="Last Name" />
            <br />
                Billing Zip Code:
            <input type=text name="zipcode" required pattern="[0-9]{5}" placeholder="Zip Code" />
            <br />
                CC Number:
            <input type=text name="ccNum" required pattern="[0-9]{16}" placeholder="16 digits, no spaces" />
            <br />
                   Expiration Date:
                <!-- month -->
            <select name="month">
                    <?php
                      for($i = 1; $i < count($months); $i++){
                           $option = "<option ";
                            if($months[$i] === date('F')){
                               $option .= "selected ='selected'";
                           }
                           $option .= "value='" . (($i < 10) ? "0" . $i : $i) . "'>";
                            $option .= $months[$i] . "</option>";
                           echo $option;
                       }
                    ?>
            </select>
            <!-- year -->
            <select name="year">
                      <?php
                        $year = date('y');
                        for($i = 0; $i <= 5; $i++){
                           echo "<option value='" . ($year + $i) ."'>" . ($year + $i) . "</option>";
                       }
                    ?>
            </select>
            <br />
            <input type=text name="cvn" required pattern="[0-9]{3,4}" placeholder="CV Number" />
            <br />
            <input type=submit id="submit" name="submit" value="Submit" />
        </form>
    </div>
    <script src="js/script.js"></script>

    <div id="view">
        <?php
            $result = mysqli_query($link, "SELECT * from creditcard_data;");
                while($row = mysqli_fetch_assoc($result)){
                    echo "<div id='ccInfo'><p>Name: " . $row['fName'] . " " . $row['lName'] . "<br/>" .
                         "\nCredit Card Number: " . decrypt($row['hash_cc_number'], $row['cc_number']) . "<br/>" .
                         "\nExpiration date: " . decrypt($row['hash_expiration_date'], $row['expiration_date']) . "<br/>" .
                         "\nCVN: " . decrypt($row['hash_cvn'], $row['cvn']) . "<br/>" .
                         "\nZip Code: " . $row['zip_code'] . "</p></div>" .
                         "<div id='submission'><p>" .  formatdate($row['created_at']) . "</p></div></p>";
                }
        ?>
    </div>
</body>
</html>