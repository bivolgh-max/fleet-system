<?php
declare(strict_types=1);
date_default_timezone_set('Europe/London');
require_once __DIR__.'/functions.php';
require_once __DIR__.'/FleetRepository.php';

function connect(): PDO {
    $cfg=require dirname(__DIR__).'/config/config.example.php';
    $local=dirname(__DIR__).'/config/config.local.php';
    if(is_file($local))$cfg=array_replace($cfg,require $local);
    $dsn=getenv('FLEET_DB_DSN')?:$cfg['dsn'];
    return new PDO($dsn,getenv('FLEET_DB_USER')!==false?getenv('FLEET_DB_USER'):$cfg['user'],getenv('FLEET_DB_PASSWORD')!==false?getenv('FLEET_DB_PASSWORD'):$cfg['password']);
}
function csrf_token(): string {
    if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(array $post): void {
    $token=$post['csrf']??null;
    if(!is_string($token)||!isset($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'],$token))throw new InvalidArgumentException('The security token has expired. Reload the page and try again.');
}
function redirect(string $url): never {header('Location: '.$url,true,303);exit;}
if(PHP_SAPI!=='cli'){
    ini_set('session.use_strict_mode','1');
    session_name('fleet_session');
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','samesite'=>'Lax','path'=>dirname($_SERVER['SCRIPT_NAME']??'/')]);
    session_start();
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    if(isset($_SESSION['user'])&&time()-($_SESSION['last_seen']??0)>1800){
        $_SESSION=[];session_regenerate_id(true);
    }
    $_SESSION['last_seen']=time();
}
