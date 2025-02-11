<?php
  include"connessioni.php";
  $strqry="SELECT * FROM `PID` WHERE PID.id=1";//.$_POST["id"];
  $dati=mysqli_query($conn,$strqry);
  $js=array();
  while($res=mysqli_fetch_array($dati))
  {
    $js= array(
      'Kp'=>$res["Kp"],
      'Ki'=>$res["Ki"],
      'Kd'=>$res["Kd"],
      'setpoint'=>$res["setpoint"],
      );
      }
    echo json_encode($js);
    mysqli_close($conn);
     file_get_contents("https://nicolaaliuni.altervista.org/irrigazione/fine.php");
?>