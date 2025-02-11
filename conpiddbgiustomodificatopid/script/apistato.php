<?php
  include"connessioni.php";
  $strqry="SELECT * FROM `statoirrigazione` WHERE statoirrigazione.id=1";//.$_POST["id"];
  $dati=mysqli_query($conn,$strqry);
  $js=array();
  while($res=mysqli_fetch_array($dati))
  {
    $js= array(
      'stato'=>	$res["stato"],
      'timer'=>$res["timer"],
      'impostatoorainizio'=>$res["impostatoorainizio"],
      'setpoint'=>$res["setpoint"],
      'responsetime'=>$res["responsetime"]

      );
      }
    echo json_encode($js);
    mysqli_close($conn);
?>