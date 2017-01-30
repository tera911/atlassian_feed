<?php

require_once 'vendor/autoload.php';

class AtlassianReader{

    protected $pdo;
    /**
     * AtlassianReader constructor.
     */
    public function __construct()
    {
        $this->sql();
    }

    protected function sql()
    {
        $pdo = new PDO('sqlite:feeds.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // テーブル作成
        $pdo->exec("CREATE TABLE IF NOT EXISTS feeds(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        feed_id VARCHAR(100),
        updated_at timestamp
    )");

        $this->pdo = $pdo;

    }

    protected function check($feed_id)
    {
        try {
            // 選択 (プリペアドステートメント)
            $stmt = $this->pdo->prepare("SELECT * FROM feeds WHERE feed_id = ?");
            $stmt->execute([ $feed_id ]);
            $r2 = $stmt->fetch();
        }catch(Exception $e)
        {
            echo $e->getMessage() . PHP_EOL;
        }
        return $r2;
    }
    protected function insert($feed_id, $updated_at)
    {
        try {
            // 挿入（プリペアドステートメント）
            $stmt = $this->pdo->prepare("INSERT INTO feeds(feed_id, updated_at) VALUES (?, ?)");
            $stmt->execute([ $feed_id, $updated_at ]);
        }catch(Exception $e)
        {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    protected function update($feed_id, $updated_at){
        try {
            // 挿入（プリペアドステートメント）
            $stmt = $this->pdo->prepare("UPDATE feeds SET updated_at = ? where feed_id = ?");
            $stmt->execute([ $updated_at, $feed_id ]);
        }catch(Exception $e)
        {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function run()
    {
        $feed = new Feed();
        $url = "";

        /** @var Feed $atom */
        $atom = $feed->loadAtom($url);
//var_dump($atom);
        foreach($atom->entry as $item)
        {
            $flag = null;
            $id = $item->id->__toString();
            $title = $item->title->__toString();
            $author = $item->author->name->__toString();
            $link = $item->link->attributes()->href->__toString();
            $updated = $item->timestamp->__toString();
            //$creator = $item->creator;
            if($row = $this->check($id)){
                if($row['updated_at'] < $updated){
                    $flag = 'updated';
                }
            }else{
                $this->insert($id, $updated);
                $flag = 'created';

                if(preg_match("/comment/", $id)){
                    $flag = 'comment';
                }
            }

            switch ($flag){
                case 'updated':
                case 'created':
                case 'comment':
                    $this->msg($title, $link, $flag);
            }
        }
    }

    public function msg($title, $link, $type)
    {
        if($type == 'created')
            echo "「${title}」が追加されました" . $link;
        if($type == 'updated')
            echo "「${title}」が更新されました" . $link;
        if($type == 'comment')
            echo "「${title}」にコメントが追記されました" . $link;
    }
}

(new AtlassianReader())->run();