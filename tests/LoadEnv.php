<?php

namespace Kuza\Tests;


$_ENV['DEPLOYMENT'] = "Development";

// URI base path. Empty if it is the document root
$_ENV['URI_BASE_PATH'] = "/";


// JWT Secret token
$_ENV['JWT_SECRET'] = ",zYVzjtV-wpFu!,D29)a9VY5s+yCGN}ZiVBbDplGVJ;*L^O+jK!";

// Database configurations
$_ENV['DB_HOST'] =   "localhost";
$_ENV['DB_NAME']     =   "gmoney";
$_ENV['DB_ENGINE']   =   "mysql";
$_ENV['DB_TYPE']     =   "mysqli";
$_ENV['DB_PORT']     =   "3306";
$_ENV['DB_USER']     =   "root";
$_ENV['DB_PASSWORD'] =   "pass";

// Mail server configurations
$_ENV['MAIL_HOST']       = "rav4.websitewelcome.com";
$_ENV['MAIL_SMTPAUTH']   = "true";
$_ENV['MAIL_USERNAME']   = "developers@kuzalab.com";
$_ENV['MAIL_PASSWORD']   = "Developer.KuzaLab@18";
$_ENV['MAIL_SMTPSECURE'] = "tls";
$_ENV['MAIL_PORT']       = "587";

// AWS Configs
$_ENV['AWS_ACCESS_KEY']              = "AKIAJHXZKYMCFTQVIDQQ";
$_ENV['AWS_SECRET_KEY']              = "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";
$_ENV['S3_BUCKET_NAME']              =   "gmoney-public-uploads";
$_ENV['CLOUDFRONT_URL']              =   "https://deq3lhx6sylew.cloudfront.net/";
$_ENV['CLOUDFRONT_KEY_PAIR_ID']      =   "APKAIYR5D6P6UHWCD4RA";

$_ENV['FCM_API_ACCESS_KEY'] = "AAAApfUc4s8:APA91bEFulDUlFf2jT7C33Op7p_F3HRPzvXA_NvlAwCkhGb8C6WrUZH_hnbaZyttzqv2xc6CDD4zmF3VFcax1df_XAjkK04uyD_QfozniPMumRrMdfDh4Jayn5OcK8kUFtRo-f3fLynL";
$_ENV['FCM_SENDER_ID'] = "712781914831";
$_ENV['FCM_WEB_API_KEY'] = "AIzaSyBR2uqp6rkaLCdcfycqc62hanukILTLyYU";
$_ENV['FCM_LEGACY_KEY'] = "AIzaSyA5w1M_6FnnisYaK-hR8X2siIDC7sNU9HM";

// AFRICA'S TALKING
$_ENV['AFRICAS_TALKING_API_KEY'] = "a9a9b69935483beba293f312514a90d7f8681950d7d79e924e028f56b64e6605";
$_ENV['AFRICAS_TALKING_USERNAME'] = "gmoney";
$_ENV['AFRICAS_TALKING_SENDER_ID'] = "G-MONEY";