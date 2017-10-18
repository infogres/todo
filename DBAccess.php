<?php
namespace ToDo;
 
/**
 * ToDo DB Access
 */
class DBAccess {

  /**
   * DBAccess
   * @var type 
   */
  private static $pdo;
  private static $ttab;
  private static $tseq;
 
  /**
   * 指定のデータベースに接続して \PDO オブジェクトのインスタンスを返す
   * @param type $params
   * @return \PDO
   * @throws \Exception
   */
  public function connect($params) {
    // 接続パラメータのチェック
    if (! isset($params['host']) ) $params['host'] = '';
    if (! isset($params['port']) ) $params['port'] = '';
    if (! isset($params['database']) ) $params['database'] = '';
    if (! isset($params['user']) ) $params['user'] = '';
    if (! isset($params['password']) ) $params['password'] = '';

    // postgresqlのデータベースに接続
    $conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", 
		      $params['host'], 
		      $params['port'], 
		      $params['database'], 
		      $params['user'], 
		      $params['password']);
    $pdo = new \PDO($conStr);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $pdo->query("SET client_encoding TO 'UTF-8'");

    return $pdo;
  }


  /**
   * コンストラクター
   * @param type $params
   */
  public function __construct($params) {
    $this->ttab = 'todo_tbl';
    $this->tseq = 'todo_tbl_id_seq';

    $this->pdo = $this->connect($params);
    return $this->pdo;
  }


  /**
   * ToDoアプリのバージョン番号設定（テーブル名とシーケンス名で使用）
   * @param type $modelnum
   * @return model number or 0 for error
   */
  public function setTodoModelNum($modelnum) {
    $no = (int)$modelnum;
    if ($no >= 1 && $no <= 3){ 
      $this->ttab = 'todo_tbl' . $no;
      $this->tseq = 'todo_tbl' . $no . '_id_seq';
    }else{
      error_log('error: todo table number is out of range!');
    }
    return $no;
  }


  /**
   * ToDoテーブルに新しい予定を追加
   * @param type $datetime
   * @param type $subject
   * @param type $detail
   * @return the id of the inserted row
   */
  public function insertTodo($datetime, $subject='', $detail='') {
    // INSERTステートメントを準備
    $sql = 'INSERT INTO '
      . $this->ttab
      . '(todo_datetime, todo_subject, todo_detail)'
      . ' VALUES'
      . '(:datetime, :subject, :detail)';
    $stmt = $this->pdo->prepare($sql);
        
    // ステートメントに値を渡す
    $datetime = strftime("%F %T", strtotime($datetime));
    $stmt->bindValue(':datetime', $datetime);
    $stmt->bindValue(':subject', pg_escape_string($subject));
    $stmt->bindValue(':detail', pg_escape_string($detail));
        
    // INSERTステートメントを実行
    $stmt->execute();
        
    // 符番されたIDを返す
    return $this->pdo->lastInsertId($this->tseq);
  }


  /**
   * ToDoテーブルの指定されたIDの予定を更新する
   * @param int $id
   * @param type $datetime
   * @param type $subject
   * @param type $detail
   * @return int
   */
  public function updateTodo($id, $datetime, $subject, $detail) {
    // UPDATEステートメントを準備
    $sql = 'UPDATE ' . $this->ttab
      . ' SET todo_datetime = :datetime'
      . ', todo_subject = :subject'
      . ', todo_detail = :detail';
    $sql .= ' WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
 
    // ステートメントに値を渡す
    $datetime = strftime("%F %T", strtotime($datetime));
    $stmt->bindValue(':datetime', $datetime);
    $stmt->bindValue(':subject', pg_escape_string($subject));
    $stmt->bindValue(':detail', pg_escape_string($detail));
    $stmt->bindValue(':id', (int)$id);

    // UPDATEステートメントを実行
    $stmt->execute();
 
    // 更新した行数を返す
    return $stmt->rowCount();
  }


  /**
   * ToDoテーブルの指定されたIDの予定に削除フラグを
   * @param int $id
   * @return int
   */
  public function deleteFlgTodo($id) {
    // UPDATEステートメントを準備
    $sql = 'UPDATE ' . $this->ttab
      . ' SET delete_datetime = CURRENT_TIMESTAMP';
    $sql .= ' WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);

    // ステートメントに値を渡す
    $stmt->bindValue(':id', (int)$id);

    // UPDATEステートメントを実行
    $stmt->execute();
 
    // 更新した行数を返す
    return $stmt->rowCount();
  }


  /**
   * ToDoテーブルから全カラムを抽出 （絞り込みとソートが可能）
   * @param type $days
   * @param type $orderby
   * @param type $scend
   * @return array
   */
  public function allTodo($days="", $orderby='id', $scend='DESC') {
    // SELECTステートメントを準備
    switch ($orderby) {  // ソート対象
    case 'datetime':
    case 'subject':
    case 'detail':
      $column = 'todo_' . $orderby;
      break;
    case 'id':
    default:
      $column = 'id';
    }
    
    if ($scend !='DESC') {  // ソート順
      $scend = ' DESC';
    }else{
      $scend = ' ASC';
    }

    // 日数による絞り込み条件
    $days = (int)$days;
    if ( $days > 0 ) {
      $today = time();
      $todate = $today + $days*3600*24;
      // 日付変換、unixタイムスタンプからISOへ
      $ftodate = strftime("'%F %T'", $todate);
      $condition = "todo_datetime <= $ftodate";
      $condition .= ' AND delete_datetime is null';
    }else{
      $condition = 'delete_datetime is null';
    }

    // SELECTステートメントを実行
    $stmt = $this->pdo->query('SELECT *'
			      . ", '(' || substring(to_char(todo_datetime, 'Day') from 1 for 3) || ')' dow"
			      . ' FROM '
			      . $this->ttab
			      . ' WHERE ' . $condition
			      . ' ORDER BY ' . $column . $scend
			      );

    // SELECT実行結果の取り出し
    $todos = array();
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $todos[] = array(
		       'id' => $row['id'],
		       'datetime' => $row['todo_datetime'],
		       'subject' => $row['todo_subject'],
		       'detail' => $row['todo_detail'],
		       'delete' => $row['delete_datetime'],
		       'dow' => $row['dow'],
		       );
    }
    return $todos;
  }


  /**
   * ToDoテーブルから指定したIDの行を取り出す
   * @param type $id
   * @return array
   */
  public function getTodoById($id) {
    // SELECTステートメントを準備
    $id = (int)$id;
    if ( $id > 0 ) {
      // IDを指定
      $condition = " WHERE id = $id";
      $condition .= ' AND delete_datetime is null';
    }else{
      // 最初の１行
      $condition = ' WHERE delete_datetime is null';
      $condition .= " ORDER BY todo_datetime LIMIT 1";
    }

    // SELECTステートメントを実行
    $stmt = $this->pdo->query('SELECT *'
			      . ", '(' || substring(to_char(todo_datetime, 'Day') from 1 for 3) || ')' dow"
			      . ' FROM ' . $this->ttab
			      . $condition
			      );

    // SELECT実行結果の取り出し
    $todo = array();
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $todo = array(
		    'id' => $row['id'],
		    'datetime' => $row['todo_datetime'],
		    'subject' => $row['todo_subject'],
		    'detail' => $row['todo_detail'],
		    'delete' => $row['delete_datetime'],
		    'dow' => $row['dow'],
		    );
      break;  // １行だけ処理して抜ける
    }
    return $todo;
  }

}
