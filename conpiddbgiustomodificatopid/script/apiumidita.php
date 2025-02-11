<?php
  include"connessioni.php";
  $strqry="SELECT * FROM `datiumidita` ORDER BY `datiumidita`.`id` DESC limit 1";//.$_POST["id"];
  $dati=mysqli_query($conn,$strqry);
  $js=array();
  while($res=mysqli_fetch_array($dati))
  {
    $js= array(
      'id'=>	$res["id"],
      'valore'=>$res["valore"],
      'data'=>$res["data"],
      'ora'=>$res["ora"],

      );
      }
    echo json_encode($js);
    mysqli_close($conn);
?>