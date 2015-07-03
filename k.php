<?php
// 密匙
$key = pack('H*',bin2hex("hcg.im123456"));
$key_size =  strlen($key);
$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
// 加密连接 这部分建议放到本地执行
if(isset($_GET["e"])){
    # 显示 AES-128, 192, 256 对应的密钥长度：
    #16，24，32 字节。
    $plaintext = $_GET["e"];

    # 为 CBC 模式创建随机的初始向量
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    

    # 创建和 AES 兼容的密文（Rijndael 分组大小 = 128）
    # 仅适用于编码后的输入不是以 00h 结尾的
    # （因为默认是使用 0 来补齐数据）
    $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,
                                 $plaintext, MCRYPT_MODE_CBC, $iv);

    # 将初始向量附加在密文之后，以供解密时使用
    $ciphertext = $iv . $ciphertext;
    
    # 对密文进行 base64 编码
    $ciphertext_base64 = base64_encode($ciphertext);

    echo  $ciphertext_base64 . "\n";
    exit();
}





if(!isset($_GET["url"])){
    header("Content-type: text/html; charset=utf-8"); 
    die("参数错误");
}
// 解密连接
$url = str_replace(" ", "+", $_GET["url"]);
$ciphertext_dec = base64_decode($url);
$iv_dec = substr($ciphertext_dec, 0, $iv_size);
$ciphertext_dec = substr($ciphertext_dec, $iv_size);
$plaintext_dec = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key,
                                $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
$url = trim($plaintext_dec);

// 开始执行代理
$method = $_SERVER["REQUEST_METHOD"];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1 );
if($method == "POST"){
    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
}

if(isset($_GET["timeout"])){
    $timeout = intval($_GET["timeout"]);
    if(!$timeout){
        $timeout = 60;
    }
}else{
    $timeout = 60;
}

curl_setopt ($ch, CURLOPT_TIMEOUT, 60);
curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);  
curl_setopt ($ch, CURLOPT_AUTOREFERER, 1 );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
$info = curl_getinfo($ch);

// 对文本内容加密
if(isset($_GET["et"]) && intval($_GET["et"]) === 1 && strpos($info["http_code"], "text/") == 0){
    $result = base64_encode($result);
}
curl_close($ch);

if($info["http_code"] == 200){
    header('Content-type: ' . $info["content_type"]);
}else if($info["http_code"] != 0) {
    http_response_code($info["http_code"]);
}else{
    header("X-Fetch-Status: NOT_FETCH", true, 504);
}

echo $result;