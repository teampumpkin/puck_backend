<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Puck Recruiter</title>
    <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}">
    <style>
        .description {
            margin: 30px auto 0;
            color: #737373;
            font-family: sans-serif;
            line-height: 33px;
            font-size: 18px;
        }
    </style>
</head>
<body style="height: 100% !important;">
<div class="container" style="background-color: #a0aec0;">
    <div class="row mt-3 description">
        <div class="col-12" style="text-align: center; background-color: #3D5CA3;padding: 2%">
            <img src="{{asset('no-cache/static_images/login_logo.png')}}" alt="">
        </div>
        <div class="col-12" style="text-align: center">
            <h5 class="mt-5" style="font-weight: bolder">Account Activated</h5>
        </div>
        <div class="col-12" style="text-align: center">
            <img src="{{asset('no-cache/static_images/email-verified.png')}}" alt="">
        </div>
        <div class="col-12" style="text-align: center">
            <b>Hello {{$details['name']}},</b>
        </div>
        <div class="col-12" style="text-align: center">
            Thank you for signing up for Puck Recruiter.
        </div>
        <div class="col-12" style="text-align: center">
            Your email has been verified. Your account is now activated.
        </div>
        <div class="col-12" style="text-align: center">
            Please download/use Puck Recruiter App and have fun
        </div>
        <div class="col-12" style="text-align: center;">
            <p style="color: #000000 !important; font-weight: bolder; font-size: x-small">*You received this email
                because you registered with Puck Recruiter</p>
        </div>
    </div>
</div>
<script src="{{asset('js/jquery-3.2.1.min.js')}}"></script>
<script src="{{asset('js/popper.min.js')}}"></script>
<script src="{{asset('js/bootstrap.min.js')}}"></script>
</body>
</html>
