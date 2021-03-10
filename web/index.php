<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/Lib/DBConnector.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

$app = new Silex\Application();


$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

// database connection settings
$user = "username";
$pass = "password";

$dbh = new DBConnector('mysql:host=host_here;dbname=forumsystems', $user, $pass);

$app->get('/', function () use ($app, $dbh) {
    session_start();
    
    $user_data = null;
    if(isset($_COOKIE["user_id"])){
        $id = urldecode($_COOKIE['user_id']);
        $user_data = $dbh->query("SELECT * FROM USERS where email='$id'")->fetch(PDO::FETCH_ASSOC);
        if($_COOKIE["PHPSESSID"] != $user_data["session_id"])
            $user_data = null;
    }
    else
        session_write_close();
    
    /// Главная страница сайта

    $data = array();
    try{
        // database querry
        $resqueery = $dbh->forums->fetchAll();
        foreach($resqueery as $row){
            array_push($data, $row);
        }
        
        // render template
        return $app['twig']->render('hello.twig', array(
            'forums' => $data,
            "userdata" => $user_data
        ));
    }
    catch(PDOException $e){
        echo "Error!";
        echo "$e";
    }
    
    session_write_close();
});

$app->get("/forum/{forum_name}", function($forum_name) use($app, $dbh){
    $data = array();
    $sessid = $_COOKIE["PHPSESSID"];
    $id = $_COOKIE["user_id"];
    $user_data = null;
    try{
        $resqueery = $dbh->forum_themes->Where("forum_name='$forum_name'");
        $user_data = $dbh->query("SELECT * FROM USERS where email='$id' and session_id='$sessid'")->fetch(PDO::FETCH_ASSOC);
        
        foreach($resqueery as $row){
            array_push($data, $row);
        }
        
    }catch(PDOException $e){
        echo "error!";
        echo "$e";
    }

    return $app['twig']->render('forum.twig', array(
        'forum_name' => $forum_name,
        'forum_themes' => $data,
        'userdata'=>$user_data
    ));
});

$app->get("/forum/{forum_name}/{theme_id}", function($forum_name, $theme_id) use($app, $dbh){
    $data = array();
    $sessid = $_COOKIE["PHPSESSID"];
    $id = $_COOKIE["user_id"];
    $user_data = null;
    try{
        $OPdata = $dbh->forum_themes->Where("id='$theme_id'")->fetch(PDO::FETCH_ASSOC);
        $themeData = $dbh->forum_post->Where("forum_theme='$theme_id'");
        $user_data = $dbh->query("SELECT * FROM USERS where email='$id' and session_id='$sessid'")->fetch(PDO::FETCH_ASSOC);
        foreach($themeData as $row){
            array_push($data, $row);
        }

        return $app['twig']->render('theme.twig',array(
            "theme" => $OPdata,
            "posts" => $data,
            "forum_name" => $forum_name,
            "userdata" => $user_data
        ));

    }catch(PDOException $e){
        echo "error!";
        echo "$e";
    }
});

$app->post("/addNewTheme", function(Request $request) use($app, $dbh) {
    try{
        $forum_name = $request->get('forum_name');
        $title = $request->get("title");
        $content = $request->get("content");
        $id = urldecode($_COOKIE["user_id"]);
        $sessid = $_COOKIE["PHPSESSID"];
        $user_data = $dbh->query("select * from users where email='$id' and session_id='$sessid'")->fetch(PDO::FETCH_ASSOC);
        if($user_data != false)
            $sth = $dbh->query("INSERT INTO forum_themes(forum_name, title, content, attachments, user_email) VALUES('$forum_name', '$title', '$content', null, '$id')");
        
        
        echo "<script>window.location.href = \"/forum/$forum_name\";</script>";

    }catch(PDOException $e){
        echo "error!";
        echo "$e";
        return New Response('database error', 500);
    }
   
});

$app->post("/responseInTheme", function(Request $request) use ($app, $dbh){
    try{
        $forum_name = $request->get("forum_name");
        $theme_id = $request->get("forum_id");
        $title = $request->get("title");
        $content = $request->get("content");
        $id = urldecode($_COOKIE["user_id"]);
        $sessid = $_COOKIE["PHPSESSID"];
        $user_data = $dbh->query("select * from users where email='$id' and session_id='$sessid'")->fetch(PDO::FETCH_ASSOC);
        if($user_data != false)
            $sth = $dbh->query("INSERT INTO forum_post(forum_theme, title, content, attachments, user_email) VALUES('$theme_id', '$title', '$content', null, '$id')");
        
        
        echo "<script>window.location.href = \"/forum/$forum_name\";</script>";

    }catch(PDOException $e){
        echo "error!";
        echo "$e";
        return New Response('database error', 500);
    }
});

$app->get("/signup", function () use($app, $dbh){
    return $app['twig']->render('register.twig', array(
        "purpose"=>"регистрации",
        "method" =>"new"));
});

$app->post("/login/new", function(Request $request) use ($app, $dbh){
    session_start();
    $login = $request->get("login");
    $email = $request->get("email");
    $password = $request->get("password");
    $sessid = $_COOKIE["PHPSESSID"];

    $res = $dbh->query("INSERT INTO USERS(login, password, email, registration_date, session_id) VALUES('$login', '$password', '$email', now(), '$sessid')");
    if($res == false){
        return New Response('database error', 400);
    }
    $res = $dbh->query("Select * from users where login = '$login'")->fetch(PDO::FETCH_ASSOC);
    $_SESSION["user_id"] = $res["email"];
    setcookie("user_id", $res["email"], path:"/");
    session_write_close();
    echo "<script>window.location.href = \"/\";</script>";
});

$app->get("/signin", function () use($app, $dbh){
    return $app['twig']->render('register.twig', array(
        "purpose"=>"авторизации",
        "method"=>"exists"));
});

$app->post("/login/exists", function(Request $request) use ($app, $dbh){
    session_start();
    $login = $request->get("login");
    $email = $request->get("email");
    $password = $request->get("password");
    $res = $dbh->query("select * from users where password='$password' and email='$email'")->fetch(PDO::FETCH_ASSOC);
    if($res == false){
        return New Response('database error', 400);
    }
    $_SESSION["user_id"] = $res["email"];
    setcookie("user_id", $res["email"], path:"/");
    $sessid = $_COOKIE["PHPSESSID"];
    $dbh->query("UPDATE users set session_id = '$sessid' where email = '$email'");
    
    echo "<script>window.location.href = \"/\";</script>";
    session_write_close();
});

$app->run();