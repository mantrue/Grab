## Grab
### 淘宝 天猫  京东 一号店  数据抓取 最新 后续根据要求增加更多的网站
###使用说明：
    *首页在grad.php同级目录下创建Uploads文件夹  该文件夹是存放抓取图片下载的目录
    require_once 'grad.php'
    $grad = new Grad();
    $data   = $grad->filterUrl('要抓取的商品详情url');
    print_r($data);
    
###
    还有很多不完善的，希望有大神能对代码进行修改，指点。谢谢 如果能支持curl并发抓取，那就太好了
