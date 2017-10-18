<!DOCTYPE html>
<html>
<head>
  <title>ToDo App.(PostgreSQL PHP)</title>
  <meta HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
  <link rel="stylesheet" href="./css/todo.css">
</head>
<body>
<?php
   //
#require 'vendor/autoload.php';
require 'DBAccess.php';
use ToDo\DBAccess as DBAccess;

// 初期化
try {
  // パラメータを iniフォーマットの構成ファイルから読み込む
  $params = parse_ini_file('conf/todo.ini', true);
  if ($params === false) {
    throw new \Exception("Error reading ini configuration file");
  }
  if (! isset($params['database']) || ! is_array($params['database'])) {
    throw new \Exception("No database in ini configuration");
  }
  if (! isset($params['default']) || ! is_array($params['default'])) {
    throw new \Exception("No default in ini configuration");
  }

  // デバグレベルの設定
  if (isset($params['default']['debug']))
    $DEBUG=(int)$params['default']['debug'];
  else
    $DEBUG=0;

  // DB接続
  $dbac = new DBAccess($params['database']);
  error_log('\PDO... connection OK.');

  // ToDoアプリのバージョン設定（テーブル名、シーケンス名）
  if (isset($params['default']['version'])){
    $version = (int)$params['default']['version'];
    $dbac->setTodoModelNum($version);
  }

  // テスト
  //var_dump($dbac);
  //  $fnow = strftime('%D %X%z (%a)', time());
  //  $seqno = $dbac->insertTodo('2017-10-10 '. substr($fnow, 9, 8), $fnow );
  //  error_log("sequence no = $seqno \n");

} catch (\PDOException $e) {
  error_log( "\PDO::例外: " . $e->getMessage() );
  echo ("メンテナンス中です。");
  goto end;
}

// 現行時刻を記憶する
$now = strftime('%F %T', time());
// 以下、本体ヘッダー部
?>
  <div class="container">
  <h1>ToDo</h1>
  <font size="3">
  </font>
  <div class="left-column">
  <a href="<?php echo $_SERVER['SCRIPT_NAME'];?>"> [一覧] </a>
  <a href="<?php echo $_SERVER['SCRIPT_NAME'];?>?mode=edit"> [作成] </a>
  </div>
  <div class="right-column"><?php echo $now;?></div>
    <div>
    <blockquote>
    <hr size="1">
<?php
// URLパラメータの表示モードによりページ内容を切り替え
if (isset($_GET['mode']) )
  $mode = $_GET['mode'];
else
  $mode = '';

// saveのときはオプションを確認
if ($mode == "save" && $_POST['SaveOpt'] != "保存"){
  // 保存でなければlistに変更
  echo "<center>キャンセルしました。</center>";
  $mode = "list";
}

// listのときにオプションが「削除」、idは負の値
if ($mode == "list" && isset($_POST['id'])
    && isset($_POST['SaveOpt']) && $_POST['SaveOpt'] == "削除"){
  $id = (int)$_POST['id'] * -1;
  error_log("delete: id = $id");
  // idの行を削除する
  try {
    $num = $dbac->deleteFlgTodo($id);
  } catch (\PDOException $e) {
    error_log( "\PDO::例外: " . $e->getMessage() );
    echo "メンテナンス中ですm&gt;{$id}&lt;m";
    return;
  }
  error_log("DELETE: affected lins = $num");
  echo "<center>削除しました。($id)</center>";
}

switch ($mode) {
case 'edit':
  // 編集|作成
  include "edit.php";
  break;
case 'save':
  // 保存
  include "save.php";
  break;
case 'delete':
  // 削除
  include "delete.php";
  break;
default:
  // 一覧
  include "list.php";
  break;
}
// 以下、フッター部
?>
    </blockquote>
    </div>
  </div>
  <div class="left-column">
  <img src="/icons/back.gif"><a href="<?php echo $_SERVER['SCRIPT_NAME'];?>">戻る</a><br/>
  </div>
<?php end: ?>
  <div class="right-column">
  <img src="/icons/layout.gif"><a href="/">サイトトップへ</a>
  </div>
<?php $hostname=gethostname();?>
  <address>
  <hr size="1">
  連絡先：<a href="mailto:webmaster@<?php echo $hostname;?>">webmaster@<?php echo $hostname;?></a>
  </address>
</body>
</html>
