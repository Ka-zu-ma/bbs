<?php
require_once '../require.php';
require_once CLASS_EX_REALDIR . 'page_extends/LC_Page_Ex.php';


/**
 * ユーザーカスタマイズ用のページクラス
 *
 * 管理画面から自動生成される
 *
 * @package Page
 */
class LC_Page_User extends LC_Page_Ex
{
    const LIMIT_OF_NUMBER_OF_CHARACTERS = 20; //最新コメント表示文字数制限
    const MAKER_TEST_TABLE = 'dtb_maker_test';
    /**
     * Page を初期化する.
     *
     * @return void
     */
    function init()
    {
        parent::init();
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    function process()
    {
        parent::process();
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    function action(){

// GC_Utils_Ex::gfPrintLog("log");


// ファイルを読み込み専用でオープンする
$fp = fopen('maker_master.csv', 'r');

$master_arr = array();
$line_counter = 1;

while (!feof($fp)) {
        //1行ずつ読み込む
        $line = fgets($fp);

        if(!empty($line)){
            if ($line_counter != 1) {

                $line_utf = mb_convert_encoding($line, 'UTF-8','SJIS');
                //1レコードを分割する 
                $ex_arr = explode( ',',$line_utf);

                $master_record['trade_code'] = trim(trim($ex_arr[0]),'"');
                $master_record['trade_name'] = trim(trim($ex_arr[1]),'"');
                $master_record['search_keywords'] = trim(trim($ex_arr[2]),'"');

                $master_arr[] = $master_record; 
            }
            $line_counter++;
        }
}
fclose($fp);

// 読み込んだ行を出力する
// print_r($master_arr);

$objCustomer = new SC_Customer_Ex();

//dtb_maker_testは定数にいれる
$objQuery =& SC_Query_Ex::getSingletonInstance();
$table = self::MAKER_TEST_TABLE;
$where = 'koj_trade_code = ?';
$from = self::MAKER_TEST_TABLE;

$create_user_name = '作った人';//仮に入れている
$update_user_name = '更新した人';//仮に入れている
$delete_user_name = '削除した人';//仮に入れている

foreach ($master_arr as $key => $value) {

$arrWhereVal = array($master_arr[$key]['trade_code']);
$sqlval['koj_search_keywords'] = '';

if (!empty($master_arr[$key]['search_keywords'])) {

    $sqlval['koj_search_keywords'] = $master_arr[$key]['search_keywords'];
}

if (!empty($master_arr[$key]['trade_code'])) {

    $sqlval['koj_trade_code'] = $master_arr[$key]['trade_code'];
}

$koj_trade_code_exists = $objQuery->exists($table,$where,$arrWhereVal);
$sqlval['name'] = $master_arr[$key]['trade_name'];
$sqlval['creator_id'] = $objCustomer->getValue('customer_id');

    if ($koj_trade_code_exists) {
        //更新
        $sqlval['update_date'] = 'now()';
        $sqlval['update_user_name'] = $update_user_name;
        $objQuery->update($table, $sqlval, $where, $arrWhereVal);
    } else {
        //新規登録
        // $sqlval['maker_id'] = 'trade_code関数でtrade_codeを変換した値が入る';
        $sqlval['create_user_name'] = $create_user_name;
        $objQuery->insert($table, $sqlval);
    }
}

        if(isset($_REQUEST['mode'])){
            $mode = $_REQUEST['mode'];
        }

        $objFormParam = new SC_FormParam_Ex();
        $this->lfInitFormParam($objFormParam, $_REQUEST);
        $thread_id = $objFormParam->getValue('thread_id');

        switch ($mode) {

            case 'register':

                  $arrErr = $objFormParam->checkError();

                if(count($arrErr) == 0){

                    $arrFormParam = $objFormParam->getHashArray();
                    $this->registerData($arrFormParam);
                    header('Location: http://lumpen-work.net/user_data/bbs_miyazaki_parent.php');
                    exit;

                }else{

                    $this->arrErr = $arrErr;
                    $this->arrFormParam = $objFormParam->getHashArray();
                }

                break;

            case 'edit':

                $is_exist = $this->searchExist($thread_id);

                if ($is_exist) {

                    $where = 'customer_id = ? and thread_id = ?';
                    $objCustomer = new SC_Customer_Ex();
                    $customer_id = $objCustomer->getValue("customer_id");
                    $arrWhereVal = array($customer_id, $thread_id);

                    $this->arrFormParam = $this->editData($where, $arrWhereVal);

                }else{

                    $this->invalidText = "この操作は無効です。";
                }

                break;

            case 'delete':

                $is_exist = $this->searchExist($thread_id);
                    
                if ($is_exist){

                    $table = 'parent_dtb_keijiban';
                    $where = 'customer_id = ? and thread_id = ?';
                    $objCustomer = new SC_Customer_Ex();
                    $customer_id = $objCustomer->getValue("customer_id");
                    $arrWhereVal = array($customer_id, $thread_id);

                    $this->arrFormParam = $this->deleteData($table);
         
                }else{

                    $this->invalidText = "この操作は無効です。";
                }

                break;

            case 'sort':

            default:

                $this->type = $objFormParam->getValue('type');

                switch ($this->type) {

                    case 'new':
                        $order = 'update_date desc';
                     
                        break;
                    case 'old':
                        $order = 'update_date asc';
                      
                        break;
                    default:
                        $order = 'thread_id desc';

                        break;
                }

            break;
        }

        $objCustomer = new SC_Customer_Ex();
        $this->customer_id = $objCustomer->getValue("customer_id");

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $cols = "*";
        $del_flg = 0;
        
        $from = 'parent_dtb_keijiban';
        $where = 'del_flg = ?';
        $table = 'parent_dtb_keijiban';
        $arrWhereVal = array($del_flg);

        $objQuery->setOrder($order);
        $is_paging = (isset($_REQUEST['start']) && !empty($_REQUEST['start']))? true: false;

        // デフォルト値
        $numberOfDispPages = 5; 
        $commentStartNo = ($is_paging)? $_REQUEST['start']: 0;

        //ページ送り番号
        $this->nextStartNumber = $commentStartNo + $numberOfDispPages;

        if($is_paging) $this->preStartNumber = $commentStartNo - $numberOfDispPages;

        //セレクト文
        $objQuery->setLimitOffset($numberOfDispPages, $commentStartNo);

        $arr_result = $objQuery->select($cols, $from, $where, $arrWhereVal);

        $arr_pref = array();
        $arr_target_tids = array();

        foreach($arr_result as $arr_data) {

            $arr_data['child_cnt'] = 0;//はじめから0をセット
            $arr_data['first_data']['title'] = '';
            $arr_data['first_data']['comment'] = '';
            $key = $arr_data['thread_id'];
            $arr_target_tids[] = $key;
            $arr_pref[$key] = $arr_data;
        }


        $thread_id_in = implode(',',$arr_target_tids);

        //あるスレッドのコメント数を取得するクエリ
        $comment_count_sql = 'select thread_id, count(*) as cnt from dtb_keijiban where thread_id in('.$thread_id_in.') group by thread_id';//SQLをターミナルで叩く癖つける

        //あるスレッドの最新タイトル、最新コメントを取得するクエリ
        $latest_title_comment_sql = 'select thread_id, title, comment from dtb_keijiban where id = any(select max(id) from dtb_keijiban where thread_id in ('.$thread_id_in.') group by thread_id)';

        // $child_comment_count = array();
        $child_comment_count = $objQuery->getAll($comment_count_sql);
        $latest_title_comment = $objQuery->getAll($latest_title_comment_sql);


        foreach ($child_comment_count as $key => $value) {

            //valueの使い方!!
            $arr_pref[$value['thread_id']]['child_cnt'] = $value['cnt'];
        }

        foreach ($latest_title_comment as $key => $value) {

            $arr_pref[$value['thread_id']]['first_data']['title'] = $value['title'];

            // $disp_comment = '';

            // if (self::LIMIT_OF_NUMBER_OF_CHARACTERS <= mb_strlen($value['comment'])) {
            //     $disp_comment = mb_substr($value['comment'],0,self::LIMIT_OF_NUMBER_OF_CHARACTERS).'...';
            // }else{
            //     $disp_comment = $value['comment'];
            // }
            //古賀さん書き方
            // $disp_comment = mb_substr($value['comment'],0,self::LIMIT_OF_NUMBER_OF_CHARACTERS);
            // if (self::LIMIT_OF_NUMBER_OF_CHARACTERS < mb_strlen($value['comment'])) $disp_comment .= '...';


            // $arr_pref[$value['thread_id']]['first_data']['comment'] = $disp_comment;

            $arr_pref[$value['thread_id']]['first_data']['comment'] = $value['comment'];


        }

GC_Utils_Ex::gfPrintLog('$arr_pref=['.print_r($arr_pref,true).']');


        $this->arrPref = $arr_pref;


// select * from hoge where id in (1,3,5,7,9);
//implode,explode
//count

// GC_Utils_Ex::gfPrintLog('$arr_pref=['.print_r($arr_pref,true).']');
// GC_Utils_Ex::gfPrintLog('$arr_tids=['.print_r($arr_target_tids,true).']');

        //掲示板に表示されるスレッド数が0のときに、新しい順、古い順を非表示にするため、del_flg = 0のスレッド数を取得
        $this->threadCount = $objQuery->count($table, $where, $arrWhereVal);

    }

    function registerData($arrParam){

        $objQuery =& SC_Query_Ex::getSingletonInstance();

        $table = 'parent_dtb_keijiban';

        $sqlval = array('title' => $arrParam["title"], 'comment' => $arrParam["comment"], 'update_date' => "now()");
        
        if (!empty($arrParam['tokosya'])) {

            $sqlval['tokosya'] = $arrParam['tokosya'];         
        }

        if (!empty($arrParam['thread_id'])) { //更新

            $arrWhereVal =array($arrParam["thread_id"]);
            $where = 'thread_id = ?';
            $objQuery->update($table,$sqlval,$where,$arrWhereVal);

        }else{//新規登録

            $objCustomer = new SC_Customer_Ex();
            $sqlval['customer_id'] = $objCustomer->getValue("customer_id");
            $sqlval['create_date'] = "now()";
            $objQuery->insert($table,$sqlval);
        }
        return;
    }

    function editData($where, $arrWhereVal){

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $cols = "*";
        $from = 'parent_dtb_keijiban';

        return $objQuery->getRow($cols, $from, $where, $arrWhereVal);
    }

    function deleteData($table){

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $sqlval = array('del_flg' => '1');
        $where = 'thread_id = ?';
        $arrWhereVal = array($_GET["thread_id"]);
        
        return $objQuery->update($table, $sqlval, $where, $arrWhereVal);
    }

    function searchExist($thread_id){

        $objQuery =& SC_Query_Ex::getSingletonInstance();
                
        $table = 'parent_dtb_keijiban';
        $where = 'customer_id = ? and thread_id = ?';

        $objCustomer = new SC_Customer_Ex();
        $customer_id = $objCustomer->getValue("customer_id");

        $arrWhereVal = array($customer_id, $thread_id);
        
        $is_exist = $objQuery->exists($table, $where, $arrWhereVal);

        return $is_exist;
    }

    public function lfInitFormParam(&$objFormParam, $arrPost){

        $objFormParam->addParam('スレッドID', 'thread_id', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('カスタマーID', 'customer_id', INT_LEN, 'n', array('NUM_CHECK','SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('投稿者', 'tokosya', STEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('タイトル', 'title', STEXT_LEN, 'KVa', array('EXIST_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('コメント', 'comment', STEXT_LEN, 'KVa', array('EXIST_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));

        if (isset($arrPost['type'])) {
        
            $objFormParam->addParam('タイプ', 'type', STEXT_LEN, 'KVa', array('EXIST_CHECK','SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        }

        $objFormParam->setParam($arrPost);
        $objFormParam->convParam();
    }

}
$objPage = new LC_Page_User();
$objPage->init();
$objPage->process();
