<?php

namespace App\Libraries\Common;

class Emailtemplate
{

    public static function verifyotp($data = []){
        $template = '<!DOCTYPE html>
        <html lang="en">
          <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Email Template</title>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
            <link
              href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap"
              rel="stylesheet"
            />
          </head>
          <style>
            @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap");
          </style>
          <body
            style="
              box-sizing: border-box;
              margin: 0;
              padding: 0;
              font-family: "Roboto", sans-serif;
              line-height: 1.5;
            "
          >
            <table
              class="container"
              background="'.env("S3_BASEURL").'emailer/email-verification.png"
              width="100%"
              height="100"
              style="
                max-width: 540px;
                margin: 0px auto;
                padding: 0px 15px;
                background-repeat: no-repeat;
                background-size: contain;
                height: 790px;
                position: relative;
                background-position: center;
              "
            >
              <tbody
                class="main-content"
                style="
                  width: 400px;
            text-align: center;
            margin: 200px auto;
            display: block;
                "
              >
                <tr class="heading" style="display: block;">
                  <td>
                    <h2 style="font-size: 25px; margin: 0; margin-bottom: 15px">
                      One-Time Password (OTP) for Account Login
                    </h2>
                  </td>
                 
                </tr>
        
                <tr class="content-wrap" style="margin-bottom: 30px; max-width: 600px;display: block;">
                  <td>
                    <p style="margin: 0">
                      To proceed with logging into your account, please use the following
                      One-Time Password (OTP):
                    </p>
                    <strong style="margin: 20px 0; color: #f92232; display: block"
                      >'.$data['otp'].'</strong
                    >
                    <p style="margin: 0">
                      Please note that this OTP is valid for a limited time and should be
                      entered as soon as possible.
                    </p>
                  </td>
                  
                </tr>
              </tbody>
            </table>
          </body>
        </html>';
    
        return $template;
    }    

  public static function reset($data = []){ 
    $template = '<!DOCTYPE html>
    <html lang="en">
      <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Email Template</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
          href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap"
          rel="stylesheet"
        />
      </head>
      <style>
        @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap");
      </style>
      <body style=" box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: "Roboto", sans-serif;
      line-height: 1.5rem;">
        <table
          class="container"
          background="'.env("S3_BASEURL").'emailer/Forgot.png"
          width="100%"
          height="100%"
          style="
            max-width: 630px;
            margin: 0px auto;
            padding: 0px 15px;
            background-repeat: no-repeat;
            background-size: contain;
            height: 740px;
            position: relative;
            background-position: center;
          "
        >
          <tbody class="main-content" style="width: 500px; margin: 0px auto;
          display: block;">
            <tr valign="bottom" style="">
              <td>
                <h1 style=" font-size: 48px;
                margin: 0;
                margin-bottom: 15px;padding: 200px 0px 0px;">FORGOT</h1>
                <span>YOUR PASSWORD?</span>
              </td>
            </tr>
            <tr class="content-wrap" style="margin-bottom: 30px; display: block;">
              <td>
                <h4 style="font-size: 24px; margin-top: 30px;">Hello User</h4>
                <p>
                  It appears that you have requested a password reset for your account
                  with us.
                </p>
              </td>
            </tr>
            <tr class="content-wrap" style="max-width: 600px; display: block;">
              <td>
                <p>
                  To reset your password, please click on the following link. This
                  button will direct you to a secure page where you can create a new
                  password for your account.
                </p>
              </td>
            </tr>
            <tr class="action-btn" style="display: block;">
              <td>
                <a
                  href="'.$data['link'].'"
                  style="
                    background-color: #f92232;
                    border-radius: 36px;
                    color: white;
                    padding: 10px 25px;
                    border: none;
                    margin-top: 30px;
                  "
                >
                  RESET PASSWORD
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </body>
    </html>';

    return $template;
  }

