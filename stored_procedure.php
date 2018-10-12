<?php
error_reporting(0);

$dbname = 'd4d';

try {
  $pdo = new PDO("mysql:host=localhost;dbname=$dbname", "root", "", array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));  
} catch(PDOException $e) {
  die('Could not connect: ' . $e->getMessage());
}
 
$sql = "SHOW TABLES FROM $dbname";
$res = $pdo->prepare($sql);
$res->execute();
$html_body= '';

while ($row1 = $res->fetch(PDO::FETCH_ASSOC)) {


  $table_name='';
  $table_name=$row1['Tables_in_d4d'];
  $html_body .= '<h3>Table Name: <b>"' . $table_name .'"</b></h3>';

  $sql   = "SHOW COLUMNS FROM ".$table_name;
  $query = $pdo->prepare($sql);
  $query->execute();

  $err = $query->errorInfo();
  $bug = $err[2];

  $table_name = str_replace('d4d_', '', $table_name);

  if ($bug != "") { echo "<p>$bug</p>"; }
  $html_body.="DELIMITER $$ <br/>CREATE PROCEDURE sp_".$table_name." (";
  $tbl_spfields_type ='';
  $tbl_fields=array();
  $tbl_values=array();
  $updatefields=array();
  $tbl_fields_id='';
  while ($row = $query->fetch(PDO::FETCH_ASSOC))
  {
    $tbl_spfields_type .=" IN var_".$row['Field']." ".$row['Type'].", ";
    $tbl_fields[]=$row['Field'];

    if($row['Field']=='id')
    {
      $tbl_fields_id.="var_".$row['Field'];
      $tbl_values[]="null";
    } else {
      $tbl_values[]="var_".$row['Field'];
    }
    if($row['Field'] !='id')
    {
      $updatefields[]=$row['Field']." = COALESCE(var_".$row['Field'].", ".$row['Field'].")";
    }
  }
  
  $tbl_spfields_type.="IN var_orderby VARCHAR(55), ";
  $tbl_spfields_type.="IN var_offset INT(11), ";
  $tbl_spfields_type.="IN var_limit INT(11), ";
  $tbl_spfields_type.="IN sp_action VARCHAR(100)"; 
  $html_body.=$tbl_spfields_type;
  $html_body.=")";
  $html_body.="<br>BEGIN<br>";
  $tbl_fields=implode(', ',$tbl_fields);
  $tbl_values=implode(', ',$tbl_values);
  $updatefields=implode(', ',$updatefields);

  $html_body_inner="";
 $html_body_inner .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .='IF sp_action = "INSERT" THEN';
 $html_body_inner .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .="INSERT INTO ".$table_name." (".$tbl_fields.") VALUES (".$tbl_values.");<br/>SELECT LAST_INSERT_ID() as last_id;";
 $html_body_inner .="<br/>&nbsp;&nbsp;&nbsp;&nbsp;END IF;<br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .='IF sp_action = "SELECT" THEN';
 $html_body_inner .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .="SELECT ". $tbl_fields ." FROM ".$table_name." ORDER BY var_orderby LIMIT var_offset, var_limit;";
 $html_body_inner .="<br/>&nbsp;&nbsp;&nbsp;&nbsp;END IF;<br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .='IF sp_action = "SELECTBYID" THEN';
 $html_body_inner .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .="SELECT ". $tbl_fields ." FROM ".$table_name." WHERE id = ".$tbl_fields_id.";";
 $html_body_inner .="<br/>&nbsp;&nbsp;&nbsp;&nbsp;END IF;<br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .='IF sp_action = "UPDATE" THEN';
 $html_body_inner .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
 $html_body_inner .="UPDATE ".$table_name." SET ".$updatefields."
        WHERE id =".$tbl_fields_id.";";
 $html_body_inner .="<br/>&nbsp;&nbsp;&nbsp;&nbsp;END IF;<br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;";
//  $html_body_inner .='IF sp_action = "DELETE" THEN';
//  $html_body_inner .= "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
//  $html_body_inner .="DELETE FROM ".$table_name." WHERE id = ".$tbl_fields_id.";";
// $html_body_inner .="<br/>&nbsp;&nbsp;&nbsp;&nbsp;END IF;<br/>";
  $html_body.= $html_body_inner;
 $html_body.="<br/>END<br/><br/><br /><hr><hr>";
}
echo $html_body;