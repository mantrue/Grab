<?php
/**   
* 类的含义说明 
* @access public 
* @since 1.0 
* @author    penghui444@163.com
* @explain   各大网站数据的抓取
*/ 
class Grab{
    /** 
    * filterUrl
    * 获取模型
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 无返回值
    */
    public function filterUrl ( $url ) {
        //$url   = $_POST['url'];
        $urlname = parse_url($url);
        $urlname = $urlname['host'];
        switch ($urlname) {
            case 'item.jd.com':  //京东专用
                $arr = $this->jdInterFace( $url );
                break;
            case 'item.taobao.com': //淘宝抓取专用
                $arr = $this->tbInterFace( $url );
                break;
            case 'detail.tmall.com': //抓取天猫
                $arr = $this->tmInterFace( $url );
                break;
            case 'item.yhd.com': //抓取1号店
                $arr = $this->yhdInterFace( $url );
                break;
            default:
                $arr['code']=400;
                break;
        }
		return $arr;
    }
    /** 
    * jdInterFace
    * 京东数据接口
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 抓取成功的数据
    */
    public function jdInterFace ( $url ) {
        
        $output = self::sendCurl( $url );
        //匹配图片
        $ul = explode( '<div class="spec-items">',$output );
        list( $img ) = explode( "</div>",$ul[1] );
        $pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg]))[\'|\"].*?[\/]?>/";
        preg_match_all( $pattern,$img,$imgdata );
        //更换链接
        $imgrep = array();
        foreach( $imgdata[1] as $v ) {
            $imgrep[] = str_replace( 'n5','n0','http:'.$v );
        }
        //匹配标题
        $title = explode( '<h1>',$output );
        list($titledata) = explode( '</h1>',$title[1] );
        $filepath = 'Uploads/'.date('Y-m-d',time()); //远程图片要保存的路径
        $arr = array();
        
        foreach($imgrep as $k=>$v) {
            $arr['imgurl'][] = self::writeImage($v,$filepath);
        }
        $arr['title'] = iconv('gbk','utf-8',$titledata);
        $arr['url'] = $url;
        $arr['price'] = self::getPrice( $url );
        $arr['source'] = '京东';
        $arr['code'] = 200;
        return $arr;
    }
    /** 
    * sendCurl
    * 发送curl请求    共享
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 获取该$url的静态页面字符串
    */
    public static function sendCurl ( $url ) {
        $curl = curl_init();
        curl_setopt( $curl,CURLOPT_URL,$url );
        curl_setopt( $curl,CURLOPT_RETURNTRANSFER,true );
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//规避证书
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 防止302 盗链
        $output = curl_exec( $curl );
        curl_close( $curl );
        return $output;
    }
    /** 
    * writeImage
    * 根据url图片路径把图片写入到执行的目录下  共享
    * @access public 
    * @param mixed $url 图片url $filepath 图片保存路径
    * @since 1.0
    * @return 保存成功的图片名
    */
    public static function writeImage($url, $filepath) {
        if ($url == '') {  
            return false;  
        }  
        $ext = strrchr($url, '.');  
      
        if ($ext != '.gif' && $ext != '.jpg' && $ext != '.png') {  
            return false;  
        }
        //判断路经是否存在
        !is_dir($filepath)?mkdir($filepath):null;  
      
        //获得随机的图片名，并加上后辍名  
        $filetime = time();  
        $filename = date("YmdHis",$filetime).rand(100,999).'.'.substr($url,-3,3);
        //读取图片  
        ob_start();  
        readfile($url); 
        $img=ob_get_contents();  
        ob_end_clean();
        //指定打开的文件
        $fp = @ fopen($filepath.'/'.$filename, 'a');
        //写入图片到指定的文本  
        fwrite($fp, $img);
        fclose($fp);
        return '/'.$filepath.'/'.$filename;
    }
    /** 
    * getPrice    京东专用
    * 根据url获取该url商品的价格
    * @access public 
    * @param mixed $url 图片url
    * @since 1.0
    * @return 商品的价格
    */
    public static function getPrice ( $url ) {
        $urlname = basename( $url ); 
        $pattern = '/\d+/i';
        preg_match($pattern,$urlname,$arr);
        $shopid = $arr[0];
        $getprice = "http://p.3.cn/prices/get?skuid=J_{$shopid}&type=1";
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$getprice);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl);
        curl_close($curl);
        $pd = json_decode($output);
        return $pd[0]->p;
    }
    /** 
    * tbInterFace
    * 淘宝数据接口
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 抓取成功的数据
    */
    public function tbInterFace ( $url ) {
        $output = self::sendCurl( $url );
        $ul = explode( '<div class="spec-items">',$output );
        $div = explode('<ul id="J_UlThumb"',$output);
        list($imgdata) = explode('</ul>', $div[1]);
        $pattern = '/src="\/\/(.*)[\w\.]*"/';
        preg_match_all($pattern,$imgdata,$imgpath);
        //匹配出图片链接同时替换为大图的规则
        $imgrep = array();
        foreach( $imgpath[1] as $v ) {
            //$imgrep[] = str_replace( '50','400','http://'.$v );
            $imgrep[] = str_replace( '50x50','400x400','http://'.$v );
        }
        //匹配标题
        preg_match_all('/<h3\s(.*)\sdata-title=\"(.*)\"/',$output,$title);
        //匹配价格
        preg_match_all('/<em class=\"tb-rmb-num\">(.*)<\/em>/',$output,$price);
        $filepath = __ROOT__.'./Uploads/Admin/'.date('Y-m-d',time()); //远程图片要保存的路径
        $arr = array();
        foreach($imgrep as $k=>$v) {
            $arr['imgurl'][] = self::writeImage($v,$filepath);
        }
        $arr['title'] = iconv('gbk','utf-8',$title[2][0]);
        $arr['url'] = $url;
        $zudiprice = self::getTbPrice( $url );
        if (empty($zudiprice)) {
            $arr['price'] = $price[1][0];
        } else {
            $arr['price'] = $zudiprice;
        }
        $arr['source'] = '淘宝';
        $arr['code'] = 200;
        return $arr;
      
    }
    /** 
    * tmInterFace
    * 获取淘宝价格接口
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 抓取成功的数据
    */
    public static function getTbPrice ( $urls ) {
        preg_match_all('/id=(\d+)/',$urls,$id);
        $url='https://detailskip.taobao.com/json/sib.htm?itemId='.$id[1][0]."&p=1";  
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $url);  
        //设置来源链接，这里是商品详情页链接  
        curl_setopt($ch,CURLOPT_REFERER,$urls);  
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//规避证书
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 防止302 盗链
        $result = curl_exec($ch);  
        curl_close($ch);
        //去除回车、空格等   
        $result=str_replace(array("\r\n","\n","\r","\t",chr(9),chr(13)),'',$result); 
        preg_match_all('/price:\"(.*)\"/U',$result,$re);
        return $re[1][0];
    }
    /** 
    * tmInterFace
    * 天猫数据接口
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 抓取成功的数据
    */
    public function tmInterFace ( $url ) {
        $output = self::sendCurl( $url );
        $ul = explode( '<div class="spec-items">',$output );
        $div = explode('<ul id="J_UlThumb"',$output);
        list($imgdata) = explode('</ul>', $div[1]);
        $pattern = '/src="\/\/(.*)[\w\.]*"/';
        preg_match_all($pattern,$imgdata,$imgpath);
        //匹配出图片链接同时替换为大图的规则
        $imgrep = array();
        foreach( $imgpath[1] as $v ) {
            $imgrep[] = str_replace( '60x60','430x430','http://'.$v );
        }
        //匹配标题
        preg_match_all('/content=\"(.*)\"/',$output,$title);
        $filepath = __ROOT__.'./Uploads/Admin/'.date('Y-m-d',time()); //远程图片要保存的路径
        $arr = array();
        foreach($imgrep as $k=>$v) {
            $arr['imgurl'][] = self::writeImage($v,$filepath);
        }
        $arr['title'] = iconv('gbk','utf-8',$title[1][0]);
        $arr['url'] = $url;
        $arr['price'] = self::getTmPrice( $url );
        $arr['source'] = '天猫';
        $arr['code'] = 200;
        return $arr;
        
    }
    /** 
    * tmInterFace
    * 获取天猫价格接口
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 抓取成功的数据
    */
    public static function getTmPrice ( $urls ) {
        preg_match_all('/id=(\d+)/',$urls,$id);
        $url='https://mdskip.taobao.com/core/initItemDetail.htm?itemId='.$id[1][0];  
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL, $url);  
        //设置来源链接，这里是商品详情页链接  
        curl_setopt($ch,CURLOPT_REFERER,$urls);  
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//规避证书
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 防止302 盗链
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/0 (Windows; U; Windows NT 0; zh-CN; rv:3)"); //模拟浏览器请求
        $result = curl_exec($ch);  
        curl_close($ch);
        //去除回车、空格等   
        $result=str_replace(array("\r\n","\n","\r","\t",chr(9),chr(13)),'',$result); 
        $mode="#([0-9]+)\:#m";  
        preg_match_all($mode,$result,$s);
        $s=$s[1];  
        if(count($s)>0){  
            foreach($s as $v){  
                $result=str_replace($v.':','"'.$v.'":',$result);  
            }  
        }  
        //将字符编码转为utf-8，并且将中文转译，否则json_decode会出现错误   
        $result=iconv('gbk','utf-8',$result);  
        $str=array();  
        $mode='/([\x80-\xff]*)/i';  
        if(preg_match_all($mode,$result,$s)){  
            foreach($s[0] as $v){  
                if(!empty($v)){  
                    $str[base64_encode($v)]=$v;  
                    $result=str_replace('"'.$v.'"','"'.base64_encode($v).'"',$result);  
                }  
            }  
        }  
        $result=json_decode($result,true);
        foreach($result['defaultModel']['itemPriceResultDO']['priceInfo'] as $k=>$v) {
            if (empty($v['promotionList'])) {
                $arrprice = $v['price'];
            } else {
                $arrprice = $v['promotionList'][0]['price'];
            }
            
        }
        return $arrprice;
    }
    /** 
    * yhdInterFace
    * 1号店数据接口
    * @access public 
    * @param mixed $url 要抓取的网站的地址
    * @since 1.0
    * @return 抓取成功的数据
    */
    public function yhdInterFace ( $url ) {
        $output = self::sendCurl( $url );
        //匹配图片
        $div = explode('<div class="mBox clearfix"',$output);
        list($imgdata) = explode('</div>', $div[1]);
        $pattern = '/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(jpg|gif|png)/';
        preg_match_all( $pattern,$imgdata,$imgpath );
        $imgrep = array();
        foreach( $imgpath[0] as $v ) {
            $imgrep[] = str_replace( '50x50','360x360',$v ); //标准替换，看成一个整体
        }
        //匹配标题
        preg_match_all('/<h1(.*)>(.*)<\/h1>/',$output,$title);//print_r($price[2][0]);
        //匹配价格
        preg_match_all('/<a[\s]class=\"ico_sina\"(.*)￥(\d+\.*[\d*])(.*)>(.*)<\/a>/',$output,$price);
        $filepath = __ROOT__.'./Uploads/Admin/'.date('Y-m-d',time()); //远程图片要保存的路径
        $arr = array();
        foreach($imgrep as $k=>$v) {
            $arr['imgurl'][] = self::writeImage($v,$filepath);
        }
        $arr['title'] = $title[2][0];
        $arr['url'] = $url;
        $arr['price'] = $price[2][0];
        $arr['source'] = '1号店';
        $arr['code'] = 200;
        return $arr;
    }
    //获取测试图片
    function getimg_ceshi($url, $filepath) {  
  
        if ($url == '') {  
            return false;  
        }  
        $ext = strrchr($url, '.');  
      
        if ($ext != '.gif' && $ext != '.jpg' && $ext != '.png') {  
            return false;  
        }
        //判断路经是否存在
        !is_dir($filepath)?mkdir($filepath):null;  
      
        //获得随机的图片名，并加上后辍名  
        $filetime = time();  
        $filename = date("YmdHis",$filetime).rand(100,999).'.'.substr($url,-3,3);  
      
        //读取图片  
        $ch = curl_init();
        $timeout = 5;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en; rv:1.9.2) Gecko/20100115 Firefox/3.6 GTBDFff GTB7.0');
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $img = curl_exec($ch);
        //$img = file_get_contents($url); 
        
        //指定打开的文件  
        $fp = @ fopen($filepath.'/'.$filename, 'a');  
        //写入图片到指定的文本  
        fwrite($fp, $img);  
        fclose($fp);  
        return '/'.$filepath.'/'.$filename;  
    }
}
