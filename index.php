<?php
$data = [
    'link'      => isset($_GET['link']) ? $_GET['link'] : '',
    'isPremium' => isset($_GET['is_vip']) ? $_GET['is_vip'] : '',
    'user'      => isset($_GET['user']) ? $_GET['user'] : '',
    'enableP2p' => isset($_GET['p2p']) ? $_GET['p2p'] : false,
    'liveTime'  => isset($_GET['live_time']) ? $_GET['live_time'] : 0,
    'baseUrl'   => isset($_GET['site']) ? $_GET['site'] : '',
];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        body {
            margin: unset;
        }

        .video-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
        }
    </style>
</head>
<body>
<div class="video-wrapper">
    <div id="player" style="width: 100%;"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="//ssl.p.jwpcdn.com/player/v/8.30.1/jwplayer.js"></script>
<script src="//cdn.jsdelivr.net/npm/ecocdn/sdkv2/jwplayer.hlsjs.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/ecocdn/sdkv2/hls.min.js"></script>
<script
        disable-devtool-auto
        md5="b6bb43df4525b928a105fb5741bddbea"
        tk-name="bb"
        src="https://cdn.jsdelivr.net/npm/disable-devtool@latest"
>
</script>
<script>
    if (window.self === window.top) {
        alert('Not embed');
        return false;
    }
    jwplayer.key = "uoW6qHjBL3KNudxKVnwa3rt5LlTakbko9e6aQ6VUyKQ=";
    // const link = 'https://content.jwplatform.com/manifests/yp34SRmf.m3u8';
    const data = JSON.parse('<?php echo json_encode($data) ?>');
    const { link, user, isPremium, enableP2p, liveTime, baseUrl} = data
    const p2pConfig = {
        logLevel: 'debug',
        live: true,
        swFile: baseUrl + './sw.js',
        strictSegmentId: true,
        useDiskCache: false,
        useHttpRange: false,
        token: 'VN1aDj5SR',
        channelId: function (url) {
            const parseUrl = new URL(url)
            const hostname = parseUrl.hostname;
            const pathSegments = parseUrl.pathname.split('/');
            const segment = pathSegments[pathSegments.length - 2];

            return hostname + segment
        },
    }
    let jwConf = {
        file: link,
        width: '100%',
        height: '100vh',
        floating: {"dismissible": true, "mode": 'notVisible'},
        hlsjsdefault: true,
        pipIcon: "disabled",
        hlsjsConfig: {
            liveSyncDurationCount: 6,
            p2pConfig
        },
    }
    if (isPremium) {
        jwConf = Object.assign({}, jwConf, {
            pipIcon: "enabled"
        })
    }
    const checkFullScreen = () => {
        const isIOS = (function () {
            const iosQuirkPresent = function () {
                const audio = new Audio();

                audio.volume = 0.5;
                return audio.volume === 1;   // volume cannot be changed from "1" on iOS 12 and below
            };

            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAppleDevice = navigator.userAgent.includes('Macintosh');
            const isTouchScreen = navigator.maxTouchPoints >= 1;   // true for iOS 13 (and hopefully beyond)

            return isIOS || (isAppleDevice && (isTouchScreen || iosQuirkPresent()));
        })();
        if (!(user && isPremium) && isIOS) {
            jwplayer('player').setAllowFullscreen(false);
            jwplayer('player').setFloating(false);
        }
    }
    const initPopup = () => {
        if ($('#player .playerPopup').length === 0) {
            $('#player').append('<div class="playerPopup"><h3 id="popupModalLabel"></h3><a type="button" class="btn btn-danger" style="background: #00efff;" id="popupModalBtn"></a></div>')
        }
    }
    if (enableP2p) {
        const isSamsungBrowser = navigator.userAgent.indexOf('SamsungBrowser') > -1;
        if (!isSamsungBrowser && !Hls.P2pEngine.isMSESupported() || Hls.P2pEngine.getBrowser() === 'iPhone-Safari') {
            new Hls.P2pEngine.ServiceWorkerEngine(p2pConfig)
        }
        Hls.P2pEngine.tryRegisterServiceWorker(p2pConfig).then(() => {
            jwplayer('player').setup(jwConf);
            jwplayer('player').on('play', function () {
                initPopup()
            })
            checkFullScreen();
        })
        jwplayer_hls_provider.attach();
    } else {
        jwplayer('player').setup(jwConf);
        jwplayer('player').on('play', function () {
            initPopup()
        })
        checkFullScreen();
    }

    let checkTime = 2 * 60;
    if (liveTime >= 600 && (!user || !isPremium)) {
        jwplayer('player').setFullscreen(false);
        $('#popupModalLabel').html('Your trial period for this match has expired, please upgrade');
        $('#popupModalBtn').html('Buy VIP');
        $("#popupModalBtn").attr("href", "{{route('package.index')}}");
        $('.playerPopup').css('visibility', 'visible');
        jwplayer('player').on('ready', function (evt) {
            // document.getElementById('player').innerHTML = '<img style="width:100%" src="{{asset('images/ae-sport/banner-match.png')}}" />'
        });
    }
    if (user) {
        if (!isPremium) {
            let interval_obj = setInterval(function () {
                jwplayer('player').setFullscreen(false);
                clearInterval(interval_obj);
                $('#popupModalLabel').html('Purchasing a Plan for fullscreen mode & without popup');
                $('#popupModalBtn').html('Buy VIP');
                $("#popupModalBtn").attr("href", "{{route('package.index')}}");
                $('.playerPopup').css('visibility', 'visible');
            }, checkTime * 1000);
        }
    } else {
        let interval_obj = setInterval(function () {
            jwplayer('player').setFullscreen(false);
            clearInterval(interval_obj);
            $('#popupModalLabel').html('Please login to continue');
            $('#popupModalBtn').html('Login Account');
            $("#popupModalBtn").attr("href", "{{route('login', ['returnUrl' => \Illuminate\Support\Facades\URL::current()])}}");
            $('.playerPopup').css('visibility', 'visible');
        }, checkTime * 1000);
    }

    if (!user || !isPremium) {
        let index = 1;
        let interval_last_time = setInterval(function () {
            $.ajax({
                url: "{{route('live.last.time')}}",
                type: 'post',
                data: {
                    second: index * 30,
                    content_type: 'match',
                    content_id: '0'
                },
                success: function (data) {
                    if (data.error == 1) {
                        $('#popupModalLabel').html('Your trial period for this match has expired, please upgrade');
                        $('#popupModalBtn').html('Buy VIP');
                        $("#popupModalBtn").attr("href", "{{route('package.index')}}");
                        $('.playerPopup').css('visibility', 'visible');
                        // document.getElementById('player').innerHTML = '<img style="width:100%" src="{{asset('images/ae-sport/banner-match.png')}}" />'
                        clearInterval(interval_last_time);
                    }
                }
            });
            index++;
        }, 30 * 1000);
    }
</script>
</body>
</html>

