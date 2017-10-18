<?php
/*
 * 編集|登録フォーム
 */
if($DEBUG)
  error_log("{$_SERVER['PHP_SELF']}@<b>".__FILE__.":".__LINE__."</b>");

if (isset($_POST['DateTime']) ) {
  $DateTime = date("Y-m-d H:i:s", strtotime($_POST['DateTime']));
}else{
  $DateTime = date("Y-m-d H:i:s");
}

$id=NULL;
if (isset($_GET['id']) ) {
  $id = intval($_GET['id']);

  // 指定idのToDoを取得
  try {
    $todo = $dbac->getTodoById($id);
  } catch (\PDOException $e) {
    error_log( "\PDO::例外: " . $e->getMessage() );
    echo "メンテナンス中です[$id]";
    return;
  }
  //var_dump($todo);

  $title = "編集($id)";
  $datetime = htmlspecialchars($todo['datetime']);
  $subject = htmlspecialchars($todo['subject']);
  $detail = htmlspecialchars($todo['detail']);
  //echo "<br/>";  var_dump($datetime);  var_dump($subject);  var_dump($detail);
} else {
  $title = "作成";
  $datetime = $now;	// 雛型としてデフォルト値に現行時刻
  $subject = '';
  $detail = '';
}

// フォーム表示
?>
<center>
<font size="5"><?php echo $title;?></font>
</center>
<table>
<tr><td>
<form action="<?php echo $_SERVER['SCRIPT_NAME'];?>?mode=save" method="post">
  <input type="hidden" name="id" value="<?php echo $id; ?>"/>
  <font size=-1><tt><b>日時</b></tt></font><br/>
  <input type="text" name="DateTime" size="19" value="<?php echo $datetime;?>"/><br/>
  <font size=-1><tt><b>件名</b></tt></font><br/>
  <input type="text" name="Subject" size="56" value="<?php echo $subject;?>"/><br/>
  <font size=-1><tt><b>詳細</b></tt></font><br/>
  <textarea name="Detail" rows="24" cols="72"><?php echo $detail;?></textarea><br><br>
  <center><input type="submit" name="SaveOpt" value="キャンセル"/>
	  <input type="submit" name="SaveOpt" value="保存"/></center>
</form>
</td></tr>
</table>
