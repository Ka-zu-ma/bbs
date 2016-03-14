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
    //固定値
    const NUMBER_OF_COMMENT_PER_PAGE = 5;//1ページあたりのコメント数
    const DISP_PAGE_NUMBER = 10;         //表示可能なページナンバー数
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
    function action()
    {   
        $thread_id = (isset($_REQUEST['thread_id'])) ? $_REQUEST['thread_id'] : '';

        if (empty($thread_id) || !is_numeric($thread_id)) {
            header('Location: http://lumpen-work.net/user_data/bbs_miyazaki_parent.php');
            exit;
        }

        $this->arrFormParam['thread_id'] = $thread_id;

        $pageNo = (isset($_REQUEST['pageNo'])) ? $_REQUEST['pageNo'] : 1;

        //$pageNoが空、又は、数字でないとき、$pageNoに1をセット
        if (empty($pageNo) || !is_numeric($pageNo)) $pageNo = 1;

        //1ページあたりのコメント数
        $numberOfCommentPerPage = self::NUMBER_OF_COMMENT_PER_PAGE; //15行目で数値設定

        $mode = (isset($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';

        $objFormParam = new SC_FormParam_Ex();
        $this->lfInitFormParam($objFormParam, $_REQUEST);
        $thread_id = $objFormParam->getValue('thread_id');

        $child_id = $objFormParam->getValue('id');
        $is_child_exist = $this->existData($child_id);

        //指定したスレッドIDのチェック
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $table = 'parent_dtb_keijiban';
        $where = 'thread_id = ?';
        $arrWhereVal = array($thread_id);

        $is_parent_exist = $objQuery->exists($table,$where,$arrWhereVal);
        $err_msg = '';

        // 指定したスレッドIDが存在しなかった場合、エラーメッセージをセット
        if (!$is_parent_exist) $err_msg = '該当するスレッドはありません。';

        $this->thread_idLessText = $err_msg;

        switch ($mode) {
            case 'register':
                //登録
                $arrErr = $objFormParam->checkError();

                if(count($arrErr) == 0){

                    $arr_FormParam = $objFormParam->getHashArray();
                    //登録した場合の結果を返す
                    $result = $this->registerData($arr_FormParam);

                    if ( $result) {
// GC_Utils_Ex::gfPrintLog('result=true');
                        # code...
                    }else{
// GC_Utils_Ex::gfPrintLog('result=false');

                    }


                    if ($result) {

                        $redirect_url = 'Location: http://lumpen-work.net/user_data/bbs_miyazaki.php?thread_id='.$thread_id;
                        //ページが２ページ以上の場合、パラメーター付与
                        if ( $pageNo > 1 ) $redirect_url .= '&pageNo='.$pageNo;

                        header($redirect_url);
                        exit;

                    }else{
                        //登録失敗した場合
                        $err_msg = '登録に失敗しました。';
                        $this->err_msg = $err_msg;
                    }

                }else{

                    $this->arrErr = $arrErr;
                    $this->arrFormParam = $objFormParam->getHashArray();
                }
                break;

            case 'edit':
                //編集

                if ($is_child_exist) {

                    $where = 'customer_id = ? and id = ? and thread_id = ?';
                    $objCustomer = new SC_Customer_Ex();
                    $customer_id = $objCustomer->getValue('customer_id');

                    $arrWhereVal = array($customer_id, $child_id,$thread_id);

                    $this->arrFormParam = $this->editData($where, $arrWhereVal);

                }else{

                    $this->invalidText = 'この操作は無効です。';
                }

                break;

            case 'delete':
                //削除

                if ($is_child_exist){

                    $table = 'dtb_keijiban';
                    $where = 'customer_id = ? and id = ? thread_id = ?';
                    $objCustomer = new SC_Customer_Ex();
                    $customer_id = $objCustomer->getValue('customer_id');
                    $arrWhereVal = array($customer_id, $child_id);

                    $this->arrFormParam = $this->deleteData($table);
         
                }else{

                    $this->invalidText = 'この操作は無効です。';
                }

                break;

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

                        $order = 'id asc';

                        break;
                }

            break;
        }
        $cols = '*';
        $del_flg = 0;
        $from = 'dtb_keijiban';
        $where = 'del_flg = ? and thread_id = ?';
        $table = 'dtb_keijiban';

        $arrWhereVal = array($del_flg,$thread_id);
        $objQuery->setOrder($order);
        
        // del_flg = 0のコメント数が0のとき　つまり　掲示板に表示されるコメント数が0のときに並び替えを非表示にするため、del_flg = 0のコメント数を取得
        $comment_count = $objQuery->count($table, $where, $arrWhereVal);
        
        $this->commentCount = $comment_count;

        //総コメント数を1ページあたりのコメント表示数で割ったときの余り
        $surplus = $comment_count % $numberOfCommentPerPage;

        //合計ページ数
        $total_Page = ($surplus == 0) ? ($comment_count / $numberOfCommentPerPage) : ceil($comment_count / $numberOfCommentPerPage);

        //マイナスのページがセットされたとき、pageNoに1をセット
        if ($pageNo <= 0) $pageNo = 1;

        //最終ページより大きいページがセットされたとき、pageNoに最終ページをセット
        if ($total_Page < $pageNo) $pageNo = $total_Page;

        $this->pageNo = $pageNo;

        //選択ページのコメントを取得し表示させるための条件設定
        $start = $numberOfCommentPerPage * ($pageNo - 1);
        $objQuery->setLimitOffset($numberOfCommentPerPage,$start);

        //DBから設定した条件のコメントを取得する
        $arr_bbs_param = $objQuery->select($cols, $from, $where, $arrWhereVal);

        $this->arrBbsParam = $arr_bbs_param;

        //最初へ、最後へ、pageNoの配列のpageNoの始めのNoをセット
        $start_PageNo = ($pageNo <= 6) ? 1 : $pageNo - 5; //5を動的にするべきか?

        //表示可能なページナンバー数
        $dispPageNumber = ($total_Page < 10) ? $total_Page : 10 ;

        //最終ページ以上のページリンクが出ないように配列の頭のpageNoをセット
        if ($total_Page < $start_PageNo + $dispPageNumber) $start_PageNo = $total_Page - ($dispPageNumber - 1);

        //最初へリンク、最後へリンク、表示するPageNo、が含まれる配列を返している
        $this->arrDispPageNo = '';
        if (count($arr_bbs_param) != 0){
            $this->arrDispPageNo = $this->createArrDispPageNo($start_PageNo,$dispPageNumber,$total_Page);
        }

        //カスタマーIDを取得
        $objCustomer = new SC_Customer_Ex();
        $this->customer_id = $objCustomer->getValue('customer_id');

        //親スレッドの投稿者名、タイトル名、コメントを取得
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $cols = '*';
        $from = 'parent_dtb_keijiban';
        $where = 'thread_id = ?';
        $arrWhereVal = array($thread_id);
        $this->arrThreadDataParam = $objQuery->getRow($cols, $from, $where, $arrWhereVal);
    }

    /**
    *最初へリンク、最後へリンク、表示するPageNo、が含まれる配列を作成
    * @param $start_PageNo : 表示するPageNo配列の最初
    * @param $dispPageNumber : 表示可能なページナンバー数
    * @param $total_Page : 合計ページ数
    * @return 最初へリンク、最後へリンク、表示するPageNoが入った配列
    */
    function createArrDispPageNo($start_PageNo,$dispPageNumber,$total_Page){

        //最初へリンク、最後へリンク、表示するpageNo、が含まれる配列初期化
        $arr_disp_pageNo = array();

        //配列のpageNoの頭が1以外のとき、「最初へ」リンクセット
        if ($start_PageNo != 1) $arr_disp_pageNo[] = array('最初へ',1);

        for ($i = $start_PageNo ; $i < $start_PageNo + $dispPageNumber; $i++) {

                $arrTmp = array($i,$i);
                $arr_disp_pageNo[] = $arrTmp;
        }
GC_Utils_Ex::gfPrintLog('arr_disp_pageNo=['.print_r($arr_disp_pageNo,true).']');
GC_Utils_Ex::gfPrintLog('total_Page='.$total_Page);

        //配列のpageNoの中に最終pageNoが含まれていないとき、「最後へ」リンクセット
        if(!(in_array(array($total_Page,$total_Page),$arr_disp_pageNo))) {
            $arr_disp_pageNo[] = array('最後へ',$total_Page);
        }
GC_Utils_Ex::gfPrintLog('arr_disp_pageNo=['.print_r($arr_disp_pageNo,true).']');

        return $arr_disp_pageNo;

    }

    /**
    *コメントを登録　又は、すでにあるコメントを編集し更新
    * @param $arrParam : DBのカラムがキーになってる配列
    * @return 最初へリンク、最後へリンク、表示するPageNoが入った配列
    */
    function registerData($arrParam){

        $objQuery =& SC_Query_Ex::getSingletonInstance();

        $table = 'dtb_keijiban';

        $sqlval = array(
                'title' => $arrParam['title'], 
                'comment' => $arrParam['comment'], 
                'update_date' => 'now()',
                 'thread_id' => $arrParam['thread_id']
                );
        
        if (!empty($arrParam['tokosya'])) $sqlval['tokosya'] = $arrParam['tokosya'];

        $rtn = false;
        if (!empty($arrParam['id'])) { //更新

            $arrWhereVal =array($arrParam['id']);
            $where = 'id = ?';
            $objQuery->update($table,$sqlval,$where,$arrWhereVal);
            $rtn = true;

        }else{//新規登録

            $objCustomer = new SC_Customer_Ex();
            $sqlval['customer_id'] = $objCustomer->getValue('customer_id');
            $sqlval['create_date'] = 'now()';
            $objQuery->insert($table,$sqlval);
            $rtn = true;
        }
        return $rtn;
    }
    /**
    *指定した条件を満たすすべてのカラム値をとってくる
    * @param $where : 指定した条件
    * @param $arrWhereVal : 指定した条件に沿った値
    * @return 指定した条件を満たすデータ１列を返す
    */
    function editData($where, $arrWhereVal){

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $cols = '*';
        $from = 'dtb_keijiban';
        $query = $objQuery->getRow($cols, $from, $where, $arrWhereVal);

        return $query;
    }

    /**
    *あるコメントIDのdel_flgを1にすることでそのコメントを非表示にする
    * @param $table : del_flgを1にしたいコメントがあるテーブル
    * @return DB上のある条件のコメントを更新する
    */
    function deleteData($table){

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $sqlval = array('del_flg' => '1');
        $where = 'id = ?';
        $arrWhereVal = array($_GET['id']);
        $query = $objQuery->update($table, $sqlval, $where, $arrWhereVal);

        return $query;
    }

    /**
    *dtb_keijibanで、ある特定のcustomer_idのコメントIDが存在しているかチェック
    * @param $child_id : コメントのID
    * @return 存在していたらtrue,存在していなかったらfalse
    */
    function existData($child_id){

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $table = 'dtb_keijiban';
        $where = 'customer_id = ? and id = ?';

        $objCustomer = new SC_Customer_Ex();
        $customer_id = $objCustomer->getValue('customer_id');

        $arrWhereVal = array($customer_id, $child_id);
        
        $is_exist = $objQuery->exists($table, $where, $arrWhereVal);

        return $is_exist;
    }
    
    /**
    *DBテーブルのカラムをパラメータにセット
    * @param $arrPost : $_REQUESTがセットされている
    * @param $objFormParam : フォームパラム
    * @return 
    */
    public function lfInitFormParam(&$objFormParam, $arrPost){

        $objFormParam->addParam('ID', 'id', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('カスタマーID', 'customer_id', INT_LEN, 'n', array('NUM_CHECK','SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('投稿者', 'tokosya', STEXT_LEN, 'KVa', array('SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('タイトル', 'title', STEXT_LEN, 'KVa', array('EXIST_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('コメント', 'comment', MLTEXT_LEN, 'KVa', array('EXIST_CHECK', 'SPTAB_CHECK', 'MAX_LENGTH_CHECK'));
        $objFormParam->addParam('スレッドID', 'thread_id', INT_LEN, 'n', array('NUM_CHECK', 'MAX_LENGTH_CHECK'));
        

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
