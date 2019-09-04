<?php
$defaultDaysToScreen   = 30; // default
$daysToScreen = $defaultDaysToScreen;

if (isset( $_POST['submit'] ) ) {
    
    if (!empty($_POST["days"]) ) {
        $userDaysToScreen = $_POST["days"];
        if ($userDaysToScreen <> $defaultDaysToScreen ) $daysToScreen = $userDaysToScreen;
    }
    
    unset( $_POST['submit']);

    include('cron-daily.php');
}

?>

<html>  
<body>

<p>By default, the script will scrape  <?php echo $defaultDaysToScreen ?> days. You can change this here:</p>

<form action="<?php echo $_SERVER["PHP_SELF"] ?>" method="post">
Days to scrape: <input type="text" name="days" placeholder="30"><br>
<input type="submit" name="submit" label="Start">
</form>

</body>
</html>