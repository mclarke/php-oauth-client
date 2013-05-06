<!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Debug Authorize Request</title>
  <style>
        dt { font-weight: bold; }
  </style>
</head>

<body>
    <h1>Debug Authorize Request</h1>
    <p>The request will go to the endpoint at <tt><?php echo $_e($authorizeUri); ?></tt> and will have the following parameters:</p>
    <dl>
    <?php foreach ($queryParameters as $k => $v) { ?>
        <dt><?php echo $_e($k); ?></dt>
        <dd><?php echo $_e($v); ?></dd>
    <?php } ?>
    </dl>
    <p>The full URI you will be redirected to is <tt><?php echo $_e($authorizeUriQuery); ?></tt></p>

    <form method="get" action="<?php echo $_e($authorizeUri); ?>">
        <?php foreach ($queryParameters as $k => $v) { ?>
            <input type="hidden" name="<?php echo $_e($k); ?>" value="<?php echo $_e($v); ?>">
        <?php } ?>
        <input type="submit" value="Continue">
    </form>
</body>
</html>