  public static function register($data = []){
          $template = '<!DOCTYPE html>
          <html lang="en">
            <head>
              <meta charset="UTF-8" />
              <meta name="viewport" content="width=device-width, initial-scale=1.0" />
              <title>Email Template</title>
              <link rel="preconnect" href="https://fonts.googleapis.com" />
              <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
              <link
                href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap"
                rel="stylesheet"
              />
            </head>
            <style>
              @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap");
            </style>
            <body
              style="
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                font-family: "Roboto", sans-serif;
                line-height: 1.5;
              "
            >
              <table
                class="container"
                background="'.env("S3_BASEURL").'emailer/registration.png"
                width="100%"
                height="100%"
                style="
                  max-width: 540px;
                  margin: 0px auto;
                  padding: 0px 15px;
                  background-repeat: no-repeat;
                  background-size: contain;
                  height: 850px;
                  position: relative;
                  background-position: center;
                  padding: 150px;
                "
              >
                <tbody
                  class="main-content"
                  style="
                    width: 500px;
                    text-align: center;
                    display: block;
                    height:100%;
          
                  "
                  valign="bottom"
                >
                  <tr>
                    <td>
                      <h2>Welcome to SprintNXT</h2>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <span
                        class="img-wrap"
                        style="
                          width: 100%;
                          width: 300px;
                          height: 300px;
                          margin: -50px auto 50px;
                          display: block;
                        "
                      >
                        <img
                          src="images/login-img.png"
                          alt=""
                          style="width: 360px; "
                        />
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <h2 >Dear '.$data['username'].',</h2>
                      <p style="margin: 0">Congratulations and welcome to SprintNXT.</p>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <p style="margin: 0">
                        We are thrilled to have you as a new member of our community. To
                        fully activate your account and access all the benefits and
                        features we offer, we kindly request you to complete your KYC
                        process.
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <button
                      href="'.$data['link'].'"
                      style="
                        background-color: #f92232;
                        border-radius: 36px;
                        color: white;
                        padding: 10px 25px;
                        border: none;
                        margin-top: 20px;">
                      Login
                    </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </body>
          </html>
          ';
      
          return $template;
      }
      public static function transactionReport($data = []){

        $template = '<!DOCTYPE html>
        <html lang="en">
          <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Email Template</title>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
            <link
              href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap"
              rel="stylesheet"
            />
          </head>
          <style>
            @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap");
          </style>
          <body
            style="
              box-sizing: border-box;
              margin: 0;
              padding: 0;
              font-family: "Roboto", sans-serif;
              line-height: 1.5;
            "
          >
            <table
              class="container"
              background="'.env("S3_BASEURL").'emailer/email-verification.png"
              width="100%"
              height="100"
              style="
                max-width: 540px;
                margin: 0px auto;
                padding: 0px 15px;
                background-repeat: no-repeat;
                background-size: contain;
                height: 790px;
                position: relative;
                background-position: center;
              "
            >
              <tbody
                class="main-content"
                style="
                  width: 400px;
            text-align: center;
            margin: 200px auto;
            display: block;
                "
              >
                <tr class="heading" style="display: block;">
                  <td>
                    <h2 >Dear Sir/Mam,</h2>
                    <h2 style="font-size: 25px; margin: 0; margin-bottom: 15px">
                      Transaction Report
                    </h2>
                  </td>
                 
                </tr>
        
                <tr class="content-wrap" style="margin-bottom: 30px; max-width: 600px;display: block;">
                  <td>
                    <p style="margin: 0">
                      Please find the attached tranasaction report of <strong style="margin: 20px 0; color: #f92232; display: block"
                      >'.$data['from_date'].' to '.$data['to_date'].'</strong>
                    </p>
                  </td>
                  
                </tr>
              </tbody>
            </table>
          </body>
        </html>';
    
        return $template;
    }  



    public static function cbsContent($data = []){

      $template = '<!DOCTYPE html>
      <html lang="en">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <title>Email Template</title>
          <link rel="preconnect" href="https://fonts.googleapis.com" />
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
          <link
            href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap"
            rel="stylesheet"
          />
        </head>
        <style>
          @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap");
        </style>
        <body
          style="
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Roboto", sans-serif;
            line-height: 1.5;
          "
        >
          <table
            class="container"
            background="'.env("S3_BASEURL").'emailer/email-verification.png"
            width="100%"
            height="100"
            style="
              max-width: 540px;
              margin: 0px auto;
              padding: 0px 15px;
              background-repeat: no-repeat;
              background-size: contain;
              height: 900px;
              position: relative;
              background-position: center;
            "
          >
            <tbody
              class="main-content"
              style="
                width: 400px;
          margin: 200px auto;
          display: block;
              "
            >
              <tr class="heading" style="display: block;">
                <td>
                  <h2 >Dear '.$data['send_username'].',</h2>
                </td>
              </tr>
              <tr class="content-wrap" style="margin-bottom: 30px; max-width: 600px;display: block;">
                <td>
                  <p style="margin: 0">
                   I hope this email finds you well. We are writing to inform you that the SSL certificate for our services is set to expire on '.$data['expiry_date'].'. To ensure the continued security of our platform, we will be updating the SSL certificate on that date.
                  </p>
                   <p style="font-weight:bold;margin:5px 0">Important Note:</p>
                   <p style="margin: 0">During the update process, we kindly request that you <strong>do not initiate any transactions</strong> to avoid potential interruptions. once the SSL certificate update is complete, and services are fully restored.</p>
                   <p style="margin: 0">Please let us know if you have any questions or need further clarification. We appreciate your cooperation and understanding in ensuring a smooth update.</p>
                   <a style="margin-top: 12px; background: #f92232; padding: 5px; color: #fff; text-decoration: none; display: block; border-radius: 8px; text-align:center" href="https://nexgen.sprintnxt.in/access/#/bankList">Click here to update.</a>
                </td>
                
              </tr>
            </tbody>
          </table>
        </body>
      </html>';
  
      return $template;
  }  
  }

