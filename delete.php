<?php
/*
 * フォームから送られたデータをToDoデータベースに保存｜更新する
 */
if($DEBUG)
  error_log("{$_SERVER['PHP_SELF']}@<b>".__FILE__.":".__LINE__."</b>");

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
  //  var_dump($todo);

  $title = "削除($id)";
  $datetime = htmlspecialchars($todo['datetime']);
  $subject = htmlspecialchars($todo['subject']);
  $detail = htmlspecialchars($todo['detail']);
}

// フォーム表示
?>
<center>
<font size="5"><?php echo $title;?></font>
</center>
<table>
<tr><td>
<form action="<?php echo $_SERVER['SCRIPT_NAME'];?>?mode=list" method="post">
  <input type="hidden" name="id" value="<?php printf("-%d", $id); ?>"/>
  <font size=-1><tt><b>日時</b></tt></font><br/>
  <input type="text" name="DateTime" size="19" readonly value="<?php echo $datetime;?>" class="read-only"/><br/>
  <font size=-1><tt><b>件名</b></tt></font><br/>
  <input type="text" name="Subject" size="56" readonly value="<?php echo $subject;?>" class="read-only"/><br/>
  <font size=-1><tt><b>詳細</b></tt></font><br/>
  <textarea name="Detail" rows="24" cols="72" readonly class="read-only"><?php echo $detail;?></textarea><br><br>
  <center><input type="submit" name="SaveOpt" value="キャンセル"/>
	  <input type="submit" name="SaveOpt" value="削除"/></center>
</form>
</td></tr>
</table>
