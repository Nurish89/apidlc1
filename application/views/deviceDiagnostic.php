<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<div class="jumbotron text-center">
  <h1>Diagnostic</h1>
  <h4>National ID : <?php echo $nationalid ?></h4>
  <h4>IMEI : <?php echo $imei ?></h4>
  <p></p> 
</div>
  
<div class="container">
    <div class="row">
    <form action="<?= base_url('diagnostic/calculateDeviceValue') ?>" method="post">
        <div class="col-sm-4"><h2><i class="fa fa-mobile" aria-hidden="true"></i><strong> Device Diagnostic</h2>
            <h4><strong>Screen Calibration</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="a1" id="a1" value="1" checked>
                <label class="form-check-label" for="a1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="a1" id="a1" value="0">
                <label class="form-check-label" for="a2">FAILED</label>
            </div>
            <br>
            <h4><strong>Device Rotation</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="b1" id="b1" value="1" checked>
                <label class="form-check-label" for="b1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="b1" id="b1" value="0">
                <label class="form-check-label" for="b2">FAILED</label>
            </div>
            <br>
            <h4><strong>Hardware buttons (Volume up, down & back)</h3>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="c1" id="c1" value="1" checked>
                <label class="form-check-label" for="c1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="c1" id="c1" value="0">
                <label class="form-check-label" for="c2">FAILED</label>
            </div>
            <br>
            <h4><strong>Camera Check (Front & Back)</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="d1" id="d1" value="1" checked>
                <label class="form-check-label" for="d1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="d1" id="d1" value="0">
                <label class="form-check-label" for="d2">FAILED</label>
            </div>
            <br>
            <h4><strong>Camera Autofocus</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="e1" id="e1" value="1" checked>
                <label class="form-check-label" for="e1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="e1" id="e1" value="0">
                <label class="form-check-label" for="e2">FAILED</label>
            </div>
            <br>
            <h4><strong>Biometric ID/ Fingerprint Scanner</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="f1" id="f1" value="1" checked>
                <label class="form-check-label" for="f1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="f1" id="f1" value="0">
                <label class="form-check-label" for="f2">FAILED</label>
            </div>
            <br>
            <h4><strong>Bluetooth, GPS & Wi-Fi</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="g1" id="g1" value="1" checked>
                <label class="form-check-label" for="g1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="g1" id="g1" value="0">
                <label class="form-check-label" for="g2">FAILED</label>
            </div>
            <br>
            <h4><strong>GSM</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="h1" id="h1" value="1" checked>
                <label class="form-check-label" for="h1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="h1" id="h1" value="0">
                <label class="form-check-label" for="h2">FAILED</label>
            </div>
            <br>
            <h4><strong>Device Microphone</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="i1" id="i1" value="1" checked>
                <label class="form-check-label" for="i1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="i1" id="i1" value="0">
                <label class="form-check-label" for="i2">FAILED</label>
            </div>
            <br>
            <h4><strong>Device Speaker</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="j1" id="j1" value="1" checked>
                <label class="form-check-label" for="j1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="j1" id="j1" value="0">
                <label class="form-check-label" for="j2">FAILED</label>
            </div>
            <br>
            <h4><strong>Device Vibrator</h4>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="k1" id="k1" value="1" checked>
                <label class="form-check-label" for="k1">PASSED</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="k1" id="k1" value="0">
                <label class="form-check-label" for="k2">FAILED</label>
            </div>
        </div>
        <div class="col-sm-4"><h3><i class="fa fa-check-square-o" aria-hidden="true"></i><strong> Physical Declaration</h3>
            <h4><strong>Display & Touch Screen</h4>
            <div class="form-check">
                <select id="displayNtouchScreen" name="displayNtouchScreen" class="form-control selectable">
                    <option value="Flawless">Flawless</option>
                    <option value="Minor Scratches">Minor Scratches</option>
                    <option value="Heavily Scratched">Heavily Scratched</option>
                    <option value="Dented">Dented</option>
                    <option value="Cracked">Cracked</option>
                    <option value="Not working (Loose LCD)">Not working (Loose LCD)</option>
                </select>
            </div>

            <h4><strong>Device body (Back Panel/Cover)</h4>
            <div class="form-check">
                <select id="deviceBody" name="deviceBody" class="form-control selectable">
                    <option value="Flawless">Flawless</option>
                    <option value="Minor Scratches">Minor Scratches</option>
                    <option value="Heavily Scratched">Heavily Scratched</option>
                    <option value="Dented">Dented</option>
                    <option value="Cracked">Cracked</option>
                </select>
            </div>

            <h4><strong>Device body (Back Panel/Cover)</h4>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="Bloated Battery" name="deviceCondition[]">
                <label class="form-check-label" for="defaultCheck1">Bloated Battery</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="Liquid Damage" name="deviceCondition[]">
                <label class="form-check-label" for="defaultCheck2">Liquid Damage</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="Ghost Touch" name="deviceCondition[]">
                <label class="form-check-label" for="defaultCheck3">Ghost Touch</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="Sim Card Tray Broken" name="deviceCondition[]">
                <label class="form-check-label" for="defaultCheck4">Sim Card Tray Broken</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="Home & Power Button" name="deviceCondition[]">
                <label class="form-check-label" for="defaultCheck5">Home & Power Button</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="None of These" name="deviceCondition[]">
                <label class="form-check-label" for="defaultCheck6">None of these</label>
                <input type="hidden" name="nationalid" id="nationalid" value="<?php echo $nationalid ?>" class="form-control">
                <input type="hidden" name="imei" id="imei" value="<?php echo $imei ?>" class="form-control">
            </div>
        </div>
        <div class="col-sm-4">
            <button type="submit" class="btn btn-primary btn-lg">SUBMIT</button>
        </div>
    </form>
    </div>
</div>

</body>
<?php
    if($result){
        echo '<script type="text/javascript">alert("' . $result . '");</script>';

        $yourURL= base_url()."diagnostic/deviceDiagnostic";
        echo ("<script>location.href='$yourURL'</script>");
    }

?>
</html>
